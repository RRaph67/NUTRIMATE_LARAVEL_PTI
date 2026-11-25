{{-- Modal popup Nutrition Calculator (BMR, TDEE, IMT). Tombol "Hitung Cepat"
     menghitung di sisi klien lewat window.NutriMate.calcNutrition(). --}}
<div class="modal" id="calcModal" aria-hidden="true">
    <div class="modal-card calc-modal-card" role="dialog" aria-modal="true" aria-label="Nutrition Calculator">
        <button type="button" class="modal-close" data-close-modal aria-label="Tutup">
            <svg viewBox="0 0 24 24">
                <path d="M6 6l12 12M18 6L6 18" />
            </svg>
        </button>

        <div class="modal-content">
            <h2 class="auth-title">Nutrition Calculator</h2>
            <p class="auth-sub">Hitung estimasi BMR &amp; kebutuhan kalori harianmu (TDEE).</p>

            <form id="calcForm" method="POST" action="{{ route('calculator.store') }}">
                @csrf
                <div class="calc-modal-row">
                    <label class="field">
                        <span>Jenis Kelamin</span>
                        <select id="calcGender" name="gender" required>
                            <option value="male">Laki-laki</option>
                            <option value="female">Perempuan</option>
                        </select>
                    </label>
                    <label class="field">
                        <span>Usia (tahun)</span>
                        <input type="number" id="calcAge" name="age" value="20" min="15" max="90"
                            step="1" required>
                    </label>
                </div>

                <div class="calc-modal-row">
                    <label class="field">
                        <span>Berat Badan (kg)</span>
                        <input type="number" id="calcWeight" name="weight" value="60" min="30" max="250"
                            step="0.1" required>
                    </label>
                    <label class="field">
                        <span>Tinggi Badan (cm)</span>
                        <input type="number" id="calcHeight" name="height" value="170" min="100"
                            max="230" step="0.1" required>
                    </label>
                </div>

                <label class="field">
                    <span>Aktivitas Fisik</span>
                    <select id="calcActivity" name="activity" required>
                        @foreach (\App\Support\NutritionCalculator::ACTIVITY_LEVELS as $factor => $label)
                            <option value="{{ $factor }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="calc-modal-actions">
                    <button type="button" class="btn-sm btn-outline"
                        onclick="window.NutriMate && window.NutriMate.calcNutrition();">
                        Hitung Cepat
                    </button>
                    @auth
                        <button type="submit" class="btn-auth">Hitung &amp; Simpan</button>
                    @else
                        <button type="button" class="btn-auth"
                            onclick="window.NutriMate && window.NutriMate.closeCalculator(); window.NutriMate && window.NutriMate.openAuthModal('login');">
                            Masuk untuk Simpan
                        </button>
                    @endauth
                </div>
            </form>

            <div class="calc-modal-results" id="calcResults" hidden>
                <div class="calc-modal-results-head">Hasil Analisis Gizi</div>
                <div class="calc-modal-tdee" id="calcTdee">2.150 kkal/hari</div>
                <div class="calc-modal-bmr">
                    BMR: <strong id="calcBmr">1.540 kkal</strong> · Kebutuhan harian untuk mempertahankan berat
                    badan stabil.
                </div>

                <div class="calc-macro">
                    <div class="calc-macro-box">
                        <div class="calc-macro-label">Protein (15%)</div>
                        <div class="calc-macro-val" id="calcProtein">80g</div>
                    </div>
                    <div class="calc-macro-box">
                        <div class="calc-macro-label">Karbo (55%)</div>
                        <div class="calc-macro-val" id="calcCarbs">295g</div>
                    </div>
                    <div class="calc-macro-box">
                        <div class="calc-macro-label">Lemak (30%)</div>
                        <div class="calc-macro-val" id="calcFat">71g</div>
                    </div>
                </div>

                <p class="calc-modal-bmi">
                    IMT: <strong id="calcBmi">21,8</strong>
                    <span id="calcBmiCategory"></span>
                </p>

                @auth
                    <a href="{{ route('calculator.index') }}" class="calc-modal-link">
                        Lihat detail &amp; riwayat perhitungan
                    </a>
                @else
                    <a href="#" class="calc-modal-link"
                        onclick="event.preventDefault(); window.NutriMate && window.NutriMate.closeCalculator(); window.NutriMate && window.NutriMate.openAuthModal('login');">
                        Masuk untuk menyimpan hasil &amp; riwayat
                    </a>
                @endauth
            </div>
        </div>
    </div>
</div>
