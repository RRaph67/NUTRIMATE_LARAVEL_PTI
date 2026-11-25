@php
    $successMessage = session('success') ?? session('status');
    $errorMessage = session('error');
@endphp

@if ($successMessage)
    <div class="alert alert-success" role="status"
        style="margin-bottom: 20px; padding: 14px 18px; border-radius: 12px; background: #eaf5d8; color: #436006; border: 1px solid #cce5a3; font-weight: 500;">
        {{ $successMessage }}
    </div>
@endif

@if ($errorMessage)
    <div class="alert alert-error" role="alert"
        style="margin-bottom: 20px; padding: 14px 18px; border-radius: 12px; background: #fde8e4; color: #a81c00; border: 1px solid #f9bdb1; font-weight: 500;">
        {{ $errorMessage }}
    </div>
@endif

@if ($errors->any() && ! $errorMessage)
    <div class="alert alert-error" role="alert"
        style="margin-bottom: 20px; padding: 14px 18px; border-radius: 12px; background: #fde8e4; color: #a81c00; border: 1px solid #f9bdb1; font-weight: 500;">
        {{ $errors->first() }}
    </div>
@endif
