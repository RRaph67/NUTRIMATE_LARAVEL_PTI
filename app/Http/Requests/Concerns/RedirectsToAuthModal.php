<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Form login & register berada di dalam modal pada halaman beranda.
 *
 * Saat validasi gagal, pengguna dikembalikan ke beranda dengan modal yang
 * sesuai tetap terbuka, bukan ke halaman form terpisah yang tidak ada.
 */
trait RedirectsToAuthModal
{
    /**
     * Panel modal yang harus dibuka kembali ("login" atau "register").
     */
    abstract protected function authPanel(): string;

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            redirect()
                ->route('home', ['auth' => $this->authPanel()])
                ->withInput($this->except('password', 'password_confirmation'))
                ->withErrors($validator)
        );
    }
}
