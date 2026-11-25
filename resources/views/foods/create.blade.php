@extends('layouts.app')

@section('content')
    <div class="page-header"
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1>Tambah makanan</h1>
            <p>Lengkapi formulir berikut untuk menambahkan makanan baru ke katalog.</p>
        </div>
        <a class="btn btn-secondary" href="{{ route('foods.index') }}">← Kembali ke katalog</a>
    </div>

    <div class="panel"
        style="background: #fff; padding: 28px; border-radius: 18px; border: 1px solid var(--border, #ece5d8);">
        <form action="{{ route('foods.store') }}" method="POST" novalidate>
            @csrf
            @include('foods._form')

            <div class="btn-row" style="margin-top: 18px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">Simpan Makanan</button>
                <a class="btn btn-secondary" href="{{ route('foods.index') }}">Batal</a>
            </div>
        </form>
    </div>
@endsection
