@extends('layouts.app')

@section('content')
    <section class="page">
        <div class="container">
            <div class="page-head" style="text-align: center;">
                <span class="page-badge">Error 404</span>
                <h1 class="page-title">Halaman Tidak Ditemukan</h1>
                <p class="page-subtitle">
                    Alamat yang kamu buka tidak tersedia atau sudah dipindahkan.
                    Periksa kembali tautannya, atau kembali ke halaman utama.
                </p>
            </div>

            <div class="btn-row" style="justify-content: center; gap: 10px; margin-bottom: 40px;">
                <a href="{{ route('home') }}" class="btn btn-primary">Kembali ke Beranda</a>
                @auth
                    <a href="{{ route('foods.index') }}" class="btn btn-secondary">Lihat Katalog Makanan</a>
                @endauth
            </div>
        </div>
    </section>
@endsection
