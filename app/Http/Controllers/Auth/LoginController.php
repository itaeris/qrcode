<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Enter a valid email address.',
            'password.required' => 'Password is required.',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'The email or password is incorrect.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function google(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')->with('error', 'Google sign-in failed. Try again.');
        }

        $email = mb_strtolower(trim((string) $googleUser->getEmail()));

        if ($email === '' || ! $this->googleEmailIsVerified($googleUser)) {
            return redirect()->route('login')->with('error', 'Google did not confirm this email address.');
        }

        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if (! $user) {
            return redirect()->route('login')->with('error', 'This Google account is not registered.');
        }

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function googleEmailIsVerified(SocialiteUser $googleUser): bool
    {
        $raw = isset($googleUser->user) && is_array($googleUser->user) ? $googleUser->user : [];
        $verified = $raw['email_verified'] ?? $raw['verified_email'] ?? false;

        return filter_var($verified, FILTER_VALIDATE_BOOLEAN);
    }
}
