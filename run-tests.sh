#!/bin/bash

echo "🧪 Running API Tests with Pest..."
echo "================================"

# Run feature tests
echo "📋 Running Feature Tests..."
./vendor/bin/pest tests/Feature/ProfileTest.php
PROFILE_FEATURE_RESULT=$?

./vendor/bin/pest tests/Feature/GamesTest.php
GAMES_FEATURE_RESULT=$?

# Run unit tests
echo "🔬 Running Unit Tests..."
./vendor/bin/pest tests/Unit/ProfileControllerTest.php
PROFILE_UNIT_RESULT=$?

# Run all tests
echo "🚀 Running All Tests..."
./vendor/bin/pest
ALL_TESTS_RESULT=$?

echo "================================"
echo "📊 Test Results Summary:"
echo "Profile Feature Tests: $([ $PROFILE_FEATURE_RESULT -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo "Games Feature Tests: $([ $GAMES_FEATURE_RESULT -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo "Profile Unit Tests: $([ $PROFILE_UNIT_RESULT -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo "All Tests: $([ $ALL_TESTS_RESULT -eq 0 ] && echo "✅ PASSED" || echo "❌ FAILED")"
echo "================================"

# Exit with failure if any test failed
if [ $PROFILE_FEATURE_RESULT -ne 0 ] || [ $GAMES_FEATURE_RESULT -ne 0 ] || [ $PROFILE_UNIT_RESULT -ne 0 ] || [ $ALL_TESTS_RESULT -ne 0 ]; then
    echo "❌ Some tests failed. Please fix the issues before proceeding."
    exit 1
else
    echo "✅ All tests passed! Safe to proceed with changes."
    exit 0
fi
