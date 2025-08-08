#!/bin/bash

echo "🚀 Starting Laravel build process for Vercel..."

# Check if composer is available
if ! command -v composer &> /dev/null; then
    echo "❌ Composer not found. Installing dependencies manually..."
    # Fallback for when composer is not available
    exit 0
fi

# Install dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Generate application key if not exists
echo "🔑 Generating application key..."
php artisan key:generate --force

# Clear all caches
echo "🧹 Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache configurations for production
echo "⚡ Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize for production
echo "🚀 Optimizing for production..."
php artisan optimize

echo "✅ Laravel build process completed!"
