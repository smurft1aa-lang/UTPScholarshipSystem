<?php
/**
 * Simple script to parse PHPUnit clover coverage XML and enforce a minimum percentage.
 * Usage: php check_coverage.php coverage.xml 80
 */

if ($argc < 3) {
    echo "Usage: php check_coverage.php <clover-file> <minimum-percentage>\n";
    exit(1);
}

$file = $argv[1];
$minPercentage = (float) $argv[2];

if (!file_exists($file)) {
    echo "Error: Clover file '$file' not found.\n";
    exit(1);
}

$xml = simplexml_load_file($file);
if (!$xml) {
    echo "Error: Could not parse XML file.\n";
    exit(1);
}

$metrics = $xml->project->metrics;
if (!$metrics) {
    echo "Error: No metrics found in clover file.\n";
    exit(1);
}

$elements = (int) $metrics['elements'];
$coveredElements = (int) $metrics['coveredelements'];

if ($elements === 0) {
    echo "No executable code found.\n";
    exit(0);
}

$percentage = ($coveredElements / $elements) * 100;

echo sprintf("Code Coverage: %.2f%% (Target: %.2f%%)\n", $percentage, $minPercentage);

if ($percentage < $minPercentage) {
    echo "❌ Code coverage gate FAILED.\n";
    exit(1);
}

echo "✅ Code coverage gate PASSED.\n";
exit(0);
