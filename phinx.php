<?php
/**
 * Phinx Database Migration Configuration
 *
 * Reads credentials from the .env file for portability.
 * Usage:
 *   vendor/bin/phinx migrate        — Run pending migrations
 *   vendor/bin/phinx status         — Show migration status
 *   vendor/bin/phinx rollback       — Rollback last migration
 *   vendor/bin/phinx create MyMigr  — Create a new migration class
 */

// Load .env credentials
$envFile = __DIR__ . '/.env';
$env = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim($value);
    }
}

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
        'seeds'      => '%%PHINX_CONFIG_DIR%%/db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinx_migrations',
        'default_environment'     => 'production',

        'production' => [
            'adapter' => 'mysql',
            'host'    => $env['DB_HOST'] ?? 'localhost',
            'name'    => $env['DB_NAME'] ?? 'utp_scholarship',
            'user'    => $env['DB_USER'] ?? 'root',
            'pass'    => $env['DB_PASS'] ?? '',
            'port'    => $env['DB_PORT'] ?? 3306,
            'charset' => 'utf8mb4',
        ],

        'testing' => [
            'adapter' => 'sqlite',
            'memory'  => true,
        ],
    ],

    'version_order' => 'creation',
];
