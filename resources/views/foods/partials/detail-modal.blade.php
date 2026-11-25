{{-- Modal popup detail makanan pada halaman katalog. --}}
<div class="modal-overlay" id="modalOverlay" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-img-wrapper">
            <span class="category-badge" id="modalBadge"></span>
            <button type="button" class="btn-close" id="btnCloseModal" aria-label="Tutup">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
            <img id="modalImg" src="" alt="" style="display: none;">
        </div>
        <div class="modal-content">
            <h2 class="modal-title" id="modalTitle"></h2>
            <div class="modal-price"><span id="modalPrice"></span> <span id="modalUnit"></span></div>

            <div class="modal-desc-box" id="modalDesc"></div>

            <p class="sim-note" id="modalSimNote">Estimasi gizi untuk simulasi · 1 porsi</p>

            <div class="portion-row">
                <span class="portion-label">Jumlah porsi</span>
                <div class="stepper" role="group" aria-label="Jumlah porsi">
                    <button type="button" class="stepper-btn" id="portionMinus" aria-label="Kurangi porsi">−</button>
                    <span class="stepper-val" id="portionValue" aria-live="polite">1</span>
                    <button type="button" class="stepper-btn" id="portionPlus" aria-label="Tambah porsi">+</button>
                </div>
            </div>

            <div class="macro-title">Kandungan Makronutrisi:</div>
            <div class="macro-grid">
                <div class="macro-box">
                    <div class="macro-label">Kalori</div>
                    <div class="macro-val" id="modalCal"></div>
                </div>
                <div class="macro-box">
                    <div class="macro-label">Protein</div>
                    <div class="macro-val highlight" id="modalProtein"></div>
                </div>
                <div class="macro-box">
                    <div class="macro-label">Karbo</div>
                    <div class="macro-val" id="modalCarbs"></div>
                </div>
                <div class="macro-box">
                    <div class="macro-label">Lemak</div>
                    <div class="macro-val" id="modalFat"></div>
                </div>
            </div>

            <div class="macro-title">Pilih waktu makan untuk menambahkan:</div>
            <form method="POST" action="{{ route('meal-planner.store-food') }}" class="slot-buttons" id="addFoodForm">
                @csrf
                <input type="hidden" name="food_id" id="addFoodId" value="">
                <input type="hidden" name="portions" id="addPortions" value="1">
                <button type="submit" name="slot" value="sarapan" class="btn-slot">Sarapan</button>
                <button type="submit" name="slot" value="makan_siang" class="btn-slot">Makan Siang</button>
                <button type="submit" name="slot" value="makan_malam" class="btn-slot">Makan Malam</button>
            </form>

            <p class="modal-links">
                <a href="#" id="modalShowLink">Detail lengkap</a> ·
                <a href="#" id="modalEditLink">Edit</a>
            </p>
        </div>
    </div>
</div>
