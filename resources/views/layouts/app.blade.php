<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description"
        content="NutriMate — Gizi terjaga, Hemat Terasa. Katalog makanan lengkap dengan informasi harga, porsi, dan nilai gizi.">
    {{-- Token CSRF untuk permintaan AJAX (endpoint rekomendasi). --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle ?? 'NutriMate — Gizi Terjaga, Hemat Terasa' }}</title>

    {{-- Google Font: Poppins --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- CSS Global NutriMate --}}
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>

<body>
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

    @include('partials.navbar')
    @include('partials.sidebar')

    <main id="main-content" class="site-main" tabindex="-1">
        <div class="container">
            @include('layouts.alerts')
            @yield('content')
        </div>
    </main>

    @include('partials.auth-modal')
    @include('partials.calc-modal')
    @include('partials.footer')

    <script src="{{ asset('assets/js/app.js') }}" defer></script>
    @stack('scripts')
</body>

</html>
