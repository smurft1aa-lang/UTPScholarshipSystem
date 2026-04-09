#!/bin/bash
set -e

echo "=== UTP System Starting ==="

# Ensure required directories exist with correct permissions
mkdir -p /var/www/html/logs
mkdir -p /var/www/html/uploads/documents
chown -R www-data:www-data /var/www/html/logs
chown -R www-data:www-data /var/www/html/uploads/documents

# Install Composer dependencies if not already installed
if [ -f "composer.json" ] && [ ! -d "vendor" ]; then
    echo "Running composer install..."
    composer install --no-dev --optimize-autoloader
fi

# Wait for MySQL using PHP PDO (works with Railway's dynamic DB_HOST)
echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT:-3306}..."
MAX_TRIES=60
COUNT=0
until php -r "
    \$host = getenv('DB_HOST') ?: 'db';
    \$port = getenv('DB_PORT') ?: 3306;
    \$db   = getenv('DB_NAME') ?: 'railway';
    \$user = getenv('DB_USER') ?: 'root';
    \$pass = getenv('DB_PASS') ?: '';
    try {
        new PDO(\"mysql:host=\$host;port=\$port;dbname=\$db\", \$user, \$pass, [PDO::ATTR_TIMEOUT => 3]);
        exit(0);
    } catch (Exception \$e) {
        exit(1);
    }
" 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ "$COUNT" -ge "$MAX_TRIES" ]; then
        echo "ERROR: MySQL did not become ready after ${MAX_TRIES} attempts. Exiting."
        exit 1
    fi
    echo "MySQL not ready yet (attempt $COUNT/$MAX_TRIES) — retrying in 5s..."
    sleep 5
done
echo "MySQL is ready."

# Set default admin password if still placeholder
echo "Checking admin account..."
php -r "
    \$host = getenv('DB_HOST') ?: 'db';
    \$port = getenv('DB_PORT') ?: 3306;
    \$db   = getenv('DB_NAME') ?: 'utp_scholarship';
    \$user = getenv('DB_USER') ?: 'root';
    \$pass = getenv('DB_PASS') ?: '';
    \$pdo = new PDO(\"mysql:host=\$host;port=\$port;dbname=\$db\", \$user, \$pass);
    \$stmt = \$pdo->prepare('SELECT password_hash FROM users WHERE email = ?');
    \$stmt->execute(['admin@utp.edu.my']);
    \$row = \$stmt->fetch();
    if (\$row && \$row['password_hash'] === 'PLACEHOLDER_HASH_REPLACED_AT_SETUP') {
        \$hash = password_hash('Admin1234', PASSWORD_DEFAULT);
        \$update = \$pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
        \$update->execute([\$hash, 'admin@utp.edu.my']);
        echo \"Admin password initialized.\n\";
    } else {
        echo \"Admin account OK.\n\";
    }
" 2>&1

echo "=== Starting Apache ==="
exec "$@"