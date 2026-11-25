<?php

namespace App\Http\Requests\Calculator;

use App\Support\NutritionCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi input perhitungan gizi.
 *
 * Rentang nilai mengikuti batas yang masuk akal secara medis, dan faktor
 * aktivitas harus salah satu nilai yang dikenal (bukan hanya dari dropdown).
 */
class CalculateNutritionRequest extends FormRequest
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
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'age' => ['required', 'integer', 'min:15', 'max:90'],
            'weight' => ['required', 'numeric', 'min:30', 'max:250'],
            'height' => ['required', 'numeric', 'min:100', 'max:230'],
            'activity' => ['required', 'string', Rule::in(array_keys(NutritionCalculator::ACTIVITY_LEVELS))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gender.required' => 'Jenis kelamin wajib dipilih.',
            'gender.in' => 'Jenis kelamin tidak dikenali.',
            'age.required' => 'Usia wajib diisi.',
            'age.integer' => 'Usia harus berupa angka bulat.',
            'age.min' => 'Usia harus antara 15 sampai 90 tahun.',
            'age.max' => 'Usia harus antara 15 sampai 90 tahun.',
            'weight.required' => 'Berat badan wajib diisi.',
            'weight.numeric' => 'Berat badan harus berupa angka.',
            'weight.min' => 'Berat badan harus antara 30 sampai 250 kg.',
            'weight.max' => 'Berat badan harus antara 30 sampai 250 kg.',
            'height.required' => 'Tinggi badan wajib diisi.',
            'height.numeric' => 'Tinggi badan harus berupa angka.',
            'height.min' => 'Tinggi badan harus antara 100 sampai 230 cm.',
            'height.max' => 'Tinggi badan harus antara 100 sampai 230 cm.',
            'activity.required' => 'Tingkat aktivitas wajib dipilih.',
            'activity.in' => 'Tingkat aktivitas tidak dikenali.',
        ];
    }
}
