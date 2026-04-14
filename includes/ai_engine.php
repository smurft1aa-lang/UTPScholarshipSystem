<?php
declare(strict_types=1);
/**
 * DEPRECATED: Procedural AIEngine has been migrated to UTP\Services\AIEngine.
 * This file is kept only as a backward-compatible bridge.
 * All calls are delegated to the OOP class via includes/init.php.
 */
// TODO: Remove this bridge after all pages migrated to use src/ directly (Target: Q3 2026)
require_once __DIR__ . '/init.php';

// Legacy static wrapper — delegates to the OOP instance
if (!class_exists('AIEngine', false)) {
    class AIEngine
    {
        public static function checkEligibility($qualificationId, $forceRefresh = false)
        {
            $engine = new \UTP\Services\AIEngine(getDB());
            return $engine->checkEligibility((int) $qualificationId, $forceRefresh);
        }

        public static function getMatchingScholarships(array $eligibleProgrammeIds, array $fitPercentages)
        {
            $engine = new \UTP\Services\AIEngine(getDB());
            return $engine->getMatchingScholarships($eligibleProgrammeIds, $fitPercentages);
        }
    }
}
