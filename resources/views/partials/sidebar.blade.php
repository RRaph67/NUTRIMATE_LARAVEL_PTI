@php
    $isLoggedIn = auth()->check();
@endphp

<div class="overlay" id="overlay"></div>

<aside class="sidebar" id="sidebar" aria-hidden="true">
    <div class="side-brand">
        <img src="{{ asset('assets/img/logo.svg') }}" alt="" class="brand-logo" width="28" height="28">
        <div>
            <div class="brand-name">Nutri<b>Mate</b></div>
            <div class="tagline">Gizi terjaga, Hemat Terasa</div>
        </div>
    </div>

    @auth
        <a href="{{ route('profile.show') }}"
            class="side-profile {{ request()->routeIs('profile.*') ? 'active' : '' }}">
            <span class="side-avatar">{{ $navUserInitials }}</span>
            <span class="side-profile-info">
                <span class="side-profile-name">{{ $navUserName }}</span>
                <span class="side-profile-sub">Lihat profil</span>
            </span>
            <svg class="chev" viewBox="0 0 24 24">
                <path d="M9 6l6 6-6 6" />
            </svg>
        </a>
    @endauth

    <div class="side-label">General</div>
    <nav class="side-nav">
        @foreach ($navItems as $item)
            @php
                $isActive = \App\Support\Navigation::isActive($item);
                $href = $isLoggedIn || $item['guestAction'] === 'calculator'
                    ? route($item['route'])
                    : '#';
            @endphp
            <a href="{{ $href }}" class="side-item {{ $isActive ? 'active' : '' }}"
                @if (! $isLoggedIn && $item['guestAction'] === 'login') onclick="event.preventDefault(); window.NutriMate && window.NutriMate.openAuthModal('login');"
                @elseif (! $isLoggedIn && $item['guestAction'] === 'calculator') onclick="event.preventDefault(); window.NutriMate && window.NutriMate.openCalculator();" @endif>
                <span>{{ $item['label'] }}</span>
                <svg class="chev" viewBox="0 0 24 24">
                    <path d="M9 6l6 6-6 6" />
                </svg>
            </a>
        @endforeach

        @auth
            <a href="{{ route('meal-planner.index') }}"
                class="side-item {{ request()->routeIs('meal-planner.*') ? 'active' : '' }}">
                <span>Meal Planner</span>
                <svg class="chev" viewBox="0 0 24 24">
                    <path d="M9 6l6 6-6 6" />
                </svg>
            </a>
        @endauth
    </nav>

    <div class="side-label">Akun</div>
    <nav class="side-nav">
        @auth
            <form action="{{ route('logout') }}" method="POST" style="margin: 0; padding: 0;">
                @csrf
                <button type="submit" class="side-item logout"
                    style="width: 100%; border: none; background: transparent; cursor: pointer; font-family: inherit; text-align: left;">
                    <span>Keluar</span>
                    <svg class="out" viewBox="0 0 24 24">
                        <path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9" />
                    </svg>
                </button>
            </form>
        @else
            <button type="button" class="side-item" data-open-modal="register"
                style="width: 100%; border: none; background: transparent; cursor: pointer; font-family: inherit; text-align: left;">
                <span>Daftar</span>
                <svg class="chev" viewBox="0 0 24 24">
                    <path d="M9 6l6 6-6 6" />
                </svg>
            </button>
            <button type="button" class="side-item" data-open-modal="login"
                style="width: 100%; border: none; background: transparent; cursor: pointer; font-family: inherit; text-align: left;">
                <span>Masuk</span>
                <svg class="chev" viewBox="0 0 24 24">
                    <path d="M9 6l6 6-6 6" />
                </svg>
            </button>
        @endauth
    </nav>
</aside>
