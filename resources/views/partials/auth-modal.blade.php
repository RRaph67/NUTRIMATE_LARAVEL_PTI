@guest
    @php
        // Buka panel yang diminta (?auth=login|register) atau panel terakhir
        // yang gagal validasi, supaya pengguna tidak perlu klik ulang.
        $activePanel = old('_panel')
            ?: (request()->query('auth') === 'register' ? 'register' : (request()->query('auth') === 'login' ? 'login' : 'login'));
        $shouldOpen = request()->filled('auth') || $errors->any();
    @endphp

    <div class="modal {{ $shouldOpen ? 'is-open' : '' }}" id="authModal"
        data-open-panel="{{ $activePanel }}" aria-hidden="{{ $shouldOpen ? 'false' : 'true' }}">
        <div class="modal-card" role="dialog" aria-modal="true" aria-label="Masuk atau daftar NutriMate">
            <button type="button" class="modal-close" data-close-modal aria-label="Tutup">
                <svg viewBox="0 0 24 24">
                    <path d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>

            {{-- ================= PANEL MASUK ================= --}}
            <section class="auth-panel" data-panel="login" @if ($activePanel !== 'login') hidden @endif>
                <h2 class="auth-title">Masuk Akun</h2>
                <p class="auth-sub">Masuk untuk mulai hidup sehatmu</p>

                <form method="POST" action="{{ route('login.store') }}" novalidate>
                    @csrf

                    <label class="field">
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@exmp.com"
                            autocomplete="email" required>
                    </label>

                    <label class="field">
                        <span>Password</span>
                        <div class="pw-wrap">
                            <input type="password" name="password" placeholder="••••••••"
                                autocomplete="current-password" required>
                            <button type="button" class="pw-toggle" aria-label="Tampilkan password">
                                <svg viewBox="0 0 24 24">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                    <path class="slash" d="M4 4l16 16" />
                                </svg>
                            </button>
                        </div>
                    </label>

                    <button type="submit" class="btn-auth"
                        style="display:block; width:100%; padding:12px; border:0; border-radius:99px; background:#718a14; color:#fff; font-size:13px; font-weight:600; cursor:pointer; box-shadow:0 6px 14px rgba(113,138,20,0.25);">Masuk</button>
                </form>

                <p class="auth-switch"
                    style="margin-top:17px; padding-top:14px; border-top:1px solid #ececec; text-align:center; font-size:12px; color:#55554f;">
                    Belum punya akun?
                    <button type="button" class="switch-link" data-switch-panel="register"
                        style="font-family:inherit; font-size:12px; font-weight:700; color:#718a14; background:transparent; border:none; padding:0; margin-left:4px; cursor:pointer; text-decoration:none;">Daftar
                        sekarang</button>
                </p>
            </section>

            {{-- ================= PANEL DAFTAR ================= --}}
            <section class="auth-panel" data-panel="register" @if ($activePanel !== 'register') hidden @endif>
                <h2 class="auth-title">Daftar Akun</h2>
                <p class="auth-sub">Mulai perjalanan gizi seimbangmu hari ini</p>

                <form method="POST" action="{{ route('register.store') }}" novalidate>
                    @csrf
                    <input type="hidden" name="_panel" value="register">

                    <label class="field">
                        <span>Nama Lengkap</span>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Budi Santoso"
                            autocomplete="name" required>
                    </label>

                    <label class="field">
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="nama@exmp.com"
                            autocomplete="email" required>
                    </label>

                    <label class="field">
                        <span>Password</span>
                        <div class="pw-wrap">
                            <input type="password" name="password" placeholder="Min. 8 karakter"
                                autocomplete="new-password" required>
                            <button type="button" class="pw-toggle" aria-label="Tampilkan password">
                                <svg viewBox="0 0 24 24">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                    <path class="slash" d="M4 4l16 16" />
                                </svg>
                            </button>
                        </div>
                    </label>

                    <label class="field">
                        <span>Konfirmasi Password</span>
                        <div class="pw-wrap">
                            <input type="password" name="password_confirmation" placeholder="Ulangi kata sandi"
                                autocomplete="new-password" required>
                            <button type="button" class="pw-toggle" aria-label="Tampilkan password">
                                <svg viewBox="0 0 24 24">
                                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                                    <circle cx="12" cy="12" r="3" />
                                    <path class="slash" d="M4 4l16 16" />
                                </svg>
                            </button>
                        </div>
                    </label>

                    <button type="submit" class="btn-auth"
                        style="display:block; width:100%; padding:12px; border:0; border-radius:99px; background:#718a14; color:#fff; font-size:13px; font-weight:600; cursor:pointer; box-shadow:0 6px 14px rgba(113,138,20,0.25);">Daftar
                        Akun</button>
                </form>

                <p class="auth-switch"
                    style="margin-top:17px; padding-top:14px; border-top:1px solid #ececec; text-align:center; font-size:12px; color:#55554f;">
                    Sudah punya akun?
                    <button type="button" class="switch-link" data-switch-panel="login"
                        style="font-family:inherit; font-size:12px; font-weight:700; color:#718a14; background:transparent; border:none; padding:0; margin-left:4px; cursor:pointer; text-decoration:none;">Masuk
                        di sini</button>
                </p>
            </section>
        </div>
    </div>
@endguest
