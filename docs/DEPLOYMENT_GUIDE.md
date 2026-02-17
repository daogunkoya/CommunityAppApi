# Laravel API Deployment Guide: From Fresh Installation to Production

## Overview
This guide documents the complete process of creating a fresh Laravel API, resolving CSRF token mismatch issues, and deploying to 1.com hosting with working authentication.

## Problem Solved
- **Original Issue**: `419 CSRF token mismatch` error on login endpoint
- **Root Cause**: Configuration conflicts in existing Laravel project
- **Solution**: Fresh Laravel installation with gradual file migration

---

## Step 1: Create Fresh Laravel Project

### 1.1 Create New Laravel Project
```bash
# Navigate to parent directory
cd /Users/daniel/Documents/development/projects/

# Create fresh Laravel project
composer create-project laravel/laravel fresh-laravel-api

# Navigate to project directory
cd fresh-laravel-api
```

### 1.2 Verify Fresh Installation
```bash
# Start development server
php artisan serve --port=8088

# Test basic functionality
curl -X GET http://localhost:8088/api/test -H "Accept: application/json" -H "Content-Type: application/json"
```

**Expected Response**: `{"message":"Fresh Laravel API is working!"}`

---

## Step 2: Configure API Routes

### 2.1 Update bootstrap/app.php
Ensure API routes are loaded in Laravel 12:
```php
// bootstrap/app.php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__."/../routes/web.php",
        api: __DIR__."/../routes/api.php",  // This line is crucial
        commands: __DIR__."/../routes/console.php",
        health: "/up",
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ... middleware configuration
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // ... exception configuration
    })->create();
```

### 2.2 Create Simple API Routes
```php
// routes/api.php
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthLoginController;
use App\Http\Controllers\AuthRegisterController;

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'Fresh Laravel API is working!']);
});

// Simple test login route (without Passport)
Route::post('/login-test', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    return response()->json([
        'message' => 'Login test endpoint reached successfully!',
        'email' => $credentials['email'],
        'csrf_working' => 'No CSRF token required for API routes!'
    ]);
});

// Auth routes
Route::post('/login', AuthLoginController::class);
Route::post('/register', AuthRegisterController::class);
```

---

## Step 3: Gradual File Migration

### 3.1 Copy User Model and Migration
```bash
# Copy User model
cp ../ComApi/app/Models/User.php app/Models/

# Copy migration (use the correct filename)
cp ../ComApi/database/migrations/0001_01_01_000000_create_users_table.php database/migrations/
```

### 3.2 Copy Auth Controllers
```bash
# Copy authentication controllers
cp ../ComApi/app/Http/Controllers/AuthLoginController.php app/Http/Controllers/
cp ../ComApi/app/Http/Controllers/AuthRegisterController.php app/Http/Controllers/
```

### 3.3 Copy Middleware Files
```bash
# Create middleware directory if it doesn't exist
mkdir -p app/Http/Middleware

# Copy middleware files
cp ../ComApi/app/Http/Middleware/DisableCsrfForApi.php app/Http/Middleware/
cp ../ComApi/app/Http/Middleware/ApiSecurityMiddleware.php app/Http/Middleware/
```

### 3.4 Copy Configuration Files
```bash
# Copy environment example
cp ../ComApi/.env.example .env.example

# Copy configuration files
cp ../ComApi/config/database.php config/
cp ../ComApi/config/auth.php config/
cp ../ComApi/config/passport.php config/
```

---

## Step 4: Install and Configure Laravel Passport

### 4.1 Install Passport
```bash
# Install Laravel Passport
composer require laravel/passport

# Update dependencies if composer.json was modified
composer update
```

### 4.2 Configure Passport
```bash
# Install Passport (skip migrations if OAuth tables already exist)
php artisan passport:install --force

# If duplicate migrations exist, remove them
rm database/migrations/2025_08_11_081345_create_oauth_auth_codes_table.php
rm database/migrations/2025_08_11_081346_create_oauth_access_tokens_table.php
rm database/migrations/2025_08_11_081347_create_oauth_refresh_tokens_table.php
rm database/migrations/2025_08_11_081348_create_oauth_clients_table.php
rm database/migrations/2025_08_11_081349_create_oauth_device_codes_table.php

# Create Passport client
php artisan passport:client --personal
# Name: communitysport
# Provider: users
```

