<?php

// Laravel entry point for Vercel
try {
    // Load Composer autoloader
    require_once __DIR__ . '/../vendor/autoload.php';

    // Create storage directories if they don't exist
    $storageDirs = [
        __DIR__ . '/../storage/framework/cache',
        __DIR__ . '/../storage/framework/sessions',
        __DIR__ . '/../storage/framework/views',
        __DIR__ . '/../storage/logs',
    ];

    foreach ($storageDirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    // Create bootstrap/cache directory if it doesn't exist
    $bootstrapCacheDir = __DIR__ . '/../bootstrap/cache';
    if (!is_dir($bootstrapCacheDir)) {
        $result = mkdir($bootstrapCacheDir, 0755, true);
        error_log("Creating bootstrap/cache directory: " . ($result ? "SUCCESS" : "FAILED"));
    }
    
    // Ensure the directory is writable
    if (is_dir($bootstrapCacheDir)) {
        chmod($bootstrapCacheDir, 0755);
        error_log("Bootstrap cache directory exists and is writable: " . (is_writable($bootstrapCacheDir) ? "YES" : "NO"));
    } else {
        error_log("Bootstrap cache directory does not exist after creation attempt");
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
