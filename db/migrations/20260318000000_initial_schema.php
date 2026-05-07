<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Initial Schema Migration
 *
 * Converts the manual setup.sql into a versioned migration.
 * This establishes the baseline schema for the UTP Scholarship System.
 */
final class InitialSchema extends AbstractMigration
{
    public function change(): void
    {
        // ─── Users ──────────────────────────────────────────────
        $users = $this->table('users');
        $users->addColumn('full_name', 'string', ['limit' => 100])
              ->addColumn('email', 'string', ['limit' => 100])
              ->addColumn('password_hash', 'string', ['limit' => 255])
              ->addColumn('ic_number', 'string', ['limit' => 14])
              ->addColumn('phone', 'string', ['limit' => 15])
              ->addColumn('role', 'string', ['limit' => 10, 'default' => 'student'])
              ->addColumn('email_verified', 'boolean', ['default' => false])
              ->addColumn('totp_secret', 'string', ['limit' => 64, 'null' => true])
              ->addColumn('totp_enabled', 'boolean', ['default' => false])
              ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
              ->addIndex(['email'], ['unique' => true])
              ->addIndex(['ic_number'], ['unique' => true])
              ->create();

        // ─── Qualifications ────────────────────────────────────
        $qualifications = $this->table('qualifications');
        $qualifications->addColumn('user_id', 'integer')
                       ->addColumn('qual_type', 'string', ['limit' => 20])
                       ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                       ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
                       ->create();

        // ─── Grades ────────────────────────────────────────────
        $grades = $this->table('grades');
        $grades->addColumn('qualification_id', 'integer')
               ->addColumn('subject', 'string', ['limit' => 100])
               ->addColumn('grade', 'string', ['limit' => 5])
               ->addForeignKey('qualification_id', 'qualifications', 'id', ['delete' => 'CASCADE'])
               ->create();

        // ─── Programmes ────────────────────────────────────────
        $programmes = $this->table('programmes');
        $programmes->addColumn('name', 'string', ['limit' => 150])
                   ->addColumn('category', 'string', ['limit' => 50])
                   ->addColumn('description', 'text', ['null' => true])
                   ->addColumn('duration', 'string', ['limit' => 20, 'null' => true])
                   ->addColumn('foundation_fee', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
                   ->addColumn('undergraduate_fee', 'decimal', ['precision' => 10, 'scale' => 2, 'default' => 0])
                   ->addColumn('is_active', 'boolean', ['default' => true])
                   ->create();

        // ─── Entry Requirements ────────────────────────────────
        $entryReqs = $this->table('entry_requirements');
        $entryReqs->addColumn('programme_id', 'integer')
                  ->addColumn('qual_type', 'string', ['limit' => 20])
                  ->addColumn('subject', 'string', ['limit' => 100])
                  ->addColumn('min_grade', 'string', ['limit' => 5])
                  ->addColumn('weight', 'decimal', ['precision' => 3, 'scale' => 2, 'default' => 1.00])
                  ->addForeignKey('programme_id', 'programmes', 'id', ['delete' => 'CASCADE'])
                  ->create();

        // ─── Scholarships ──────────────────────────────────────
        $scholarships = $this->table('scholarships');
        $scholarships->addColumn('name', 'string', ['limit' => 150])
                     ->addColumn('description', 'text', ['null' => true])
                     ->addColumn('type', 'string', ['limit' => 20, 'default' => 'scholarship'])
                     ->addColumn('budget_min', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0])
                     ->addColumn('budget_max', 'decimal', ['precision' => 12, 'scale' => 2, 'default' => 0])
                     ->addColumn('min_fit_percentage', 'integer', ['default' => 50])
                     ->addColumn('start_date', 'date', ['null' => true])
                     ->addColumn('end_date', 'date', ['null' => true])
                     ->addColumn('is_active', 'boolean', ['default' => true])
                     ->create();

        // ─── Scholarship ↔ Programme (junction) ────────────────
        $scholarshipProgramme = $this->table('scholarship_programme', ['id' => false, 'primary_key' => ['scholarship_id', 'programme_id']]);
        $scholarshipProgramme->addColumn('scholarship_id', 'integer')
                             ->addColumn('programme_id', 'integer')
                             ->addForeignKey('scholarship_id', 'scholarships', 'id', ['delete' => 'CASCADE'])
                             ->addForeignKey('programme_id', 'programmes', 'id', ['delete' => 'CASCADE'])
                             ->create();

        // ─── Applications ──────────────────────────────────────
        $applications = $this->table('applications');
        $applications->addColumn('user_id', 'integer')
                     ->addColumn('qualification_id', 'integer')
                     ->addColumn('programme_id_1', 'integer', ['null' => true])
                     ->addColumn('programme_id_2', 'integer', ['null' => true])
                     ->addColumn('programme_id_3', 'integer', ['null' => true])
                     ->addColumn('scholarship_id', 'integer', ['null' => true])
                     ->addColumn('status', 'string', ['limit' => 20, 'default' => 'submitted'])
                     ->addColumn('admin_notes', 'text', ['null' => true])
                     ->addColumn('reviewed_by', 'integer', ['null' => true])
                     ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                     ->addColumn('updated_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                     ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
                     ->addForeignKey('qualification_id', 'qualifications', 'id', ['delete' => 'CASCADE'])
                     ->create();

        // ─── Eligibility Results ───────────────────────────────
        $eligResults = $this->table('eligibility_results');
        $eligResults->addColumn('application_id', 'integer')
                    ->addColumn('programme_id', 'integer')
                    ->addColumn('eligible', 'boolean', ['default' => false])
                    ->addColumn('fit_percentage', 'decimal', ['precision' => 5, 'scale' => 2, 'default' => 0])
                    ->addColumn('recommendation_text', 'text', ['null' => true])
                    ->addForeignKey('application_id', 'applications', 'id', ['delete' => 'CASCADE'])
                    ->create();

        // ─── Login Attempts ────────────────────────────────────
        $loginAttempts = $this->table('login_attempts');
        $loginAttempts->addColumn('ip_address', 'string', ['limit' => 45])
                      ->addColumn('attempted_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                      ->addIndex(['ip_address', 'attempted_at'])
                      ->create();

        // ─── Email Verifications ───────────────────────────────
        $emailVerifications = $this->table('email_verifications');
        $emailVerifications->addColumn('user_id', 'integer')
                           ->addColumn('token', 'string', ['limit' => 64])
                           ->addColumn('expires_at', 'datetime')
                           ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                           ->addIndex(['token'], ['unique' => true])
                           ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
                           ->create();

        // ─── Password Resets ───────────────────────────────────
        $passwordResets = $this->table('password_resets');
        $passwordResets->addColumn('user_id', 'integer')
                       ->addColumn('token', 'string', ['limit' => 64])
                       ->addColumn('expires_at', 'datetime')
                       ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                       ->addIndex(['token'], ['unique' => true])
                       ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
                       ->create();

        // ─── Audit Log ─────────────────────────────────────────
        $auditLog = $this->table('audit_log');
        $auditLog->addColumn('user_id', 'integer', ['null' => true])
                 ->addColumn('action', 'string', ['limit' => 255])
                 ->addColumn('target_type', 'string', ['limit' => 50, 'null' => true])
                 ->addColumn('target_id', 'integer', ['null' => true])
                 ->addColumn('details', 'text', ['null' => true])
                 ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
                 ->addColumn('created_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                 ->create();

        // ─── Documents ─────────────────────────────────────────
        $documents = $this->table('documents');
        $documents->addColumn('user_id', 'integer')
                  ->addColumn('doc_type', 'string', ['limit' => 50])
                  ->addColumn('filename', 'string', ['limit' => 255])
                  ->addColumn('original_name', 'string', ['limit' => 255])
                  ->addColumn('file_size', 'integer')
                  ->addColumn('uploaded_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP'])
                  ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
                  ->create();
    }
}
