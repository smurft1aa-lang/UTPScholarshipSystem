#!/bin/bash
set -e

# Run composer install if composer.json exists
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
fi

# Pass execution to exactly what was requested (apache2-foreground)
exec "$@"
