<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Tamu yang membuka halaman terproteksi diarahkan ke beranda dengan
        // modal login terbuka (aplikasi tidak punya halaman login terpisah).
        $middleware->redirectGuestsTo(fn () => route('home', ['auth' => 'login']));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
