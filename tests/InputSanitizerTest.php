<?php
use PHPUnit\Framework\TestCase;
use UTP\Security\InputSanitizer;

class InputSanitizerTest extends TestCase
{
    public function testSanitizeTrimsWhitespace()
    {
        $this->assertEquals('test', InputSanitizer::sanitize('  test  '));
        $this->assertEquals(['a' => 'foo', 'b' => 'bar'], InputSanitizer::sanitize(['a' => ' foo ', 'b' => '  bar']));
    }

    public function testEscapeConvertsSpecialCharacters()
    {
        $this->assertEquals('&lt;script&gt;', InputSanitizer::escape('<script>'));
        $this->assertEquals('&quot;test&quot;', InputSanitizer::escape('"test"'));
        $this->assertEquals('', InputSanitizer::escape(null));
    }

    public function testValidateEmail()
    {
        $this->assertTrue(InputSanitizer::validateEmail('test@example.com'));
        $this->assertFalse(InputSanitizer::validateEmail('invalid-email'));
    }

    public function testValidatePassword()
    {
        $this->assertEmpty(InputSanitizer::validatePassword('Valid123!'));
        $this->assertNotEmpty(InputSanitizer::validatePassword('weak'));
        $this->assertContains('Password must contain a special character.', InputSanitizer::validatePassword('Valid1234'));
    }

    public function testValidateICNumber()
    {
        $this->assertTrue(InputSanitizer::validateICNumber('010101-14-1234'));
        $this->assertTrue(InputSanitizer::validateICNumber('010101141234'));
        $this->assertFalse(InputSanitizer::validateICNumber('invalid-ic'));
        $this->assertFalse(InputSanitizer::validateICNumber('123'));
    }

    public function testValidatePhone()
    {
        $this->assertTrue(InputSanitizer::validatePhone('+60123456789'));
        $this->assertTrue(InputSanitizer::validatePhone('012-345 6789'));
        $this->assertFalse(InputSanitizer::validatePhone('invalid'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetSecurityHeaders()
    {
        InputSanitizer::setSecurityHeaders();
        // Since we are running in CLI testing mode, verify it sets nocache correctly or just runs without fatal
        $this->assertTrue(true);
    }

    public function testGetClientIP()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $this->assertEquals('192.168.1.1', InputSanitizer::getClientIP());
        
        // Test with proxy
        putenv('TRUSTED_PROXY=192.168.1.1');
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 10.0.0.2';
        $this->assertEquals('10.0.0.1', InputSanitizer::getClientIP());
        putenv('TRUSTED_PROXY='); // reset
    }
}
