<?php

use PHPUnit\Framework\TestCase;
use UTP\Security\PasswordStrength;

class PasswordStrengthTest extends TestCase
{
    public function testEvaluateReturnsScoreAndFeedback()
    {
        $result = PasswordStrength::evaluate('password123');
        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('strong_enough', $result);
        $strongResult = PasswordStrength::evaluate('CorrectHorseBatteryStaple_12345!@#');
        $this->assertTrue($strongResult['strong_enough']);
        $this->assertGreaterThanOrEqual(3, $strongResult['score']);
    }

    public function testValidateChecksTraditionalRulesAndEntropy()
    {
        // 1. Fails traditional rules (no specials)
        $errors = PasswordStrength::validate('Password123');
        $this->assertNotEmpty($errors);
        $this->assertContains('Password must contain a special character.', $errors);
// 2. Passes traditional but fails entropy (e.g. Test1234!)
        $errors = PasswordStrength::validate('Password1!');
        $this->assertNotEmpty($errors);
        $hasEntropyError = false;
        foreach ($errors as $error) {
            if (strpos($error, 'Password is too easy to guess') !== false) {
                $hasEntropyError = true;
            }
        }
        $this->assertTrue($hasEntropyError);
// 3. Passes both
        $errors = PasswordStrength::validate('Super#Secure99!Pwd');
        $this->assertEmpty($errors);
    }
}
