<?php
/**
 * PHP-CS-Fixer Configuration
 * Enforces PSR-12 coding standards on src/ and tests/
 */

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12'                     => true,
        'array_syntax'               => ['syntax' => 'short'],
        'no_unused_imports'          => true,
        'ordered_imports'            => ['sort_algorithm' => 'alpha'],
        'single_quote'               => true,
        'trailing_comma_in_multiline'=> true,
        'blank_line_before_statement'=> ['statements' => ['return', 'throw', 'try']],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(false);
