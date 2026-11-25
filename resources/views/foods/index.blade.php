@extends('layouts.app')

@section('content')
    <div class="page">
        <div class="container">
            <div class="page-head">
                <span class="page-badge">Katalog Nutrisi</span>
                <h1 class="page-title">Database Makanan &amp; Nutrisi</h1>
                <p class="page-subtitle">Katalog makanan bernutrisi ramah kantong mahasiswa dan anak kost.</p>
            </div>

            {{-- Pencarian, kategori, dan pengurutan --}}
            <form class="search-filter-box" action="{{ route('foods.index') }}" method="GET" id="foodFilterForm">
                <div class="search-row">
                    <div class="search-input-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="8" />
                            <path d="M21 21l-4.35-4.35" />
                        </svg>
                        <input type="text" id="searchInput" name="q" value="{{ $filters['q'] }}"
                            placeholder="Cari makanan..." aria-label="Cari makanan" autocomplete="off">
                    </div>
                    <select class="sort-select" id="sortSelect" name="sort" aria-label="Urutkan makanan">
                        @foreach ($sorts as $value => $option)
                            <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <input type="hidden" name="category" id="categoryInput" value="{{ $filters['category'] }}">
                <div class="category-pills" id="categoryPills">
                    <button type="button" class="pill {{ $filters['category'] === '' ? 'active' : '' }}"
                        data-category="">Semua</button>
                    @foreach ($categories as $category)
                        <button type="button" class="pill {{ $filters['category'] === $category ? 'active' : '' }}"
                            data-category="{{ $category }}">{{ $category }}</button>
                    @endforeach
                </div>
            </form>

            <div class="btn-row" style="margin-bottom: 22px; justify-content: flex-end;">
                <a href="{{ route('foods.create') }}" class="btn btn-primary">+ Tambah Menu Makanan</a>
            </div>

            @if ($foods === [])
                <div class="grid-status">
                    <span class="status-title">Makanan tidak ditemukan</span>
                    <span>Coba kata kunci atau filter kategori lain.</span>
                    <br>
                    <a href="{{ route('foods.index') }}" class="btn-retry"
                        style="display: inline-block; text-decoration: none;">Tampilkan Semua</a>
                </div>
            @else
                <div class="food-grid" id="foodGrid" aria-live="polite">
                    @foreach ($foods as $food)
                        <article class="food-card">
                            <div class="card-img-wrapper">
                                <span class="category-badge">{{ $food['category'] }}</span>
                                @if (! empty($food['image_url']))
                                    <img src="{{ $food['image_url'] }}" alt="{{ $food['name'] }}" loading="lazy"
                                        onerror="this.style.display='none'">
                                @else
                                    <div class="card-img-fallback" aria-hidden="true">🍽️</div>
                                @endif
                            </div>
                            <div class="card-body">
                                <h3 class="card-title">{{ $food['name'] }}</h3>
                                <div class="card-meta">
                                    <div class="card-price">
                                        {{ rupiah($food['price']) }}
                                        <span>/ {{ $food['portion_description'] }}</span>
                                    </div>
                                    <div class="card-calories">{{ fmt_num($food['calories'], 0) }} kcal</div>
                                </div>
                                <button type="button" class="btn-detail" data-food-name="{{ $food['name'] }}"
                                    data-food-category="{{ $food['category'] }}"
                                    data-food-price="{{ rupiah($food['price']) }}"
                                    data-food-unit="/ {{ $food['portion_description'] }}"
                                    data-food-desc="{{ $food['description'] ?: 'Tidak ada deskripsi rinci.' }}"
                                    data-food-cal="{{ fmt_num($food['calories'], 0) }} kcal"
                                    data-food-protein="{{ fmt_num($food['protein'], 0) }}g"
                                    data-food-carbs="{{ fmt_num($food['carbs'], 0) }}g"
                                    data-food-fat="{{ fmt_num($food['fat'], 0) }}g"
                                    data-food-image="{{ $food['image_url'] ?? '' }}"
                                    data-food-id="{{ $food['id'] }}" data-food-cal-raw="{{ (float) $food['calories'] }}"
                                    data-food-protein-raw="{{ (float) $food['protein'] }}"
                                    data-food-carbs-raw="{{ (float) $food['carbs'] }}"
                                    data-food-fat-raw="{{ (float) $food['fat'] }}"
                                    data-food-edit="{{ route('foods.edit', $food['id']) }}"
                                    data-food-show="{{ route('foods.show', $food['id']) }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 16v-4M12 8h.01" />
                                    </svg>
                                    Detail &amp; Gizi
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @include('foods.partials.detail-modal')
@endsection
