# Git to 1.com Deployment Workflow

## Overview
This guide explains how to reconcile Git changes with your 1.com hosting deployment. There are multiple approaches depending on your workflow preferences.

---

## 🔄 **Deployment Options**

### **Option 1: Git-Based Deployment (Recommended)**
Pull changes directly from Git repository on the server.

### **Option 2: Local Push Deployment**
Push local changes to server using rsync.

### **Option 3: Hybrid Approach**
Use Git for version control, rsync for deployment.

---

## 🚀 **Option 1: Git-Based Deployment**

### **Step 1: Initialize Git Repository**
```bash
# Initialize Git in your local project
git init
git add .
git commit -m "Initial commit: Fresh Laravel API with CSRF fix"

# Add remote repository (create on GitHub/GitLab first)
git remote add origin https://github.com/yourusername/fresh-laravel-api.git
git push -u origin main
```

### **Step 2: Set Up Git on 1.com Server**
```bash
# SSH into your server
ssh ogunkoya

# Clone the repository
cd ~
git clone https://github.com/yourusername/fresh-laravel-api.git fresh-laravel-api
cd fresh-laravel-api

# Set up environment
cp .env.example .env
php artisan key:generate

# Configure database
sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
sed -i 's/# DB_HOST=127.0.0.1/DB_HOST=ogunkoya.net.mysql/' .env
sed -i 's/# DB_DATABASE=ogunkoya_net/DB_DATABASE=ogunkoya_netcommunitysport/' .env
sed -i 's/# DB_USERNAME=ogunkoya_net/DB_USERNAME=ogunkoya_netcommunitysport/' .env
sed -i 's/# DB_PASSWORD=ogunkoya_net/DB_PASSWORD=communitysport/' .env

# Install dependencies and set up
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan passport:install --force

# Deploy to web directory
cp -r ~/fresh-laravel-api ../httpd.www/fresh-api
```

### **Step 3: Use Git-Based Deployment Script**
```bash
# Update the repository URL in deploy-git.sh
nano deploy-git.sh
# Change: GIT_REPO="https://github.com/yourusername/fresh-laravel-api.git"

# Deploy from specific branch
./deploy-git.sh main

# Or deploy from different branch
./deploy-git.sh develop
```

### **Step 4: Workflow for Changes**
```bash
# 1. Make changes locally
# 2. Commit and push to Git
git add .
git commit -m "Add new feature"
git push origin main

# 3. Deploy to production
./deploy-git.sh main
```

---

## 📤 **Option 2: Local Push Deployment**

### **Step 1: Use Existing deploy.sh Script**
```bash
# Make changes locally
# Test locally
php artisan serve --port=8088

# Deploy using existing script
./deploy.sh
```

### **Step 2: Git Workflow with Local Deployment**
```bash
# 1. Make changes and test locally
git add .
git commit -m "Add new feature"
git push origin main

# 2. Deploy to production
./deploy.sh
```

---

## 🔀 **Option 3: Hybrid Approach**

### **Step 1: Set Up Both Systems**
```bash
# Local development with Git
git init
git remote add origin https://github.com/yourusername/fresh-laravel-api.git

# Server with Git clone
ssh ogunkoya "cd ~ && git clone https://github.com/yourusername/fresh-laravel-api.git fresh-laravel-api"
```

### **Step 2: Choose Deployment Method**
```bash
# For quick fixes: Use rsync (deploy.sh)
./deploy.sh

# For major updates: Use Git (deploy-git.sh)
./deploy-git.sh main
```

---

## 📋 **Complete Git Workflow**

### **Daily Development Workflow**
```bash
# 1. Start development
git checkout -b feature/new-feature
# Make changes...

# 2. Test locally
php artisan serve --port=8088
curl -X GET http://localhost:8088/api/test

# 3. Commit changes
git add .
git commit -m "Add new feature"

# 4. Push to repository
git push origin feature/new-feature

# 5. Create pull request (if using GitHub/GitLab)
# Merge to main branch

# 6. Deploy to production
./deploy-git.sh main
```

