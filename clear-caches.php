<?php

// Simple script to clear Laravel caches
echo "Clearing Laravel caches...\n";

// Clear config cache
if (file_exists('bootstrap/cache/config.php')) {
    unlink('bootstrap/cache/config.php');
    echo "✓ Config cache cleared\n";
}

// Clear route cache
if (file_exists('bootstrap/cache/routes.php')) {
    unlink('bootstrap/cache/routes.php');
    echo "✓ Route cache cleared\n";
}

// Clear view cache
if (file_exists('bootstrap/cache/views.php')) {
    unlink('bootstrap/cache/views.php');
    echo "✓ View cache cleared\n";
}

// Clear compiled classes
if (file_exists('bootstrap/cache/compiled.php')) {
    unlink('bootstrap/cache/compiled.php');
    echo "✓ Compiled classes cache cleared\n";
}

// Clear application cache
if (file_exists('bootstrap/cache/packages.php')) {
    unlink('bootstrap/cache/packages.php');
    echo "✓ Packages cache cleared\n";
}

echo "All caches cleared successfully!\n";

