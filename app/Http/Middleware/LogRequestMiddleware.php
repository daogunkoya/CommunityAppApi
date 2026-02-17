<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequestMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $userId = optional($request->user())->id;

        Log::channel('request')->info('HTTP Request', [
            'method'  => $request->getMethod(),
            'path'    => $request->getPathInfo(),
            'ip'      => $request->ip(),
            'user_id' => $userId,
            'query'   => $request->query(),
            'payload' => $request->except(['password', 'password_confirmation']),
        ]);

        return $next($request);
    }
}