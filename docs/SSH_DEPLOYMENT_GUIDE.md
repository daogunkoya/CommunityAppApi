# SSH Deployment Guide for 1.com Hosting

## Overview
This guide covers the complete SSH deployment process for Laravel applications to 1.com hosting, including SSH key setup, file transfer, server configuration, and troubleshooting.

---

## Step 1: SSH Key Setup

### 1.1 Generate SSH Key (if not exists)
```bash
# Generate SSH key pair
ssh-keygen -t rsa -b 4096 -C "your-email@example.com" -f ~/.ssh/ogunkoya_key

# Set proper permissions
chmod 600 ~/.ssh/ogunkoya_key
chmod 644 ~/.ssh/ogunkoya_key.pub
```

### 1.2 Add Public Key to 1.com Server
```bash
# Copy public key to clipboard (macOS)
pbcopy < ~/.ssh/ogunkoya_key.pub

# Or display for manual copy
cat ~/.ssh/ogunkoya_key.pub
```

**Add the public key to your 1.com hosting control panel:**
1. Log into your 1.com hosting control panel
2. Navigate to SSH/SFTP settings
3. Add the public key content
4. Save the configuration

### 1.3 Configure SSH Client
```bash
# Create/edit SSH config file
nano ~/.ssh/config

# Add the following configuration:
Host ogunkoya
  HostName ssh.ogunkoya.net
  User ogunkoya.net
  IdentityFile ~/.ssh/ogunkoya_key
  IdentitiesOnly yes
  Port 22
  ServerAliveInterval 60
  ServerAliveCountMax 3
```

### 1.4 Test SSH Connection
```bash
# Test connection
ssh ogunkoya "echo 'SSH connection successful!'"

# If successful, you should see: "SSH connection successful!"
```

---

## Step 2: Prepare Local Project for Deployment

### 2.1 Optimize for Production
```bash
# Navigate to your Laravel project
cd /Users/daniel/Documents/development/projects/fresh-laravel-api

# Install production dependencies
composer install --no-dev --optimize-autoloader

# Clear and cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set proper permissions (if on macOS/Linux)
chmod -R 755 storage bootstrap/cache
```

### 2.2 Create Deployment Script
```bash
# Create deployment script
nano deploy.sh
```

**Content for deploy.sh:**
```bash
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

# Step 1: Prepare local project
print_status "Preparing local project..."
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 2: Deploy files to server
print_status "Deploying files to server..."
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
    --exclude='DEPLOYMENT_GUIDE.md' \
    --exclude='QUICK_REFERENCE.md' \
    --exclude='SSH_DEPLOYMENT_GUIDE.md' \
    . ${REMOTE_HOST}:${REMOTE_PATH}/

# Step 3: Install dependencies on server
print_status "Installing dependencies on server..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && composer install --no-dev --optimize-autoloader"

# Step 4: Set up environment file
print_status "Setting up environment configuration..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && cp .env.example .env"

# Step 5: Configure database settings
print_status "Configuring database settings..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=ogunkoya.net.mysql/' .env"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && sed -i 's/# DB_PORT=3306/DB_PORT=3306/' .env"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && sed -i 's/# DB_DATABASE=ogunkoya_net/DB_DATABASE=ogunkoya_netcommunitysport/' .env"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && sed -i 's/# DB_USERNAME=ogunkoya_net/DB_USERNAME=ogunkoya_netcommunitysport/' .env"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && sed -i 's/# DB_PASSWORD=ogunkoya_net/DB_PASSWORD=communitysport/' .env"

# Step 6: Generate application key
print_status "Generating application key..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan key:generate"

# Step 7: Clear caches
print_status "Clearing application caches..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan config:clear"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan cache:clear"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan route:clear"
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan view:clear"

# Step 8: Run migrations
print_status "Running database migrations..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan migrate --force"

# Step 9: Install Passport (if needed)
print_status "Setting up Passport authentication..."
ssh ${REMOTE_HOST} "cd ${REMOTE_PATH} && php artisan passport:install --force"

# Step 10: Deploy to web directory
print_status "Deploying to web directory..."
ssh ${REMOTE_HOST} "rm -rf ${WEB_PATH} && cp -r ${REMOTE_PATH} ${WEB_PATH}"

# Step 11: Set proper permissions
print_status "Setting file permissions..."
ssh ${REMOTE_HOST} "chmod -R 755 ${WEB_PATH}/storage"
ssh ${REMOTE_HOST} "chmod -R 755 ${WEB_PATH}/bootstrap/cache"

print_status "✅ Deployment completed successfully!"
print_status "🌐 Your application is now live at: https://ogunkoya.net/fresh-api/public/"
print_status "🔗 API endpoint: https://ogunkoya.net/fresh-api/public/api/test"
```

