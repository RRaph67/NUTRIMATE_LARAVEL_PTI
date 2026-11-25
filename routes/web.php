<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\FoodController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute publik
|--------------------------------------------------------------------------
*/

Route::get('/', HomeController::class)->name('home');

// Login & register berada di modal halaman beranda.
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Rute khusus pengguna yang sudah masuk
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Food Database (CRUD)
    Route::resource('foods', FoodController::class);

    // Profil & keamanan akun
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Meal Planner
    Route::get('/meal-planner', [MealPlanController::class, 'index'])->name('meal-planner.index');
    Route::post('/meal-planner/food', [MealPlanController::class, 'storeFood'])->name('meal-planner.store-food');
    Route::delete('/meal-planner/slot', [MealPlanController::class, 'destroySlot'])->name('meal-planner.destroy-slot');

    // Rekomendasi paket makan
    Route::get('/recommendation', [RecommendationController::class, 'index'])->name('recommendation.index');
    Route::post('/recommendation/apply', [RecommendationController::class, 'apply'])->name('recommendation.apply');
    Route::post('/api/recommend', [RecommendationController::class, 'api'])->name('recommendation.api');

    // Nutrition Calculator
    Route::get('/calculator', [CalculatorController::class, 'index'])->name('calculator.index');
    Route::post('/calculator', [CalculatorController::class, 'store'])->name('calculator.store');
    Route::delete('/calculator/history', [CalculatorController::class, 'clear'])->name('calculator.clear');
    Route::delete('/calculator/history/{id}', [CalculatorController::class, 'destroy'])->name('calculator.destroy');

    // Penyusun menu (disimpan di session)
    Route::post('/calculator/items', [CalculatorController::class, 'addItem'])->name('calculator.add-item');
    Route::delete('/calculator/items', [CalculatorController::class, 'removeItem'])->name('calculator.remove-item');
    Route::delete('/calculator/items/all', [CalculatorController::class, 'clearItems'])->name('calculator.clear-items');
    Route::post('/calculator/save-to-meal-plan', [CalculatorController::class, 'saveToMealPlan'])
        ->name('calculator.save-to-meal-plan');
});