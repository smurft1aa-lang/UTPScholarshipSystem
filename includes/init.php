<?php
/**
 * Unified Initialization file capturing modular dependencies
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/RoleGuard.php';
require_once __DIR__ . '/UserAuth.php';
require_once __DIR__ . '/CSRF.php';
require_once __DIR__ . '/RateLimiter.php';
require_once __DIR__ . '/InputSanitizer.php';
require_once __DIR__ . '/mailer.php';
