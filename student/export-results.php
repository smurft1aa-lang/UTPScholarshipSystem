<?php
/**
 * AI Eligibility Results — PDF Export
 *
 * Generates a clean, printable HTML page styled as a PDF report
 * that the student can print or save via the browser's Print > Save as PDF.
 * This avoids heavy PDF library dependencies (DOMPDF/TCPDF).
 */
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/ai_engine.php';
requireStudent();

$db = getDB();
$userId = $_SESSION['user_id'];

// Get the student's latest qualification
$stmt = $db->prepare("SELECT id, qual_type, created_at FROM qualifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$userId]);
$qual = $stmt->fetch();

if (!$qual) {
    echo "No qualification found. Please submit your grades first.";
    exit;
}

// Get eligibility results (OOP instance — consistent with rest of codebase)
$aiEngine = new \UTP\Services\AIEngine($db);
$results = $aiEngine->checkEligibility((int) $qual['id']);

// Get student info
$stmt = $db->prepare("SELECT full_name, email, ic_number FROM users WHERE id = ?");
$stmt->execute([$userId]);
$student = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Eligibility Report — <?= htmlspecialchars($student['full_name']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', sans-serif; 
            color: #1a1a2e; 
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        .report-header {
            text-align: center;
            border-bottom: 3px solid #e8630a;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .report-header h1 { font-size: 1.5rem; color: #e8630a; }
        .report-header p { color: #5a5a72; font-size: 0.9rem; }
        
        .student-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            background: #f7f8fa;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.9rem;
        }
        .student-info strong { color: #5a5a72; }
        
        .programme-card {
            border: 1px solid #e8e8e8;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .programme-card.eligible { border-left: 4px solid #22a867; }
        .programme-card.ineligible { border-left: 4px solid #dc3545; }
        
        .programme-name { font-size: 1.1rem; font-weight: 600; margin-bottom: 4px; }
        .programme-meta { font-size: 0.85rem; color: #5a5a72; margin-bottom: 8px; }
        
        .badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-eligible { background: #edfaf3; color: #22a867; }
        .badge-ineligible { background: #fef0f1; color: #dc3545; }
        
        .fit-bar {
            height: 8px;
            background: #e8e8e8;
            border-radius: 4px;
            overflow: hidden;
            margin: 8px 0;
        }
        .fit-bar-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, #e8630a, #f59e0b);
        }
        
        .gaps-list { margin-top: 8px; font-size: 0.85rem; }
        .gaps-list li { color: #dc3545; margin-left: 20px; }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 0.75rem;
            color: #8e8ea0;
            border-top: 1px solid #e8e8e8;
            padding-top: 16px;
        }
        
        .no-print { margin-bottom: 24px; text-align: center; }
        .no-print button {
            padding: 10px 24px;
            background: #e8630a;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
        }
        
        @media print {
            .no-print { display: none; }
            body { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">📄 Download / Print as PDF</button>
</div>

<div class="report-header">
    <h1>🎓 UTP Scholarship System</h1>
    <h2 style="font-size:1.2rem; margin-top:8px;">AI Eligibility Report</h2>
    <p>Generated: <?= date('d M Y, h:i A') ?></p>
</div>

<div class="student-info">
    <div><strong>Name:</strong> <?= htmlspecialchars($student['full_name']) ?></div>
    <div><strong>Email:</strong> <?= htmlspecialchars($student['email']) ?></div>
    <div><strong>IC Number:</strong> <?= htmlspecialchars($student['ic_number']) ?></div>
    <div><strong>Qualification:</strong> <?= htmlspecialchars($qual['qual_type']) ?></div>
</div>

<h3 style="margin-bottom:12px;">Programme Results (<?= count($results) ?> evaluated)</h3>

<?php foreach ($results as $r): ?>
<div class="programme-card <?= $r['eligible'] ? 'eligible' : 'ineligible' ?>">
    <div class="programme-name">
        <?= htmlspecialchars($r['programme_name']) ?>
        <span class="badge <?= $r['eligible'] ? 'badge-eligible' : 'badge-ineligible' ?>">
            <?= $r['eligible'] ? 'Eligible' : 'Not Eligible' ?>
        </span>
    </div>
    <div class="programme-meta">
        Fit: <?= number_format($r['fit_percentage'], 1) ?>% · 
        Confidence: <?= htmlspecialchars($r['confidence_label'] ?? 'N/A') ?>
    </div>
    <div class="fit-bar">
        <div class="fit-bar-fill" style="width: <?= min(100, $r['fit_percentage']) ?>%"></div>
    </div>
    <?php if (!empty($r['gaps'])): ?>
        <ul class="gaps-list">
            <?php foreach ($r['gaps'] as $gap): ?>
                <li><?= htmlspecialchars($gap['subject']) ?>: requires <?= htmlspecialchars($gap['min_grade']) ?>, got <?= htmlspecialchars($gap['student_grade'] ?? 'missing') ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
<?php endforeach; ?>

<div class="footer">
    <p>This report was generated by the UTP AI Eligibility Engine.</p>
    <p>© <?= date('Y') ?> UTP Scholarship System. For official purposes, please contact the Admissions Office.</p>
</div>

</body>
</html>
