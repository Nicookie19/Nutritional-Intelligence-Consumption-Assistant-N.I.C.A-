<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveFoodItemRequest;
use App\Http\Requests\SaveMealPlanRequest;
use App\Http\Requests\StoreDietitianRequest;
use App\Http\Requests\StoreMealPlanItemRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\ConsultationRequest;
use App\Models\Dietitian;
use App\Models\FeedbackRequest;
use App\Models\FoodItem;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\UserExperience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminActionController extends Controller
{
    public function updateUserExperience(UpdateProfileRequest $request, UserExperience $userExperience): RedirectResponse
    {
        $validated = $request->validated();
        $profileMetrics = $this->profileMetrics($validated, $userExperience);

        $userExperience->update([
            'full_name' => $validated['full_name'] ?? $userExperience->full_name,
            'age' => $validated['age'] ?? $userExperience->age,
            'gender' => $validated['gender'] ?? $userExperience->gender,
            'activity_level' => $validated['activity_level'] ?? $userExperience->activity_level,
            'primary_goal' => $validated['primary_goal'] ?? $userExperience->primary_goal,
            'active_dietitian_id' => array_key_exists('active_dietitian_id', $validated)
                ? $validated['active_dietitian_id']
                : $userExperience->active_dietitian_id,
            'height_cm' => $profileMetrics['height'] > 0 ? $profileMetrics['height'] : $userExperience->height_cm,
            'current_weight_kg' => $profileMetrics['currentWeight'] > 0 ? $profileMetrics['currentWeight'] : $userExperience->current_weight_kg,
            'target_weight_kg' => $profileMetrics['targetWeight'] > 0 ? $profileMetrics['targetWeight'] : $userExperience->target_weight_kg,
            'starting_weight_kg' => $profileMetrics['startingWeight'] > 0 ? $profileMetrics['startingWeight'] : $userExperience->starting_weight_kg,
            'bmi_history' => $this->updatedBmiHistory($userExperience, $profileMetrics['currentWeight'], $profileMetrics['bmi']),
        ]);

        return redirect()
            ->route('admin.users', ['edit_user' => $userExperience->id])
            ->with('status', 'User profile updated from admin hub.');
    }

    public function storeDietitian(StoreDietitianRequest $request): RedirectResponse
    {
        Dietitian::query()->create([
            ...$request->validated(),
            'patient_count' => 0,
            'rating' => 5.0,
            'status' => 'active',
        ]);

        return redirect()->route('admin.dietitians')->with('status', 'Dietitian added.');
    }

    public function updateDietitian(StoreDietitianRequest $request, Dietitian $dietitian): RedirectResponse
    {
        $dietitian->update($request->validated());

        return redirect()
            ->route('admin.dietitians', ['edit_dietitian' => $dietitian->id])
            ->with('status', 'Dietitian updated.');
    }

    public function destroyDietitian(Dietitian $dietitian): RedirectResponse
    {
        FeedbackRequest::query()
            ->where('dietitian_id', $dietitian->id)
            ->update(['dietitian_id' => null]);

        ConsultationRequest::query()
            ->where('dietitian_id', $dietitian->id)
            ->update(['dietitian_id' => null]);

        Dietitian::destroy($dietitian->getKey());

        return redirect()->route('admin.dietitians')->with('status', 'Dietitian deleted.');
    }

    public function saveFoodItem(SaveFoodItemRequest $request, ?FoodItem $foodItem = null): RedirectResponse
    {
        $foodItem ??= new FoodItem;
        $foodItem->fill($request->validated())->save();

        return redirect()->route('admin.content')->with('status', 'Food item saved.');
    }

    public function destroyFoodItem(FoodItem $foodItem): RedirectResponse
    {
        FoodItem::destroy($foodItem->getKey());

        return redirect()->route('admin.content')->with('status', 'Food item deleted.');
    }

    public function saveMealPlan(SaveMealPlanRequest $request, ?MealPlan $mealPlan = null): RedirectResponse
    {
        $mealPlan ??= new MealPlan;
        $mealPlan->fill([
            'name' => $request->string('name')->toString(),
            'description' => $request->string('description')->toString(),
            'daily_calories' => $request->integer('daily_calories'),
            'tags' => collect(explode(',', (string) $request->string('tags')))->map(fn (string $tag): string => trim($tag))->filter()->values()->all(),
            'is_template' => true,
            'rating' => $mealPlan->rating ?? 4.8,
        ])->save();

        return redirect()->route('admin.content', ['edit_meal_plan' => $mealPlan->id])->with('status', 'Meal plan saved.');
    }

    public function addMealPlanItem(StoreMealPlanItemRequest $request, MealPlan $mealPlan): RedirectResponse
    {
        abort_unless($mealPlan->is_template, 404);

        $foodItem = FoodItem::query()->findOrFail($request->integer('food_item_id'));

        $mealPlan->items()->create([
            'food_item_id' => $foodItem->id,
            'meal_slot' => $request->string('meal_slot')->toString(),
            'item_name' => $foodItem->name,
            'serving_label' => $request->string('serving_label')->toString(),
            'sort_order' => (int) $mealPlan->items()->where('meal_slot', $request->string('meal_slot')->toString())->count() + 1,
        ]);

        return redirect()->route('admin.content', ['edit_meal_plan' => $mealPlan->id])->with('status', 'Meal added to template plan.');
    }

    public function destroyMealPlanItem(MealPlan $mealPlan, MealPlanItem $mealPlanItem): RedirectResponse
    {
        abort_unless($mealPlan->is_template && $mealPlanItem->meal_plan_id === $mealPlan->id, 404);

        MealPlanItem::destroy($mealPlanItem->getKey());

        return redirect()->route('admin.content', ['edit_meal_plan' => $mealPlan->id])->with('status', 'Meal removed from template plan.');
    }

    public function destroyMealPlan(MealPlan $mealPlan): RedirectResponse
    {
        abort_unless($mealPlan->is_template, 404);

        MealPlan::destroy($mealPlan->getKey());

        return redirect()->route('admin.content')->with('status', 'Meal plan deleted.');
    }

    public function completeFeedback(FeedbackRequest $feedbackRequest): RedirectResponse
    {
        $feedbackRequest->update([
            'status' => 'completed',
            'is_read' => true,
        ]);

        return redirect()->route('admin.feedback')->with('status', 'Feedback request marked complete.');
    }

    public function updateConsultationStatus(Request $request, ConsultationRequest $consultationRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,in-progress,completed'],
        ]);

        $consultationRequest->update([
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.feedback')->with('status', 'Consultation request updated.');
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{height: float, currentWeight: float, targetWeight: float, startingWeight: float, bmi: float}
     */
    private function profileMetrics(array $validated, UserExperience $userExperience): array
    {
        $height = $this->validatedFloat($validated, 'height_cm', (float) ($userExperience->height_cm ?? 0));
        $currentWeight = $this->validatedFloat($validated, 'current_weight_kg', (float) ($userExperience->current_weight_kg ?? 0));
        $targetWeight = $this->validatedFloat($validated, 'target_weight_kg', (float) ($userExperience->target_weight_kg ?? 0));
        $startingWeightFallback = $userExperience->starting_weight_kg ?: $currentWeight;
        $startingWeight = $this->validatedFloat($validated, 'starting_weight_kg', (float) $startingWeightFallback);

        return [
            'height' => $height,
            'currentWeight' => $currentWeight,
            'targetWeight' => $targetWeight,
            'startingWeight' => $startingWeight,
            'bmi' => $this->bmi($height, $currentWeight),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validatedFloat(array $validated, string $key, float $fallback): float
    {
        if (! array_key_exists($key, $validated) || $validated[$key] === null) {
            return $fallback;
        }

        return (float) $validated[$key];
    }

    private function bmi(float $height, float $currentWeight): float
    {
        $heightInMeters = $height > 0 ? $height / 100 : 0;

        if ($heightInMeters <= 0 || $currentWeight <= 0) {
            return 0.0;
        }

        return round($currentWeight / ($heightInMeters * $heightInMeters), 1);
    }

    /**
     * @return array<int, array{date: string, weight: string, bmi: string}>
     */
    private function updatedBmiHistory(UserExperience $userExperience, float $currentWeight, float $bmi): array
    {
        $history = $userExperience->bmi_history ?? [];

        if ($currentWeight <= 0) {
            return array_slice($history, -6);
        }

        $history[] = [
            'date' => now()->format('M j'),
            'weight' => number_format($currentWeight, 1).' kg',
            'bmi' => number_format($bmi, 1),
        ];

        return array_slice($history, -6);
    }
}
