<?php
declare(strict_types=1);
namespace UTP\Services;

use UTP\Services\GradeMapper;

/**
 * AI-Driven Recommendation Engine (OOP)
 *
 * Evaluates student academic profiles against programme entry requirements
 * using a weighted scoring algorithm with gap analysis, confidence labelling,
 * and scholarship matching.
 */
class AIEngine implements \UTP\Contracts\ChecksEligibility
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Run full eligibility check for a student qualification.
     *
     * Results are cached in the session for 10 minutes per qualification ID.
     * Pass $forceRefresh = true to bypass the cache.
     *
     * @param int  $qualificationId The qualification record ID
     * @param bool $forceRefresh    Skip session cache if true
     * @return array<int, array<string, mixed>> Ranked programme results
     */
    public function checkEligibility(int $qualificationId, bool $forceRefresh = false): array
    {
        // ── APCu Cache check (user-scoped to prevent cross-user leakage) ──
        $userId = $_SESSION['user_id'] ?? 0;
        $cacheKey = 'eligibility_' . $userId . '_' . $qualificationId;
        if (!$forceRefresh && function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey);
            if ($cached !== false && is_array($cached)) {
                return $cached['results'];
            }
        } elseif (!$forceRefresh && isset($_SESSION[$cacheKey]) && is_array($_SESSION[$cacheKey])) {
            $cached = $_SESSION[$cacheKey];
            if (isset($cached['timestamp']) && (time() - $cached['timestamp']) < 600) {
                return $cached['results'];
            }
        }

        if (function_exists('startTimer')) {
            startTimer('ai_eligibility');
        }

        try {
            $stmt = $this->db->prepare("SELECT * FROM qualifications WHERE id = ?");
            $stmt->execute([$qualificationId]);
            $qual = $stmt->fetch();
            if (!$qual) {
                return [];
            }

            $qualType = $qual['qual_type'];

            // Get student grades
            $stmt = $this->db->prepare("SELECT subject, grade FROM grades WHERE qualification_id = ?");
            $stmt->execute([$qualificationId]);
            $studentGrades = [];
            while ($row = $stmt->fetch()) {
                $studentGrades[strtolower(trim($row['subject']))] = $row['grade'];
            }

            // Get all active programmes
            $stmt = $this->db->prepare("SELECT * FROM programmes WHERE is_active = 1");
            $stmt->execute();
            $programmes = $stmt->fetchAll();

            if (empty($programmes)) {
                return [];
            }
            // ── Pre-load ALL entry requirements for this qual_type in one query ──
            // This eliminates the N+1 problem (previously 1 query per programme)
            $stmt = $this->db->prepare(
                "SELECT programme_id, subject, min_grade, weight
                 FROM entry_requirements WHERE qual_type = ?"
            );
            $stmt->execute([$qualType]);
            $allRequirements = $stmt->fetchAll();

            // Group requirements by programme_id for O(1) lookup
            $requirementsByProgramme = [];
            foreach ($allRequirements as $req) {
                $requirementsByProgramme[$req['programme_id']][] = $req;
            }

            $results = [];

            foreach ($programmes as $prog) {
                $requirements = $requirementsByProgramme[$prog['id']] ?? [];

                if (empty($requirements)) {
                    continue;
                }

                $result = $this->evaluateProgramme($prog, $requirements, $studentGrades, $qualType);
                $results[] = $result;
            }

            // Sort: eligible first, then by fit percentage descending
            usort($results, function ($a, $b) {
                if ($a['eligible'] !== $b['eligible']) {
                    return $b['eligible'] - $a['eligible'];
                }
                return $b['fit_percentage'] - $a['fit_percentage'];
            });

            if (function_exists('endTimer')) {
                $time = endTimer('ai_eligibility');
                if ($time > 500 && function_exists('trackEvent')) {
                    trackEvent('Slow AI Calculation', ['time_ms' => $time, 'qualification_id' => $qualificationId], 'WARNING');
                }
            }

            // ── Store in cache ──
            $cacheData = [
                'results' => $results,
                'timestamp' => time(),
            ];
            
            if (function_exists('apcu_store')) {
                apcu_store($cacheKey, $cacheData, 600);
            } elseif (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION[$cacheKey] = $cacheData;
            }

            return $results;
        } catch (\Exception $e) {
            if (function_exists('trackEvent')) {
                trackEvent('AI Engine Check Failed', ['exception' => $e, 'qualification_id' => $qualificationId], 'ERROR');
            }
            throw new \RuntimeException(
                'Eligibility check failed: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e
            );
        }
    }

    /**
     * Evaluate a single programme against student grades.
     *
     * @param array<string, mixed>   $programme     Programme record
     * @param array<int, array>      $requirements  Entry requirements
     * @param array<string, string>  $studentGrades Subject→grade map
     * @param string                 $qualType      Qualification type
     * @return array<string, mixed>  Evaluation result
     */
    private function evaluateProgramme(array $programme, array $requirements, array $studentGrades, string $qualType): array
    {
        $totalWeightedScore = 0;
        $maxWeightedScore = 0;
        $allMet = true;
        $subjectResults = [];
        $gaps = [];
        $minPassPoints = GradeMapper::getMinPassPoints($qualType);
        $maxPoints = GradeMapper::getMaxPoints($qualType);

        foreach ($requirements as $req) {
            $subjectKey = strtolower(trim($req['subject']));
            $weight = floatval($req['weight']);
            $minGradePoints = GradeMapper::gradeToPoints($req['min_grade'], $qualType);

            $studentGradeStr = null;
            if (isset($studentGrades[$subjectKey])) {
                $studentGradeStr = $studentGrades[$subjectKey];
            }

            $maxWeightedScore += $maxPoints * $weight;

            if ($studentGradeStr !== null) {
                $studentPoints = GradeMapper::gradeToPoints($studentGradeStr, $qualType);
                $totalWeightedScore += $studentPoints * $weight;
                $met = $studentPoints >= $minGradePoints;

                if (!$met) {
                    $allMet = false;
                    $gaps[] = [
                        'subject' => $req['subject'],
                        'required' => $req['min_grade'],
                        'got' => $studentGradeStr,
                        'message' => "Need at least {$req['min_grade']} in {$req['subject']}, got {$studentGradeStr}",
                    ];
                }

                $subjectResults[] = [
                    'subject' => $req['subject'],
                    'required_grade' => $req['min_grade'],
                    'student_grade' => $studentGradeStr,
                    'met' => $met,
                    'weight' => $weight,
                ];
            } else {
                // Subject not found — check if it's a generic "Other Subject" placeholder
                if (strpos($subjectKey, 'other') !== false) {
                    $totalWeightedScore += $minPassPoints * $weight;
                    $subjectResults[] = [
                        'subject' => $req['subject'],
                        'required_grade' => $req['min_grade'],
                        'student_grade' => 'N/A (auto-matched)',
                        'met' => true,
                        'weight' => $weight,
                    ];
                } else {
                    $allMet = false;
                    $gaps[] = [
                        'subject' => $req['subject'],
                        'required' => $req['min_grade'],
                        'got' => 'Not taken',
                        'message' => "Missing required subject: {$req['subject']}",
                    ];
                    $subjectResults[] = [
                        'subject' => $req['subject'],
                        'required_grade' => $req['min_grade'],
                        'student_grade' => 'Not taken',
                        'met' => false,
                        'weight' => $weight,
                    ];
                }
            }
        }

        $fitPercentage = $maxWeightedScore > 0
            ? round(($totalWeightedScore / $maxWeightedScore) * 100, 1)
            : 0;

        // Apply STEM-specific bonus (use qualification-aware threshold)
        if (!empty($programme['stem_bonus'])) {
            $stemThreshold = (int) round(GradeMapper::getMaxPoints($qualType) * 0.9);
            $physicsPoints = GradeMapper::gradeToPoints($studentGrades['physics'] ?? '', $qualType);
            $chemistryPoints = GradeMapper::gradeToPoints($studentGrades['chemistry'] ?? '', $qualType);

            if ($physicsPoints >= $stemThreshold && $chemistryPoints >= $stemThreshold) {
                $fitPercentage = min(100, $fitPercentage + 5.0);
            }
        }

        // Confidence label
        if ($fitPercentage >= 90) {
            $confidenceLabel = "Excellent Match";
        } elseif ($fitPercentage >= 75) {
            $confidenceLabel = "Strong Match";
        } elseif ($fitPercentage >= 60) {
            $confidenceLabel = "Good Match";
        } elseif ($fitPercentage >= 40) {
            $confidenceLabel = "Possible Match";
        } else {
            $confidenceLabel = "Not Recommended";
        }

        $recommendation = $this->generateRecommendation($programme['name'], $allMet, $fitPercentage, $gaps);

        return [
            'programme_id' => $programme['id'],
            'programme_name' => $programme['name'],
            'category' => $programme['category'],
            'description' => $programme['description'],
            'eligible' => $allMet,
            'fit_percentage' => $fitPercentage,
            'confidence_label' => $confidenceLabel,
            'subject_results' => $subjectResults,
            'gaps' => $gaps,
            'recommendation' => $recommendation,
        ];
    }

    /**
     * Generate natural-language recommendation text.
     *
     * @param string $programmeName Programme display name
     * @param bool   $eligible      Whether all requirements were met
     * @param float  $fitPct        Fit percentage score
     * @param array  $gaps          Array of gap entries
     * @return string Human-readable recommendation
     */
    private function generateRecommendation(string $programmeName, bool $eligible, float $fitPct, array $gaps): string
    {
        if ($eligible && $fitPct >= 80) {
            return "Excellent match for {$programmeName}. Your grades strongly meet all entry requirements. This programme is highly recommended for you.";
        } elseif ($eligible && $fitPct >= 60) {
            return "Good match for {$programmeName}. You meet all minimum requirements. Consider strengthening your grades in core subjects for scholarship opportunities.";
        } elseif ($eligible) {
            return "You meet the minimum entry requirements for {$programmeName}. Your grades are at the threshold level — strengthening them would improve your profile.";
        } else {
            $gapSubjects = array_map(fn($g) => $g['subject'], array_slice($gaps, 0, 3));
            $gapList = implode(', ', $gapSubjects);
            if ($fitPct >= 60) {
                return "You are close to qualifying for {$programmeName}. Focus on improving: {$gapList}. A small improvement could make you eligible.";
            }
            return "You do not currently meet the requirements for {$programmeName}. Key areas to improve: {$gapList}.";
        }
    }

    /**
     * Get matching scholarships for eligible programmes.
     *
     * @param array<int>          $eligibleProgrammeIds Programme IDs the student is eligible for
     * @param array<int, float>   $fitPercentages       Programme ID → fit percentage map
     * @return array<int, array<string, mixed>> Matched scholarships
     */
    public function getMatchingScholarships(array $eligibleProgrammeIds, array $fitPercentages): array
    {
        if (empty($eligibleProgrammeIds)) {
            return [];
        }

        $placeholders = str_repeat('?,', count($eligibleProgrammeIds) - 1) . '?';

        $stmt = $this->db->prepare("
            SELECT DISTINCT s.*, GROUP_CONCAT(sp.programme_id) as programme_ids
            FROM scholarships s
            JOIN scholarship_programme sp ON s.id = sp.scholarship_id
            WHERE sp.programme_id IN ({$placeholders})
            AND s.is_active = 1
            AND (s.end_date IS NULL OR s.end_date >= CURRENT_DATE)
            GROUP BY s.id
            ORDER BY s.budget_max DESC
        ");
        $stmt->execute($eligibleProgrammeIds);
        $scholarships = $stmt->fetchAll();

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
