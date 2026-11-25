<?php

namespace App\Http\Controllers;

use App\Repositories\FoodRepository;
use App\Repositories\ProfileRepository;
use App\Services\MealPlanService;
use App\Services\Recommender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    /** Batas budget harian yang diterima endpoint rekomendasi. */
    private const MIN_BUDGET = 10000;

    private const MAX_BUDGET = 1000000;

    public function __construct(
        private ProfileRepository $profiles,
        private FoodRepository $foods,
        private Recommender $recommender,
        private MealPlanService $mealPlans,
    ) {}

    public function index(): View
    {
        $userId = (int) Auth::id();
        $profile = $this->profiles->findByUserId($userId);

        return view('recommendation.index', [
            'pageTitle' => 'Rekomendasi Cerdas Sesuai Dompet — NutriMate',
            'defaultBudget' => (int) ($profile['daily_budget'] ?? 30000),
            'defaultTarget' => (int) ($profile['calorie_target'] ?? 2000),
            'hasProfile' => $profile !== null,
        ]);
    }

    /**
     * Endpoint JSON: hitung paket rekomendasi dari budget & preferensi.
     */
    public function api(Request $request): JsonResponse
    {
        $budget = filter_var($request->input('budget'), FILTER_VALIDATE_FLOAT);

        if ($budget === false || $budget === null) {
            return response()->json(['ok' => false, 'error' => 'Budget tidak valid.'], 422);
        }

        if ($budget < self::MIN_BUDGET) {
            return response()->json(
                ['ok' => false, 'error' => 'Budget minimal Rp '.number_format(self::MIN_BUDGET, 0, ',', '.').' per hari.'],
                422,
            );
        }

        if ($budget > self::MAX_BUDGET) {
            return response()->json(
                ['ok' => false, 'error' => 'Budget maksimal Rp '.number_format(self::MAX_BUDGET, 0, ',', '.').' per hari.'],
                422,
            );
        }

        $focus = $request->input('focus', []);
        $focus = is_array($focus)
            ? array_values(array_filter(array_map('strval', $focus)))
            : [];

        $target = filter_var($request->input('target_calories'), FILTER_VALIDATE_INT);
        if ($target === false || $target === null || $target <= 0) {
            $target = $this->profiles->calorieTargetFor((int) Auth::id());
        }
        $target = max(500, min(6000, (int) $target));

        $count = filter_var($request->input('count', 1), FILTER_VALIDATE_INT);
        $count = ($count === false || $count === null) ? 1 : max(1, min(3, (int) $count));

        $result = $this->recommender->recommend(
            (float) $budget,
            $focus,
            $target,
            $count,
            $this->foods->allForRecommendation(),
        );

        return response()->json([
            'ok' => true,
            'packages' => $result['packages'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Terapkan satu paket rekomendasi ke Meal Planner hari ini.
     */
    public function apply(Request $request): RedirectResponse
    {
        $payload = json_decode((string) $request->input('payload', ''), true);

        if (! is_array($payload) || $payload === []) {
            return redirect()->route('recommendation.index')->with('error', 'Paket rekomendasi tidak valid.');
        }

        $result = $this->mealPlans->applyRecommendation((int) Auth::id(), $payload);

        if ($result['added'] > 0) {
            $message = $result['added'].' menu rekomendasi berhasil ditambahkan ke Meal Planner hari ini.';
            if ($result['skipped'] > 0) {
                $message .= ' '.$result['skipped'].' menu dilewati karena sudah ada di slot yang sama.';
            }

            return redirect()->route('meal-planner.index')->with('success', $message);
        }

        if ($result['skipped'] > 0) {
            return redirect()->route('recommendation.index')->with(
                'error',
                'Semua menu pada paket ini sudah ada di Meal Planner hari ini. Kosongkan slot terlebih dahulu bila ingin menambah ulang.',
            );
        }

        return redirect()->route('recommendation.index')->with('error', 'Tidak ada menu yang bisa ditambahkan.');
    }
}