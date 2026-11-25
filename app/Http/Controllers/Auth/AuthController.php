<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private UserRepository $users) {}

    /**
     * Tampilkan halaman masuk.
     *
     * Aplikasi memakai modal di halaman beranda, jadi tamu diarahkan ke
     * beranda dengan modal login terbuka.
     */
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('foods.index');
        }

        return redirect()->route('home', ['auth' => 'login']);
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Email atau kata sandi salah.');
        }

        $request->session()->regenerate();

        return redirect()
            ->intended(route('foods.index'))
            ->with('success', 'Selamat datang kembali, '.Auth::user()->displayName().'!');
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('foods.index');
        }

        return redirect()->route('home', ['auth' => 'register']);
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $user = $this->users->create(
            $request->string('name')->trim()->value(),
            $request->string('email')->trim()->lower()->value(),
            Hash::make($request->string('password')->value()),
        );

        Auth::loginUsingId($user);
        $request->session()->regenerate();

        return redirect()
            ->route('foods.index')
            ->with('success', 'Pendaftaran berhasil! Selamat datang di NutriMate.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('success', 'Anda telah berhasil keluar.');
    }
}
