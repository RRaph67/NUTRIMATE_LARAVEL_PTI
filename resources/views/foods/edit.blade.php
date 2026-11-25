@extends('layouts.app')

@section('content')
    <div class="page-header"
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1>Edit makanan</h1>
            <p>Perbarui informasi <strong>{{ $food['name'] }}</strong> di katalog Anda.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('foods.show', $food['id']) }}">← Kembali ke detail</a>
    </div>

    <div class="panel"
        style="background: #fff; padding: 28px; border-radius: 18px; border: 1px solid var(--border, #ece5d8);">
        <form action="{{ route('foods.update', $food['id']) }}" method="POST" novalidate>
            @csrf
            @method('PUT')
            @include('foods._form')

            <div class="btn-row" style="margin-top: 18px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Perbarui Makanan</button>
                <a class="btn btn-secondary" href="{{ route('foods.show', $food['id']) }}">Batal</a>
            </div>
        </form>
    </div>
@endsection
