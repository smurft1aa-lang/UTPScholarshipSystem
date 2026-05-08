<?php
/**
 * Convert PHPUnit serialized coverage data to Clover XML format.
 * Usage: php scripts/convert-coverage.php <coverage-php-file> <clover-output>
 *
 * This is a workaround for environments where PHPUnit's --coverage-clover
 * fails to write the file directly (CI runner + PCOV edge case).
 */

if ($argc < 3) {
    echo "Usage: php convert-coverage.php <coverage.php> <coverage.xml>\n";
    exit(1);
}

require_once __DIR__ . '/../vendor/autoload.php';

$inputFile = $argv[1];
$outputFile = $argv[2];

if (!file_exists($inputFile)) {
    echo "Error: Coverage data file '$inputFile' not found.\n";
    exit(1);
}

$coverage = require $inputFile;

if (!$coverage instanceof \SebastianBergmann\CodeCoverage\CodeCoverage) {
    echo "Error: File does not contain a valid CodeCoverage object.\n";
    exit(1);
}

$writer = new \SebastianBergmann\CodeCoverage\Report\Clover();
$writer->process($coverage, $outputFile);

echo "✅ Clover report written to $outputFile\n";
