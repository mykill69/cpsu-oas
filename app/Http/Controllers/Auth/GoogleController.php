<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * "Sign in with Google" for staff. Only verified accounts on the institutional
 * domain whose email the QA office registered under Accounts get in.
 * Accreditors never come through here; they use their share link + code.
 */
class GoogleController extends Controller
{
    public function redirect()
    {
        abort_unless(filled(config('services.google.client_id')), 404);

        return $this->driver()
            ->with(array_filter(['hd' => config('oas.allowed_domain'), 'prompt' => 'select_account']))
            ->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $google = $this->driver()->user();
        } catch (\Throwable $e) {
            Log::notice('Google sign-in failed', ['error' => $e->getMessage()]);

            return $this->deny('Google sign-in was cancelled or expired. Please try again.');
        }

        $email = mb_strtolower((string) $google->getEmail());
        $domain = mb_strtolower((string) config('oas.allowed_domain'));

        if (! ($google->user['email_verified'] ?? false)) {
            return $this->deny('That Google account has no verified email.');
        }
        if ($domain && ! str_ends_with($email, '@' . $domain)) {
            return $this->deny("Use your institutional account (@{$domain}).");
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if (! $user || $user->getRoleNames()->isEmpty()) {
            return $this->deny("{$email} isn't registered in the accreditation system. Ask the QA office to add your account.");
        }
        if ($user->google_sub && $user->google_sub !== $google->getId()) {
            return $this->deny('This email is linked to a different Google account. Contact the QA office.');
        }

        $user->forceFill(['google_sub' => $google->getId(), 'last_login_at' => now()])->save();
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    private function driver()
    {
        return Socialite::driver('google')->redirectUrl(route('auth.google.callback'));
    }

    private function deny(string $message)
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
