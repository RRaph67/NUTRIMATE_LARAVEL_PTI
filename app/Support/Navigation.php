<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * Menu navigasi utama (navbar & sidebar).
 *
 * Didefinisikan sekali di sini supaya label, tujuan, dan status aktif pada
 * navbar dan sidebar selalu konsisten.
 */
final class Navigation
{
    /**
     * @return list<array{label: string, route: string, active: string, guestAction: ?string}>
     */
    public static function items(): array
    {
        return [
            [
                'label' => 'Home',
                'route' => 'home',
                'active' => 'home',
                'guestAction' => null,
            ],
            [
                'label' => 'Food Database',
                'route' => 'foods.index',
                'active' => 'foods.*',
                'guestAction' => 'login',
            ],
            [
                'label' => 'Recommendation',
                'route' => 'recommendation.index',
                'active' => 'recommendation.*',
                'guestAction' => 'login',
            ],
            [
                'label' => 'Calculate Nutritions',
                'route' => 'calculator.index',
                'active' => 'calculator.*',
                'guestAction' => 'calculator',
            ],
        ];
    }

    /**
     * Apakah item menu sedang aktif berdasarkan route saat ini.
     */
    public static function isActive(array $item): bool
    {
        return Route::has($item['route']) && request()->routeIs($item['active']);
    }
}
