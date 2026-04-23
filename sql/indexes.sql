-- ═══════════════════════════════════════════════════════════
-- Performance Indexes — UTP Scholarship System
-- Run after initial setup to optimize hot query paths.
--
-- Usage:  mysql -u root -p utp_scholarship < sql/indexes.sql
-- Or via: vendor/bin/phinx migrate (if wrapped in a migration)
-- ═══════════════════════════════════════════════════════════

-- Grades: speed up grade lookups by qualification
ALTER TABLE grades ADD INDEX idx_grades_qualification (qualification_id);

-- Entry Requirements: speed up AI engine requirement lookups (N+1 fix companion)
ALTER TABLE entry_requirements ADD INDEX idx_entry_req_prog_qual (programme_id, qual_type);

-- Applications: speed up per-user and status-based queries
ALTER TABLE applications ADD INDEX idx_applications_user_date (user_id, created_at);
ALTER TABLE applications ADD INDEX idx_applications_status (status);
ALTER TABLE applications ADD INDEX idx_status (status);
ALTER TABLE applications ADD INDEX idx_user_id (user_id);

-- Eligibility Results: speed up result lookups per application
ALTER TABLE eligibility_results ADD INDEX idx_eligibility_app (application_id);
ALTER TABLE eligibility_results ADD INDEX idx_eligibility_prog (programme_id, eligible);
ALTER TABLE eligibility_results ADD INDEX idx_programme_id (programme_id);
-- Audit Log: speed up date-range filtered exports
ALTER TABLE audit_log ADD INDEX idx_audit_created (created_at);
ALTER TABLE audit_log ADD INDEX idx_audit_user (user_id);

-- Scholarships: speed up active scholarship filtering
ALTER TABLE scholarships ADD INDEX idx_scholarships_active_date (is_active, end_date);

-- Documents: speed up per-user document lookups
ALTER TABLE documents ADD INDEX idx_documents_user_type (user_id, doc_type);

-- Scholarship-Programme junction: already has composite PK, but add reverse index
ALTER TABLE scholarship_programme ADD INDEX idx_sp_programme (programme_id);
