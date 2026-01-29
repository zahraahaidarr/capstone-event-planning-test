#!/usr/bin/env sh
set -e

php artisan migrate --force

# Run seeders only if explicitly enabled
if [ "$RUN_SEED" = "true" ]; then
  php artisan db:seed --force
fi

exec apache2-foreground
