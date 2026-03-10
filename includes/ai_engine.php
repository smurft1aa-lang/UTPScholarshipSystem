<?php
/**
 * AI-Driven Recommendation Engine
 * Weighted scoring, fit percentage, gap analysis, smart ranking
 */

require_once __DIR__ . '/../config/database.php';

class AIEngine {

    // Grade-to-points mapping for SPM
    private static $spmGradePoints = [
        'A+' => 10, 'A' => 9, 'A-' => 8,
        'B+' => 7, 'B' => 6, 'B-' => 5,
        'C+' => 4, 'C' => 3,
        'D' => 2, 'E' => 1, 'G' => 0, 'F' => 0
    ];

    // Grade-to-points mapping for O-Level / IGCSE
    private static $olevelGradePoints = [
        'A*' => 10, 'A' => 9, 'B' => 7, 'C' => 5,
        'D' => 3, 'E' => 2, 'F' => 1, 'G' => 0, 'U' => 0
    ];

    // Minimum passing grade points
    private static $minPassSPM = 3;   // C
    private static $minPassOLevel = 5; // C

    /**
     * Convert a grade string to numeric points
     */
    public static function gradeToPoints($grade, $qualType) {
        $grade = strtoupper(trim($grade));
        if ($qualType === 'SPM') {
            return self::$spmGradePoints[$grade] ?? 0;
        }
        return self::$olevelGradePoints[$grade] ?? 0;
    }

    /**
     * Get the minimum pass points by qual type
     */
    public static function getMinPassPoints($qualType) {
        return ($qualType === 'SPM') ? self::$minPassSPM : self::$minPassOLevel;
    }

    /**
     * Get the maximum points by qual type
     */
    public static function getMaxPoints($qualType) {
        return 10; // A+ or A*
    }

    /**
     * Run full eligibility check for a student
     * Returns array of programme results sorted by fit percentage
     */
    public static function checkEligibility($qualificationId) {
        $db = getDB();

        // Get qualification info
        $stmt = $db->prepare("SELECT * FROM qualifications WHERE id = ?");
        $stmt->execute([$qualificationId]);
        $qual = $stmt->fetch();
        if (!$qual) return [];

        $qualType = $qual['qual_type'];

        // Get student grades
        $stmt = $db->prepare("SELECT subject, grade FROM grades WHERE qualification_id = ?");
        $stmt->execute([$qualificationId]);
        $studentGrades = [];
        while ($row = $stmt->fetch()) {
            $studentGrades[strtolower(trim($row['subject']))] = $row['grade'];
        }

        // Get all active programmes
        $stmt = $db->prepare("SELECT * FROM programmes WHERE is_active = 1");
        $stmt->execute();
        $programmes = $stmt->fetchAll();

        $results = [];

        foreach ($programmes as $prog) {
            // Get requirements for this programme and qual type
            $stmt = $db->prepare("SELECT subject, min_grade, weight FROM entry_requirements WHERE programme_id = ? AND qual_type = ?");
            $stmt->execute([$prog['id'], $qualType]);
            $requirements = $stmt->fetchAll();

            if (empty($requirements)) continue;

            $result = self::evaluateProgramme($prog, $requirements, $studentGrades, $qualType);
            $results[] = $result;
        }

        // Sort by eligible first, then by fit percentage descending
        usort($results, function($a, $b) {
            if ($a['eligible'] !== $b['eligible']) return $b['eligible'] - $a['eligible'];
            return $b['fit_percentage'] - $a['fit_percentage'];
        });

        return $results;
    }