### 2.3 Make Script Executable
```bash
chmod +x deploy.sh
```

---

## Step 3: Deploy to 1.com Server

### 3.1 Manual Deployment (Step by Step)
```bash
# Step 1: Deploy files
rsync -avz --delete \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='.env' \
    . ogunkoya:~/fresh-laravel-api/

# Step 2: Install dependencies on server
ssh ogunkoya "cd fresh-laravel-api && composer install --no-dev --optimize-autoloader"

# Step 3: Set up environment
ssh ogunkoya "cd fresh-laravel-api && cp .env.example .env"

# Step 4: Configure database
ssh ogunkoya "cd fresh-laravel-api && sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env && sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=ogunkoya.net.mysql/' .env && sed -i 's/# DB_DATABASE=ogunkoya_net/DB_DATABASE=ogunkoya_netcommunitysport/' .env && sed -i 's/# DB_USERNAME=ogunkoya_net/DB_USERNAME=ogunkoya_netcommunitysport/' .env && sed -i 's/# DB_PASSWORD=ogunkoya_net/DB_PASSWORD=communitysport/' .env"

# Step 5: Generate app key
ssh ogunkoya "cd fresh-laravel-api && php artisan key:generate"

# Step 6: Run migrations
ssh ogunkoya "cd fresh-laravel-api && php artisan migrate --force"

# Step 7: Install Passport
ssh ogunkoya "cd fresh-laravel-api && php artisan passport:install --force"

# Step 8: Deploy to web directory
ssh ogunkoya "rm -rf ../httpd.www/fresh-api && cp -r fresh-laravel-api ../httpd.www/fresh-api"
```

### 3.2 Automated Deployment (Using Script)
```bash
# Run the deployment script
./deploy.sh
```

---

## Step 4: Server Configuration

### 4.1 Directory Structure on 1.com
```
~/ (home directory)
├── fresh-laravel-api/          # Laravel application files
│   ├── app/
│   ├── config/
│   ├── database/
│   ├── public/
│   ├── routes/
│   ├── storage/
│   └── vendor/
└── ../httpd.www/               # Web-accessible directory
    └── fresh-api/              # Public web directory
        ├── public/             # Laravel public files
        ├── .htaccess           # Apache configuration
        └── index.php           # Entry point
```

### 4.2 Apache Configuration (.htaccess)
```apache
# /httpd.www/fresh-api/.htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ public/index.php [L]
```

### 4.3 PHP Configuration
```bash
# Check PHP version
ssh ogunkoya "php -v"

# Check PHP modules
ssh ogunkoya "php -m | grep -E '(pdo|mysql|openssl|mbstring|tokenizer|xml|ctype|json)'"
```

---

## Step 5: Database Setup

### 5.1 Create Database (if needed)
```bash
# Connect to MySQL (if you have access)
ssh ogunkoya "mysql -h ogunkoya.net.mysql -u ogunkoya_netcommunitysport -p"

# Or create via 1.com control panel
# 1. Log into 1.com control panel
# 2. Navigate to Databases
# 3. Create new MySQL database
# 4. Note down credentials
```

### 5.2 Database Credentials
```env
DB_CONNECTION=mysql
DB_HOST=ogunkoya.net.mysql
DB_PORT=3306
DB_DATABASE=ogunkoya_netcommunitysport
DB_USERNAME=ogunkoya_netcommunitysport
DB_PASSWORD=communitysport
```

### 5.3 Run Migrations
```bash
# Run migrations
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan migrate --force"

# Seed database (if needed)
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan db:seed --force"
```

---

## Step 6: Testing Deployment

### 6.1 Test Basic Functionality
```bash
# Test web server
curl -I https://ogunkoya.net/fresh-api/

# Test API endpoint
curl -X GET https://ogunkoya.net/fresh-api/public/api/test \
  -H "Accept: application/json" \
  -H "Content-Type: application/json"

# Test login endpoint
curl -X POST https://ogunkoya.net/fresh-api/public/api/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"password"}'
```

### 6.2 Check Server Logs
```bash
# Check Laravel logs
ssh ogunkoya "tail -f ../httpd.www/fresh-api/storage/logs/laravel.log"

# Check Apache error logs
ssh ogunkoya "tail -f /var/log/apache2/error.log"
```

---

## Step 7: Troubleshooting

