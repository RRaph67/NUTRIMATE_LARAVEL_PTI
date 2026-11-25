<?php

namespace App\Http\Controllers;

use App\Http\Requests\Food\FoodRequest;
use App\Repositories\FoodRepository;
use App\Support\FoodCategory;
use App\Support\FoodSort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FoodController extends Controller
{
    public function __construct(private FoodRepository $foods) {}

    /**
     * Katalog makanan dengan pencarian, filter kategori, dan pengurutan.
     */
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'category' => trim((string) $request->query('category', '')),
            'sort' => FoodSort::normalize($request->query('sort')),
        ];

        return view('foods.index', [
            'pageTitle' => 'Database Makanan & Nutrisi — NutriMate',
            'foods' => $this->foods->all($filters),
            'filters' => $filters,
            'categories' => FoodCategory::all(),
            'sorts' => FoodSort::options(),
        ]);
    }

    public function create(): View
    {
        return view('foods.create', [
            'pageTitle' => 'Tambah Makanan — NutriMate',
            'food' => null,
            'categories' => FoodCategory::all(),
        ]);
    }

    public function store(FoodRequest $request): RedirectResponse
    {
        $id = $this->foods->create($request->foodData());

        return redirect()
            ->route('foods.show', $id)
            ->with('success', 'Menu makanan berhasil ditambahkan!');
    }

    /**
     * Detail makanan. Mendukung respons JSON untuk permintaan AJAX.
     */
    public function show(Request $request, string $food): View|JsonResponse|RedirectResponse
    {
        $row = $this->foods->find($food);

        if ($row === null) {
            return redirect()->route('foods.index')->with('error', 'Makanan tidak ditemukan.');
        }

        if ($request->wantsJson()) {
            return response()->json(['data' => $row]);
        }

        return view('foods.show', [
            'pageTitle' => $row['name'].' — NutriMate',
            'food' => $row,
        ]);
    }

    public function edit(string $food): View|RedirectResponse
    {
        $row = $this->foods->find($food);

        if ($row === null) {
            return redirect()->route('foods.index')->with('error', 'Makanan tidak ditemukan.');
        }

        return view('foods.edit', [
            'pageTitle' => 'Edit '.$row['name'].' — NutriMate',
            'food' => $row,
            'categories' => FoodCategory::all(),
        ]);
    }

    public function update(FoodRequest $request, string $food): RedirectResponse
    {
        if ($this->foods->find($food) === null) {
            return redirect()->route('foods.index')->with('error', 'Makanan tidak ditemukan.');
        }

        $this->foods->update($food, $request->foodData());

        return redirect()
            ->route('foods.show', $food)
            ->with('success', 'Menu makanan berhasil diperbarui!');
    }

    /**
     * Hapus makanan (POST-only; route sudah dibatasi lewat Route::delete).
     */
    public function destroy(string $food): RedirectResponse
    {
        if ($this->foods->delete($food)) {
            return redirect()->route('foods.index')->with('success', 'Menu makanan berhasil dihapus.');
        }

        return redirect()->route('foods.index')->with('error', 'Makanan tidak ditemukan.');
    }
}