### 4.3 Verify User Model Configuration
Ensure User model has the `HasApiTokens` trait:
```php
// app/Models/User.php
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    // ... rest of the model
}
```

---

## Step 5: Database Setup

### 5.1 Configure Environment
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 5.2 Run Migrations
```bash
# Run all migrations
php artisan migrate --force

# If duplicate OAuth migrations exist, remove them first
rm database/migrations/2025_08_11_082953_create_oauth_auth_codes_table.php
rm database/migrations/2025_08_11_082954_create_oauth_access_tokens_table.php
rm database/migrations/2025_08_11_082955_create_oauth_refresh_tokens_table.php
rm database/migrations/2025_08_11_082956_create_oauth_clients_table.php
rm database/migrations/2025_08_11_082957_create_oauth_device_codes_table.php
```

### 5.3 Create Test User
```bash
# Create test user via tinker
php artisan tinker --execute="App\Models\User::create(['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com', 'password' => bcrypt('password')]);"

# Verify email for testing
php artisan tinker --execute="App\Models\User::where('email', 'john@example.com')->update(['email_verified_at' => now()]);"
```

---

## Step 6: Test Local Functionality

### 6.1 Test Basic API
```bash
# Test endpoint
curl -X GET http://localhost:8088/api/test -H "Accept: application/json" -H "Content-Type: application/json"
```

### 6.2 Test Login Endpoint
```bash
# Test login with correct credentials
curl -X POST http://localhost:8088/api/login -H "Accept: application/json" -H "Content-Type: application/json" -d '{"email":"john@example.com","password":"password"}'
```

**Expected Response**: JSON with user data and access token

---

## Step 7: Deploy to 1.com Hosting

### 7.1 SSH Configuration
Ensure SSH key is configured for passwordless access:
```bash
# ~/.ssh/config
Host ogunkoya
  HostName ssh.ogunkoya.net
  User ogunkoya.net
  IdentityFile ~/.ssh/ogunkoya_key
  IdentitiesOnly yes
```

### 7.2 Deploy Files
```bash
# Deploy from local to server
rsync -avz --exclude='.git' --exclude='node_modules' --exclude='vendor' --exclude='storage/logs/*' --exclude='storage/framework/cache/*' --exclude='storage/framework/sessions/*' --exclude='storage/framework/views/*' --exclude='.env' . ogunkoya:~/fresh-laravel-api/
```

### 7.3 Install Dependencies on Server
```bash
# Install Composer dependencies
ssh ogunkoya "cd fresh-laravel-api && composer install --no-dev --optimize-autoloader"
```

### 7.4 Configure Environment on Server
```bash
# Set up environment file
ssh ogunkoya "cd fresh-laravel-api && cp .env.example .env && echo 'APP_KEY=base64:$(openssl rand -base64 32)' >> .env"

# Configure database settings
ssh ogunkoya "cd fresh-laravel-api && sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env && sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=ogunkoya.net.mysql/' .env && sed -i 's/# DB_PORT=3306/DB_PORT=3306/' .env && sed -i 's/# DB_DATABASE=ogunkoya_net/DB_DATABASE=ogunkoya_netcommunitysport/' .env && sed -i 's/# DB_USERNAME=ogunkoya_net/DB_USERNAME=ogunkoya_netcommunitysport/' .env && sed -i 's/# DB_PASSWORD=ogunkoya_net/DB_PASSWORD=communitysport/' .env"
```

### 7.5 Set Up Web Server Configuration
```bash
# Create public directory for web access
ssh ogunkoya "mkdir -p ~/fresh-test && cp -r fresh-laravel-api/public/* fresh-test/"

# Create .htaccess for redirection
ssh ogunkoya "echo 'RewriteEngine On' > fresh-test/.htaccess && echo 'RewriteCond %{REQUEST_FILENAME} !-d' >> fresh-test/.htaccess && echo 'RewriteCond %{REQUEST_FILENAME} !-f' >> fresh-test/.htaccess && echo 'RewriteRule ^ ../fresh-laravel-api/public/index.php [L]' >> fresh-test/.htaccess"

# Update index.php paths
ssh ogunkoya "sed -i 's|../vendor|../fresh-laravel-api/vendor|g' fresh-test/index.php && sed -i 's|../bootstrap|../fresh-laravel-api/bootstrap|g' fresh-test/index.php"
```

