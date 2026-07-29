<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveMealPlanRequest;
use App\Http\Requests\StoreConsultationRequest;
use App\Http\Requests\StoreFeedbackReplyRequest;
use App\Http\Requests\StoreFoodLogEntryRequest;
use App\Http\Requests\StoreMealPlanItemRequest;
use App\Http\Requests\StorePlannedMealEntriesRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\ConsultationRequest;
use App\Models\Dietitian;
use App\Models\FeedbackRequest;
use App\Models\FoodItem;
use App\Models\FoodLogEntry;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\PlannedMealEntry;
use App\Models\UserExperience;
use Database\Seeders\PortalDemoSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PortalActionController extends Controller
{
    public function updateProfile(UpdateProfileRequest $request): RedirectResponse
    {
        $experience = $this->currentExperience($request);
        $validated = $request->validated();

        $height = (int) $this->validatedValue($validated, 'height_cm', $experience->height_cm ?? 0);
        $currentWeight = (float) $this->validatedValue($validated, 'current_weight_kg', $experience->current_weight_kg ?? 0.0);
        $targetWeight = (float) $this->validatedValue($validated, 'target_weight_kg', $experience->target_weight_kg ?? 0.0);
        $startingWeight = (float) $this->validatedValue($validated, 'starting_weight_kg', $experience->starting_weight_kg ?? $currentWeight);

        $heightInMeters = $height > 0 ? $height / 100 : 0;
        $weight = $currentWeight;
        $bmi = $heightInMeters > 0 && $weight > 0 ? round($weight / ($heightInMeters * $heightInMeters), 1) : 0.0;

        $history = $experience->bmi_history ?? [];
        if ($weight > 0) {
            $history[] = [
                'date' => now()->format('M j'),
                'weight' => number_format($weight, 1).' kg',
                'bmi' => number_format($bmi, 1),
            ];
        }

        $experience->update([
            'full_name' => $validated['full_name'] ?? $experience->full_name,
            'age' => $validated['age'] ?? $experience->age,
            'gender' => $validated['gender'] ?? $experience->gender,
            'height_cm' => $height !== null ? (int) $height : $experience->height_cm,
            'current_weight_kg' => $weight > 0 ? $weight : $experience->current_weight_kg,
            'target_weight_kg' => $targetWeight !== null ? (float) $targetWeight : $experience->target_weight_kg,
            'starting_weight_kg' => $startingWeight !== null ? (float) $startingWeight : $experience->starting_weight_kg,
            'activity_level' => $validated['activity_level'] ?? $experience->activity_level,
            'primary_goal' => $validated['primary_goal'] ?? $experience->primary_goal,
            'bmi_history' => array_slice($history, -4),
        ]);

        return back()->with('status', 'Profile updated.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validatedValue(array $validated, string $key, mixed $fallback): mixed
    {
        return array_key_exists($key, $validated) && $validated[$key] !== null
            ? $validated[$key]
            : $fallback;
    }

    public function storeFoodLogEntry(StoreFoodLogEntryRequest $request): RedirectResponse
    {
        $experience = $this->currentExperience($request);
        $foodItemId = $request->integer('food_item_id');
        $foodItem = $foodItemId > 0 ? FoodItem::query()->findOrFail($foodItemId) : null;
        $grams = $request->integer('grams');
        $servingLabel = 'Custom serving';

        if ($grams > 0) {
            $servingLabel = $grams.'g';
        }

        if ($foodItem !== null) {
            $servingLabel = $request->string('serving_label')->toString();
        }

        FoodLogEntry::query()->create([
            'user_experience_id' => $experience->id,
            'food_item_id' => $foodItem?->id,
            'meal_slot' => $request->string('meal_slot')->toString(),
            'food_name' => $foodItem?->name ?? $request->string('food_name')->toString(),
            'serving_label' => $servingLabel,
            'entry_date' => today()->toDateString(),
            'calories' => $foodItem?->calories ?? $request->integer('calories'),
            'protein' => $foodItem?->protein ?? (float) $request->input('protein', 0),
            'carbs' => $foodItem?->carbs ?? (float) $request->input('carbs', 0),
            'fat' => $foodItem?->fat ?? (float) $request->input('fat', 0),
        ]);

        return redirect()->route('portal.food-log')->with('status', 'Food added to your log.');
    }

    public function destroyFoodLogEntry(FoodLogEntry $foodLogEntry, Request $request): RedirectResponse
    {
        $experience = $this->currentExperience($request);

        if ($foodLogEntry->user_experience_id !== $experience->id) {
            abort(403);
        }

        $mealSlot = $foodLogEntry->meal_slot;
        FoodLogEntry::destroy($foodLogEntry->getKey());

        return redirect()->route('portal.food-log')->with('status', ucfirst($mealSlot).' item has been deleted.');
    }

    public function saveMealPlan(SaveMealPlanRequest $request, ?MealPlan $mealPlan = null): RedirectResponse
    {
        $experience = $this->currentExperience($request);
        $mealPlan ??= new MealPlan;

        if ($mealPlan->getKey() !== null && $mealPlan->user_experience_id !== $experience->id) {
            abort(403);
        }

        $mealPlan->fill([
            'user_experience_id' => $experience->id,
            'name' => $request->string('name')->toString(),
            'description' => $request->string('description')->toString(),
            'daily_calories' => $request->integer('daily_calories'),
            'tags' => collect(explode(',', (string) $request->string('tags')))->map(fn (string $tag): string => trim($tag))->filter()->values()->all(),
            'is_template' => false,
        ])->save();

        return redirect()->route('portal.meal-plans', ['edit_plan' => $mealPlan->id])->with('status', 'Meal plan saved.');
    }

    public function addMealPlanItem(StoreMealPlanItemRequest $request, MealPlan $mealPlan): RedirectResponse
    {
        $experience = $this->currentExperience($request);

        if ($mealPlan->user_experience_id !== $experience->id) {
            abort(403);
        }

        $foodItemId = $request->integer('food_item_id');
        $foodItem = $foodItemId > 0 ? FoodItem::query()->find($foodItemId, ['*']) : null;

        $mealPlan->items()->create([
            'food_item_id' => $foodItem?->id,
            'meal_slot' => $request->string('meal_slot')->toString(),
            'item_name' => $foodItem?->name ?? $request->string('item_name')->toString(),
            'serving_label' => $request->string('serving_label')->toString(),
            'sort_order' => (int) $mealPlan->items()->where('meal_slot', $request->string('meal_slot')->toString())->count() + 1,
        ]);

        return redirect()->route('portal.meal-plans', ['edit_plan' => $mealPlan->id])->with('status', 'Meal added to your plan.');
    }

    public function destroyMealPlanItem(Request $request, MealPlan $mealPlan, MealPlanItem $mealPlanItem): RedirectResponse
    {
        $experience = $this->currentExperience($request);

        if ($mealPlan->user_experience_id !== $experience->id || $mealPlanItem->meal_plan_id !== $mealPlan->id) {
            abort(403);
        }

        MealPlanItem::destroy($mealPlanItem->getKey());

        return redirect()->route('portal.meal-plans', ['edit_plan' => $mealPlan->id])->with('status', 'Meal removed from the plan.');
    }

    public function activateMealPlan(Request $request, MealPlan $mealPlan): RedirectResponse
    {
        $experience = $this->currentExperience($request);

        if ($mealPlan->is_template) {
            $copy = MealPlan::query()->create([
                'user_experience_id' => $experience->id,
                'name' => $mealPlan->name.' Copy '.now()->format('Hi'),
                'description' => $mealPlan->description,
                'daily_calories' => $mealPlan->daily_calories,
                'tags' => $mealPlan->tags,
                'rating' => $mealPlan->rating,
                'is_template' => false,
                'is_active' => true,
            ]);

            foreach ($mealPlan->items as $item) {
                $copy->items()->create([
                    'food_item_id' => $item->food_item_id,
                    'meal_slot' => $item->meal_slot,
                    'item_name' => $item->item_name,
                    'serving_label' => $item->serving_label,
                    'sort_order' => $item->sort_order,
                ]);
            }

            $mealPlan = $copy;
        } elseif ($mealPlan->user_experience_id !== $experience->id) {
            abort(403);
        }

        MealPlan::query()->where('user_experience_id', '=', $experience->id, 'and')->update(['is_active' => false]);
        $mealPlan->update(['is_active' => true]);
        $experience->update(['active_meal_plan_id' => $mealPlan->id]);

        return redirect()->route('portal.meal-plans', ['edit_plan' => $mealPlan->id])->with('status', 'Active meal plan updated.');
    }

    public function storeFeedbackReply(StoreFeedbackReplyRequest $request): RedirectResponse
    {
        $experience = $this->currentExperience($request);
        $dietitianId = $request->integer('dietitian_id') ?: $experience->active_dietitian_id;

        if ($dietitianId === null) {
            throw ValidationException::withMessages([
                'message' => 'You need an assigned dietitian before you can send a reply.',
            ]);
        }

        FeedbackRequest::query()->create([
            'user_experience_id' => $experience->id,
            'dietitian_id' => $dietitianId,
            'title' => $request->string('title')->toString(),
            'topic' => 'general',
            'tag' => 'reply',
            'tag_tone' => 'slate',
            'priority' => 'medium',
            'status' => 'pending',
            'message' => $request->string('message')->toString(),
            'recommendations' => [],
            'submitted_on' => today()->toDateString(),
        ]);

        return redirect()->route('portal.feedback')->with('status', 'Message sent to your dietitian.');
    }

    public function markFeedbackAsRead(Request $request, FeedbackRequest $feedbackRequest): RedirectResponse
    {
        $experience = $this->currentExperience($request);

        if ($feedbackRequest->user_experience_id !== $experience->id) {
            abort(403);
        }

        $feedbackRequest->update(['is_read' => true]);

        return redirect()->route('portal.feedback')->with('status', 'Feedback marked as read.');
    }

    public function storeConsultation(StoreConsultationRequest $request): RedirectResponse
    {
        $experience = $this->currentExperience($request);

        ConsultationRequest::query()->create([
            'user_experience_id' => $experience->id,
            'dietitian_id' => $experience->active_dietitian_id,
            'preferred_date' => $request->date('preferred_date'),
            'note' => $request->string('note')->toString(),
            'status' => 'pending',
        ]);

        return redirect()->route('portal.feedback')->with('status', 'Consultation request submitted.');
    }

    public function storePlannedMealEntries(StorePlannedMealEntriesRequest $request): RedirectResponse
    {
        $experience = $this->currentExperience($request);
        $scheduledDate = $request->date('scheduled_date');
        $entries = collect($request->validated('entries'))
            ->mapWithKeys(function (array $entry, string $mealSlot): array {
                $foodName = trim((string) ($entry['food_name'] ?? ''));

                return [
                    $mealSlot => [
                        'food_name' => $foodName,
                        'grams' => $foodName !== '' ? (int) $entry['grams'] : null,
                        'calories' => $foodName !== '' ? (int) $entry['calories'] : null,
                        'protein' => $foodName !== '' ? (float) $entry['protein'] : null,
                        'carbs' => $foodName !== '' ? (float) $entry['carbs'] : null,
                        'fat' => $foodName !== '' ? (float) $entry['fat'] : null,
                    ],
                ];
            })
            ->filter(fn (array $entry): bool => $entry['food_name'] !== '');

        PlannedMealEntry::query()
            ->where('user_experience_id', '=', $experience->id, 'and')
            ->whereDate('scheduled_date', $scheduledDate)
            ->delete();

        foreach ($entries as $mealSlot => $entry) {
            PlannedMealEntry::query()->create([
                'user_experience_id' => $experience->id,
                'scheduled_date' => $scheduledDate->toDateString(),
                'meal_slot' => $mealSlot,
                'food_name' => $entry['food_name'],
                'grams' => $entry['grams'],
                'calories' => $entry['calories'],
                'protein' => $entry['protein'],
                'carbs' => $entry['carbs'],
                'fat' => $entry['fat'],
            ]);
        }

        return redirect()
            ->route('portal.calendar', ['date' => $scheduledDate->toDateString()])
            ->with('status', 'Meal plan saved for '.$scheduledDate->format('F j').'.');
    }

    public function destroyPlannedMealEntryFromRequest(Request $request): RedirectResponse
    {
        $experience = $this->currentExperience($request);

        // Parse ID from form data (likely hidden input)
        $data = $request->all();
        $id = $request->integer('planned_meal_entry_id')
            ?? $request->integer('id')
            ?? $request->input('plannedMealEntry')
            ?? 0;

        if (! $id) {
            return back()->with('error', 'No meal entry ID found');
        }

        $plannedMealEntry = PlannedMealEntry::query()->findOrFail($id, ['*']);

        Log::info('Fallback delete called', [
            'entry_id' => $plannedMealEntry->id,
            'data_keys' => array_keys($data),
        ]);

        if ($plannedMealEntry->user_experience_id !== $experience->id) {
            abort(403);
        }

        $scheduledDate = $plannedMealEntry->scheduled_date;
        $mealSlot = $plannedMealEntry->meal_slot;
        PlannedMealEntry::destroy($plannedMealEntry->getKey());

        return redirect()
            ->route('portal.calendar', ['date' => $scheduledDate->toDateString()])
            ->with('status', ucfirst($mealSlot).' meal has been deleted.');
    }

    public function destroyPlannedMealEntry(PlannedMealEntry $plannedMealEntry, Request $request): RedirectResponse
    {
        // Temporary debugging
        Log::info('Delete method called', [
            'entry_id' => $plannedMealEntry->id,
            'user_experience_id' => $plannedMealEntry->user_experience_id,
            'meal_slot' => $plannedMealEntry->meal_slot,
            'scheduled_date' => $plannedMealEntry->scheduled_date,
        ]);

        $experience = $this->currentExperience($request);

        if ($plannedMealEntry->user_experience_id !== $experience->id) {
            abort(403);
        }

        $scheduledDate = $plannedMealEntry->scheduled_date;
        $mealSlot = $plannedMealEntry->meal_slot;
        PlannedMealEntry::destroy($plannedMealEntry->getKey());

        return redirect()
            ->route('portal.calendar', ['date' => $scheduledDate->toDateString()])
            ->with('status', ucfirst($mealSlot).' meal has been deleted.');
    }

    private function currentExperience(Request $request): UserExperience
    {
        $this->seedDemoData();

        $user = $request->user();
        $sessionKey = $request->session()->get('portal_session_key') ?: $request->cookie('portal_session_key');

        if ($user !== null) {
            $experience = UserExperience::query()
                ->firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'session_key' => (string) Str::uuid(),
                        'active_dietitian_id' => Dietitian::query()->value('id'),
                    ]
                );

            if (! is_string($sessionKey) || trim($sessionKey) === '') {
                $sessionKey = $experience->session_key;
            }

            $sessionKeyBelongsToAnotherExperience = UserExperience::query()
                ->where('session_key', '=', $sessionKey, 'and')
                ->whereKeyNot($experience->id)
                ->exists();

            if (! $sessionKeyBelongsToAnotherExperience
                && is_string($sessionKey)
                && trim($sessionKey) !== ''
                && (! is_string($experience->session_key) || trim($experience->session_key) === '' || $experience->session_key !== $sessionKey)) {
                $experience->forceFill(['session_key' => $sessionKey])->save();
            }
        } else {
            if (! is_string($sessionKey) || trim($sessionKey) === '') {
                $sessionKey = (string) Str::uuid();
            }

            $experience = UserExperience::query()->firstOrCreate(
                ['session_key' => $sessionKey],
                ['active_dietitian_id' => Dietitian::query()->value('id')]
            );
        }

        $request->session()->put('portal_session_key', $experience->session_key);
        cookie()->queue('portal_session_key', $experience->session_key, 60 * 24 * 365); // 1 year

        if ($experience->wasRecentlyCreated) {
            $this->initializeExperience($experience);
        }

        return $experience->fresh(['activeDietitian', 'mealPlans.items', 'foodLogEntries', 'feedbackRequests.dietitian', 'plannedMealEntries']);
    }

    private function seedDemoData(): void
    {
        if (FoodItem::query()->doesntExist()) {
            app(PortalDemoSeeder::class)->run();
        }
    }

    private function initializeExperience(UserExperience $experience): void
    {
        if ($experience->active_dietitian_id === null) {
            $experience->forceFill([
                'active_dietitian_id' => Dietitian::query()->value('id'),
            ])->save();
        }
    }
}
