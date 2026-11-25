<?php

namespace App\Http\Controllers;

use App\Repositories\FoodRepository;
use App\Services\MealPlanService;
use App\Support\MealSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MealPlanController extends Controller
{
    public function __construct(
        private MealPlanService $mealPlans,
        private FoodRepository $foods,
    ) {}

    public function index(): View
    {
        return view('meal-planner.index', [
            'pageTitle' => 'Meal Planner — NutriMate',
            'plan' => $this->mealPlans->todaySummary((int) Auth::id()),
        ]);
    }

    /**
     * Tambah makanan dari modal Food Database ke salah satu slot hari ini.
     */
    public function storeFood(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'food_id' => ['required', 'string'],
            'slot' => ['required', 'string', 'in:'.implode(',', array_keys(MealSlot::all()))],
            'portions' => ['nullable', 'integer', 'min:1', 'max:2'],
        ], [
            'slot.in' => 'Waktu makan tidak valid.',
            'food_id.required' => 'Pilih makanan terlebih dahulu.',
            'portions.integer' => 'Porsi harus berupa angka.',
            'portions.min' => 'Porsi minimal 1.',
            'portions.max' => 'Porsi maksimal 2.',
        ]);

        $food = $this->foods->find($validated['food_id']);

        if ($food === null) {
            return redirect()->route('foods.index')->with('error', 'Makanan tidak ditemukan.');
        }

        $portions = (int) ($validated['portions'] ?? 1);
        $slot = $validated['slot'];

        $this->mealPlans->addFood((int) Auth::id(), $food, $slot, $portions);

        return redirect()
            ->route('meal-planner.index')
            ->with('success', sprintf(
                'Berhasil! %s (%d porsi) ditambahkan ke %s.',
                $food['name'],
                $portions,
                MealSlot::label($slot),
            ));
    }

    /**
     * Kosongkan satu slot makan hari ini.
     */
    public function destroySlot(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slot' => ['required', 'string', 'in:'.implode(',', array_keys(MealSlot::all()))],
        ], [
            'slot.in' => 'Waktu makan tidak valid.',
        ]);

        $this->mealPlans->clearSlot((int) Auth::id(), $validated['slot']);

        return redirect()
            ->route('meal-planner.index')
            ->with('success', MealSlot::label($validated['slot']).' berhasil dikosongkan.');
    }
}