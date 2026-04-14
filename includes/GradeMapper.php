<?php
declare(strict_types=1);
/**
 * DEPRECATED: Procedural GradeMapper has been migrated to UTP\Services\GradeMapper.
 * This file is kept only as a backward-compatible bridge.
 * All calls are delegated to the OOP class via Composer autoloading.
 */
// TODO: Remove this bridge after all pages migrated to use src/ directly (Target: Q3 2026)
require_once __DIR__ . '/init.php';

// Legacy class wrapper — delegates to the namespaced OOP class
if (!class_exists('GradeMapper', false)) {
    class GradeMapper
    {
        public static function gradeToPoints($grade, $qualType = 'SPM')
        {
            return \UTP\Services\GradeMapper::gradeToPoints($grade, $qualType);
        }

        public static function getMinPassPoints($qualType = 'SPM')
        {
            return \UTP\Services\GradeMapper::getMinPassPoints($qualType);
        }

        public static function getMaxPoints($qualType = 'SPM')
        {
            return \UTP\Services\GradeMapper::getMaxPoints($qualType);
        }
    }
}
