@extends('layouts.app')

@php
    $gender = $form['gender'];
    $age = (int) $form['age'];
    $weight = (float) $form['weight'];
    $height = (float) $form['height'];
    $activity = (string) $form['activity'];

    $iconBolt = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>';
    $iconTrash = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>';
@endphp

@section('content')
    <section class="page calc-page">
        <div class="calc-inner">
            <div class="page-head">
                <span class="page-badge">Nutrition Calculator</span>
                <h1 class="page-title">Hitung Kebutuhan Gizimu</h1>
                <p class="page-subtitle">
                    Estimasi BMR, TDEE, IMT, dan pembagian makronutrisi harian memakai rumus Mifflin-St Jeor.
                    Hasil perhitungan otomatis tersimpan sebagai riwayat dan menyetel target kalori profilmu.
                </p>
            </div>

            <div class="calc-grid">
                {{-- Form input --}}
                <section class="calc-card">
                    <h2 class="calc-card-title">Data Tubuh Kamu</h2>

                    <form method="POST" action="{{ route('calculator.store') }}" novalidate>
                        @csrf

                        <label class="field">
                            <span>Jenis Kelamin</span>
                            <select name="gender" required>
                                <option value="male" @selected($gender === 'male')>Laki-laki</option>
                                <option value="female" @selected($gender === 'female')>Perempuan</option>
                            </select>
                        </label>

                        <div class="calc-row">
                            <label class="field">
                                <span>Usia (tahun)</span>
                                <input type="number" name="age" value="{{ $age }}" min="15" max="90"
                                    step="1" required>
                            </label>

                            <label class="field">
                                <span>Tinggi Badan (cm)</span>
                                <input type="number" name="height" value="{{ fmt_num($height) }}" min="100"
                                    max="230" step="0.1" required>
                            </label>
                        </div>

                        <label class="field">
                            <span>Berat Badan (kg)</span>
                            <input type="number" name="weight" value="{{ fmt_num($weight) }}" min="30"
                                max="250" step="0.1" required>
                        </label>

                        <label class="field">
                            <span>Aktivitas Fisik</span>
                            <select name="activity" required>
                                @foreach ($activities as $factor => $label)
                                    <option value="{{ $factor }}" @selected((string) $factor === $activity)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <button type="submit" class="btn-auth calc-submit">
                            <span class="calc-submit-icon">{!! $iconBolt !!}</span>
                            Hitung &amp; Simpan
                        </button>
                    </form>

                    <p class="calc-note">
                        Rumus Mifflin-St Jeor: <strong>10×BB + 6,25×TB − 5×Usia</strong>
                        (+5 pria / −161 wanita), lalu dikali faktor aktivitas untuk mendapat TDEE.
                    </p>
                </section>

                {{-- Hasil perhitungan --}}
                <section class="calc-card calc-result-card">
                    @if ($result)
                        <div class="calc-result-head">
                            <span class="calc-pill calc-pill-green">Hasil Analisis Gizi</span>
                            <span class="calc-pill calc-pill-sand">
                                {{ \App\Support\NutritionCalculator::genderLabel($result['gender']) }} ·
                                {{ (int) $result['age'] }} th
                            </span>
                        </div>

                        <div class="calc-big">
                            <div class="calc-big-value">{{ fmt_num($result['tdee'], 0) }}</div>
                            <div class="calc-big-unit">kkal / hari (TDEE)</div>
                        </div>

                        <p class="calc-big-sub">
                            BMR <strong>{{ fmt_num($result['bmr'], 0) }} kkal</strong> ·
                            {{ $result['activity_label'] }} ·
                            IMT <strong>{{ fmt_num($result['bmi'], 2) }}</strong>
                            ({{ $result['bmi_category'] ?? \App\Support\NutritionCalculator::bmiCategory((float) $result['bmi']) }})
                        </p>

                        <div class="calc-macro">
                            <div class="calc-macro-box">
                                <div class="calc-macro-label">Protein (15%)</div>
                                <div class="calc-macro-val">{{ fmt_num($result['protein'], 0) }}g</div>
                            </div>
                            <div class="calc-macro-box">
                                <div class="calc-macro-label">Karbo (55%)</div>
                                <div class="calc-macro-val">{{ fmt_num($result['carbs'], 0) }}g</div>
                            </div>
                            <div class="calc-macro-box">
                                <div class="calc-macro-label">Lemak (30%)</div>
                                <div class="calc-macro-val">{{ fmt_num($result['fat'], 0) }}g</div>
                            </div>
                        </div>

                        <div class="calc-actions">
                            <a href="{{ route('calculator.index') }}" class="btn-sm btn-fill">Hitung Ulang</a>
                            <button type="button" class="btn-sm btn-outline"
                                onclick="window.NutriMate && window.NutriMate.openCalculator();">
                                Kalkulator Popup
                            </button>
                        </div>
                    @else
                        <div class="calc-empty">
                            <div class="calc-empty-icon">{!! $iconBolt !!}</div>
                            <h2 class="calc-card-title">Belum Ada Hasil</h2>
                            <p class="calc-empty-text">
                                Isi data tubuhmu di form sebelah, lalu tekan
                                <strong>Hitung &amp; Simpan</strong> untuk melihat estimasi
                                kebutuhan kalori dan makronutrisi harian.
                            </p>
                            <p class="calc-empty-text">
                                Perhitungan hanya berupa estimasi, bukan pengganti saran ahli gizi.
                            </p>
                        </div>
                    @endif
                </section>
            </div>

            {{-- Penyusun menu --}}
            <section class="calc-builder" id="susun-menu">
                <div class="calc-builder-head">
                    <div>
                        <h2 class="calc-card-title">Susun Menu &amp; Hitung Gizinya</h2>
                        <p class="calc-builder-sub">
                            Pilih bahan makanan beserta porsinya (1–{{ $maxPortions }} porsi). Total kalori dan
                            makronutrisi dihitung otomatis, lalu bisa langsung disimpan ke Meal Planner.
                        </p>
                    </div>
                    @if ($items !== [])
                        <form method="POST" action="{{ route('calculator.clear-items') }}"
                            onsubmit="return confirm('Kosongkan seluruh susunan menu?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="calc-clear">Kosongkan Menu</button>
                        </form>
                    @endif
                </div>

                <form method="POST" action="{{ route('calculator.add-item') }}" class="calc-picker">
                    @csrf
                    <label class="field calc-picker-food">
                        <span>Bahan Makanan</span>
                        <select name="food_id" required>
                            <option value="">— Pilih bahan makanan —</option>
                            @foreach ($foods as $food)
                                <option value="{{ $food['id'] }}">
                                    {{ $food['name'] }}
                                    · {{ fmt_num($food['calories'], 0) }} kkal
                                    · {{ rupiah($food['price']) }}
                                    ({{ $food['portion_description'] }})
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="field calc-picker-portion">
                        <span>Porsi</span>
                        <select name="portions">
                            @for ($p = 1; $p <= $maxPortions; $p++)
                                <option value="{{ $p }}">{{ $p }} porsi</option>
                            @endfor
                        </select>
                    </label>

                    <button type="submit" class="btn-auth calc-picker-submit">Tambahkan</button>
                </form>

                @if ($items === [])
                    <p class="calc-empty-text calc-empty-text--dark">
                        <strong>Belum ada bahan makanan dipilih.</strong>
                        Tambahkan dari daftar di atas untuk mulai menghitung gizi menu.
                    </p>
                @else
                    <div class="calc-table-wrap">
                        <table class="calc-table">
                            <thead>
                                <tr>
                                    <th>Bahan Makanan</th>
                                    <th>Porsi</th>
                                    <th>Kalori</th>
                                    <th>Protein</th>
                                    <th>Karbo</th>
                                    <th>Lemak</th>
                                    <th>Harga</th>
                                    <th aria-label="Aksi"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($items as $item)
                                    @php $food = $item['food']; @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $food['name'] }}</strong>
                                            <span class="calc-item-cat">{{ $food['category'] }}</span>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('calculator.add-item') }}"
                                                class="calc-portion-form">
                                                @csrf
                                                <input type="hidden" name="food_id"
                                                    value="{{ $food['id'] }}">
                                                <select name="portions" onchange="this.form.submit()"
                                                    aria-label="Ubah porsi {{ $food['name'] }}">
                                                    @for ($p = 1; $p <= $maxPortions; $p++)
                                                        <option value="{{ $p }}" @selected($p === $item['portions'])>
                                                            {{ $p }} porsi</option>
                                                    @endfor
                                                </select>
                                            </form>
                                        </td>
                                        <td>{{ fmt_num($item['calories'], 0) }} kkal</td>
                                        <td>{{ fmt_num($item['protein'], 1) }} g</td>
                                        <td>{{ fmt_num($item['carbs'], 1) }} g</td>
                                        <td>{{ fmt_num($item['fat'], 1) }} g</td>
                                        <td>{{ rupiah($item['price']) }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('calculator.remove-item') }}"
                                                onsubmit="return confirm('Hapus {{ $food['name'] }} dari menu?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="food_id"
                                                    value="{{ $food['id'] }}">
                                                <button type="submit" class="calc-del"
                                                    aria-label="Hapus bahan makanan">
                                                    {!! $iconTrash !!}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2"><strong>Total Menu</strong></td>
                                    <td><strong>{{ fmt_num($totals['calories'], 0) }} kkal</strong></td>
                                    <td><strong>{{ fmt_num($totals['protein'], 1) }} g</strong></td>
                                    <td><strong>{{ fmt_num($totals['carbs'], 1) }} g</strong></td>
                                    <td><strong>{{ fmt_num($totals['fat'], 1) }} g</strong></td>
                                    <td><strong>{{ rupiah($totals['price']) }}</strong></td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Perbandingan total menu dengan target kalori --}}
                    @if ($target > 0)
                        @php
                            $percent = ($totals['calories'] / $target) * 100;
                            $remaining = $target - $totals['calories'];
                            $percentClamped = max(0, min(100, $percent));
                        @endphp
                        <div class="calc-compare">
                            <div class="calc-compare-row">
                                <span>Target kalori harian (TDEE)</span>
                                <strong>{{ fmt_num($target, 0) }} kkal</strong>
                            </div>
                            <div class="calc-compare-row">
                                <span>Total menu terpilih</span>
                                <strong>{{ fmt_num($totals['calories'], 0) }} kkal
                                    ({{ fmt_num($percent, 0) }}%)</strong>
                            </div>
                            <div class="calc-compare-bar" role="img"
                                aria-label="Menu terpilih {{ fmt_num($percent, 0) }} persen dari target">
                                <span style="width: {{ fmt_num($percentClamped, 1) }}%"></span>
                            </div>
                            <div class="calc-compare-row calc-compare-foot">
                                <span>{{ $remaining >= 0 ? 'Sisa kebutuhan kalori' : 'Melebihi target' }}</span>
                                <strong class="{{ $remaining >= 0 ? '' : 'is-over' }}">
                                    {{ fmt_num(abs($remaining), 0) }} kkal
                                </strong>
                            </div>
                        </div>
                    @else
                        <p class="calc-empty-text calc-empty-text--dark">
                            Hitung dulu data tubuhmu di atas agar total menu bisa dibandingkan
                            dengan target kalori harian.
                        </p>
                    @endif

                    {{-- Simpan ke Meal Planner --}}
                    <form method="POST" action="{{ route('calculator.save-to-meal-plan') }}" class="calc-save-form">
                        @csrf
                        <label class="field calc-save-slot">
                            <span>Simpan ke Meal Planner sebagai</span>
                            <select name="slot" required>
                                @foreach ($slots as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="submit" class="btn-auth calc-save-submit">
                            Simpan {{ count($items) }} Item ke Meal Planner
                        </button>
                    </form>
                @endif
            </section>

            {{-- Riwayat perhitungan --}}
            <section class="calc-history">
                <div class="calc-history-head">
                    <h2 class="calc-card-title">Riwayat Perhitungan</h2>

                    @if ($history !== [])
                        <form method="POST" action="{{ route('calculator.clear') }}"
                            onsubmit="return confirm('Hapus seluruh riwayat perhitungan?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="calc-clear">Bersihkan Semua</button>
                        </form>
                    @endif
                </div>

                @if ($history === [])
                    <p class="calc-empty-text calc-empty-text--dark">
                        <strong>Belum ada riwayat.</strong> Setiap perhitungan yang kamu simpan akan muncul di sini.
                    </p>
                @else
                    <div class="calc-table-wrap">
                        <table class="calc-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Data</th>
                                    <th>BMR</th>
                                    <th>TDEE</th>
                                    <th>Makro (P/K/L)</th>
                                    <th>IMT</th>
                                    <th aria-label="Aksi"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($history as $row)
                                    <tr>
                                        <td>{{ \Illuminate\Support\Carbon::parse($row['created_at'])->translatedFormat('d M Y · H:i') }}
                                        </td>
                                        <td>
                                            {{ \App\Support\NutritionCalculator::genderLabel($row['gender']) }},
                                            {{ (int) $row['age'] }} th,
                                            {{ fmt_num($row['weight']) }} kg,
                                            {{ fmt_num($row['height']) }} cm
                                        </td>
                                        <td>{{ fmt_num($row['bmr'], 0) }} kkal</td>
                                        <td><strong>{{ fmt_num($row['tdee'], 0) }} kkal</strong></td>
                                        <td>
                                            {{ fmt_num($row['protein'], 0) }}g /
                                            {{ fmt_num($row['carbs'], 0) }}g /
                                            {{ fmt_num($row['fat'], 0) }}g
                                        </td>
                                        <td>{{ fmt_num($row['bmi'], 2) }}</td>
                                        <td>
                                            <form method="POST"
                                                action="{{ route('calculator.destroy', $row['id']) }}"
                                                onsubmit="return confirm('Hapus riwayat ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="calc-del"
                                                    aria-label="Hapus riwayat">
                                                    {!! $iconTrash !!}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <p class="planner-sub" style="margin-top: 24px; text-align: center;">
                Ingin menghitung cepat tanpa pindah halaman?
                <a href="#calculator"
                    onclick="event.preventDefault(); window.NutriMate && window.NutriMate.openCalculator();">Buka
                    kalkulator popup</a>.
            </p>
        </div>
    </section>
@endsection