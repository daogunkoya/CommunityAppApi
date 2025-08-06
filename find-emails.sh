#!/bin/bash

# Find Email Content in Laravel Logs
# This script searches for email content in log files

echo "🔍 Searching for email content in Laravel logs..."
echo "================================================"

# Search in daily logs
echo "📅 Checking daily log files..."
for log_file in storage/logs/laravel-*.log; do
    if [ -f "$log_file" ]; then
        echo "📁 Checking: $(basename "$log_file")"
        if grep -q -i "email\|mail\|verification\|notification" "$log_file"; then
            echo "✅ Found email-related content in $(basename "$log_file"):"
            grep -i "email\|mail\|verification\|notification" "$log_file" | head -5
            echo ""
        else
            echo "❌ No email content found"
        fi
    fi
done

# Search in main log file
echo "📄 Checking main log file..."
if [ -f "storage/logs/laravel.log" ]; then
    if grep -q -i "email\|mail\|verification\|notification" storage/logs/laravel.log; then
        echo "✅ Found email-related content in laravel.log:"
        grep -i "email\|mail\|verification\|notification" storage/logs/laravel.log | tail -10
    else
        echo "❌ No email content found in main log"
    fi
else
    echo "❌ Main log file not found"
fi

echo ""
echo "💡 Tips:"
echo "   - Emails are logged when MAIL_MAILER=log"
echo "   - Check for 'EmailVerificationNotification' in logs"
echo "   - Look for 'test-registration@example.com' or similar"
echo "   - Email content should appear as JSON or text in logs"
