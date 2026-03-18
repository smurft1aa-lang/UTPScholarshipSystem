<?php
namespace UTP\Security;

use ZxcvbnPhp\Zxcvbn;

/**
 * Password Strength Service
 *
 * Uses the zxcvbn algorithm (same as Dropbox) to evaluate password
 * entropy and provide human-readable strength feedback beyond
 * simple regex rules.
 */
class PasswordStrength
{
    /**
     * Evaluate password strength using the zxcvbn algorithm.
     *
     * @param string   $password   The password to evaluate
     * @param string[] $userInputs Additional context strings to penalize
     *                             (e.g., email, name) that would weaken the password
     * @return array{
     *     score: int,
     *     label: string,
     *     suggestions: string[],
     *     warning: string,
     *     crack_time: string,
     *     strong_enough: bool
     * }
     */
    public static function evaluate(string $password, array $userInputs = []): array
    {
        $zxcvbn = new Zxcvbn();
        $result = $zxcvbn->passwordStrength($password, $userInputs);

        $labels = [
            0 => 'Very Weak',
            1 => 'Weak',
            2 => 'Fair',
            3 => 'Strong',
            4 => 'Very Strong',
        ];

        return [
            'score' => $result['score'],
            'label' => $labels[$result['score']] ?? 'Unknown',
            'suggestions' => $result['feedback']['suggestions'] ?? [],
            'warning' => $result['feedback']['warning'] ?? '',
            'crack_time' => $result['crack_times_display']['offline_slow_hashing_1e4_per_second'] ?? 'unknown',
            'strong_enough' => $result['score'] >= 3,
        ];
    }

    /**
     * Validate password using both traditional rules AND entropy checking.
     *
     * @param string   $password   The password to validate
     * @param string[] $userInputs Context strings (email, name) to penalize
     * @return string[] Array of error messages (empty if password is acceptable)
     */
    public static function validate(string $password, array $userInputs = []): array
    {
        $errors = [];

        // Traditional rules
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain an uppercase letter.';
        if (!preg_match('/[a-z]/', $password)) $errors[] = 'Password must contain a lowercase letter.';
        if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain a number.';
        if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'Password must contain a special character.';

        // Entropy check (only if traditional rules pass)
        if (empty($errors)) {
            $strength = self::evaluate($password, $userInputs);
            if (!$strength['strong_enough']) {
                $msg = 'Password is too easy to guess.';
                if ($strength['warning']) {
                    $msg .= ' ' . $strength['warning'];
                }
                if (!empty($strength['suggestions'])) {
                    $msg .= ' Tip: ' . implode(' ', $strength['suggestions']);
                }
                $errors[] = $msg;
            }
        }

        return $errors;
    }
}
