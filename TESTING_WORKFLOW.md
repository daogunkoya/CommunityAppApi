# Testing Workflow for API Changes

## 🎯 Goal: Ensure All Tests Pass Before Any Changes

### 📋 Pre-Change Checklist

**ALWAYS run tests before making any changes:**

```bash
# Run all tests to establish baseline
php artisan test

# Or use the test runner script
./run-tests.sh
```

**Expected Result:** All tests should pass (45 passed, 1 skipped)

---

### 🔄 Development Workflow

#### 1. **Before Making Changes**
```bash
# ✅ Run full test suite
php artisan test

# ✅ Check current status
git status
```

#### 2. **During Development**
```bash
# ✅ Run tests after each significant change
php artisan test

# ✅ Run specific test suites
./vendor/bin/pest tests/Feature/ProfileTest.php
./vendor/bin/pest tests/Feature/GamesTest.php

# ✅ Run with verbose output
./vendor/bin/pest --verbose
```

#### 3. **Before Committing**
```bash
# ✅ Run all tests one final time
php artisan test

# ✅ Check for any linting issues
php artisan test --stop-on-failure

# ✅ Only commit if ALL tests pass
git add .
git commit -m "Your descriptive commit message"
```

---

### 🧪 Test Categories

#### **Core API Tests (Must Always Pass)**
- **Profile Tests**: 11 tests covering user profile functionality
- **Games Tests**: 13 tests covering game events and management
- **GameEventController Test**: API endpoint testing

#### **Supporting Tests**
- Authentication tests
- Email verification tests
- Password management tests
- Home controller tests

---

### 🚨 Failure Scenarios

#### **If Tests Fail:**
1. **STOP** - Don't commit changes
2. **Investigate** - Check what broke
3. **Fix** - Update code or tests as needed
4. **Re-run** - Ensure all tests pass
5. **Commit** - Only after all tests pass

#### **Common Issues:**
- Database constraint violations
- Missing columns in factories
- Enum value mismatches
- Authentication issues

---

### 📊 Test Coverage

#### **Profile Functionality:**
- ✅ User model attributes
- ✅ Profile updates
- ✅ File uploads (file & base64)
- ✅ Email validation
- ✅ File size/type validation
- ✅ Email verification status
- ✅ Null value handling

#### **Games Functionality:**
- ✅ Game type attributes
- ✅ Game event creation
- ✅ Participant management
- ✅ Filtering (sport, date, location)
- ✅ Skill level handling
- ✅ Venue booking features
- ✅ Data validation

---

### 🛠️ Quick Commands

```bash
# Run all tests
php artisan test

# Run specific test file
./vendor/bin/pest tests/Feature/ProfileTest.php

# Run with coverage (if available)
./vendor/bin/pest --coverage

# Run tests in parallel (faster)
./vendor/bin/pest --parallel

# Run only failing tests
./vendor/bin/pest --failed
```

---

### 📝 Best Practices

1. **Test First**: Run tests before making any changes
2. **Small Changes**: Make incremental changes and test frequently
3. **Descriptive Names**: Use clear test names that explain what's being tested
4. **Isolation**: Each test should be independent
5. **Documentation**: Update this workflow as needed

---

### 🔍 Troubleshooting

#### **Database Issues:**
```bash
# Reset test database
php artisan migrate:fresh --seed

# Clear cache
php artisan config:clear
php artisan cache:clear
```

#### **Factory Issues:**
- Check if database columns exist
- Verify enum values match database
- Ensure relationships are properly defined

#### **Authentication Issues:**
- Check Passport configuration
- Verify middleware setup
- Test with proper tokens

---

### ✅ Success Criteria

**Before any commit, ensure:**
- [ ] All tests pass (45 passed, 1 skipped)
- [ ] No new test failures
- [ ] Code follows project standards
- [ ] Changes are properly documented

**Remember:** Tests are your safety net. They protect against regressions and ensure code quality as you scale.

---

### 🚀 Scaling Tips

1. **Automated Testing**: Consider CI/CD pipeline
2. **Test Data**: Use factories for consistent test data
3. **Performance**: Monitor test execution time
4. **Coverage**: Aim for high test coverage
5. **Maintenance**: Keep tests updated with code changes

---

*Last Updated: $(date)*
*Test Status: 45 passed, 1 skipped* 
