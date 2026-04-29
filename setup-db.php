<?php
require 'includes/init.php';

$db = getDB();

$db->exec("
    CREATE TABLE IF NOT EXISTS proposals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        scholarship_id INT NOT NULL,
        title VARCHAR(255) NULL,
        content LONGTEXT NOT NULL,
        generated_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
        FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE
    );
");

try {
    $db->exec("INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint) VALUES ('20260318000000', 'InitialSchema', NOW(), NOW(), 0)");
    $db->exec("INSERT INTO phinxlog (version, migration_name, start_time, end_time, breakpoint) VALUES ('20260429040157', 'AddProposalsTable', NOW(), NOW(), 0)");
} catch (Exception $e) {
    // Ignore duplicate key errors
}

echo "Proposals table created and phinxlog updated.\n";
