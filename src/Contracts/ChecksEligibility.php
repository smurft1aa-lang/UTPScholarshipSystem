<?php
declare(strict_types=1);
namespace UTP\Contracts;

/**
 * Contract for the eligibility checking engine.
 */
interface ChecksEligibility
{
    /**
     * @param int  $qualificationId The qualification record ID
     * @param bool $forceRefresh    Skip cache if true
     * @return array<int, array<string, mixed>> Ranked programme results
     */
    public function checkEligibility(int $qualificationId, bool $forceRefresh = false): array;

    /**
     * @param array<int>        $eligibleProgrammeIds Programme IDs
     * @param array<int, float> $fitPercentages       Programme ID → fit score map
     * @return array<int, array<string, mixed>> Matched scholarships
     */
    public function getMatchingScholarships(array $eligibleProgrammeIds, array $fitPercentages): array;
}
