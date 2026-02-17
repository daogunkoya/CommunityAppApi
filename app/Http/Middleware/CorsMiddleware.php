<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * When true, allow any origin and any headers (e.g. for mobile apps / fixing request issues).
     * When false, only allow configured origins (better for production web).
     */
    protected bool $allowAllOrigins = true;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight OPTIONS requests first
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200);
        } else {
            $response = $next($request);
        }

        // Allow requests from specific origins (used when $allowAllOrigins is false)
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:5173',
            'http://localhost:4173',
            'http://localhost:8080',
            'https://matchgrinder.com',
            'https://www.matchgrinder.com',
            'http://matchgrinder.com',
            'http://www.matchgrinder.com'
        ];

        $origin = $request->header('Origin');
        $userAgent = $request->header('User-Agent', '');

        // Check if origin is from Expo (mobile apps)
        $isExpoOrigin = $origin && (
            str_starts_with($origin, 'exp://') ||
            str_starts_with($origin, 'expo://') ||
            str_starts_with($origin, 'http://localhost:') ||
            str_starts_with($origin, 'http://192.168.') ||
            str_starts_with($origin, 'http://10.') ||
            str_starts_with($origin, 'http://172.')
        );

        // Check if request is from TestFlight or production iOS app (no Origin header)
        $isMobileApp = !$origin || str_contains($userAgent, 'TestFlight') ||
            str_contains($userAgent, 'MatchGrinder') ||
            str_contains($userAgent, 'com.matchgrinder.mobile');

        // Set CORS headers: use * to accept requests from anywhere (web + iPhone/Android)
        if ($this->allowAllOrigins || $isMobileApp) {
            // Accept all origins (required for mobile apps and arbitrary clients)
            // Note: cannot use Access-Control-Allow-Credentials: true with '*' (CORS spec)
            $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
            // Do not set Allow-Credentials when using * (browsers would reject it)
        } elseif ($origin && (in_array($origin, $allowedOrigins) || $isExpoOrigin)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        } elseif ($origin) {
            // Unknown origin when not allowing all - no CORS header (browser will block)
        } else {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        // Allow any header so mobile apps and clients can send what they need
        $response->headers->set('Access-Control-Allow-Headers', '*');
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }
}
