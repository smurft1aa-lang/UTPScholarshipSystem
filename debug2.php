<?php
require_once __DIR__ . '/tests/bootstrap.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/ai_engine.php';

$db = getDB();

echo "Running AI Engine debug...\n";

// Insert temporary qualification
$db->exec("INSERT INTO qualifications (user_id, qual_type) VALUES (1, 'SPM')");
$qualId = $db->lastInsertId();

$grades = [
    'Bahasa Melayu' => 'A+',
    'English' => 'A+',
    'Mathematics' => 'A+',
    'Additional Mathematics' => 'A+',
    'Physics' => 'A+',
    'Chemistry' => 'A+'
];

foreach ($grades as $subject => $grade) {
    $stmt = $db->prepare("INSERT INTO grades (qualification_id, subject, grade) VALUES (?, ?, ?)");
    $stmt->execute([$qualId, $subject, $grade]);
}

$results = AIEngine::checkEligibility($qualId);
echo "Total results returned: " . count($results) . "\n";
foreach ($results as $r) {
    echo "Programme: " . $r['programme_name'] . " | Eligible: " . ($r['eligible'] ? 'Yes' : 'No') . " | Fit: " . $r['fit_percentage'] . "%\n";
}
$db->exec("DELETE FROM grades WHERE qualification_id=$qualId");
$db->exec("DELETE FROM qualifications WHERE id=$qualId");
