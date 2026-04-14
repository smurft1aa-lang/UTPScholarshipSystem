-- Migration 002: Add composite indexes for common query patterns
-- Apply: mysql -u root -p utp_scholarship < sql/migration_002_indexes.sql

-- Applications: filter by user and status
CREATE INDEX IF NOT EXISTS idx_applications_user_status
    ON applications (user_id, status);

-- Grades: lookup by qualification
CREATE INDEX IF NOT EXISTS idx_grades_qualification
    ON grades (qualification_id);

-- Eligibility results: lookup by application and eligibility flag
CREATE INDEX IF NOT EXISTS idx_eligibility_app_eligible
    ON eligibility_results (application_id, eligible);

-- Audit log: filter by user and date range
CREATE INDEX IF NOT EXISTS idx_audit_user_created
    ON audit_log (user_id, created_at);

-- Login attempts: rate limiter lookups by IP and timestamp
CREATE INDEX IF NOT EXISTS idx_login_attempts_ip_time
    ON login_attempts (ip_address, attempted_at);

-- Entry requirements: programme + qualification type lookup
CREATE INDEX IF NOT EXISTS idx_entry_req_prog_qual
    ON entry_requirements (programme_id, qual_type);

-- Documents: user document lookup
CREATE INDEX IF NOT EXISTS idx_documents_user
    ON documents (user_id);
