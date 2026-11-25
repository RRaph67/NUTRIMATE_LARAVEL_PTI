@extends('layouts.app')

@section('content')
    <div class="mp-page">
        <div class="mp-inner">
            <div class="page-head">
                <span class="page-badge">Rencana Makan</span>
                <h1 class="page-title">Meal Planner</h1>
                <p class="page-subtitle">Atur menu makan harianmu. Total nutrisi tiap waktu makan dihitung otomatis
                    dari Food Database.</p>
            </div>

            <div class="slot-grid">
                @foreach ($plan['slots'] as $slot)
                    <article class="slot-card">
                        <div class="slot-top">
                            <h2>{{ $slot['label'] }}</h2>
                            @if ($slot['items'] !== [])
                                <form method="POST" action="{{ route('meal-planner.destroy-slot') }}"
                                    onsubmit="return confirm('Kosongkan {{ $slot['label'] }} hari ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="slot" value="{{ $slot['key'] }}">
                                    <button type="submit" class="slot-clear">Kosongkan</button>
                                </form>
                            @endif
                        </div>

                        <p class="slot-text">
                            {{ $slot['items'] === [] ? 'Belum ada makanan' : $slot['summary'] }}
                        </p>

                        <div class="slot-kcal"><strong>{{ fmt_num($slot['calories'], 0) }}</strong> kkal</div>
                        <a href="{{ route('foods.index') }}" class="slot-add">+ Tambah Makanan</a>
                    </article>
                @endforeach
            </div>

            <div class="daily-total" role="status">
                <span class="daily-title">Total Harian</span>
                <div class="daily-stats">
                    <span><strong>{{ fmt_num($plan['totals']['calories'], 0) }}</strong> kkal</span>
                    <span><strong>{{ fmt_num($plan['totals']['protein'], 1) }}</strong> g protein</span>
                    <span><strong>{{ fmt_num($plan['totals']['carbs'], 1) }}</strong> g karbo</span>
                    <span><strong>{{ fmt_num($plan['totals']['fat'], 1) }}</strong> g lemak</span>
                    <span><strong>{{ rupiah($plan['totals']['price']) }}</strong></span>
                </div>
            </div>

            <p class="planner-sub" style="margin-top: 24px; text-align: center;">
                <a href="{{ route('foods.index') }}">Kembali ke Food Database</a> untuk menambah menu lain.
            </p>
        </div>
    </div>
@endsection