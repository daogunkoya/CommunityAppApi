<?php

// Laravel entry point for Vercel
try {
    // Load Composer autoloader
    require_once __DIR__ . '/../vendor/autoload.php';

    // Set environment variables for Vercel's read-only filesystem
    if (!isset($_ENV['APP_CONFIG_CACHE'])) {
        $_ENV['APP_CONFIG_CACHE'] = '/tmp/config.php';
    }
    if (!isset($_ENV['APP_EVENTS_CACHE'])) {
        $_ENV['APP_EVENTS_CACHE'] = '/tmp/events.php';
    }
    if (!isset($_ENV['APP_PACKAGES_CACHE'])) {
        $_ENV['APP_PACKAGES_CACHE'] = '/tmp/packages.php';
    }
    if (!isset($_ENV['APP_ROUTES_CACHE'])) {
        $_ENV['APP_ROUTES_CACHE'] = '/tmp/routes.php';
    }
    if (!isset($_ENV['APP_SERVICES_CACHE'])) {
        $_ENV['APP_SERVICES_CACHE'] = '/tmp/services.php';
    }
    if (!isset($_ENV['VIEW_COMPILED_PATH'])) {
        $_ENV['VIEW_COMPILED_PATH'] = '/tmp';
    }

    // Disable CSRF protection globally for API routes
    $_ENV['SESSION_DRIVER'] = 'array';
    $_ENV['SESSION_LIFETIME'] = '0';
    $_ENV['APP_ENV'] = 'production';

    // Bootstrap Laravel
    $app = require_once __DIR__ . '/../bootstrap/app.php';

    // Disable CSRF protection globally
    $app->singleton('Illuminate\Contracts\Http\Kernel', function ($app) {
        return new class($app) extends \Illuminate\Foundation\Http\Kernel {
            protected $middleware = [
                \App\Http\Middleware\TrustProxies::class,
                \Illuminate\Http\Middleware\HandleCors::class,
                \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
                \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
                \App\Http\Middleware\TrimStrings::class,
                \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
            ];

            protected $middlewareGroups = [
                'web' => [
                    \App\Http\Middleware\EncryptCookies::class,
                    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                    \Illuminate\Session\Middleware\StartSession::class,
                    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                    // \App\Http\Middleware\VerifyCsrfToken::class, // DISABLED CSRF
                    \Illuminate\Routing\Middleware\SubstituteBindings::class,
                ],

                'api' => [
                    \App\Http\Middleware\DisableCsrfForApi::class,
                    \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
                    \Illuminate\Routing\Middleware\SubstituteBindings::class,
                ],
            ];

            protected $middlewareAliases = [
                'auth' => \App\Http\Middleware\Authenticate::class,
                'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
                'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
                'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
                'can' => \Illuminate\Auth\Middleware\Authorize::class,
                'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
                'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
                'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
                'signed' => \App\Http\Middleware\ValidateSignature::class,
                'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
                'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            ];
        };
    });

    // Run the application
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

    $response = $kernel->handle(
        $request = Illuminate\Http\Request::capture()
    );

    $response->send();

    $kernel->terminate($request, $response);

} catch (Exception $e) {
    // Log the error
    error_log('Laravel Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    // Return a proper error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal Server Error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
