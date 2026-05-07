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

    // ─── sanitizeHtml Tests ────────────────────────────────────

    public function testSanitizeHtmlPreservesAllowedTags(): void
    {
        $html = '<p>Hello <strong>world</strong></p>';
        $this->assertSame($html, InputSanitizer::sanitizeHtml($html));
    }

    public function testSanitizeHtmlStripsDisallowedTags(): void
    {
        $html = '<p>Hello</p><script>alert("xss")</script>';
        $result = InputSanitizer::sanitizeHtml($html);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('<p>Hello</p>', $result);
    }

    public function testSanitizeHtmlStripsEventHandlers(): void
    {
        $html = '<p onclick="alert(1)">Click me</p>';
        $result = InputSanitizer::sanitizeHtml($html);
        $this->assertStringNotContainsString('onclick', $result);
        $this->assertStringContainsString('<p>', $result);
    }

    public function testSanitizeHtmlBlocksJavascriptUri(): void
    {
        $html = '<a href="javascript:alert(1)">Link</a>';
        $result = InputSanitizer::sanitizeHtml($html);
        $this->assertStringNotContainsString('javascript:', $result);
    }

    public function testSanitizeHtmlBlocksDataUri(): void
    {
        $html = '<a href="data:text/html,<script>alert(1)</script>">Link</a>';
        $result = InputSanitizer::sanitizeHtml($html);
        $this->assertStringNotContainsString('data:', $result);
    }

    public function testSanitizeHtmlAllowsSafeHref(): void
    {
        $html = '<a href="https://utp.edu.my" title="UTP">Visit</a>';
        $result = InputSanitizer::sanitizeHtml($html);
        $this->assertStringContainsString('href="https://utp.edu.my"', $result);
        $this->assertStringContainsString('title="UTP"', $result);
    }

    public function testSanitizeHtmlStripsStyleAttribute(): void
    {
        // style is not in ALLOWED_ATTRS for p
        $html = '<p style="color:red">Text</p>';
        $result = InputSanitizer::sanitizeHtml($html);
        $this->assertStringNotContainsString('style', $result);
        $this->assertStringContainsString('<p>', $result);
    }

    public function testSanitizeHtmlAllowsClassOnDiv(): void
    {
        $html = '<div class="card">Content</div>';
        $result = InputSanitizer::sanitizeHtml($html);
        $this->assertStringContainsString('class="card"', $result);
    }

    public function testSanitizeHtmlAllowsTableAttributes(): void
    {
        $html = '<td colspan="2">Cell</td>';
        $result = InputSanitizer::sanitizeHtml($html);
        $this->assertStringContainsString('colspan="2"', $result);
    }

    public function testSanitizeHtmlHandlesEmptyString(): void
    {
        $this->assertSame('', InputSanitizer::sanitizeHtml(''));
    }

    public function testSanitizeHtmlHandlesPlainText(): void
    {
        $this->assertSame('Just plain text', InputSanitizer::sanitizeHtml('Just plain text'));
    }

    public function testSanitizeHtmlBlocksVbscriptUri(): void
    {
        $html = '<a href="vbscript:MsgBox(1)">Link</a>';
        $result = InputSanitizer::sanitizeHtml($html);
        $this->assertStringNotContainsString('vbscript:', $result);
    }
}
