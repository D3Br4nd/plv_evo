<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    private function isMobile(Request $request): bool
    {
        $ua = strtolower((string) $request->userAgent());
        return str_contains($ua, 'iphone')
            || str_contains($ua, 'ipad')
            || str_contains($ua, 'ipod')
            || str_contains($ua, 'android')
            || str_contains($ua, 'mobile')
            || str_contains($ua, 'tablet');
    }

    /**
     * Show the login form.
     */
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = $request->user();
            $isAdmin = in_array($user->role, ['super_admin', 'admin'], true);

            // If the user is entering from the PWA/member area, always honor the intended /me* URL
            // even for admin roles. This keeps the mobile/PWA experience on /me.
            $intended = $request->session()->get('url.intended');
            if (is_string($intended)) {
                $path = parse_url($intended, PHP_URL_PATH) ?: '';
                if (str_starts_with($path, '/me')) {
                    return redirect()->to($intended);
                }
            }

            // Mobile: default to the PWA/mobile UI unless the user explicitly opted into the admin UI.
            $preferAdminUi = $request->session()->get('ui.prefer') === 'admin';
            if ($this->isMobile($request) && ! $preferAdminUi) {
                return $user->must_set_password
                    ? redirect()->to('/me/onboarding')
                    : redirect()->to('/me');
            }

            // If user explicitly intended an admin page, keep it (desktop or opted-in mobile).
            if (is_string($intended)) {
                $path = parse_url($intended, PHP_URL_PATH) ?: '';
                if (str_starts_with($path, '/admin')) {
                    if ($isAdmin) {
                        return redirect()->to($intended);
                    }
                    // Non-admin users should never be redirected into /admin/* after login.
                    // Clear the intended URL to avoid confusing 403 redirects.
                    $request->session()->forget('url.intended');
                }
            }

            if ($isAdmin) return redirect()->intended('/admin/dashboard');

            return $user->must_set_password
                ? redirect()->intended('/me/onboarding')
                : redirect()->intended('/me');
        }

        throw ValidationException::withMessages([
            'email' => 'Le credenziali fornite non sono corrette.',
        ]);
    }

    /**
     * Show the registration form.
     */
    public function showRegister()
    {
        abort(404);
    }

    /**
     * Handle registration request.
     */
    public function register(Request $request)
    {
        abort(404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::Member->value,
            'membership_status' => 'pending',
        ]);

        Auth::login($user);

        return redirect('/me/card');
    }

    /**
     * Handle logout request.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
