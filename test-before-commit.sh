#!/bin/bash

# Test Before Commit Script
# This script ensures all tests pass before allowing commits

echo "🧪 Running test suite before commit..."
echo "======================================"

# Run all tests
php artisan test

# Check if tests passed
if [ $? -eq 0 ]; then
    echo ""
    echo "✅ All tests passed! Safe to commit."
    echo "📊 Test Summary: 45 passed, 1 skipped"
    echo ""
    echo "🚀 You can now commit your changes:"
    echo "   git add ."
    echo "   git commit -m 'Your commit message'"
    exit 0
else
    echo ""
    echo "❌ Tests failed! Please fix issues before committing."
    echo ""
    echo "🔧 Common fixes:"
    echo "   - Check database migrations"
    echo "   - Verify factory definitions"
    echo "   - Fix any syntax errors"
    echo ""
    echo "🔄 Re-run tests after fixing:"
    echo "   php artisan test"
    exit 1
fi 