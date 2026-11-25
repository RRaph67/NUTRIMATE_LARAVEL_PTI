@extends('layouts.app')

@php
    $name = $user?->displayName() ?? '';
    $email = (string) ($user?->email ?? '');
    $initial = initials_of($name);

    $calorieTarget = (int) ($profile['calorie_target'] ?? 2000);
    $dailyBudget = (float) ($profile['daily_budget'] ?? 30000);

    $eyeSvg = '<svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/><path class="slash" d="M4 4l16 16"/></svg>';
    $lockSvg = '<svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>';
@endphp

@section('content')
    <div class="pf-container">
        {{-- 1. Kartu identitas pengguna --}}
        <section class="pf-card pf-user-card" aria-label="Informasi Pengguna">
            <div class="pf-avatar">{{ $initial }}</div>
            <div class="pf-user-info">
                <span class="pf-name">{{ $name }}</span>
                <span class="pf-email">{{ $email }}</span>
            </div>
        </section>

        <div class="pf-form-grid">
            {{-- 2. Data diri --}}
            <section class="pf-card" id="data-diri">
                <div class="pf-card-head">
                    <h2>Data Diri</h2>
                    <p>Kamu bisa mengubah nama, sedangkan email terkunci.</p>
                </div>

                <form method="POST" action="{{ route('profile.update') }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="pf-fields">
                        <label class="field" for="full_name">
                            <span>Nama Lengkap</span>
                            <input type="text" name="full_name" id="full_name"
                                value="{{ old('full_name', $name) }}" maxlength="60" autocomplete="name" required>
                            @error('full_name')
                                <small class="hint" style="color:#a81c00">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="field" for="email_readonly">
                            <span>Email</span>
                            <div class="lock-wrap">
                                <input type="email" id="email_readonly" value="{{ $email }}"
                                    placeholder="Email belum tersedia" readonly aria-readonly="true" tabindex="-1">
                                <i class="lock-ic" aria-hidden="true">{!! $lockSvg !!}</i>
                            </div>
                            <small class="hint">Email tidak dapat diubah.</small>
                        </label>

                        <label class="field" for="calorie_target">
                            <span>Target Kalori Harian (kkal)</span>
                            <input type="number" name="calorie_target" id="calorie_target"
                                value="{{ (int) old('calorie_target', $calorieTarget) }}" min="500" max="6000"
                                step="50">
                            <small class="hint">Dipakai sebagai target gizi pada fitur Rekomendasi.</small>
                            @error('calorie_target')
                                <small class="hint" style="color:#a81c00">{{ $message }}</small>
                            @enderror
                        </label>

                        <label class="field" for="daily_budget">
                            <span>Budget Makan Harian (Rp)</span>
                            <input type="number" name="daily_budget" id="daily_budget"
                                value="{{ (int) old('daily_budget', $dailyBudget) }}" min="0" max="10000000"
                                step="500">
                            <small class="hint">Menjadi nilai awal budget di halaman Rekomendasi.</small>
                            @error('daily_budget')
                                <small class="hint" style="color:#a81c00">{{ $message }}</small>
                            @enderror
                        </label>
                    </div>

                    <div class="pf-card-footer">
                        <button type="submit" class="btn-auth btn-auth--full">Simpan Perubahan</button>
                    </div>
                </form>
            </section>

            {{-- 3. Keamanan --}}
            <section class="pf-card" id="keamanan">
                <div class="pf-card-head">
                    <h2>Keamanan</h2>
                    <p>Ganti password untuk menjaga akunmu tetap aman.</p>
                </div>

                <form method="POST" action="{{ route('profile.password') }}" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="pf-fields">
                        @foreach ([
        ['current_password', 'Password Lama', '••••••••', 'current-password'],
        ['password', 'Password Baru', 'Minimal 8 karakter', 'new-password'],
        ['password_confirmation', 'Konfirmasi Password Baru', 'Ulangi password baru', 'new-password'],
    ] as [$field, $label, $placeholder, $autocomplete])
                            <label class="field" for="{{ $field }}">
                                <span>{{ $label }}</span>
                                <div class="pw-wrap">
                                    <input type="password" name="{{ $field }}" id="{{ $field }}"
                                        placeholder="{{ $placeholder }}" autocomplete="{{ $autocomplete }}"
                                        minlength="8" required>
                                    <button type="button" class="pw-toggle" aria-label="Tampilkan password">
                                        {!! $eyeSvg !!}
                                    </button>
                                </div>
                                @error($field)
                                    <small class="hint" style="color:#a81c00">{{ $message }}</small>
                                @enderror
                            </label>
                        @endforeach
                    </div>

                    <div class="pf-card-footer">
                        <button type="submit" class="btn-auth btn-auth--full">Ganti Password</button>
                    </div>
                </form>
            </section>
        </div>

        {{-- Tombol keluar --}}
        <form action="{{ route('logout') }}" method="POST" class="pf-logout-container">
            @csrf
            <button type="submit" class="btn-logout-full">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M9 4H5v16h4M16 8l4 4-4 4M20 12H9" />
                </svg>
                Keluar
            </button>
        </form>
    </div>
@endsection