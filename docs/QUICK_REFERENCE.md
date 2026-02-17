# Quick Reference: CSRF Issue Resolution

## Problem
`419 CSRF token mismatch` error on Laravel API login endpoint

## Solution Summary
Fresh Laravel installation + gradual file migration + proper API route configuration

## Key Commands

### 1. Create Fresh Laravel Project
```bash
composer create-project laravel/laravel fresh-laravel-api
cd fresh-laravel-api
php artisan serve --port=8088
```

### 2. Essential File Copies
```bash
# User model and migration
cp ../ComApi/app/Models/User.php app/Models/
cp ../ComApi/database/migrations/0001_01_01_000000_create_users_table.php database/migrations/

# Auth controllers
cp ../ComApi/app/Http/Controllers/AuthLoginController.php app/Http/Controllers/
cp ../ComApi/app/Http/Controllers/AuthRegisterController.php app/Http/Controllers/

# Middleware
mkdir -p app/Http/Middleware
cp ../ComApi/app/Http/Middleware/DisableCsrfForApi.php app/Http/Middleware/
cp ../ComApi/app/Http/Middleware/ApiSecurityMiddleware.php app/Http/Middleware/

# Config files
cp ../ComApi/config/database.php config/
cp ../ComApi/config/auth.php config/
cp ../ComApi/config/passport.php config/
```

### 3. Install Passport
```bash
composer require laravel/passport
composer update
php artisan passport:install --force
php artisan passport:client --personal
# Name: communitysport
# Provider: users
```

### 4. Database Setup
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --force

# Create test user
php artisan tinker --execute="App\Models\User::create(['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com', 'password' => bcrypt('password')]);"
php artisan tinker --execute="App\Models\User::where('email', 'john@example.com')->update(['email_verified_at' => now()]);"
```

### 5. Deploy to 1.com
```bash
# Deploy files
rsync -avz --exclude='.git' --exclude='node_modules' --exclude='vendor' --exclude='storage/logs/*' --exclude='storage/framework/cache/*' --exclude='storage/framework/sessions/*' --exclude='storage/framework/views/*' --exclude='.env' . ogunkoya:~/fresh-laravel-api/

# Install dependencies
ssh ogunkoya "cd fresh-laravel-api && composer install --no-dev --optimize-autoloader"

# Configure environment
ssh ogunkoya "cd fresh-laravel-api && cp .env.example .env && echo 'APP_KEY=base64:$(openssl rand -base64 32)' >> .env"

# Set database config
ssh ogunkoya "cd fresh-laravel-api && sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env && sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=ogunkoya.net.mysql/' .env && sed -i 's/# DB_DATABASE=ogunkoya_net/DB_DATABASE=ogunkoya_netcommunitysport/' .env && sed -i 's/# DB_USERNAME=ogunkoya_net/DB_USERNAME=ogunkoya_netcommunitysport/' .env && sed -i 's/# DB_PASSWORD=ogunkoya_net/DB_PASSWORD=communitysport/' .env"

# Deploy to web directory
ssh ogunkoya "rm -rf ../httpd.www/fresh-api && cp -r fresh-laravel-api ../httpd.www/fresh-api"

# Setup database
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan migrate --force && php artisan passport:client --personal"
```

## Critical Configuration Files

### bootstrap/app.php
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__."/../routes/web.php",
        api: __DIR__."/../routes/api.php",  // CRITICAL: This line loads API routes
        commands: __DIR__."/../routes/console.php",
        health: "/up",
    )
    // ... rest of configuration
```

### routes/api.php
```php
<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthLoginController;
use App\Http\Controllers\AuthRegisterController;

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'Fresh Laravel API is working!']);
});

// Auth routes
Route::post('/login', AuthLoginController::class);
Route::post('/register', AuthRegisterController::class);
```

### .env (Production)
```env
DB_CONNECTION=mysql
DB_HOST=ogunkoya.net.mysql
DB_PORT=3306
DB_DATABASE=ogunkoya_netcommunitysport
DB_USERNAME=ogunkoya_netcommunitysport
DB_PASSWORD=communitysport
```

## Test Commands

### Local Testing
```bash
# Test basic API
curl -X GET http://localhost:8088/api/test -H "Accept: application/json" -H "Content-Type: application/json"

# Test login
curl -X POST http://localhost:8088/api/login -H "Accept: application/json" -H "Content-Type: application/json" -d '{"email":"john@example.com","password":"password"}'
```

### Production Testing
```bash
# Test basic API
curl -X GET https://ogunkoya.net/fresh-api/public/api/test -H "Accept: application/json" -H "Content-Type: application/json"

# Test login
curl -X POST https://ogunkoya.net/fresh-api/public/api/login -H "Accept: application/json" -H "Content-Type: application/json" -d '{"email":"john@example.com","password":"password"}'
```

## Key Success Factors

1. **Fresh Laravel Installation** - Avoid configuration conflicts
2. **API Routes in bootstrap/app.php** - Ensure API routes are loaded
3. **No Conflicting Web Routes** - Remove any `/api/login` routes from `routes/web.php`
4. **Proper Database Configuration** - Use correct 1.com MySQL credentials
5. **Passport Installation** - For token-based authentication
6. **Gradual File Migration** - Copy files one by one and test

## Production URLs
- **API Base**: `https://ogunkoya.net/fresh-api/public/api/`
- **Test**: `https://ogunkoya.net/fresh-api/public/api/test`
- **Login**: `https://ogunkoya.net/fresh-api/public/api/login`

## SSH Configuration
```bash
# ~/.ssh/config
Host ogunkoya
  HostName ssh.ogunkoya.net
  User ogunkoya.net
  IdentityFile ~/.ssh/ogunkoya_key
  IdentitiesOnly yes
```

---
*This quick reference captures the essential commands and configurations that successfully resolved the CSRF token mismatch issue.*
