<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectMobileAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $roleValue = $user && $user->role instanceof \UnitEnum ? $user->role->value : ($user->role ?? null);

        // If not logged in, or not an admin, let them pass
        if (!$user || !in_array($roleValue, ['super_admin', 'admin'], true)) {
            return $next($request);
        }

        // Check for mobile device
        $ua = strtolower((string) $request->userAgent());
        $isMobile = str_contains($ua, 'iphone')
            || str_contains($ua, 'ipad')
            || str_contains($ua, 'ipod')
            || str_contains($ua, 'android')
            || str_contains($ua, 'mobile')
            || str_contains($ua, 'tablet');

        if (!$isMobile) {
            return $next($request);
        }

        $preferAdminUi = $request->session()->get('ui.prefer') === 'admin';
        
        \Log::info('Mobile Admin Access Check', [
            'user' => $user->email,
            'path' => $request->path(),
            'is_mobile' => $isMobile,
            'prefer_admin_ui' => $preferAdminUi,
        ]);

        // Check if the user explicitly preferred the admin UI for this session
        if ($preferAdminUi) {
            return $next($request);
        }

        // We are on mobile and have NO preference for Admin UI.
        // Try to redirect to equivalent /me routes.
        
        $path = $request->path();
        
        // Exact mappings
        if ($path === 'admin/dashboard') {
            return redirect('/me');
        }

        // Pattern mappings
        if (str_starts_with($path, 'admin/projects')) {
            $segments = $request->segments();
            if (isset($segments[2])) { 
                return redirect('/me/projects/' . $segments[2]);
            }
            return redirect('/me/projects');
        }

        if (str_starts_with($path, 'admin/events')) {
             $segments = $request->segments();
            if (isset($segments[2])) {
                return redirect('/me/events/' . $segments[2]);
            }
            return redirect('/me/events');
        }

        if (str_starts_with($path, 'admin/committees')) {
             $segments = $request->segments();
            // Handle post specifically: admin/committees/{cid}/posts/{pid}
            if (isset($segments[2]) && isset($segments[3]) && $segments[3] === 'posts' && isset($segments[4])) {
                return redirect('/me/committees/posts/' . $segments[4]);
            }
            if (isset($segments[2])) {
                return redirect('/me/committees/' . $segments[2]);
            }
            return redirect('/me/committees');
        }

        if (str_starts_with($path, 'admin/broadcasts')) {
             $segments = $request->segments();
            if (isset($segments[2])) {
                return redirect('/me/broadcasts/' . $segments[2]);
            }
            return redirect('/me'); // No broadcast index for members yet?
        }

        if (str_starts_with($path, 'admin/content-pages')) {
            return redirect('/me'); // Or maybe a public link?
        }

        // Default redirect for any other admin path
        return redirect('/me');
    }
}
