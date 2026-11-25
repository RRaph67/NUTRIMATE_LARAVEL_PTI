<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi pembaruan data diri & target harian.
 *
 * Nilai di luar rentang ditolak dengan pesan yang jelas (bukan dipotong
 * diam-diam), sehingga angka tersimpan selalu sama dengan yang diketik.
 */
class UpdateProfileRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:60'],
            'calorie_target' => ['required', 'integer', 'min:500', 'max:6000'],
            'daily_budget' => ['required', 'numeric', 'min:0', 'max:10000000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Nama lengkap tidak boleh kosong.',
            'full_name.max' => 'Nama lengkap maksimal 60 karakter.',
            'calorie_target.required' => 'Target kalori wajib diisi.',
            'calorie_target.integer' => 'Target kalori harus berupa angka bulat.',
            'calorie_target.min' => 'Target kalori minimal 500 kkal.',
            'calorie_target.max' => 'Target kalori maksimal 6.000 kkal.',
            'daily_budget.required' => 'Budget makan harian wajib diisi.',
            'daily_budget.numeric' => 'Budget makan harian harus berupa angka.',
            'daily_budget.min' => 'Budget makan harian tidak boleh negatif.',
            'daily_budget.max' => 'Budget makan harian maksimal Rp 10.000.000.',
        ];
    }
}
