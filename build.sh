#!/bin/bash

# Vercel build script for Laravel
echo "Starting Laravel build for Vercel..."

# Check if composer is available
if ! command -v composer &> /dev/null; then
    echo "Composer not found, installing..."
    # Vercel should handle this automatically, but we'll try to install if needed
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# Install dependencies
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Create storage directories if they don't exist
echo "Creating storage directories..."
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs

# Set permissions (Vercel handles this automatically)
# chmod -R 775 storage
# chmod -R 775 bootstrap/cache

# Generate application key if not set
if [ -z "$APP_KEY" ]; then
    echo "APP_KEY not set, generating..."
    php artisan key:generate --force
fi

# Clear and cache config
echo "Caching configuration..."
php artisan config:clear
php artisan config:cache

# Clear and cache routes
echo "Caching routes..."
php artisan route:clear
php artisan route:cache

# Clear and cache views
echo "Caching views..."
php artisan view:clear
php artisan view:cache

echo "Build completed successfully!"