    /**
     * Evaluate a single programme against student grades
     */
    private static function evaluateProgramme($programme, $requirements, $studentGrades, $qualType) {
        $totalWeightedScore = 0;
        $maxWeightedScore = 0;
        $allMet = true;
        $subjectResults = [];
        $gaps = [];
        $minPassPoints = self::getMinPassPoints($qualType);
        $maxPoints = self::getMaxPoints($qualType);

        foreach ($requirements as $req) {
            $subjectKey = strtolower(trim($req['subject']));
            $weight = floatval($req['weight']);
            $minGradePoints = self::gradeToPoints($req['min_grade'], $qualType);

            // Check if student has this subject, handling "Other Subject" generics
            $studentGradeStr = null;
            $matchedSubject = null;

            if (isset($studentGrades[$subjectKey])) {
                $studentGradeStr = $studentGrades[$subjectKey];
                $matchedSubject = $subjectKey;
            }

            $maxWeightedScore += $maxPoints * $weight;

            if ($studentGradeStr !== null) {
                $studentPoints = self::gradeToPoints($studentGradeStr, $qualType);
                $totalWeightedScore += $studentPoints * $weight;
                $met = $studentPoints >= $minGradePoints;

                if (!$met) {
                    $allMet = false;
                    $gaps[] = [
                        'subject' => $req['subject'],
                        'required' => $req['min_grade'],
                        'got' => $studentGradeStr,
                        'message' => "Need at least {$req['min_grade']} in {$req['subject']}, got {$studentGradeStr}"
                    ];
                }

                $subjectResults[] = [
                    'subject' => $req['subject'],
                    'required_grade' => $req['min_grade'],
                    'student_grade' => $studentGradeStr,
                    'met' => $met,
                    'weight' => $weight
                ];
            } else {
                // Subject not found — check if it's a generic "Other Subject" placeholder
                if (strpos($subjectKey, 'other') !== false) {
                    // For "Other Subject" slots, give partial credit
                    $totalWeightedScore += $minPassPoints * $weight;
                    $subjectResults[] = [
                        'subject' => $req['subject'],
                        'required_grade' => $req['min_grade'],
                        'student_grade' => 'N/A (auto-matched)',
                        'met' => true,
                        'weight' => $weight
                    ];
                } else {
                    $allMet = false;
                    $gaps[] = [
                        'subject' => $req['subject'],
                        'required' => $req['min_grade'],
                        'got' => 'Not taken',
                        'message' => "Missing required subject: {$req['subject']}"
                    ];
                    $subjectResults[] = [
                        'subject' => $req['subject'],
                        'required_grade' => $req['min_grade'],
                        'student_grade' => 'Not taken',
                        'met' => false,
                        'weight' => $weight
                    ];
                }
            }
        }

        $fitPercentage = $maxWeightedScore > 0
            ? round(($totalWeightedScore / $maxWeightedScore) * 100, 1)
            : 0;

        // Generate recommendation text
        $recommendation = self::generateRecommendation($programme['name'], $allMet, $fitPercentage, $gaps);

        return [
            'programme_id' => $programme['id'],
            'programme_name' => $programme['name'],
            'category' => $programme['category'],
            'description' => $programme['description'],
            'eligible' => $allMet,
            'fit_percentage' => $fitPercentage,
            'subject_results' => $subjectResults,
            'gaps' => $gaps,
            'recommendation' => $recommendation
        ];
    }

    /**
     * Generate natural-language recommendation text
     */
    private static function generateRecommendation($programmeName, $eligible, $fitPct, $gaps) {
        if ($eligible && $fitPct >= 80) {
            return "Excellent match for {$programmeName}. Your grades strongly meet all entry requirements. This programme is highly recommended for you.";
        } elseif ($eligible && $fitPct >= 60) {
            return "Good match for {$programmeName}. You meet all minimum requirements. Consider strengthening your grades in core subjects for scholarship opportunities.";
        } elseif ($eligible) {
            return "You meet the minimum entry requirements for {$programmeName}. Your grades are at the threshold level — strengthening them would improve your profile.";
        } else {
            $gapSubjects = array_map(function($g) { return $g['subject']; }, array_slice($gaps, 0, 3));
            $gapList = implode(', ', $gapSubjects);
            if ($fitPct >= 60) {
                return "You are close to qualifying for {$programmeName}. Focus on improving: {$gapList}. A small improvement could make you eligible.";
            }
            return "You do not currently meet the requirements for {$programmeName}. Key areas to improve: {$gapList}.";
        }
    }

    /**
     * Get matching scholarships for eligible programmes
     */
    public static function getMatchingScholarships($eligibleProgrammeIds, $fitPercentages) {
        if (empty($eligibleProgrammeIds)) return [];

        $db = getDB();
        $placeholders = str_repeat('?,', count($eligibleProgrammeIds) - 1) . '?';

        $stmt = $db->prepare("
            SELECT DISTINCT s.*, GROUP_CONCAT(sp.programme_id) as programme_ids
            FROM scholarships s
            JOIN scholarship_programme sp ON s.id = sp.scholarship_id
            WHERE sp.programme_id IN ({$placeholders})
            AND s.is_active = 1
            AND (s.end_date IS NULL OR s.end_date >= CURDATE())
            GROUP BY s.id
            ORDER BY s.budget_max DESC
        ");
        $stmt->execute($eligibleProgrammeIds);
        $scholarships = $stmt->fetchAll();

        // Filter by min_fit_percentage
        $matched = [];
        foreach ($scholarships as $sch) {
            $progIds = explode(',', $sch['programme_ids']);
            $bestFit = 0;
            foreach ($progIds as $pid) {
                if (isset($fitPercentages[$pid]) && $fitPercentages[$pid] > $bestFit) {
                    $bestFit = $fitPercentages[$pid];
                }
            }
            if ($bestFit >= $sch['min_fit_percentage']) {
                $sch['best_fit'] = $bestFit;
                $matched[] = $sch;
            }
        }

        return $matched;
    }
}
