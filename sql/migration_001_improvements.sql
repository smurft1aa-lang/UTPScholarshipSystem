-- =============================================
-- UTP Scholarship System — Database Improvements
-- Migration: Add indexes, junction table, updated_at columns
-- =============================================

-- ── 1. Add missing indexes for performance ──
CREATE INDEX IF NOT EXISTS idx_applications_user_id    ON applications(user_id);
CREATE INDEX IF NOT EXISTS idx_applications_status     ON applications(status);
CREATE INDEX IF NOT EXISTS idx_qualifications_user_id  ON qualifications(user_id);

-- ── 2. Add updated_at columns where missing ──
ALTER TABLE users          ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE qualifications ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE grades         ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE documents      ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- ── 3. Create junction table to replace programme_id_1/2/3 ──
CREATE TABLE IF NOT EXISTS application_programmes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    programme_id INT NOT NULL,
    preference_order TINYINT NOT NULL DEFAULT 1 COMMENT '1=first choice, 2=second, 3=third',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_app_pref (application_id, preference_order),
    UNIQUE KEY unique_app_prog (application_id, programme_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── 4. Migrate existing data from programme_id_1/2/3 to junction table ──
-- Only runs if programme_id_1 column exists
INSERT IGNORE INTO application_programmes (application_id, programme_id, preference_order)
SELECT id, programme_id_1, 1 FROM applications WHERE programme_id_1 IS NOT NULL;

INSERT IGNORE INTO application_programmes (application_id, programme_id, preference_order)
SELECT id, programme_id_2, 2 FROM applications WHERE programme_id_2 IS NOT NULL;

INSERT IGNORE INTO application_programmes (application_id, programme_id, preference_order)
SELECT id, programme_id_3, 3 FROM applications WHERE programme_id_3 IS NOT NULL;

-- Note: The old programme_id_1/2/3 columns are kept for backward compatibility.
-- They can be dropped after verifying migration:
-- ALTER TABLE applications DROP COLUMN programme_id_1;
-- ALTER TABLE applications DROP COLUMN programme_id_2;
-- ALTER TABLE applications DROP COLUMN programme_id_3;
