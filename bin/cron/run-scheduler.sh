#!/usr/bin/env bash
# Run the Symfony scheduler worker (iCal sync at 06:00, booking reminders hourly).
# Add to crontab — runs every minute, exits after ~55s so jobs don't overlap:
#
#   * * * * * /Users/tdb/Sites/Domo/bin/cron/run-scheduler.sh >> /tmp/domoxango-scheduler.log 2>&1
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

exec php bin/console messenger:consume scheduler_default \
    --time-limit=55 \
    --memory-limit=128M \
    --no-interaction
