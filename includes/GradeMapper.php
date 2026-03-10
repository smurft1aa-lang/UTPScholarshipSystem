<?php
/**
 * Grade Mapping Component
 * Extracts grade calculation constants from AI Engine
 */

class GradeMapper {
    private static $spmGradePoints = [
        'A+' => 10, 'A' => 9, 'A-' => 8,
        'B+' => 7, 'B' => 6, 'B-' => 5,
        'C+' => 4, 'C' => 3,
        'D' => 2, 'E' => 1, 'G' => 0, 'F' => 0
    ];

    private static $olevelGradePoints = [
        'A*' => 10, 'A' => 9, 'B' => 7, 'C' => 5,
        'D' => 3, 'E' => 2, 'F' => 1, 'G' => 0, 'U' => 0
    ];

    private static $minPassSPM = 3;
    private static $minPassOLevel = 5;

    public static function gradeToPoints($grade, $qualType) {
        $grade = strtoupper(trim($grade));
        if ($qualType === 'SPM') return self::$spmGradePoints[$grade] ?? 0;
        return self::$olevelGradePoints[$grade] ?? 0;
    }

    public static function getMinPassPoints($qualType) {
        return ($qualType === 'SPM') ? self::$minPassSPM : self::$minPassOLevel;
    }

    public static function getMaxPoints($qualType) {
        return 10;
    }
}
