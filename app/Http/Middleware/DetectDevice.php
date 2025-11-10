<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Detect device type from User-Agent header
        $userAgent = $request->header(config('pagination.detection.header', 'User-Agent'), '');
        $deviceType = 'web';

        if (config('pagination.detection.enabled')) {
            // Check for tablet first (more specific)
            foreach (config('pagination.detection.tablet_patterns', []) as $pattern) {
                if (stripos($userAgent, $pattern) !== false) {
                    $deviceType = 'tablet';
                    break;
                }
            }

            // Check for mobile if not already detected as tablet
            if ($deviceType === 'web') {
                foreach (config('pagination.detection.mobile_patterns', []) as $pattern) {
                    if (stripos($userAgent, $pattern) !== false) {
                        $deviceType = 'mobile';
                        break;
                    }
                }
            }
        }

        // Set device type in request attributes
        $request->attributes->set('device_type', $deviceType);

        return $next($request);
    }
}
