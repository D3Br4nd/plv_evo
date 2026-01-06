<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Ensure Italian UI strings for backend-driven labels (e.g. pagination).
        app()->setLocale(env('APP_LOCALE', 'it'));

        $user = $request->user();
        $authUser = null;
        if ($user) {
            $authUser = [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'plv_expires_at' => $user->plv_expires_at?->format('d/m/Y'),
                'residence_type' => $user->residence_type,
                'residence_street' => $user->residence_street,
                'residence_house_number' => $user->residence_house_number,
                'residence_locality' => $user->residence_locality,
                'residence_province_code' => $user->residence_province_code,
                'residence_city' => $user->residence_city,
                'residence_country' => $user->residence_country,
                'role' => $user->role,
                'plv_role' => $user->plv_role,
                'must_set_password' => (bool) $user->must_set_password,
                'avatar_url' => $user->avatar_url,
            ];
        }

        return [
            ...parent::share($request),
            'flash' => [
                // Share actual values (not closures) because the frontend renders them directly.
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'invite_url' => $request->session()->get('invite_url'),
            ],
            'auth' => [
                'user' => $authUser,
                'can' => [
                    'manageRoles' => $user ? $user->can('manage-roles') : false,
                ],
            ],
        ];
    }
}