### 7.1 Common SSH Issues

**Problem**: Permission denied (publickey)
```bash
# Solution: Check SSH key permissions
chmod 600 ~/.ssh/ogunkoya_key
chmod 644 ~/.ssh/ogunkoya_key.pub

# Test connection with verbose output
ssh -v ogunkoya
```

**Problem**: Connection timeout
```bash
# Solution: Check SSH config
# Add to ~/.ssh/config:
Host ogunkoya
  HostName ssh.ogunkoya.net
  User ogunkoya.net
  IdentityFile ~/.ssh/ogunkoya_key
  IdentitiesOnly yes
  Port 22
  ServerAliveInterval 60
  ServerAliveCountMax 3
```

### 7.2 File Permission Issues
```bash
# Fix storage permissions
ssh ogunkoya "chmod -R 755 ../httpd.www/fresh-api/storage"
ssh ogunkoya "chmod -R 755 ../httpd.www/fresh-api/bootstrap/cache"

# Fix ownership (if needed)
ssh ogunkoya "chown -R ogunkoya.net:ogunkoya.net ../httpd.www/fresh-api"
```

### 7.3 Database Connection Issues
```bash
# Test database connection
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan tinker --execute='DB::connection()->getPdo();'"

# Check environment variables
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan config:show database"
```

### 7.4 Application Errors
```bash
# Clear all caches
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan config:clear"
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan cache:clear"
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan route:clear"
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan view:clear"

# Regenerate autoloader
ssh ogunkoya "cd ../httpd.www/fresh-api && composer dump-autoload"
```

---

## Step 8: Maintenance and Updates

### 8.1 Update Application
```bash
# Pull latest changes (if using git)
git pull origin main

# Run deployment script
./deploy.sh
```

### 8.2 Backup Database
```bash
# Create database backup
ssh ogunkoya "mysqldump -h ogunkoya.net.mysql -u ogunkoya_netcommunitysport -p ogunkoya_netcommunitysport > backup_$(date +%Y%m%d_%H%M%S).sql"

# Download backup
scp ogunkoya:~/backup_*.sql ./
```

### 8.3 Monitor Application
```bash
# Check disk space
ssh ogunkoya "df -h"

# Check memory usage
ssh ogunkoya "free -h"

# Monitor Laravel logs
ssh ogunkoya "tail -f ../httpd.www/fresh-api/storage/logs/laravel.log"
```

---

## Step 9: Security Best Practices

### 9.1 File Permissions
```bash
# Secure file permissions
ssh ogunkoya "find ../httpd.www/fresh-api -type f -exec chmod 644 {} \;"
ssh ogunkoya "find ../httpd.www/fresh-api -type d -exec chmod 755 {} \;"
ssh ogunkoya "chmod -R 775 ../httpd.www/fresh-api/storage"
ssh ogunkoya "chmod -R 775 ../httpd.www/fresh-api/bootstrap/cache"
```

### 9.2 Environment Security
```bash
# Ensure .env is not accessible via web
ssh ogunkoya "echo 'Deny from all' > ../httpd.www/fresh-api/.env"
```

### 9.3 SSL Configuration
```bash
# Force HTTPS redirect (add to .htaccess)
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Quick Commands Reference

### Deployment Commands
```bash
# Quick deploy
./deploy.sh

# Manual deploy
rsync -avz --delete --exclude='.git' --exclude='vendor' --exclude='.env' . ogunkoya:~/fresh-laravel-api/

# Update dependencies
ssh ogunkoya "cd fresh-laravel-api && composer install --no-dev --optimize-autoloader"

# Deploy to web
ssh ogunkoya "cp -r fresh-laravel-api ../httpd.www/fresh-api"
```

### Maintenance Commands
```bash
# Clear caches
ssh ogunkoya "cd ../httpd.www/fresh-api && php artisan config:clear && php artisan cache:clear"

# Check logs
ssh ogunkoya "tail -f ../httpd.www/fresh-api/storage/logs/laravel.log"

# Test API
curl -X GET https://ogunkoya.net/fresh-api/public/api/test
```

---

## Production URLs

- **Application**: `https://ogunkoya.net/fresh-api/`
- **API Base**: `https://ogunkoya.net/fresh-api/public/api/`
- **Test Endpoint**: `https://ogunkoya.net/fresh-api/public/api/test`
- **Login Endpoint**: `https://ogunkoya.net/fresh-api/public/api/login`

---

*This guide provides a complete SSH deployment workflow for Laravel applications on 1.com hosting, ensuring secure and efficient deployments.*
