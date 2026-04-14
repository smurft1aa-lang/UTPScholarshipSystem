<?php
declare(strict_types=1);
namespace UTP\Services;

/**
 * Grade Mapping Service
 *
 * Converts SPM and O-Level letter grades to numeric point values
 * and provides minimum pass thresholds for eligibility calculations.
 */
class GradeMapper
{
    /** @var array<string, int> SPM grade to point mapping */
    private static array $spmGradePoints = [
        'A+' => 10, 'A' => 9, 'A-' => 8,
        'B+' => 7, 'B' => 6, 'B-' => 5,
        'C+' => 4, 'C' => 3,
        'D' => 2, 'E' => 1, 'G' => 0, 'F' => 0
    ];

    /** @var array<string, int> O-Level grade to point mapping */
    private static array $olevelGradePoints = [
        'A*' => 10, 'A' => 9, 'B' => 7, 'C' => 5,
        'D' => 3, 'E' => 2, 'F' => 1, 'G' => 0, 'U' => 0
    ];

    private static int $minPassSPM = 3;
    private static int $minPassOLevel = 5;

    /**
     * Convert a letter grade to numeric points.
     *
     * @param string $grade    The letter grade (e.g., 'A+', 'C')
     * @param string $qualType Qualification type ('SPM' or 'O-Level')
     * @return int Grade points (0 if unrecognized)
     */
    public static function gradeToPoints(string $grade, string $qualType): int
    {
        $grade = strtoupper(trim($grade));
        if ($qualType === 'SPM') return self::$spmGradePoints[$grade] ?? 0;
        return self::$olevelGradePoints[$grade] ?? 0;
    }

    /**
     * Get the minimum passing grade points for a qualification type.
     */
    public static function getMinPassPoints(string $qualType): int
    {
        return ($qualType === 'SPM') ? self::$minPassSPM : self::$minPassOLevel;
    }

    /**
     * Get the maximum possible grade points (always 10).
     */
    public static function getMaxPoints(string $qualType): int
    {
        return 10;
    }
}
