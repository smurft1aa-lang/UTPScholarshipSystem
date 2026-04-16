<?php

use PHPUnit\Framework\TestCase;
use UTP\Core\SessionManager;

class SessionManagerTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testSessionStartsAndSetsVariables()
    {
        $manager = new SessionManager(1800);
        $manager->start();
        $this->assertEquals(PHP_SESSION_ACTIVE, session_status());
        $this->assertArrayHasKey('last_activity', $_SESSION);
    }

    /**
     * @runInSeparateProcess
     */
    public function testLogoutDestroysSession()
    {
        $manager = new SessionManager(1800);
        $manager->start();
        $_SESSION['user_id'] = 1;
        $manager->logout();
        $this->assertEmpty($_SESSION);
    }
}
