@php
    /** @var array|null $food */
@endphp

<div class="form-grid two-col">
    <div class="field span-2">
        <label for="name">
            Nama makanan <span class="required" aria-hidden="true">*</span>
        </label>
        <input id="name" name="name" type="text"
            value="{{ old('name', $food['name'] ?? '') }}"
            placeholder="Contoh: Nasi Goreng Spesial" required minlength="2" maxlength="150">
        @error('name')
            <span class="hint" style="color:#a81c00">{{ $message }}</span>
        @enderror
    </div>

    <div class="field">
        <label for="category">
            Kategori <span class="required" aria-hidden="true">*</span>
        </label>
        <select id="category" name="category" required>
            <option value="">Pilih kategori…</option>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected(old('category', $food['category'] ?? '') === $category)>
                    {{ $category }}
                </option>
            @endforeach
        </select>
        @error('category')
            <span class="hint" style="color:#a81c00">{{ $message }}</span>
        @enderror
    </div>

    <div class="field">
        <label for="price">
            Harga (Rp) <span class="required" aria-hidden="true">*</span>
        </label>
        <input id="price" name="price" type="number" inputmode="decimal"
            value="{{ old('price', $food['price'] ?? '') }}" placeholder="Contoh: 25000" required
            min="0" step="0.01">
        @error('price')
            <span class="hint" style="color:#a81c00">{{ $message }}</span>
        @enderror
    </div>

    <div class="field span-2">
        <label for="portion_description">
            Deskripsi porsi <span class="required" aria-hidden="true">*</span>
        </label>
        <input id="portion_description" name="portion_description" type="text"
            value="{{ old('portion_description', $food['portion_description'] ?? '') }}"
            placeholder="Contoh: 1 piring (250 gram)" required minlength="2" maxlength="100">
        @error('portion_description')
            <span class="hint" style="color:#a81c00">{{ $message }}</span>
        @enderror
    </div>

    <div class="field span-2">
        <label for="description">Deskripsi</label>
        <textarea id="description" name="description" maxlength="2000"
            placeholder="Ceritakan rasa, bahan utama, atau cara penyajian…">{{ old('description', $food['description'] ?? '') }}</textarea>
        <span class="hint">Opsional, maksimal 2000 karakter.</span>
    </div>

    @foreach ([['calories', 'Kalori (kkal)', 'Contoh: 350'], ['protein', 'Protein (gram)', 'Contoh: 20'], ['carbs', 'Karbohidrat (gram)', 'Contoh: 40'], ['fat', 'Lemak (gram)', 'Contoh: 12']] as [$field, $label, $placeholder])
        <div class="field">
            <label for="{{ $field }}">
                {{ $label }} <span class="required" aria-hidden="true">*</span>
            </label>
            <input id="{{ $field }}" name="{{ $field }}" type="number" inputmode="decimal"
                value="{{ old($field, $food[$field] ?? '') }}" placeholder="{{ $placeholder }}" required
                min="0" step="0.01">
            @error($field)
                <span class="hint" style="color:#a81c00">{{ $message }}</span>
            @enderror
        </div>
    @endforeach

    <div class="field span-2">
        <label for="image_url">URL gambar <span class="optional">(opsional)</span></label>
        <input id="image_url" name="image_url" type="url"
            value="{{ old('image_url', $food['image_url'] ?? '') }}"
            placeholder="https://contoh.com/gambar-makanan.jpg" maxlength="255" inputmode="url"
            autocomplete="off" pattern="https?://.+" aria-describedby="image_url_hint">
        <span class="hint" id="image_url_hint">
            Tempel link gambar dari internet. Harus diawali <code>http://</code> atau <code>https://</code>.
            Kosongkan bila tidak ada gambar.
        </span>
        @error('image_url')
            <span class="hint" style="color:#a81c00">{{ $message }}</span>
        @enderror
        <div class="img-preview" id="imgPreview" hidden>
            <img id="imgPreviewEl" src="" alt="Pratinjau gambar" loading="lazy">
            <span class="img-preview-status" id="imgPreviewStatus"></span>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            var input = document.getElementById('image_url');
            var box = document.getElementById('imgPreview');
            var img = document.getElementById('imgPreviewEl');
            var status = document.getElementById('imgPreviewStatus');
            if (!input || !box || !img) return;

            var timer = null;

            function reset() {
                box.hidden = true;
                img.removeAttribute('src');
                status.textContent = '';
                status.className = 'img-preview-status';
            }

            function preview() {
                var url = input.value.trim();
                if (url === '' || !/^https?:\/\/.+/i.test(url)) {
                    reset();
                    return;
                }

                box.hidden = false;
                status.textContent = 'Memuat gambar...';
                status.className = 'img-preview-status';

                img.onload = function() {
                    status.textContent = 'Gambar berhasil dimuat.';
                    status.className = 'img-preview-status is-ok';
                };
                img.onerror = function() {
                    status.textContent = 'Gambar tidak bisa dimuat. Periksa kembali link-nya.';
                    status.className = 'img-preview-status is-error';
                };
                img.src = url;
            }

            input.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(preview, 600);
            });

            preview();
        })();
    </script>
@endpush
