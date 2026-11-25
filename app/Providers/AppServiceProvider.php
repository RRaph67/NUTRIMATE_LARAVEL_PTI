<?php

namespace App\Providers;

use App\Auth\PdoUserProvider;
use App\Http\ViewComposers\LayoutComposer;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(UserRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Daftarkan driver auth "pdo" agar aplikasi tetap memakai query PDO
        // sementara sesi & guard tetap dikelola Laravel.
        Auth::provider('pdo', function ($app, array $config) {
            return new PdoUserProvider(
                $app->make(UserRepository::class),
                $app['hash'],
            );
        });

        // Data bersama untuk navbar & sidebar.
        View::composer(
            ['partials.navbar', 'partials.sidebar'],
            LayoutComposer::class,
        );
    }
}
