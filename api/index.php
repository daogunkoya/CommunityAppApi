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

    // Bootstrap Laravel
    $app = require_once __DIR__ . '/../bootstrap/app.php';

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
