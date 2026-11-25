<?php

namespace App\Http\ViewComposers;

use App\Services\MealPlanService;
use App\Support\Navigation;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Data bersama untuk navbar & sidebar.
 *
 * Dengan view composer, layout tidak perlu memanggil service langsung dan
 * jumlah item Meal Planner hanya dihitung sekali per request.
 */
class LayoutComposer
{
    private ?int $plannerCount = null;

    public function __construct(private MealPlanService $mealPlans) {}

    public function compose(View $view): void
    {
        $user = Auth::user();

        $view->with([
            'navItems' => Navigation::items(),
            'navUser' => $user,
            'navUserName' => $user?->displayName() ?? '',
            'navUserInitials' => $user?->initials() ?? '',
            'plannerCount' => $this->plannerCount(),
        ]);
    }

    private function plannerCount(): int
    {
        if (! Auth::check()) {
            return 0;
        }

        return $this->plannerCount ??= $this->mealPlans->todayCount((int) Auth::id());
    }
}