### 7.6 Alternative: Direct Deployment to Web Directory
```bash
# Remove existing broken installation
ssh ogunkoya "rm -rf ../httpd.www/fresh-api"

# Copy working installation to web directory
ssh ogunkoya "cp -r fresh-laravel-api ../httpd.www/fresh-api"
```

---

## Step 8: Database Setup on Server

### 8.1 Run Migrations
```bash
# Run migrations on server
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan migrate --force"
```

### 8.2 Install Passport on Server
```bash
# Install Passport clients
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan passport:client --personal"
# Name: communitysport
# Provider: users
```

### 8.3 Create Test User on Server
```bash
# Create test user
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan tinker --execute=\"App\Models\User::create(['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com', 'password' => bcrypt('password')]);\""

# Verify email
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan tinker --execute=\"App\Models\User::where('email', 'john@example.com')->update(['email_verified_at' => now()]);\""
```

---

## Step 9: Test Production Deployment

### 9.1 Test Basic API
```bash
# Test endpoint
curl -X GET https://ogunkoya.net/fresh-api/public/api/test -H "Accept: application/json" -H "Content-Type: application/json"
```

### 9.2 Test Login Endpoint
```bash
# Test login
curl -X POST https://ogunkoya.net/fresh-api/public/api/login -H "Accept: application/json" -H "Content-Type: application/json" -d '{"email":"john@example.com","password":"password"}'
```

**Expected Response**: JSON with user data and access token

---

## Step 10: Troubleshooting Common Issues

### 10.1 CSRF Token Mismatch
**Problem**: `419 CSRF token mismatch` error
**Solution**: 
- Ensure API routes are not being processed by web middleware
- Remove any conflicting routes in `routes/web.php`
- Verify `DisableCsrfForApi` middleware is applied

### 10.2 Route Not Found (404)
**Problem**: API routes returning 404
**Solution**:
- Check if API routes are loaded in `bootstrap/app.php`
- Clear route cache: `php artisan route:clear`
- Restart development server

### 10.3 Database Connection Issues
**Problem**: MySQL connection refused
**Solution**:
- Verify database credentials in `.env`
- Check if database host is correct (`ogunkoya.net.mysql`)
- Ensure database exists and user has permissions

### 10.4 Duplicate OAuth Migrations
**Problem**: `Table 'oauth_auth_codes' already exists`
**Solution**:
- Remove duplicate Passport migrations
- Run `php artisan passport:install --force` to create clients only

### 10.5 Web Server Configuration
**Problem**: 403/404 errors on production
**Solution**:
- Ensure `.htaccess` file exists and is properly configured
- Check file permissions
- Verify paths in `index.php` point to correct directories

---

## Key Success Factors

1. **Fresh Laravel Installation**: Start with clean slate to avoid configuration conflicts
2. **Gradual File Migration**: Copy files one by one and test after each step
3. **Proper API Route Configuration**: Ensure API routes are loaded and not mixed with web routes
4. **Correct Database Configuration**: Use proper MySQL credentials for 1.com hosting
5. **Passport Installation**: Install and configure Laravel Passport for token authentication
6. **Web Server Setup**: Proper `.htaccess` configuration for Laravel routing

---

## Production URLs

- **API Base URL**: `https://ogunkoya.net/fresh-api/public/api/`
- **Test Endpoint**: `https://ogunkoya.net/fresh-api/public/api/test`
- **Login Endpoint**: `https://ogunkoya.net/fresh-api/public/api/login`

---

## Database Configuration (1.com)

```env
DB_CONNECTION=mysql
DB_HOST=ogunkoya.net.mysql
DB_PORT=3306
DB_DATABASE=ogunkoya_netcommunitysport
DB_USERNAME=ogunkoya_netcommunitysport
DB_PASSWORD=communitysport
```

---

## Final Verification Checklist

- [ ] Fresh Laravel installation working locally
- [ ] API routes responding correctly
- [ ] User model and migrations copied
- [ ] Auth controllers working
- [ ] Passport installed and configured
- [ ] Database migrations completed
- [ ] Test user created and verified
- [ ] Files deployed to 1.com server
- [ ] Dependencies installed on server
- [ ] Environment configured correctly
- [ ] Database connected and working
- [ ] Production API responding
- [ ] Login endpoint working without CSRF errors
- [ ] Authentication tokens generated successfully

---

*This guide documents the successful resolution of CSRF token mismatch issues through a systematic approach of fresh installation and gradual migration.*
