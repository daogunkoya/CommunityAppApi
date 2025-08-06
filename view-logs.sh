#!/bin/bash

# View Laravel Logs by Date
# Usage: ./view-logs.sh [date] [lines]
# Examples:
#   ./view-logs.sh                    # View today's logs
#   ./view-logs.sh 2025-08-05        # View specific date
#   ./view-logs.sh 2025-08-05 50     # View last 50 lines of specific date

DATE=${1:-$(date +%Y-%m-%d)}
LINES=${2:-50}
LOG_FILE="storage/logs/laravel-${DATE}.log"

echo "📋 Viewing Laravel logs for: ${DATE}"
echo "📁 Log file: ${LOG_FILE}"
echo "📊 Showing last ${LINES} lines"
echo "----------------------------------------"

if [ -f "$LOG_FILE" ]; then
    tail -n $LINES "$LOG_FILE"
else
    echo "❌ No log file found for ${DATE}"
    echo "Available log files:"
    ls -la storage/logs/laravel-*.log 2>/dev/null || echo "No log files found"
fi
