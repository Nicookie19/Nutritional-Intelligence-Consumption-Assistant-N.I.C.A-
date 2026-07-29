<?php

namespace App\Http\Controllers;

use App\Models\ConsultationRequest;
use App\Models\Dietitian;
use App\Models\FeedbackRequest;
use App\Models\FoodItem;
use App\Models\FoodLogEntry;
use App\Models\MealPlan;
use App\Models\PlannedMealEntry;
use App\Models\User;
use App\Models\UserExperience;
use Database\Seeders\PortalDemoSeeder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PortalController extends Controller // NOSONAR
{
    private const GOAL_WEIGHT_MAINTENANCE = 'Weight Maintenance';

    private const GOAL_MUSCLE_GAIN = 'Muscle Gain';

    private const GOAL_WEIGHT_LOSS = 'Weight Loss';

    private const GOAL_LIFESTYLE = 'Lifestyle';

    public function user(Request $request, string $page = 'dashboard'): View
    {
        $pages = $this->userPages();

        abort_unless(array_key_exists($page, $pages), 404);

        $experience = $this->currentExperience($request);
        $selectedDate = $this->selectedDate($request);

        return view('portal', [
            'page' => $page,
            'pages' => $pages,
            'currentPage' => $pages[$page],
            'portalData' => $this->portalData($experience, $selectedDate, $request),
        ]);
    }

    public function admin(Request $request, string $page = 'dashboard'): View
    {
        $pages = $this->adminPages();

        abort_unless(array_key_exists($page, $pages), 404);

        $this->seedDemoData();

        return view('admin-portal', [
            'page' => $page,
            'pages' => $pages,
            'currentPage' => $pages[$page],
            'adminData' => $this->adminData($request),
        ]);
    }

    /**
     * @return array<string, array{name: string, route: string}>
     */
    private function userPages(): array
    {
        return [
            'dashboard' => ['name' => 'Dashboard', 'route' => 'portal.dashboard'],
            'food-log' => ['name' => 'Food Log', 'route' => 'portal.food-log'],
            'calendar' => ['name' => 'Calendar', 'route' => 'portal.calendar'],
            'insights' => ['name' => 'Insights', 'route' => 'portal.insights'],
            'meal-plans' => ['name' => 'Meal Plans', 'route' => 'portal.meal-plans'],
            'feedback' => ['name' => 'Feedback', 'route' => 'portal.feedback'],
            'profile' => ['name' => 'Profile', 'route' => 'portal.profile'],
        ];
    }

    /**
     * @return array<string, array{name: string, route: string}>
     */
    private function adminPages(): array
    {
        return [
            'dashboard' => ['name' => 'Dashboard', 'route' => 'admin.dashboard'],
            'users' => ['name' => 'Users', 'route' => 'admin.users'],
            'dietitians' => ['name' => 'Dietitians', 'route' => 'admin.dietitians'],
            'feedback' => ['name' => 'Feedback', 'route' => 'admin.feedback'],
            'content' => ['name' => 'Content', 'route' => 'admin.content'],
            'analytics' => ['name' => 'Analytics', 'route' => 'admin.analytics'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function portalData(UserExperience $experience, Carbon $selectedDate, Request $request): array
    {
        $todayEntries = $experience->foodLogEntries()
            ->whereDate('entry_date', today())
            ->orderBy('meal_slot')
            ->latest('id')
            ->get();

        $mealSlots = ['Breakfast', 'Lunch', 'Dinner', 'Snacks'];
        $groupedEntries = collect($mealSlots)->mapWithKeys(function (string $slot) use ($todayEntries): array {
            $entries = $todayEntries->where('meal_slot', $slot)->values();

            return [
                $slot => [
                    'count' => $entries->count(),
                    'summary' => sprintf(
                        '%d cal • %.1fg protein',
                        (int) $entries->sum('calories'),
                        $entries->sum(fn (FoodLogEntry $entry): float => (float) $entry->protein),
                    ),
                    'items' => $entries,
                ],
            ];
        });

        $activePlan = MealPlan::query()
            ->with('items')
            ->find($experience->active_meal_plan_id, ['*']);

        $customPlans = $experience->mealPlans()->with('items')->latest()->get();
        $templatePlans = MealPlan::query()->where('is_template', true)->with('items')->orderBy('name')->get();

        $editablePlan = $customPlans->firstWhere('id', (int) $request->integer('edit_plan'))
            ?? $activePlan
            ?? $customPlans->first();

        $dietitian = $experience->activeDietitian()->first() ?? Dietitian::query()->first();
        $feedbackItems = $experience->feedbackRequests()->with('dietitian')->latest('submitted_on')->get();
        $consultationCount = ConsultationRequest::query()->where('user_experience_id', $experience->id)->count();

        $macroTotals = [
            'Calories' => ['value' => $todayEntries->sum('calories'), 'goal' => 2000, 'suffix' => ''],
            'Protein' => ['value' => round($todayEntries->sum(fn (FoodLogEntry $entry): float => (float) $entry->protein), 1), 'goal' => 150, 'suffix' => 'g'],
            'Meals Logged' => ['value' => $todayEntries->count(), 'goal' => 4, 'suffix' => ' meals'],
            'Unread Notes' => ['value' => $feedbackItems->where('is_read', false)->count(), 'goal' => 0, 'suffix' => ''],
        ];

        $calendar = $this->calendarData($experience, $selectedDate, $todayEntries);

        return [
            'hero' => $this->heroContent(),
            'dashboardStats' => collect($macroTotals)->map(function (array $metric, string $label): array {
                $goal = max((float) $metric['goal'], 1);
                $progress = min((((float) $metric['value']) / $goal) * 100, 100);

                return [
                    'label' => $label,
                    'value' => $metric['value'],
                    'goal' => $metric['goal'],
                    'suffix' => $metric['suffix'],
                    'progress' => round($progress, 1),
                ];
            })->values()->all(),
            'foodLogMeals' => $groupedEntries,
            'foodItems' => FoodItem::query()->where('is_active', true)->orderBy('name')->get(),
            'selectedMeal' => $request->query('meal', 'Breakfast'),
            'mealPlans' => $templatePlans,
            'customPlans' => $customPlans,
            'editablePlan' => $editablePlan,
            'activePlan' => $activePlan,
            'calendar' => $calendar,
            'feedbackItems' => $feedbackItems,
            'dietitian' => $dietitian,
            'consultationCount' => $consultationCount,
            'profile' => $this->profileData($experience),
            'insights' => $this->insightsData($experience, $todayEntries, $activePlan),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminData(Request $request): array
    {
        $foodItems = FoodItem::query()->orderBy('name', 'asc')->get();
        $mealPlans = MealPlan::query()->where('is_template', true)->with('items')->latest()->get();
        $dietitians = Dietitian::query()->orderBy('name', 'asc')->get();
        $users = UserExperience::query()
            ->with(['activeDietitian', 'activeMealPlan', 'foodLogEntries', 'plannedMealEntries', 'feedbackRequests'])
            ->orderBy('full_name')
            ->get();
        $feedbackRequests = FeedbackRequest::query()->with(['dietitian', 'userExperience'])->latest('submitted_on')->get();
        $consultationRequests = ConsultationRequest::query()->with(['dietitian', 'userExperience'])->latest('preferred_date')->get();
        $editingUser = $users->firstWhere('id', $request->integer('edit_user')) ?? $users->first();
        $editingDietitian = $dietitians->firstWhere('id', $request->integer('edit_dietitian')) ?? $dietitians->first();
        $editingFoodItem = FoodItem::query()->find($request->integer('edit_food'), ['*']);
        $editingMealPlan = MealPlan::query()->where('is_template', true)->with('items')->find($request->integer('edit_meal_plan'), ['*']);
        $today = today();
        $lastSevenDays = collect(range(6, 0))->map(fn (int $daysAgo): Carbon => $today->copy()->subDays($daysAgo));
        $lastSevenMonths = collect(range(6, 0))->map(fn (int $monthsAgo): Carbon => $today->copy()->subMonths($monthsAgo)->startOfMonth());

        $experienceRegistrations = UserExperience::query()
            ->where('created_at', '>=', $lastSevenMonths->first()->copy()->startOfMonth())
            ->get(['id', 'created_at'])
            ->groupBy(fn (UserExperience $experience): string => optional($experience->created_at)->format('Y-m'));

        $userGrowth = $lastSevenMonths->map(function (Carbon $month) use ($experienceRegistrations): array {
            $monthKey = $month->format('Y-m');

            return [
                'label' => $month->format('M'),
                'value' => $experienceRegistrations->get($monthKey, collect())->count(),
            ];
        })->values();

        $loggingEntries = FoodLogEntry::query()
            ->where('entry_date', '>=', $lastSevenDays->first()->toDateString())
            ->get(['id', 'entry_date'])
            ->groupBy(fn (FoodLogEntry $entry): string => optional($entry->entry_date)->format('Y-m-d'));

        $loggingActivity = $lastSevenDays->map(function (Carbon $day) use ($loggingEntries): array {
            $dayKey = $day->toDateString();

            return [
                'label' => $day->format('D'),
                'value' => $loggingEntries->get($dayKey, collect())->count(),
            ];
        })->values();

        $activeMealPlansCount = $users->filter(fn (UserExperience $user): bool => $user->activeMealPlan !== null)->count();
        $completedFeedbackCount = $feedbackRequests->where('status', 'completed')->count();
        $pendingFeedbackCount = $feedbackRequests->where('status', 'pending')->count();
        $dashboardHealth = $this->adminDashboardHealth(
            $users,
            $today,
            $completedFeedbackCount,
            $pendingFeedbackCount,
            $activeMealPlansCount,
        );
        $recentUsers = $this->recentAdminUsers($users);
        $dietitianData = $this->adminDietitianData($dietitians, $feedbackRequests, $users);
        $feedbackData = $this->adminFeedbackData($feedbackRequests, $consultationRequests);
        $analyticsData = $this->adminAnalyticsData($users);
        $contentData = $this->adminContentData($foodItems, $mealPlans);

        return [
            'summary' => [
                ['label' => 'Users', 'value' => $users->count()],
                ['label' => 'Dietitians', 'value' => $dietitians->count()],
                ['label' => 'Food Items', 'value' => $foodItems->count()],
                ['label' => 'Meal Plans', 'value' => $mealPlans->count()],
            ],
            'dashboard' => [
                'user_growth' => $userGrowth->all(),
                'logging_activity' => $loggingActivity->all(),
                'health' => $dashboardHealth,
                'recent_users' => $recentUsers,
                'totals' => [
                    'registered_users' => User::query()->count('*'),
                    'experiences' => $users->count(),
                    'feedback_pending' => $pendingFeedbackCount,
                ],
            ],
            'dietitiansData' => $dietitianData,
            'feedbackData' => $feedbackData,
            'analyticsData' => $analyticsData,
            'contentData' => $contentData,
            'users' => $users,
            'dietitians' => $dietitians,
            'foodItems' => $foodItems,
            'mealPlans' => $mealPlans,
            'feedbackRequests' => $feedbackRequests,
            'consultationRequests' => $consultationRequests,
            'editingUser' => $editingUser,
            'editingDietitian' => $editingDietitian,
            'editingFoodItem' => $editingFoodItem,
            'editingMealPlan' => $editingMealPlan,
        ];
    }

    /**
     * @param  Collection<int, UserExperience>  $users
     * @return array<int, array{title: string, copy: string, tone: string}>
     */
    private function adminDashboardHealth(Collection $users, Carbon $today, int $completedFeedbackCount, int $pendingFeedbackCount, int $activeMealPlansCount): array
    {
        $usersWithRecentActivity = $users->filter(function (UserExperience $user) use ($today): bool {
            return $user->foodLogEntries->contains(fn (FoodLogEntry $entry): bool => $entry->entry_date?->greaterThanOrEqualTo($today->copy()->subDays(6)))
                || $user->plannedMealEntries->contains(fn (PlannedMealEntry $entry): bool => $entry->scheduled_date?->greaterThanOrEqualTo($today->copy()->subDays(6)));
        })->count();

        return [
            [
                'title' => 'Active users this week',
                'copy' => $usersWithRecentActivity.' user accounts logged meals or kept planned meals in the last 7 days.',
                'tone' => $usersWithRecentActivity > 0 ? 'portal-tone-green' : 'portal-tone-yellow',
            ],
            [
                'title' => 'Feedback turnaround',
                'copy' => $completedFeedbackCount.' feedback requests completed, '.$pendingFeedbackCount.' still pending action.',
                'tone' => $pendingFeedbackCount > 0 ? 'portal-tone-blue' : 'portal-tone-green',
            ],
            [
                'title' => 'Meal plan adoption',
                'copy' => $activeMealPlansCount.' users currently have an active meal plan attached to their profile.',
                'tone' => $activeMealPlansCount > 0 ? 'portal-tone-yellow' : 'portal-tone-red',
            ],
        ];
    }

    /**
     * @param  Collection<int, UserExperience>  $users
     * @return array<int, array<string, mixed>>
     */
    private function recentAdminUsers(Collection $users): array
    {
        return $users->sortByDesc('created_at')->take(5)->values()->map(function (UserExperience $user): array {
            $heightInMeters = $user->height_cm ? $user->height_cm / 100 : 0;
            $bmi = $heightInMeters > 0 && $user->current_weight_kg
                ? round(((float) $user->current_weight_kg) / ($heightInMeters * $heightInMeters), 1)
                : null;

            return [
                'name' => $user->full_name ?: 'Unnamed user',
                'joined_at' => optional($user->created_at)?->format('M d, Y') ?? 'Unknown',
                'goal' => $user->primary_goal ?: 'Not set',
                'dietitian' => $user->activeDietitian?->name ?? 'Unassigned',
                'meal_count' => $user->foodLogEntries->count(),
                'feedback_count' => $user->feedbackRequests->count(),
                'bmi' => $bmi !== null ? number_format($bmi, 1) : '—',
            ];
        })->all();
    }

    /**
     * @param  Collection<int, Dietitian>  $dietitians
     * @param  Collection<int, FeedbackRequest>  $feedbackRequests
     * @param  Collection<int, UserExperience>  $users
     * @return array<string, mixed>
     */
    private function adminDietitianData(Collection $dietitians, Collection $feedbackRequests, Collection $users): array
    {
        return [
            'cards' => [
                ['label' => 'Total Dietitians', 'value' => $dietitians->count(), 'tone' => 'portal-tone-purple'],
                ['label' => 'Total Patients', 'value' => $users->whereNotNull('active_dietitian_id')->count(), 'tone' => 'portal-tone-blue'],
                ['label' => 'Average Rating', 'value' => number_format((float) ($dietitians->avg('rating') ?? 0), 1), 'tone' => 'portal-tone-yellow'],
                ['label' => 'Active', 'value' => $dietitians->where('status', 'active')->count(), 'tone' => 'portal-tone-green'],
            ],
            'list' => $dietitians->map(function (Dietitian $dietitian) use ($feedbackRequests, $users): array {
                $nameParts = preg_split('/\s+/', trim($dietitian->name)) ?: [];
                $initials = collect($nameParts)->take(2)->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))->implode('');
                $assignedUsersCount = $users->where('active_dietitian_id', $dietitian->id)->count();

                return [
                    'id' => $dietitian->id,
                    'name' => $dietitian->name,
                    'email' => $dietitian->email,
                    'specialization' => $dietitian->specialization,
                    'experience_years' => $dietitian->experience_years,
                    'patient_count' => $assignedUsersCount,
                    'rating' => number_format((float) $dietitian->rating, 1),
                    'status' => $dietitian->status,
                    'initials' => $initials !== '' ? $initials : 'DI',
                    'feedback_count' => $feedbackRequests->where('dietitian_id', $dietitian->id)->count(),
                ];
            })->all(),
        ];
    }

    /**
     * @param  Collection<int, FeedbackRequest>  $feedbackRequests
     * @param  Collection<int, ConsultationRequest>  $consultationRequests
     * @return array<string, mixed>
     */
    private function adminFeedbackData(Collection $feedbackRequests, Collection $consultationRequests): array
    {
        return [
            'cards' => [
                ['label' => 'Total Requests', 'value' => $feedbackRequests->count(), 'tone' => 'portal-tone-blue'],
                ['label' => 'Pending', 'value' => $feedbackRequests->where('status', 'pending')->count(), 'tone' => 'portal-tone-yellow'],
                ['label' => 'In Progress', 'value' => $feedbackRequests->where('status', 'in-progress')->count(), 'tone' => 'portal-tone-purple'],
                ['label' => 'Completed', 'value' => $feedbackRequests->where('status', 'completed')->count(), 'tone' => 'portal-tone-green'],
            ],
            'consultations' => [
                'total' => $consultationRequests->count(),
                'pending' => $consultationRequests->where('status', 'pending')->count(),
                'in_progress' => $consultationRequests->where('status', 'in-progress')->count(),
                'completed' => $consultationRequests->where('status', 'completed')->count(),
            ],
        ];
    }

    /**
     * @param  Collection<int, UserExperience>  $users
     * @return array<string, mixed>
     */
    private function adminAnalyticsData(Collection $users): array
    {
        $bmiBuckets = $this->bmiBuckets($users);
        $goalBuckets = $this->goalBuckets($users);
        $bmiTotal = max(array_sum($bmiBuckets), 1);
        $goalTotal = max(array_sum($goalBuckets), 1);
        $bmiAngles = $this->chartAngles($bmiBuckets, ['Underweight', 'Normal', 'Overweight', 'Obese'], $bmiTotal);
        $goalAngles = $this->chartAngles($goalBuckets, [
            self::GOAL_WEIGHT_MAINTENANCE,
            self::GOAL_MUSCLE_GAIN,
            self::GOAL_WEIGHT_LOSS,
            self::GOAL_LIFESTYLE,
        ], $goalTotal);

        return [
            'bmi_distribution' => [
                'style' => sprintf(
                    'conic-gradient(#5d84f0 %.2fdeg %.2fdeg, #59ba87 %.2fdeg %.2fdeg, #ef7f2f %.2fdeg %.2fdeg, #e5534d %.2fdeg 360deg)',
                    $bmiAngles['Underweight'][0], $bmiAngles['Underweight'][1],
                    $bmiAngles['Normal'][0], $bmiAngles['Normal'][1],
                    $bmiAngles['Overweight'][0], $bmiAngles['Overweight'][1],
                    $bmiAngles['Obese'][0]
                ),
                'legend' => [
                    ['label' => 'Underweight', 'count' => $bmiBuckets['Underweight'], 'color' => '#5d84f0', 'percent' => (int) round(($bmiBuckets['Underweight'] / $bmiTotal) * 100)],
                    ['label' => 'Normal', 'count' => $bmiBuckets['Normal'], 'color' => '#59ba87', 'percent' => (int) round(($bmiBuckets['Normal'] / $bmiTotal) * 100)],
                    ['label' => 'Overweight', 'count' => $bmiBuckets['Overweight'], 'color' => '#ef7f2f', 'percent' => (int) round(($bmiBuckets['Overweight'] / $bmiTotal) * 100)],
                    ['label' => 'Obese', 'count' => $bmiBuckets['Obese'], 'color' => '#e5534d', 'percent' => (int) round(($bmiBuckets['Obese'] / $bmiTotal) * 100)],
                ],
            ],
            'goals' => [
                'style' => sprintf(
                    'conic-gradient(#557fe8 %.2fdeg %.2fdeg, #e5534d %.2fdeg %.2fdeg, #59ba87 %.2fdeg %.2fdeg, #f3a433 %.2fdeg 360deg)',
                    $goalAngles[self::GOAL_WEIGHT_MAINTENANCE][0], $goalAngles[self::GOAL_WEIGHT_MAINTENANCE][1],
                    $goalAngles[self::GOAL_MUSCLE_GAIN][0], $goalAngles[self::GOAL_MUSCLE_GAIN][1],
                    $goalAngles[self::GOAL_WEIGHT_LOSS][0], $goalAngles[self::GOAL_WEIGHT_LOSS][1],
                    $goalAngles[self::GOAL_LIFESTYLE][0]
                ),
                'legend' => [
                    ['label' => 'Maintenance', 'count' => $goalBuckets[self::GOAL_WEIGHT_MAINTENANCE], 'color' => '#557fe8', 'percent' => (int) round(($goalBuckets[self::GOAL_WEIGHT_MAINTENANCE] / $goalTotal) * 100)],
                    ['label' => self::GOAL_MUSCLE_GAIN, 'count' => $goalBuckets[self::GOAL_MUSCLE_GAIN], 'color' => '#e5534d', 'percent' => (int) round(($goalBuckets[self::GOAL_MUSCLE_GAIN] / $goalTotal) * 100)],
                    ['label' => self::GOAL_WEIGHT_LOSS, 'count' => $goalBuckets[self::GOAL_WEIGHT_LOSS], 'color' => '#59ba87', 'percent' => (int) round(($goalBuckets[self::GOAL_WEIGHT_LOSS] / $goalTotal) * 100)],
                    ['label' => self::GOAL_LIFESTYLE, 'count' => $goalBuckets[self::GOAL_LIFESTYLE], 'color' => '#f3a433', 'percent' => (int) round(($goalBuckets[self::GOAL_LIFESTYLE] / $goalTotal) * 100)],
                ],
            ],
        ];
    }

    /**
     * @param  Collection<int, UserExperience>  $users
     * @return array{Underweight: int, Normal: int, Overweight: int, Obese: int}
     */
    private function bmiBuckets(Collection $users): array
    {
        $buckets = [
            'Underweight' => 0,
            'Normal' => 0,
            'Overweight' => 0,
            'Obese' => 0,
        ];

        foreach ($users as $user) {
            $heightInMeters = $user->height_cm ? $user->height_cm / 100 : 0;
            $bmi = $heightInMeters > 0 && $user->current_weight_kg
                ? ((float) $user->current_weight_kg) / ($heightInMeters * $heightInMeters)
                : null;

            if ($bmi === null) {
                continue;
            }

            $bucket = match (true) {
                $bmi < 18.5 => 'Underweight',
                $bmi < 25 => 'Normal',
                $bmi < 30 => 'Overweight',
                default => 'Obese',
            };

            $buckets[$bucket]++;
        }

        return $buckets;
    }

    /**
     * @param  Collection<int, UserExperience>  $users
     * @return array<string, int>
     */
    private function goalBuckets(Collection $users): array
    {
        $buckets = [
            self::GOAL_WEIGHT_MAINTENANCE => 0,
            self::GOAL_MUSCLE_GAIN => 0,
            self::GOAL_WEIGHT_LOSS => 0,
            self::GOAL_LIFESTYLE => 0,
        ];

        foreach ($users as $user) {
            $goal = Str::lower((string) $user->primary_goal);
            $bucket = match (true) {
                str_contains($goal, 'maint') => self::GOAL_WEIGHT_MAINTENANCE,
                str_contains($goal, 'muscle') || str_contains($goal, 'gain') => self::GOAL_MUSCLE_GAIN,
                str_contains($goal, 'loss') || str_contains($goal, 'lose') || str_contains($goal, 'cut') => self::GOAL_WEIGHT_LOSS,
                default => self::GOAL_LIFESTYLE,
            };

            $buckets[$bucket]++;
        }

        return $buckets;
    }

    /**
     * @param  array<string, int>  $buckets
     * @param  array<int, string>  $labels
     * @return array<string, array{0: float, 1: float}>
     */
    private function chartAngles(array $buckets, array $labels, int $total): array
    {
        $angles = [];
        $progress = 0.0;

        foreach ($labels as $label) {
            $angle = round(($buckets[$label] / $total) * 360, 2);
            $angles[$label] = [$progress, $progress + $angle];
            $progress += $angle;
        }

        return $angles;
    }

    /**
     * @param  Collection<int, FoodItem>  $foodItems
     * @param  Collection<int, MealPlan>  $mealPlans
     * @return array<string, mixed>
     */
    private function adminContentData(Collection $foodItems, Collection $mealPlans): array
    {
        return [
            'cards' => [
                ['label' => 'Food Items', 'value' => $foodItems->count(), 'tone' => 'portal-tone-blue'],
                ['label' => 'Template Plans', 'value' => $mealPlans->count(), 'tone' => 'portal-tone-green'],
                ['label' => 'Avg Plan Rating', 'value' => number_format((float) ($mealPlans->avg('rating') ?? 0), 1), 'tone' => 'portal-tone-yellow'],
                ['label' => 'Active Foods', 'value' => $foodItems->where('is_active', true)->count(), 'tone' => 'portal-tone-purple'],
            ],
        ];
    }

    /**
     * @return array<string, array{title: string, subtitle: string}>
     */
    private function heroContent(): array
    {
        return [
            'dashboard' => ['title' => 'Your nutrition at a glance', 'subtitle' => 'Track what you eat, switch plans, and keep your profile current.'],
            'food-log' => ['title' => 'Food log', 'subtitle' => 'Add foods to each meal and keep your totals current.'],
            'calendar' => ['title' => 'Meal calendar', 'subtitle' => 'Preview the next few days from your active meal plan.'],
            'insights' => ['title' => 'Insights', 'subtitle' => 'See trends pulled from your saved activity and active plan.'],
            'meal-plans' => ['title' => 'Meal plans', 'subtitle' => 'Use a template, then customize every meal item however you want.'],
            'feedback' => ['title' => 'Feedback and support', 'subtitle' => 'Request help, schedule a consultation, and keep up with dietitian notes.'],
            'profile' => ['title' => 'Profile', 'subtitle' => 'Your saved body metrics and goals update the experience across the app.'],
        ];
    }

    /**
     * @param  Collection<int, FoodLogEntry>  $todayEntries
     * @return array<string, mixed>
     */
    private function insightsData(UserExperience $experience, Collection $todayEntries, ?MealPlan $activePlan): array
    {
        $calories = (int) $todayEntries->sum('calories');
        $protein = round($todayEntries->sum(fn (FoodLogEntry $entry): float => (float) $entry->protein), 1);
        $goalGap = max((float) $experience->current_weight_kg - (float) $experience->target_weight_kg, 0);
        $proteinGoal = 150;
        $calorieGoal = 2000;
        $weeklyInsights = $this->weeklyInsights($experience);
        $weeklyEntries = $weeklyInsights['entries'];
        $avgWeeklyCalories = $weeklyInsights['average_calories'];
        $avgProtein = $weeklyInsights['average_protein'];
        $daysLogged = $weeklyInsights['days_logged'];
        $plannedMealsCount = PlannedMealEntry::query()
            ->where('user_experience_id', $experience->id)
            ->whereBetween('scheduled_date', [today()->startOfWeek(), today()->endOfWeek()])
            ->count();

        return [
            'cards' => [
                ['title' => 'Calories today', 'value' => $calories, 'meta' => 'Goal 2000'],
                ['title' => 'Protein today', 'value' => $protein.'g', 'meta' => 'Goal 150g'],
                ['title' => 'Current goal gap', 'value' => $goalGap.'kg', 'meta' => 'Remaining to target'],
                ['title' => 'Active plan', 'value' => $activePlan?->name ?? 'No active plan', 'meta' => $activePlan ? $activePlan->daily_calories.' cal/day' : 'Create a plan'],
            ],
            'recommendations' => [
                'Add more foods to dinner if your logged calories are far below target.',
                'Use the meal-plan editor to remove or swap foods instead of starting over.',
                'Save your profile metrics after updates so the BMI and intake summaries stay aligned.',
            ],
            'insightHighlights' => $this->insightHighlights($experience, $avgWeeklyCalories, $avgProtein, $daysLogged, $calorieGoal, $proteinGoal),
            'performanceCards' => $this->performanceCards($avgWeeklyCalories, $avgProtein, $daysLogged, $calorieGoal, $proteinGoal),
            'achievements' => $this->achievements($weeklyEntries, $daysLogged, $plannedMealsCount, $calorieGoal),
            'personalizedRecommendations' => $this->personalizedRecommendations($avgWeeklyCalories, $avgProtein, $daysLogged, $plannedMealsCount, $calorieGoal, $proteinGoal),
        ];
    }

    /**
     * @return array{entries: Collection<string, Collection<int, FoodLogEntry>>, average_calories: float, average_protein: float, days_logged: int}
     */
    private function weeklyInsights(UserExperience $experience): array
    {
        $last7Days = collect(range(0, 6))->map(fn (int $days): string => today()->subDays($days)->toDateString());
        $weeklyEntries = $experience->foodLogEntries()
            ->whereIn('entry_date', $last7Days)
            ->get()
            ->groupBy('entry_date');
        $weeklyCalories = $last7Days
            ->map(fn (string $date): int|float => $weeklyEntries->get($date, collect())->sum('calories'))
            ->values();

        return [
            'entries' => $weeklyEntries,
            'average_calories' => (float) ($weeklyCalories->avg() ?? 0),
            'average_protein' => (float) ($weeklyEntries->flatten()->avg(fn (FoodLogEntry $entry): mixed => $entry->protein) ?? 0),
            'days_logged' => $weeklyEntries->count(),
        ];
    }

    /**
     * @return array<int, array{title: string, copy: string, tone: string}>
     */
    private function insightHighlights(UserExperience $experience, float $avgWeeklyCalories, float $avgProtein, int $daysLogged, int $calorieGoal, int $proteinGoal): array
    {
        return array_merge(
            [$this->calorieInsight($avgWeeklyCalories, $calorieGoal)],
            [$this->proteinInsight($avgProtein, $proteinGoal)],
            [$this->loggingInsight($daysLogged)],
            $this->weightProgressInsights($experience),
        );
    }

    /**
     * @return array{title: string, copy: string, tone: string}
     */
    private function calorieInsight(float $avgWeeklyCalories, int $calorieGoal): array
    {
        if ($avgWeeklyCalories > $calorieGoal * 1.1) {
            return [
                'title' => 'High calorie intake',
                'copy' => 'Your average daily calories are above your target. Consider reducing portion sizes.',
                'tone' => 'portal-tone-red',
            ];
        }

        if ($avgWeeklyCalories < $calorieGoal * 0.9) {
            return [
                'title' => 'Low calorie intake',
                'copy' => 'You\'re consuming fewer calories than recommended. Try adding nutrient-dense foods.',
                'tone' => 'portal-tone-yellow',
            ];
        }

        return [
            'title' => 'Great calorie balance!',
            'copy' => 'You\'re maintaining healthy caloric intake levels this week.',
            'tone' => 'portal-tone-green',
        ];
    }

    /**
     * @return array{title: string, copy: string, tone: string}
     */
    private function proteinInsight(float $avgProtein, int $proteinGoal): array
    {
        if ($avgProtein >= $proteinGoal * 0.9) {
            return [
                'title' => 'Excellent protein intake!',
                'copy' => 'You\'re consistently meeting your protein goals. Keep up the great work!',
                'tone' => 'portal-tone-green',
            ];
        }

        if ($avgProtein >= $proteinGoal * 0.7) {
            return [
                'title' => 'Good protein progress',
                'copy' => 'You\'re getting decent protein, but there\'s room for improvement.',
                'tone' => 'portal-tone-blue',
            ];
        }

        return [
            'title' => 'Boost your protein',
            'copy' => 'Consider adding more protein-rich foods like lean meats, eggs, or legumes.',
            'tone' => 'portal-tone-yellow',
        ];
    }

    /**
     * @return array{title: string, copy: string, tone: string}
     */
    private function loggingInsight(int $daysLogged): array
    {
        if ($daysLogged >= 6) {
            return [
                'title' => 'Consistent logging!',
                'copy' => 'You\'ve been great about logging your meals this week.',
                'tone' => 'portal-tone-green',
            ];
        }

        if ($daysLogged >= 4) {
            return [
                'title' => 'Good logging habits',
                'copy' => 'You\'re logging most days. Try to log every meal for better insights.',
                'tone' => 'portal-tone-blue',
            ];
        }

        return [
            'title' => 'Increase meal logging',
            'copy' => 'Regular meal logging helps us provide better personalized recommendations.',
            'tone' => 'portal-tone-yellow',
        ];
    }

    /**
     * @return array<int, array{title: string, copy: string, tone: string}>
     */
    private function weightProgressInsights(UserExperience $experience): array
    {
        $recentWeights = $experience->bmi_history
            ? collect($experience->bmi_history)
                ->take(4)
                ->pluck('weight')
                ->map(fn (mixed $weight): float => (float) str_replace(' kg', '', (string) $weight))
            : collect();
        $insights = [];

        if ($recentWeights->count() > 1) {
            $weightTrend = $recentWeights->first() - $recentWeights->last();

            if ($weightTrend > 0.5) {
                $insights[] = [
                    'title' => 'Great weight progress!',
                    'copy' => 'You\'ve lost weight recently. Keep up the healthy habits!',
                    'tone' => 'portal-tone-green',
                ];
            } elseif ($weightTrend < -0.5) {
                $insights[] = [
                    'title' => 'Weight gain detected',
                    'copy' => 'Consider reviewing your calorie intake and portion sizes.',
                    'tone' => 'portal-tone-red',
                ];
            }
        }

        return $insights;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function performanceCards(float $avgWeeklyCalories, float $avgProtein, int $daysLogged, int $calorieGoal, int $proteinGoal): array
    {
        return [
            [
                'title' => 'Average Calories',
                'value' => number_format($avgWeeklyCalories, 0),
                'suffix' => '',
                'target' => 'Target: '.$calorieGoal,
                'progress' => min(($avgWeeklyCalories / $calorieGoal) * 100, 150),
            ],
            [
                'title' => 'Protein Goal Achievement',
                'value' => number_format(($avgProtein / $proteinGoal) * 100, 0),
                'suffix' => '%',
                'target' => 'Target: 100%',
                'progress' => min(($avgProtein / $proteinGoal) * 100, 100),
            ],
            [
                'title' => 'Meal Logging Consistency',
                'value' => $daysLogged,
                'suffix' => ' /7 days',
                'target' => 'Target: 7/7 days',
                'progress' => ($daysLogged / 7) * 100,
            ],
        ];
    }

    /**
     * @param  Collection<string, Collection<int, FoodLogEntry>>  $weeklyEntries
     * @return array<int, array<string, mixed>>
     */
    private function achievements(Collection $weeklyEntries, int $daysLogged, int $plannedMealsCount, int $calorieGoal): array
    {
        $highProteinDays = $weeklyEntries->filter(function (Collection $dayEntries): bool {
            $protein = $dayEntries->sum(fn (FoodLogEntry $entry): mixed => $entry->protein);

            return $protein >= 130;
        })->count();
        $consistentDays = $weeklyEntries->filter(function (Collection $dayEntries) use ($calorieGoal): bool {
            return abs($dayEntries->sum('calories') - $calorieGoal) <= 200;
        })->count();

        return [
            $this->achievement('7-Day Streak', 'Logged meals for 7 consecutive days', 'Log meals for 7 days in a row', 'portal-tone-green', $daysLogged >= 7),
            $this->achievement('Protein Master', 'Met protein goals for 5+ days this week', 'Meet protein goals for 5 days this week', 'portal-tone-green', $highProteinDays >= 5),
            $this->achievement('Calorie Consistency', 'Stayed within 200 calories of goal for 5+ days', 'Stay within 200 calories of your goal for 5 days', 'portal-tone-blue', $consistentDays >= 5),
            $this->achievement('Meal Planner Pro', 'Planned 14+ meals for the week', 'Plan meals for the entire week', 'portal-tone-purple', $plannedMealsCount >= 14),
        ];
    }

    /**
     * @return array{title: string, copy: string, tone: string, unlocked: bool}
     */
    private function achievement(string $title, string $unlockedCopy, string $lockedCopy, string $tone, bool $unlocked): array
    {
        return [
            'title' => $title,
            'copy' => $unlocked ? $unlockedCopy : $lockedCopy,
            'tone' => $tone,
            'unlocked' => $unlocked,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function personalizedRecommendations(float $avgWeeklyCalories, float $avgProtein, int $daysLogged, int $plannedMealsCount, int $calorieGoal, int $proteinGoal): array
    {
        $recommendations = [];

        if ($avgWeeklyCalories < $calorieGoal * 0.85) {
            $recommendations[] = [
                'title' => 'Increase Calorie Intake',
                'priority' => 'high priority',
                'priority_tone' => 'portal-badge--red',
                'copy' => 'Your calorie intake is below target. Consider adding calorie-dense, nutrient-rich foods.',
                'tips' => ['Add healthy fats like avocados and nuts', 'Include more complex carbohydrates', 'Consider protein shakes or smoothies'],
            ];
        } elseif ($avgWeeklyCalories > $calorieGoal * 1.15) {
            $recommendations[] = [
                'title' => 'Reduce Calorie Intake',
                'priority' => 'high priority',
                'priority_tone' => 'portal-badge--red',
                'copy' => 'Your calorie intake exceeds your target. Focus on portion control and nutrient density.',
                'tips' => ['Use smaller plates to control portions', 'Choose water or unsweetened beverages', 'Focus on high-volume, low-calorie foods'],
            ];
        }

        if ($avgProtein < $proteinGoal * 0.8) {
            $recommendations[] = [
                'title' => 'Increase Protein Intake',
                'priority' => 'medium priority',
                'priority_tone' => 'portal-badge--yellow',
                'copy' => 'You\'re not meeting your protein goals. Protein is essential for muscle maintenance and satiety.',
                'tips' => ['Include protein in every meal', 'Try Greek yogurt or cottage cheese', 'Consider lean meats, fish, or plant-based proteins'],
            ];
        }

        if ($daysLogged < 5) {
            $recommendations[] = [
                'title' => 'Improve Meal Logging',
                'priority' => 'medium priority',
                'priority_tone' => 'portal-badge--yellow',
                'copy' => 'Consistent meal logging helps us provide better insights and recommendations.',
                'tips' => ['Set reminders to log meals', 'Use the mobile app for quick logging', 'Log meals right after eating for accuracy'],
            ];
        }

        if ($plannedMealsCount < 7) {
            $recommendations[] = [
                'title' => 'Start Meal Planning',
                'priority' => 'low priority',
                'priority_tone' => 'portal-badge--blue',
                'copy' => 'Meal planning can help you stay consistent with your nutrition goals.',
                'tips' => ['Plan meals for 2-3 days at a time', 'Prep ingredients in advance', 'Use the calendar feature to schedule meals'],
            ];
        }

        return $recommendations;
    }

    /**
     * @return array<string, mixed>
     */
    private function profileData(UserExperience $experience): array
    {
        $heightInMeters = $experience->height_cm ? $experience->height_cm / 100 : 0;
        $bmi = $heightInMeters > 0 && $experience->current_weight_kg
            ? round(((float) $experience->current_weight_kg) / ($heightInMeters * $heightInMeters), 1)
            : 0.0;

        return [
            'full_name' => $this->filledString($experience->full_name),
            'age' => $this->positiveValue($experience->age),
            'gender' => $this->filledString($experience->gender),
            'activity_level' => $this->filledString($experience->activity_level),
            'primary_goal' => $this->filledString($experience->primary_goal),
            'height_cm' => $this->positiveValue($experience->height_cm),
            'current_weight_kg' => $this->positiveValue($experience->current_weight_kg),
            'target_weight_kg' => $this->positiveValue($experience->target_weight_kg),
            'starting_weight_kg' => $this->positiveValue($experience->starting_weight_kg),
            'bmi' => $bmi > 0 ? $bmi : '',
            'status' => $bmi > 0 ? $this->bmiStatus($bmi) : '',
            'remaining_weight_kg' => ($experience->current_weight_kg && $experience->target_weight_kg)
                ? max((float) $experience->current_weight_kg - (float) $experience->target_weight_kg, 0)
                : '',
            'bmi_history' => $experience->bmi_history ?? [],
        ];
    }

    private function filledString(?string $value): string
    {
        return $value !== null && trim($value) !== '' ? $value : '';
    }

    private function positiveValue(mixed $value): mixed
    {
        return $value > 0 ? $value : '';
    }

    /**
     * @param  Collection<int, FoodLogEntry>  $todayEntries
     * @return array<string, mixed>
     */
    private function calendarData(UserExperience $experience, Carbon $selectedDate, Collection $todayEntries): array
    {
        $monthStart = $selectedDate->copy()->startOfMonth();
        $daysInMonth = $monthStart->daysInMonth;
        $plannedEntries = PlannedMealEntry::query()
            ->where('user_experience_id', '=', $experience->id, 'and')
            ->whereBetween('scheduled_date', [
                $monthStart->copy()->startOfMonth()->toDateString(),
                $monthStart->copy()->endOfMonth()->toDateString(),
            ])
            ->orderBy('scheduled_date')
            ->orderBy('meal_slot')
            ->get()
            ->groupBy(fn (PlannedMealEntry $entry): string => $entry->scheduled_date->toDateString());

        $days = collect(range(1, $daysInMonth))->map(function (int $day) use ($monthStart, $selectedDate, $todayEntries, $plannedEntries): array {
            $date = $monthStart->copy()->day($day);
            $entriesForDate = $plannedEntries->get($date->toDateString(), collect())->keyBy('meal_slot');

            return [
                'label' => $day,
                'date' => $date->toDateString(),
                'selected' => $selectedDate->isSameDay($date),
                'has_log' => $todayEntries->isNotEmpty() && $date->isToday(),
                'has_plan' => $entriesForDate->isNotEmpty(),
                'count' => $entriesForDate->count(),
                'entries' => $entriesForDate,
            ];
        });

        $upcoming = collect(range(0, 2))->map(function (int $offset) use ($experience, $selectedDate): array {
            $date = $selectedDate->copy()->addDays($offset);
            $plannedEntries = PlannedMealEntry::query()
                ->where('user_experience_id', '=', $experience->id, 'and')
                ->whereDate('scheduled_date', $date)
                ->orderBy('meal_slot')
                ->get();
            $groupedMeals = $plannedEntries
                ->groupBy('meal_slot')
                ->map(fn (Collection $items): array => $items->map(
                    fn (PlannedMealEntry $item): string => $item->food_name.' ('.$item->grams.'g • '.$item->calories.' cal)'
                )->all())
                ->all();

            return [
                'label' => $date->format('l, M j'),
                'date' => $date->toDateString(),
                'meals' => $groupedMeals,
            ];
        })->all();

        $plannedDays = $days->filter(fn (array $day): bool => $day['has_plan']);
        $scheduledDaysCount = $plannedDays->count();
        $plannedMealsCount = $plannedDays->sum(fn (array $day): int => (int) $day['count']);
        $mealPrepRate = $daysInMonth > 0 ? (int) round(($scheduledDaysCount / $daysInMonth) * 100) : 0;
        $averageDailyCalories = $scheduledDaysCount > 0
            ? (int) round(
                $plannedDays
                    ->map(fn (array $day): float => (float) $day['entries']->sum('calories'))
                    ->avg() ?? 0
            )
            : 0;

        return [
            'month_label' => $monthStart->format('F Y'),
            'selected_date' => $selectedDate->toDateString(),
            'previous_month' => $monthStart->copy()->subMonthNoOverflow()->toDateString(),
            'next_month' => $monthStart->copy()->addMonthNoOverflow()->toDateString(),
            'days' => $days,
            'upcoming' => $upcoming,
            'stats' => [
                ['label' => 'Meals Planned', 'value' => $plannedMealsCount, 'tone' => 'portal-tone-green'],
                ['label' => 'Days Scheduled', 'value' => $scheduledDaysCount, 'tone' => 'portal-tone-blue'],
                ['label' => 'Meal Prep Rate', 'value' => $mealPrepRate.'%', 'tone' => 'portal-tone-orange'],
                ['label' => 'Avg Daily Calories', 'value' => number_format($averageDailyCalories), 'tone' => 'portal-tone-purple'],
            ],
        ];
    }

    private function selectedDate(Request $request): Carbon
    {
        $date = $request->query('date');

        return $date ? Carbon::parse($date) : today();
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

    private function initializeExperience(UserExperience $experience): void
    {
        if ($experience->active_dietitian_id === null) {
            $experience->forceFill([
                'active_dietitian_id' => Dietitian::query()->value('id'),
            ])->save();
        }
    }

    private function seedDemoData(): void
    {
        if (FoodItem::query()->doesntExist()) {
            app(PortalDemoSeeder::class)->run();
        }
    }

    private function bmiStatus(float $bmi): string
    {
        return match (true) {
            $bmi < 18.5 => 'Underweight',
            $bmi < 25 => 'Normal',
            $bmi < 30 => 'Overweight',
            default => 'Obese',
        };
    }
}
