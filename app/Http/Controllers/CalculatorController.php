<?php

namespace App\Http\Controllers;

use App\Http\Requests\Calculator\CalculateNutritionRequest;
use App\Repositories\FoodRepository;
use App\Repositories\NutritionCalculationRepository;
use App\Repositories\ProfileRepository;
use App\Services\MealPlanService;
use App\Services\MenuBuilder;
use App\Support\MealSlot;
use App\Support\NutritionCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CalculatorController extends Controller
{
    private const RESULT_SESSION_KEY = 'calculator.result';

    public function __construct(
        private NutritionCalculationRepository $calculations,
        private ProfileRepository $profiles,
        private FoodRepository $foods,
        private MenuBuilder $menuBuilder,
        private MealPlanService $mealPlans,
    ) {}

    /**
     * Halaman kalkulator: form, hasil terakhir, penyusun menu, dan riwayat.
     */
    public function index(): View
    {
        $userId = (int) Auth::id();

        // Hasil dari store() (pola POST-Redirect-GET), hanya tampil sekali.
        $freshResult = session()->pull(self::RESULT_SESSION_KEY);

        $latest = $this->calculations->latestForUser($userId);
        if ($latest !== null) {
            $latest['bmi_category'] = NutritionCalculator::bmiCategory((float) $latest['bmi']);
        }

        $result = $freshResult ?: $latest;
        $selection = $this->menuBuilder->selection();

        return view('calculator.index', [
            'pageTitle' => 'Nutrition Calculator — NutriMate',
            'form' => $this->formDefaults($latest),
            'result' => $result,
            'history' => $this->calculations->historyForUser($userId),
            'activities' => NutritionCalculator::ACTIVITY_LEVELS,
            'foods' => $this->foods->all(['sort' => 'cheap']),
            'items' => $selection['items'],
            'totals' => $selection['totals'],
            'target' => $result ? (float) $result['tdee'] : 0.0,
            'maxPortions' => MenuBuilder::MAX_PORTIONS,
            'slots' => MealSlot::all(),
        ]);
    }

    /**
     * Hitung BMR/TDEE/IMT, simpan riwayat, dan sinkronkan target kalori profil.
     */
    public function store(CalculateNutritionRequest $request): RedirectResponse
    {
        $userId = (int) Auth::id();

        $result = NutritionCalculator::calculate($request->validated());
        $result['id'] = $this->calculations->create($userId, $result);

        // Target kalori profil mengikuti TDEE terbaru agar Meal Planner dan
        // rekomendasi memakai angka yang sama. Budget harian tidak diubah.
        $this->profiles->updateTargets(
            $userId,
            (int) $result['tdee'],
            $this->profiles->dailyBudgetFor($userId),
        );

        session([self::RESULT_SESSION_KEY => $result]);

        return redirect()
            ->route('calculator.index')
            ->with('success', 'Perhitungan berhasil disimpan. Target kalori harianmu kini '
                .number_format($result['tdee'], 0, ',', '.').' kkal.');
    }

    /**
     * Tambah bahan makanan ke susunan menu, atau perbarui porsinya.
     */
    public function addItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'food_id' => ['required', 'string'],
            'portions' => ['nullable', 'integer', 'min:1', 'max:'.MenuBuilder::MAX_PORTIONS],
        ], [
            'food_id.required' => 'Pilih bahan makanan terlebih dahulu.',
            'portions.integer' => 'Porsi harus berupa angka.',
            'portions.min' => 'Porsi minimal 1.',
            'portions.max' => 'Porsi maksimal '.MenuBuilder::MAX_PORTIONS.'.',
        ]);

        $food = $this->foods->find($validated['food_id']);

        if ($food === null) {
            return redirect()->route('calculator.index')->with('error', 'Bahan makanan tidak ditemukan.');
        }

        $portions = (int) ($validated['portions'] ?? 1);
        $isUpdate = $this->menuBuilder->add($food['id'], $portions);

        return redirect()
            ->route('calculator.index')
            ->with('success', sprintf(
                '%s%s (%d porsi).',
                $isUpdate ? 'Porsi diperbarui: ' : 'Ditambahkan ke menu: ',
                $food['name'],
                $portions,
            ));
    }

    public function removeItem(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'food_id' => ['required', 'string'],
        ]);

        $removed = $this->menuBuilder->remove($validated['food_id']);

        return redirect()
            ->route('calculator.index')
            ->with($removed ? 'success' : 'error', $removed
                ? 'Bahan makanan dihapus dari menu.'
                : 'Bahan makanan tidak ada di menu.');
    }

    public function clearItems(): RedirectResponse
    {
        $this->menuBuilder->clear();

        return redirect()
            ->route('calculator.index')
            ->with('success', 'Susunan menu dikosongkan.');
    }

    /**
     * Kirim seluruh bahan makanan terpilih ke Meal Planner pada satu slot.
     */
    public function saveToMealPlan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slot' => ['required', 'string', 'in:'.implode(',', array_keys(MealSlot::all()))],
        ], [
            'slot.in' => 'Waktu makan tidak valid.',
        ]);

        $selection = $this->menuBuilder->selection();

        if ($selection['items'] === []) {
            return redirect()
                ->route('calculator.index')
                ->with('error', 'Susunan menu masih kosong. Tambahkan bahan makanan terlebih dahulu.');
        }

        $userId = (int) Auth::id();

        foreach ($selection['items'] as $item) {
            $this->mealPlans->addFood($userId, $item['food'], $validated['slot'], $item['portions']);
        }

        $this->menuBuilder->clear();

        return redirect()
            ->route('meal-planner.index')
            ->with('success', sprintf(
                '%d bahan makanan berhasil disimpan ke %s hari ini. Lihat di halaman Meal Planner.',
                count($selection['items']),
                MealSlot::label($validated['slot']),
            ));
    }

    public function destroy(string $id): RedirectResponse
    {
        // ID berasal dari segmen URL (route model binding manual), bukan dari
        // body form, sehingga tidak perlu divalidasi ulang sebagai input.
        $deleted = $this->calculations->deleteForUser((int) $id, (int) Auth::id());

        return redirect()
            ->route('calculator.index')
            ->with($deleted ? 'success' : 'error', $deleted
                ? 'Riwayat perhitungan dihapus.'
                : 'Riwayat perhitungan gagal dihapus.');
    }

    public function clear(): RedirectResponse
    {
        $this->calculations->clearForUser((int) Auth::id());

        return redirect()
            ->route('calculator.index')
            ->with('success', 'Seluruh riwayat perhitungan berhasil dibersihkan.');
    }

    /**
     * Nilai awal form: perhitungan terakhir bila ada, atau default aplikasi.
     *
     * @return array{gender: string, age: int, weight: float, height: float, activity: string}
     */
    private function formDefaults(?array $latest): array
    {
        return [
            'gender' => (string) ($latest['gender'] ?? 'male'),
            'age' => (int) ($latest['age'] ?? 20),
            'weight' => (float) ($latest['weight'] ?? 60),
            'height' => (float) ($latest['height'] ?? 170),
            'activity' => NutritionCalculator::normalizeActivity(
                isset($latest['activity_factor']) ? (string) (float) $latest['activity_factor'] : null,
            ),
        ];
    }
}