<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Repositories\ProfileRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileRepository $profiles,
        private UserRepository $users,
    ) {}

    public function show(): View
    {
        $user = Auth::user();

        return view('profile.show', [
            'pageTitle' => 'Profil — NutriMate',
            'user' => $user,
            'profile' => $this->profiles->findByUserId((int) $user->getAuthIdentifier()),
        ]);
    }

    /**
     * Perbarui nama lengkap, target kalori, dan budget harian.
     */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $userId = (int) Auth::id();
        $data = $request->validated();
        $fullName = trim($data['full_name']);

        $this->profiles->updateFullName($userId, $fullName);
        $this->users->updateName($userId, $fullName);
        $this->profiles->updateTargets(
            $userId,
            (int) $data['calorie_target'],
            (float) $data['daily_budget'],
        );

        return redirect()
            ->route('profile.show')
            ->with('success', 'Profil berhasil diperbarui!');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = Auth::user();

        if (! Hash::check($request->string('current_password')->value(), $user->getAuthPassword())) {
            return redirect()
                ->route('profile.show')
                ->with('error', 'Kata sandi saat ini tidak cocok.');
        }

        $this->users->updatePassword(
            (int) $user->getAuthIdentifier(),
            Hash::make($request->string('password')->value()),
        );

        return redirect()
            ->route('profile.show')
            ->with('success', 'Kata sandi berhasil diperbarui!');
    }
}