@php
    $isLoggedIn = auth()->check();
@endphp

<header class="navbar" id="navbar">
    <div class="container nav-inner">
        <a href="{{ route('home') }}" class="brand">
            <img src="{{ asset('assets/img/logo.svg') }}" alt="" class="brand-logo" width="30" height="30">
            <span class="brand-name">Nutri<b>Mate</b></span>
        </a>

        {{-- Menu desktop (tampil mulai layar >= 1024px) --}}
        <nav class="nav-pill" aria-label="Menu utama">
            @foreach ($navItems as $item)
                @php
                    $isActive = \App\Support\Navigation::isActive($item);
                    $href = $isLoggedIn || $item['guestAction'] === 'calculator'
                        ? route($item['route'])
                        : '#';
                @endphp
                <a href="{{ $href }}" class="{{ $isActive ? 'active' : '' }}"
                    @if (! $isLoggedIn && $item['guestAction'] === 'login') onclick="event.preventDefault(); window.NutriMate && window.NutriMate.openAuthModal('login');"
                    @elseif (! $isLoggedIn && $item['guestAction'] === 'calculator') onclick="event.preventDefault(); window.NutriMate && window.NutriMate.openCalculator();" @endif>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Tombol akun desktop (paling kanan) --}}
        <div class="auth">
            @auth
                <a href="{{ route('meal-planner.index') }}"
                    class="nav-icon-btn {{ request()->routeIs('meal-planner.*') ? 'nav-icon-btn--active' : '' }}"
                    title="Meal Planner" aria-label="Meal Planner, {{ $plannerCount }} item hari ini">
                    <svg viewBox="0 0 24 24" width="18" height="18">
                        <rect x="3" y="5" width="18" height="16" rx="2" />
                        <path d="M3 10h18M8 3v4M16 3v4M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01" />
                    </svg>
                    @if ($plannerCount > 0)
                        <span class="nav-icon-badge">{{ $plannerCount }}</span>
                    @endif
                </a>
                <a href="{{ route('profile.show') }}"
                    class="nav-avatar {{ request()->routeIs('profile.*') ? 'nav-avatar--active' : '' }}"
                    title="Profil Saya ({{ $navUserName }})" aria-label="Profil Saya">
                    {{ $navUserInitials }}
                </a>
            @else
                <button type="button" class="btn-sm btn-outline" data-open-modal="login">Masuk</button>
                <button type="button" class="btn-sm btn-fill" data-open-modal="register">Daftar</button>
            @endauth
        </div>

        {{-- Tombol menu mobile & tablet (sembunyi di desktop) --}}
        <button class="menu-btn" id="menuBtn" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">
            <svg viewBox="0 0 24 24">
                <path d="M4 7h16M4 12h16M4 17h16" />
            </svg>
        </button>
    </div>
</header>
