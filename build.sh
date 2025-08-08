#!/bin/bash

# Vercel build script for Laravel
echo "Starting Laravel build for Vercel..."

# Install dependencies
composer install --no-dev --optimize-autoloader

# Create storage directories if they don't exist
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
php artisan config:clear
php artisan config:cache

# Clear and cache routes
php artisan route:clear
php artisan route:cache

# Clear and cache views
php artisan view:clear
php artisan view:cache

echo "Build completed successfully!"
