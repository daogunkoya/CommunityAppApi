# 🗄️ Database Seeding Guide for MatchGrinder

## 📋 Overview

This guide provides comprehensive instructions for seeding the MatchGrinder database with realistic test data in both local and production environments.

## 🎯 What Gets Seeded

### Core Data Models
- **Users**: 22 users (including test user)
- **Game Types**: 15 sports (Basketball, Football, Tennis, etc.)
- **Communities**: 5 sports communities
- **Game Events**: 30 upcoming events
- **Discussions**: 25 sports-related discussions
- **Comments**: 120+ comments on discussions
- **Likes**: 180+ likes on discussions and comments
- **Tournaments**: 5 competitive tournaments
- **Conversations**: 20 chat conversations
- **Messages**: 200+ messages in conversations
- **User Skill Levels**: Skill ratings for users across sports
- **User Preferred Facilities**: Facility preferences for users

### Test User Credentials
- **Email**: `test@example.com`
- **Password**: `password123`

## 🚀 Local Development Seeding

### Quick Start
```bash
cd CommunityNetworkApi
./seed-local-simple.sh
```

### Manual Seeding
```bash
cd CommunityNetworkApi
php artisan db:seed
```

### What the Local Script Does
1. ✅ Checks current database status
2. 🧹 Clears existing data (with SQLite compatibility)
3. 🌱 Runs comprehensive seeding
4. 📊 Shows final data counts
5. 🔑 Provides test credentials

## 🌐 Production Seeding

### Quick Start
```bash
cd CommunityNetworkApi
./deploy-seed-production.sh
```

### What the Production Script Does
1. 🔐 Connects to production server via SSH
2. 📊 Shows current database status
3. 🧹 Clears existing data (MySQL compatible)
4. 🌱 Runs comprehensive seeding
5. 📊 Shows final data counts
6. 🔑 Provides test credentials

## 📊 Data Verification

### Check Data Counts
```bash
# Local
php artisan tinker --execute="echo 'Users: ' . App\Models\User::count(); echo 'Game Events: ' . App\Models\GameEvent::count(); echo 'Discussions: ' . App\Models\Discussion::count(); echo 'Tournaments: ' . App\Models\Tournament::count(); echo 'Conversations: ' . App\Models\Conversation::count();"

# Production (via SSH)
ssh c9k0rtix9_ssh@ssh.c9k0rtix9.service.one "cd /customers/f/b/1/c9k0rtix9/webroots/c91f3683/api/current && php artisan tinker --execute=\"echo 'Users: ' . App\Models\User::count(); echo 'Game Events: ' . App\Models\GameEvent::count();\""
```

### Expected Data Counts
- **Users**: 22
- **Game Types**: 15
- **Communities**: 5
- **Game Events**: 30
- **Discussions**: 25
- **Comments**: 120+
- **Likes**: 180+
- **Tournaments**: 5
- **Conversations**: 20
- **Messages**: 200+

## 🔧 Troubleshooting

### Common Issues

#### 1. SQLite Foreign Key Error
**Error**: `SQLSTATE[HY000]: General error: 1 near "SET": syntax error`
**Solution**: The local script handles this automatically by using SQLite-compatible commands.

#### 2. Faker Not Found
**Error**: `Class "Faker\Factory" not found`
**Solution**: Install faker package:
```bash
composer require fakerphp/faker --dev
```

#### 3. Permission Denied
**Error**: `Permission denied` on script execution
**Solution**: Make scripts executable:
```bash
chmod +x seed-local-simple.sh deploy-seed-production.sh
```

#### 4. SSH Connection Issues
**Error**: SSH connection fails
**Solution**: Check SSH credentials and server availability.

### Database Compatibility

#### Local (SQLite)
- Uses SQLite-compatible foreign key handling
- No `SET FOREIGN_KEY_CHECKS` commands
- Works with Laravel's default SQLite setup

#### Production (MySQL)
- Uses MySQL foreign key constraints
- Proper `SET FOREIGN_KEY_CHECKS` commands
- Optimized for production MySQL database

## 📁 File Structure

```
CommunityNetworkApi/
├── database/
│   ├── seeders/
│   │   ├── DatabaseSeeder.php          # Main seeder (working)
│   │   ├── MasterSeeder.php            # Comprehensive seeder (advanced)
│   │   ├── TestUsersSeeder.php         # Test user seeder
│   │   ├── GameEventSeeder.php         # Game events seeder
│   │   ├── DiscussionSeeder.php        # Discussions seeder
│   │   ├── TournamentSeeder.php        # Tournaments seeder
│   │   └── ConversationsSeeder.php     # Conversations seeder
│   └── factories/                      # Model factories
├── seed-local-simple.sh               # Local seeding script
├── seed-local.sh                      # Advanced local seeding script
├── deploy-seed-production.sh          # Production seeding script
└── DATABASE_SEEDING_GUIDE.md          # This guide
```

## 🎮 Testing the Seeded Data

### 1. Local Testing
```bash
# Start local servers
cd CommunityNetworkApi && php artisan serve --host=localhost --port=8001 &
cd ../CommunityNetworkWeb && npm run dev &

# Test login
curl -X POST http://localhost:8001/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

### 2. Production Testing
```bash
# Test production login
curl -X POST https://matchgrinder.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'
```

### 3. Frontend Testing
- Visit: http://localhost:8080 (local) or https://matchgrinder.com (production)
- Login with: `test@example.com` / `password123`
- Verify all sections show data:
  - Games/Events
  - Discussions
  - Tournaments
  - Messages

## 🔄 Reseeding

### When to Reseed
- After database migrations
- When data becomes stale
- Before demos or presentations
- After major schema changes

### Reseeding Commands
```bash
# Local
./seed-local-simple.sh

# Production
./deploy-seed-production.sh
```

## 📈 Data Quality

### Realistic Data Features
- **Geographic Diversity**: Users from different UK cities
- **Sport Variety**: 15 different sports with realistic descriptions
- **Skill Levels**: Mixed skill levels (beginner, intermediate, advanced)
- **Time Distribution**: Events spread across future dates
- **Social Interactions**: Realistic likes, comments, and conversations
- **Community Building**: Users with skill levels and facility preferences

### Data Relationships
- Users participate in game events
- Users create discussions and comments
- Users like discussions and comments
- Users join conversations and send messages
- Users have skill levels in different sports
- Users have preferred facilities

## 🛡️ Security Notes

### Production Considerations
- Test user credentials are for development only
- Production should have proper user registration
- Sensitive data is not included in seeders
- All passwords are hashed properly

### Data Privacy
- No real personal information in seed data
- All names and emails are fictional
- Profile pictures use UI Avatars service
- Phone numbers are fake

## 📞 Support

### Getting Help
1. Check this guide first
2. Review error messages carefully
3. Verify database connection
4. Check file permissions
5. Ensure all dependencies are installed

### Useful Commands
```bash
# Check database connection
php artisan tinker --execute="echo 'DB connected: ' . (DB::connection()->getPdo() ? 'Yes' : 'No');"

# Clear cache
php artisan cache:clear
php artisan config:clear

# Check migrations
php artisan migrate:status

# Reset database (DANGER: destroys all data)
php artisan migrate:fresh --seed
```

---

**🎉 Happy Seeding!** Your MatchGrinder database is now populated with comprehensive, realistic test data for both development and production environments.


