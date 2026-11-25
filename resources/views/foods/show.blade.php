@extends('layouts.app')

@section('content')
    <div class="page-header"
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1>{{ $food['name'] }}</h1>
            <p>Detail lengkap makanan, termasuk harga, porsi, dan nilai gizi.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('foods.index') }}">← Kembali ke katalog</a>
    </div>

    <div class="show-layout"
        style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 32px; background: #fff; padding: 28px; border-radius: 18px; border: 1px solid var(--border, #ece5d8);">
        <div class="detail-media">
            @if (! empty($food['image_url']))
                <img src="{{ $food['image_url'] }}" alt="Gambar {{ $food['name'] }}"
                    style="width: 100%; border-radius: 14px; object-fit: cover; max-height: 380px;"
                    onerror="this.style.display='none'">
            @else
                <div class="placeholder" aria-hidden="true"
                    style="background: var(--section, #f8f5ee); height: 260px; display: flex; align-items: center; justify-content: center; font-size: 54px; border-radius: 14px;">
                    🍴</div>
            @endif
        </div>

        <div>
            <div class="detail-title" style="margin-bottom: 20px;">
                <span class="badge">{{ $food['category'] }}</span>
                <p class="food-price"
                    style="font-size: 26px; font-weight: 700; color: var(--green-dark, #5f7710); margin-top: 8px;">
                    {{ rupiah($food['price']) }}
                </p>
            </div>

            <section class="detail-section" style="margin-bottom: 24px;">
                <h2
                    style="font-size: 18px; margin-bottom: 12px; border-bottom: 2px solid var(--section, #f8f5ee); padding-bottom: 6px;">
                    Informasi umum</h2>
                <dl class="definition-list" style="display: grid; grid-template-columns: 1fr 2fr; gap: 8px 12px;">
                    <dt style="color: var(--muted, #55554f);">Nama</dt>
                    <dd><strong>{{ $food['name'] }}</strong></dd>

                    <dt style="color: var(--muted, #55554f);">Kategori</dt>
                    <dd>{{ $food['category'] }}</dd>

                    <dt style="color: var(--muted, #55554f);">Harga</dt>
                    <dd>{{ rupiah($food['price']) }}</dd>

                    <dt style="color: var(--muted, #55554f);">Porsi</dt>
                    <dd>{{ $food['portion_description'] }}</dd>

                    <dt style="color: var(--muted, #55554f);">Deskripsi</dt>
                    <dd>{{ $food['description'] ?: '—' }}</dd>
                </dl>
            </section>

            <section class="detail-section" style="margin-bottom: 24px;">
                <h2
                    style="font-size: 18px; margin-bottom: 12px; border-bottom: 2px solid var(--section, #f8f5ee); padding-bottom: 6px;">
                    Nilai gizi per porsi</h2>
                <div class="macro-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                    @foreach ([['value' => fmt_num($food['calories'], 0), 'label' => 'kkal kalori'], ['value' => fmt_num($food['protein'], 0), 'label' => 'gram protein', 'highlight' => true], ['value' => fmt_num($food['carbs'], 0), 'label' => 'gram karbo'], ['value' => fmt_num($food['fat'], 0), 'label' => 'gram lemak']] as $macro)
                        <div class="macro-box"
                            style="text-align: center; padding: 12px; background: var(--section, #f8f5ee); border-radius: 12px;">
                            <div class="macro-val {{ ! empty($macro['highlight']) ? 'highlight' : '' }}"
                                style="font-size: 20px; font-weight: 700;{{ ! empty($macro['highlight']) ? ' color: var(--green-dark, #5f7710);' : '' }}">
                                {{ $macro['value'] }}
                            </div>
                            <div class="macro-label" style="font-size: 12px; color: var(--muted, #55554f);">
                                {{ $macro['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <div class="btn-row" style="margin-top: 24px; display: flex; gap: 10px; align-items: center;">
                <a class="btn btn-primary" href="{{ route('foods.edit', $food['id']) }}">Edit makanan</a>
                <a class="btn btn-secondary" href="{{ route('foods.index') }}">Lihat katalog</a>

                <form class="inline-form" action="{{ route('foods.destroy', $food['id']) }}" method="POST"
                    onsubmit="return confirm('Yakin ingin menghapus makanan &quot;{{ $food['name'] }}&quot;? Tindakan ini tidak dapat dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger" type="submit"
                        style="background: var(--red, #e8310c); color: #fff; border:none; padding: 10px 18px; border-radius: 99px; cursor: pointer;">Hapus
                        makanan</button>
                </form>
            </div>
        </div>
    </div>
@endsection
