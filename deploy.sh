#!/bin/bash

# Laravel Project Deployment Script for 1.com
# Usage: ./deploy.sh

set -e  # Exit on any error

echo "🚀 Starting Laravel deployment to 1.com..."

# Configuration
REMOTE_HOST="ogunkoya"
REMOTE_USER="ogunkoya.net"
PROJECT_NAME="fresh-laravel-api"
REMOTE_PATH="~/fresh-laravel-api"
WEB_PATH="../httpd.www/fresh-api"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# Check if we're in the correct directory
if [ ! -f "artisan" ]; then
    print_error "This script must be run from the Laravel project root directory"
    exit 1
fi

# Step 1: Prepare local project
print_step "1. Preparing local project..."
print_status "Installing production dependencies..."
composer install --no-dev --optimize-autoloader

print_status "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 2: Deploy files to server
print_step "2. Deploying files to server..."
print_status "Syncing files to ${REMOTE_HOST}..."
rsync -avz --delete \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='.env' \
    --exclude='.env.local' \
    --exclude='.env.production' \
    --exclude='deploy.sh' \
    --exclude='README.md' \
    --exclude='docs/' \
    . ${REMOTE_HOST}:${REMOTE_PATH}/

# Step 3: Install dependencies on server
print_step "3. Installing dependencies on server..."
print_status "Installing Composer dependencies..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && composer install --no-dev --optimize-autoloader"

# Step 4: Set up environment file
print_step "4. Setting up environment configuration..."
print_status "Creating environment file..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && cp .env.example .env"

# Step 5: Configure database settings
print_step "5. Configuring database settings..."
print_status "Updating database configuration..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=ogunkoya.net.mysql/' .env"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && sed -i 's/# DB_PORT=3306/DB_PORT=3306/' .env"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && sed -i 's/# DB_DATABASE=ogunkoya_net/DB_DATABASE=ogunkoya_netcommunitysport/' .env"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && sed -i 's/# DB_USERNAME=ogunkoya_net/DB_USERNAME=ogunkoya_netcommunitysport/' .env"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && sed -i 's/# DB_PASSWORD=ogunkoya_net/DB_PASSWORD=communitysport/' .env"

# Step 6: Generate application key
print_step "6. Generating application key..."
print_status "Creating application encryption key..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan key:generate"

# Step 7: Clear caches
print_step "7. Clearing application caches..."
print_status "Clearing all caches..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan config:clear"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan cache:clear"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan route:clear"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan view:clear"

# Step 8: Run migrations
print_step "8. Running database migrations..."
print_status "Executing database migrations..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan migrate --force"

# Step 9: Install Passport (if needed)
print_step "9. Setting up Passport authentication..."
print_status "Installing Passport clients..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan passport:install --force"

# Step 10: Deploy to web directory
print_step "10. Deploying to web directory..."
print_status "Copying to web-accessible directory..."
ssh ${REMOTE_HOST} "rm -rf ${WEB_PATH} && cp -r ${REMOTE_PATH} ${WEB_PATH}"

# Step 11: Set proper permissions
print_step "11. Setting file permissions..."
print_status "Setting storage and cache permissions..."
ssh ${REMOTE_HOST} "chmod -R 755 ${WEB_PATH}/storage"
ssh ${REMOTE_HOST} "chmod -R 755 ${WEB_PATH}/bootstrap/cache"

# Step 12: Test deployment
print_step "12. Testing deployment..."
print_status "Testing API endpoint..."
sleep 3  # Give server time to process

# Test the API endpoint
if curl -s -f "https://ogunkoya.net/fresh-api/public/api/test" > /dev/null; then
    print_status "✅ API endpoint is responding"
else
    print_warning "⚠️  API endpoint test failed - check server logs"
fi

print_status "✅ Deployment completed successfully!"
echo ""
print_status "🌐 Your application is now live at:"
echo "   https://ogunkoya.net/fresh-api/public/"
echo ""
print_status "🔗 API endpoints:"
echo "   Test: https://ogunkoya.net/fresh-api/public/api/test"
echo "   Login: https://ogunkoya.net/fresh-api/public/api/login"
echo ""
print_status "📝 Next steps:"
echo "   - Test the login endpoint with your credentials"
echo "   - Monitor logs: ssh ogunkoya 'tail -f ../httpd.www/fresh-api/storage/logs/laravel.log'"
echo "   - Check for any errors in the application"
echo ""
