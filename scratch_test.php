<?php
require 'includes/init.php';

$studentGrades = [
    'bahasa melayu' => 'A',
    'english' => 'A',
    'pendidikan islam' => 'A-',
    'sejarah' => 'A+',
    'mathematics' => 'A',
    'other subject' => 'A',
    'pendidikan seni visual' => 'B',
    'reka cipta' => 'B+'
];

$qualType = 'SPM';
$stmt = getDB()->prepare("SELECT programme_id, subject, min_grade, weight FROM entry_requirements WHERE qual_type = ?");
$stmt->execute([$qualType]);
$allRequirements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$requirementsByProgramme = [];
foreach ($allRequirements as $req) {
    $requirementsByProgramme[$req['programme_id']][] = $req;
}

$stmt = getDB()->prepare("SELECT * FROM programmes WHERE is_active = 1");
$stmt->execute();
$programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$aiEngine = new \UTP\Services\AIEngine(getDB());
$reflector = new ReflectionClass($aiEngine);
$method = $reflector->getMethod('evaluateProgramme');
$method->setAccessible(true);

foreach ($programmes as $prog) {
    $requirements = $requirementsByProgramme[$prog['id']] ?? [];
    if (empty($requirements)) continue;
    
    $result = $method->invokeArgs($aiEngine, [$prog, $requirements, $studentGrades, $qualType]);
    echo "Programme: " . $prog['name'] . "\n";
    echo "Eligible: " . ($result['eligible'] ? 'YES' : 'NO') . "\n";
    if (!$result['eligible']) {
        foreach($result['gaps'] as $gap) {
            echo "  Gap: " . $gap['message'] . "\n";
        }
    }
    echo "Fit: " . $result['fit_percentage'] . "%\n\n";
}
