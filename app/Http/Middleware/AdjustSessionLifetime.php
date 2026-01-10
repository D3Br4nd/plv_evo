<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdjustSessionLifetime
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->header('User-Agent', '');
        
        // Simple mobile/tablet detection
        $isMobileOrTablet = preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent);

        if ($isMobileOrTablet) {
            // PWA / Smartphone & Tablet: 1 month (43200 minutes)
            config(['session.lifetime' => 43200]);
        } else {
            // Desktop: 7 days (10080 minutes)
            config(['session.lifetime' => 10080]);
        }

        return $next($request);
    }
}
