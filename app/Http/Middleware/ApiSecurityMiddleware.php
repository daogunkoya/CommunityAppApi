<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiSecurityMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Create a minimal session instance to avoid null session errors
        if (!$request->hasSession()) {
            $sessionManager = app('session');
            $session = $sessionManager->driver();
            $request->setLaravelSession($session);
        }

        // Handle preflight OPTIONS requests
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200);
        } else {
            // Set headers for API responses
            $response = $next($request);
            $response->headers->set('Content-Type', 'application/json');
        }

        // Allow requests from specific origins
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

        // Check if origin is from Expo (mobile apps)
        $isExpoOrigin = $origin && (
            str_starts_with($origin, 'exp://') ||
            str_starts_with($origin, 'expo://') ||
            str_starts_with($origin, 'http://localhost:') ||
            str_starts_with($origin, 'http://192.168.') ||
            str_starts_with($origin, 'http://10.') ||
            str_starts_with($origin, 'http://172.')
        );

        // Set CORS headers
        if ($origin && (in_array($origin, $allowedOrigins) || $isExpoOrigin)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        } elseif (!$origin) {
            // For mobile apps that don't send Origin header, allow all origins
            // Note: Cannot use '*' with credentials, but mobile apps use token auth
            $response->headers->set('Access-Control-Allow-Origin', '*');
            // Don't set credentials for wildcard origin (CORS spec requirement)
        } else {
            // Unknown origin - don't set CORS headers for security
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-Auth-Token');
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }
}
