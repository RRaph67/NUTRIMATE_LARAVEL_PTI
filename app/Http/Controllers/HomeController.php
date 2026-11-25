<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Halaman beranda (landing page).
     */
    public function __invoke(): View
    {
        return view('home', [
            'pageTitle' => 'NutriMate — Makan Sehat, Kantong Aman',
            'features' => $this->features(),
        ]);
    }

    /**
     * Fitur unggulan yang ditampilkan pada bagian "Fitur Utama".
     *
     * @return list<array{icon: string, color: string, title: string, description: string}>
     */
    private function features(): array
    {
        return [
            [
                'icon' => 'food',
                'color' => 'yellow',
                'title' => 'Food Database',
                'description' => 'Katalog 1.500+ menu makanan lokal & warteg lengkap dengan estimasi harga riil per porsi.',
            ],
            [
                'icon' => 'budget',
                'color' => 'green',
                'title' => 'Budget Recommendation',
                'description' => 'Rekomendasi paket makan sehat 3x sehari otomatis disesuaikan dengan sisa uang saku.',
            ],
            [
                'icon' => 'plan',
                'color' => 'orange',
                'title' => 'Meal Planner',
                'description' => 'Atur jadwal makan mingguan secara rapi dan sinkronkan dengan sisa saldo bulanan kost.',
            ],
            [
                'icon' => 'calc',
                'color' => 'red',
                'title' => 'Nutrition Calculator',
                'description' => 'Hitung kebutuhan kalori harian dan pantau makronutrisi agar badan tetap bugar sepanjang kuliah.',
            ],
        ];
    }
}
