@extends('layouts.app')

@section('content')
    <section class="page rc-page" id="main">
        <div class="container rc-container">
            <div class="page-head">
                <span class="page-badge">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                        <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
                    </svg>
                    Smart Budget Planner
                </span>
                <h1 class="page-title">Rekomendasi Cerdas Sesuai Dompet</h1>
                <p class="page-subtitle">Optimalkan asupan gizi harian dari warteg, masakan simpel kost, dan bahan
                    lokal tanpa melebihi batas uang saku Anda.</p>
            </div>

            <section class="card planner" aria-label="Form rekomendasi">
                <form id="planner-form" novalidate>
                    <label class="field-label" for="budget">Batas Budget Harian</label>
                    <div class="budget-field" id="budget-field">
                        <span class="budget-prefix" aria-hidden="true">Rp</span>
                        <input id="budget" name="budget" type="text" inputmode="numeric" autocomplete="off"
                            value="{{ $defaultBudget }}" aria-describedby="budget-hint budget-error" maxlength="9">
                    </div>
                    <p class="field-hint" id="budget-hint">
                        Estimasi maksimal pengeluaran untuk 3 kali makan sehari
                        @if ($hasProfile && $defaultTarget > 0)
                            · target gizi <strong>{{ number_format($defaultTarget, 0, ',', '.') }} kkal</strong>
                            dari profil
                        @endif
                    </p>
                    <p class="field-error" id="budget-error" role="alert" hidden></p>

                    <fieldset class="focus">
                        <legend class="focus-legend">FOKUS PREFERENSI MENU</legend>
                        <div class="chips">
                            <button class="chip is-selected" type="button" data-focus="hemat"
                                aria-pressed="true">Menu Hemat</button>
                            <button class="chip" type="button" data-focus="protein" aria-pressed="false">Tinggi
                                Protein</button>
                            <button class="chip" type="button" data-focus="sayur" aria-pressed="false">Ada
                                Sayur</button>
                            <button class="chip" type="button" data-focus="buah" aria-pressed="false">Ada Buah</button>
                            <button class="chip" type="button" data-focus="seimbang" aria-pressed="false">Menu
                                Seimbang</button>
                        </div>
                    </fieldset>

                    <button class="btn-primary btn-generate" id="generate" type="submit">
                        <span class="btn-icon">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor"
                                stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
                            </svg>
                        </span>
                        <span class="btn-spinner" aria-hidden="true"></span>
                        <span class="btn-label">Generate Rekomendasi</span>
                    </button>
                </form>
            </section>

            <div class="notice" id="notice" role="status" aria-live="polite" hidden></div>

            <section class="results" id="results" aria-live="polite" hidden>
                <div class="results-head">
                    <h2 class="results-title" id="results-title" tabindex="-1">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor"
                            stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                            <polyline points="22 4 12 14.01 9 11.01" />
                        </svg>
                        <span id="results-title-text">1 Paket Rekomendasi Siap Pakai</span>
                    </h2>
                    <span class="pill-limit" id="results-limit">Batas Budget: Rp 30.000</span>
                </div>
                <div class="package-list" id="package-list"></div>
            </section>
        </div>
    </section>

    {{-- Template kartu paket --}}
    <template id="tpl-package">
        <article class="card package">
            <div class="package-top">
                <span class="pill pill-muted" data-ref="budget"></span>
                <span class="pill pill-green" data-ref="saving"></span>
            </div>
            <div class="package-hero">
                <img data-ref="image" alt="" width="800" height="244" decoding="async">
                <h3 class="package-name" data-ref="name"></h3>
            </div>
            <div class="price-row">
                <p class="price">
                    <span class="price-now" data-ref="total"></span>
                    <span class="price-old" data-ref="old"></span>
                </p>
                <span class="pill pill-sand" data-ref="remain"></span>
            </div>
            <p class="package-desc" data-ref="desc"></p>
            <ul class="meals" data-ref="meals"></ul>
            <div class="stats">
                <div class="stat"><span class="stat-label">Kalori</span><strong class="stat-value"
                        data-ref="kcal"></strong></div>
                <div class="stat"><span class="stat-label">Protein</span><strong class="stat-value"
                        data-ref="protein"></strong></div>
                <div class="stat"><span class="stat-label">Karbo</span><strong class="stat-value"
                        data-ref="carbs"></strong></div>
                <div class="stat"><span class="stat-label">Lemak</span><strong class="stat-value"
                        data-ref="fat"></strong></div>
            </div>
            <form method="POST" action="{{ route('recommendation.apply') }}" class="package-apply">
                @csrf
                <input type="hidden" name="payload" data-ref="payload" value="">
                <button class="btn-primary btn-use" type="submit" data-ref="use">
                    <span class="btn-label">Gunakan Rekomendasi Ini</span>
                    <svg viewBox="729.84 1463.25 15.5 12.5" width="15.5" height="12.5"
                        xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                        <path
                            d="M739.085 1464.25L744.335 1469.5M744.335 1469.5L739.085 1474.75M744.335 1469.5H730.835"
                            fill="none" stroke="white" stroke-width="1.875" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
            </form>
        </article>
    </template>

    <template id="tpl-meal">
        <li class="meal">
            <span class="meal-icon" data-ref="icon" aria-hidden="true"></span>
            <div class="meal-text">
                <span class="meal-label" data-ref="label"></span>
                <span class="meal-name" data-ref="name"></span>
            </div>
            <span class="meal-price" data-ref="price"></span>
        </li>
    </template>
@endsection

@push('scripts')
    <script>
        window.NUTRIMATE_CONFIG = {
            apiUrl: @json(route('recommendation.api')),
            imageUrl: @json(asset('assets/img/hero.jpg')),
            minBudget: 10000,
            maxBudget: 1000000,
            packageCount: 1,
            targetCalories: {{ $defaultTarget }},
            showSampleOnLoad: false
        };
    </script>
    <script src="{{ asset('assets/js/recommendation.js') }}" defer></script>
@endpush