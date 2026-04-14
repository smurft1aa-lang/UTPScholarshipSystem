<?php
declare(strict_types=1);
/**
 * @deprecated Since 2026-04-14. Scheduled for removal in Q3 2026.
 * This bridge file delegates to includes/init.php which loads the OOP classes.
 * Migrate callers to require includes/init.php directly, and use the namespaced
 * classes: \UTP\Core\SessionManager, \UTP\Security\RoleGuard, \UTP\Services\UserAuth.
 */
require_once __DIR__ . '/init.php';