### **Hotfix Workflow**
```bash
# 1. Create hotfix branch
git checkout -b hotfix/critical-fix
# Make urgent fix...

# 2. Test quickly
php artisan serve --port=8088

# 3. Deploy immediately (bypass Git for speed)
./deploy.sh

# 4. Commit and push later
git add .
git commit -m "Hotfix: critical issue"
git push origin hotfix/critical-fix
```

---

## 🔧 **Configuration Files**

### **Update deploy-git.sh Configuration**
```bash
# Edit the script to match your repository
GIT_REPO="https://github.com/yourusername/fresh-laravel-api.git"
REMOTE_HOST="ogunkoya"
REMOTE_PATH="~/fresh-laravel-api"
WEB_PATH="../httpd.www/fresh-api"
```

### **Git Ignore File (.gitignore)**
```gitignore
/node_modules
/public/hot
/public/storage
/storage/*.key
/vendor
.env
.env.backup
.phpunit.result.cache
docker-compose.override.yml
Homestead.json
Homestead.yaml
npm-debug.log
yarn-error.log
/.idea
/.vscode
deploy.sh
deploy-git.sh
docs/
```

---

## 🚨 **Important Considerations**

### **Environment Files**
```bash
# Never commit .env files
echo ".env" >> .gitignore
echo ".env.local" >> .gitignore
echo ".env.production" >> .gitignore

# Use .env.example for templates
cp .env .env.example
# Remove sensitive data from .env.example
```

### **Database Migrations**
```bash
# Always commit migrations
git add database/migrations/
git commit -m "Add new migration"

# Run migrations on server
ssh ogunkoya "cd ~/fresh-laravel-api && php artisan migrate --force"
```

### **Dependencies**
```bash
# Commit composer.json and composer.lock
git add composer.json composer.lock
git commit -m "Update dependencies"

# Install on server
ssh ogunkoya "cd ~/fresh-laravel-api && composer install --no-dev --optimize-autoloader"
```

---

## 🔄 **Deployment Commands Summary**

### **Git-Based Deployment**
```bash
# Deploy from main branch
./deploy-git.sh main

# Deploy from specific branch
./deploy-git.sh develop

# Deploy from specific commit
ssh ogunkoya "cd ~/fresh-laravel-api && git reset --hard <commit-hash>"
```

### **Local Push Deployment**
```bash
# Deploy current local state
./deploy.sh

# Deploy specific files only
rsync -avz app/Http/Controllers/ ogunkoya:~/fresh-laravel-api/app/Http/Controllers/
```

### **Manual Deployment**
```bash
# SSH into server and pull manually
ssh ogunkoya
cd ~/fresh-laravel-api
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
cp -r ~/fresh-laravel-api ../httpd.www/fresh-api
```

---

## 📊 **Deployment Comparison**

| Method | Speed | Reliability | Rollback | Complexity |
|--------|-------|-------------|----------|------------|
| **Git-Based** | Medium | High | Easy | Low |
| **Local Push** | Fast | Medium | Hard | Low |
| **Hybrid** | Variable | High | Easy | Medium |

---

## 🎯 **Recommended Workflow**

1. **Use Git for version control** - Always commit changes
2. **Use Git-based deployment for major updates** - `./deploy-git.sh main`
3. **Use local push for quick fixes** - `./deploy.sh`
4. **Test locally before deploying** - Always test on localhost first
5. **Monitor logs after deployment** - Check for errors

---

## 🚀 **Quick Start Commands**

```bash
# Initialize Git repository
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/yourusername/fresh-laravel-api.git
git push -u origin main

# Set up server
ssh ogunkoya "cd ~ && git clone https://github.com/yourusername/fresh-laravel-api.git fresh-laravel-api"

# Deploy
./deploy-git.sh main

# Future updates
git add . && git commit -m "Update" && git push origin main
./deploy-git.sh main
```

---

*This workflow ensures your Git repository stays in sync with your 1.com hosting while providing flexible deployment options.*
