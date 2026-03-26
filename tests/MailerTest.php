<?php
/**
 * Mailer Service Tests
 *
 * Verifies that mail functions correctly skip sending
 * in the testing environment and return expected values.
 */

use PHPUnit\Framework\TestCase;
use UTP\Services\Mailer;

class MailerTest extends TestCase
{
    public function testVerificationEmailSkipsInTestMode(): void
    {
        // APP_ENV is set to 'testing' in bootstrap.php
        putenv('APP_ENV=testing');
        $result = Mailer::sendVerificationEmail('1', 'test@example.com', 'Test User');
        $this->assertTrue($result);
    }

    public function testStatusEmailSkipsInTestMode(): void
    {
        putenv('APP_ENV=testing');
        $result = Mailer::sendApplicationStatusEmail(
            'test@example.com',
            'Test User',
            'approved',
            'Computer Science',
            'Congratulations!'
        );
        $this->assertTrue($result);
    }

    public function testStatusEmailWithEmptyNotes(): void
    {
        putenv('APP_ENV=testing');
        $result = Mailer::sendApplicationStatusEmail(
            'test@example.com',
            'Test User',
            'rejected',
            'Mechanical Engineering'
        );
        $this->assertTrue($result);
    }
}
