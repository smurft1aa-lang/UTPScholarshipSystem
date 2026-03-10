#!/bin/bash
set -e

echo "=== UTP System Starting ==="

# Wait for MySQL to be ready before starting Apache
echo "Waiting for MySQL at ${DB_HOST:-db}..."
MAX_TRIES=30
COUNT=0
until mysqladmin ping -h "${DB_HOST:-db}" -u root -p"${DB_PASS}" --silent 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ "$COUNT" -ge "$MAX_TRIES" ]; then
        echo "ERROR: MySQL did not become ready after ${MAX_TRIES} attempts. Exiting."
        exit 1
    fi
    echo "MySQL not ready yet (attempt $COUNT/$MAX_TRIES) — retrying in 3s..."
    sleep 3
done
echo "MySQL is ready."

# Install Composer dependencies if not already installed
if [ -f "composer.json" ] && [ ! -d "vendor" ]; then
    echo "Running composer install..."
    composer install --no-dev --optimize-autoloader
fi

# Ensure required directories exist with correct permissions
mkdir -p /var/www/html/logs
mkdir -p /var/www/html/uploads/documents
chown -R www-data:www-data /var/www/html/logs
chown -R www-data:www-data /var/www/html/uploads/documents

echo "=== Starting Apache ==="
exec "$@"
