<?php

namespace App\Http\Requests\Food;

use App\Support\FoodCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form tambah/edit makanan.
 *
 * Semua aturan yang sebelumnya dicek manual di controller kini terpusat di
 * sini: kategori harus dari daftar sah, angka tidak boleh negatif, dan URL
 * gambar wajib http/https.
 */
class FoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'category' => ['required', 'string', Rule::in(FoodCategory::all())],
            'price' => ['required', 'numeric', 'min:0'],
            'portion_description' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'calories' => ['required', 'numeric', 'min:0'],
            'protein' => ['required', 'numeric', 'min:0'],
            'carbs' => ['required', 'numeric', 'min:0'],
            'fat' => ['required', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:255', 'url:http,https'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama makanan wajib diisi.',
            'name.min' => 'Nama makanan minimal 2 karakter.',
            'category.required' => 'Kategori wajib dipilih.',
            'category.in' => 'Kategori tidak valid. Pilih salah satu: '.implode(', ', FoodCategory::all()).'.',
            'price.required' => 'Harga wajib diisi.',
            'price.numeric' => 'Harga harus berupa angka.',
            'price.min' => 'Harga tidak boleh bernilai negatif.',
            'portion_description.required' => 'Deskripsi porsi wajib diisi.',
            'calories.required' => 'Kalori wajib diisi.',
            'calories.numeric' => 'Kalori harus berupa angka.',
            'calories.min' => 'Kalori tidak boleh bernilai negatif.',
            'protein.required' => 'Protein wajib diisi.',
            'protein.numeric' => 'Protein harus berupa angka.',
            'protein.min' => 'Protein tidak boleh bernilai negatif.',
            'carbs.required' => 'Karbohidrat wajib diisi.',
            'carbs.numeric' => 'Karbohidrat harus berupa angka.',
            'carbs.min' => 'Karbohidrat tidak boleh bernilai negatif.',
            'fat.required' => 'Lemak wajib diisi.',
            'fat.numeric' => 'Lemak harus berupa angka.',
            'fat.min' => 'Lemak tidak boleh bernilai negatif.',
            'image_url.url' => 'URL gambar tidak valid. Gunakan link yang diawali http:// atau https://.',
            'image_url.max' => 'URL gambar maksimal 255 karakter.',
        ];
    }

    /**
     * Data siap simpan ke repository.
     *
     * @return array<string, mixed>
     */
    public function foodData(): array
    {
        $validated = $this->validated();

        return [
            'name' => trim($validated['name']),
            'category' => $validated['category'],
            'price' => (float) $validated['price'],
            'portion_description' => trim($validated['portion_description']),
            'description' => filled($validated['description'] ?? null) ? trim($validated['description']) : null,
            'calories' => (float) $validated['calories'],
            'protein' => (float) $validated['protein'],
            'carbs' => (float) $validated['carbs'],
            'fat' => (float) $validated['fat'],
            'image_url' => filled($validated['image_url'] ?? null) ? trim($validated['image_url']) : null,
        ];
    }
}
