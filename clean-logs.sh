#!/bin/bash

# Clean Old Laravel Log Files
# This script removes log files older than LOG_DAILY_DAYS (default: 30 days)

DAYS_TO_KEEP=${1:-30}
LOG_DIR="storage/logs"

echo "🧹 Cleaning old Laravel log files..."
echo "📅 Keeping logs for the last ${DAYS_TO_KEEP} days"
echo "📁 Log directory: ${LOG_DIR}"
echo "----------------------------------------"

# Find and remove old log files
find "$LOG_DIR" -name "laravel-*.log" -type f -mtime +$DAYS_TO_KEEP -delete

echo "✅ Cleanup completed!"
echo "📊 Current log files:"
ls -la "$LOG_DIR"/laravel-*.log 2>/dev/null || echo "No log files found"
