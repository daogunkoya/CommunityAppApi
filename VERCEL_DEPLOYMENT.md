# Vercel Deployment Guide

## Issues Fixed

1. **Removed `/vendor` from `.vercelignore`** - Laravel needs the vendor directory to run
2. **Updated `vercel.json`** - Added proper build commands and framework specification
3. **Created `build.sh`** - Build script to handle Laravel setup during deployment

## Required Environment Variables

You need to set these environment variables in your Vercel project settings:

### Required Variables:
- `APP_KEY` - Laravel application key (32 character string)
- `APP_ENV` - Set to `production`
- `APP_DEBUG` - Set to `false`
- `APP_URL` - Your Vercel deployment URL
- `DB_CONNECTION` - Set to `sqlite` (recommended for Vercel)
- `DB_DATABASE` - Set to `database/database.sqlite`

### Optional Variables:
- `LOG_CHANNEL` - Set to `stack`
- `CACHE_DRIVER` - Set to `file`
- `SESSION_DRIVER` - Set to `file`
- `QUEUE_CONNECTION` - Set to `sync`

## Deployment Steps

1. **Push your changes to GitHub**
   ```bash
   git add .
   git commit -m "Fix Vercel deployment configuration"
   git push origin main
   ```

2. **Set up environment variables in Vercel:**
   - Go to your Vercel project dashboard
   - Navigate to Settings > Environment Variables
   - Add the required variables listed above

3. **Generate an APP_KEY:**
   - You can generate one using: `php artisan key:generate --show`
   - Or let the build script handle it automatically

4. **Deploy:**
   - Vercel will automatically deploy when you push to your connected GitHub repository

## Troubleshooting

### Common Issues:

1. **"Class not found" errors:**
   - Make sure `/vendor` is NOT in `.vercelignore`
   - Check that `composer install` runs successfully

2. **"APP_KEY not set" errors:**
   - Set the `APP_KEY` environment variable in Vercel
   - Or let the build script generate one

3. **Storage permission errors:**
   - The build script creates necessary directories
   - Vercel handles permissions automatically

4. **Database connection errors:**
   - Use SQLite for Vercel deployments
   - Set `DB_CONNECTION=sqlite` and `DB_DATABASE=database/database.sqlite`

### Debugging:

- Check Vercel deployment logs in the dashboard
- Look for build errors in the "Functions" tab
- Verify environment variables are set correctly

## File Structure for Vercel

```
/
├── api/
│   └── index.php          # Laravel entry point
├── app/                   # Laravel application
├── bootstrap/             # Laravel bootstrap
├── config/                # Laravel configuration
├── database/              # Database files
├── resources/             # Views and assets
├── routes/                # Route definitions
├── storage/               # Storage directory
├── vendor/                # Composer dependencies (NOT ignored)
├── vercel.json           # Vercel configuration
├── build.sh              # Build script
└── composer.json         # PHP dependencies
```

## Notes

- The `/vendor` directory is now included in deployment (removed from `.vercelignore`)
- The build script handles Laravel setup automatically
- SQLite is recommended for database on Vercel
- All Laravel caches are generated during build 
