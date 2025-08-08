<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DisableCsrfForApi
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Completely disable CSRF protection for API routes
        $request->setLaravelSession(null);
        
        // Remove any CSRF token validation
        $request->headers->remove('X-CSRF-TOKEN');
        $request->headers->remove('X-XSRF-TOKEN');
        
        // Disable session middleware for this request
        $request->server->set('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
        
        // Set content type to JSON
        $request->headers->set('Content-Type', 'application/json');
        
        return $next($request);
    }
}
