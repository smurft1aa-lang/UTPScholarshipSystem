-- UTP Scholarship & Course Eligibility System
-- Database Setup Script
-- Based on official UTP entry requirements and fee schedule

CREATE DATABASE IF NOT EXISTS utp_scholarship;
USE utp_scholarship;

-- =====================================================
-- TABLES
-- =====================================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    ic_number VARCHAR(20) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    email_verified TINYINT(1) DEFAULT 0,
    totp_secret TEXT DEFAULT NULL,
    totp_enabled TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS qualifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    qual_type ENUM('SPM', 'O-Level', 'IGCSE') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS grades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qualification_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    grade VARCHAR(10) NOT NULL,
    FOREIGN KEY (qualification_id) REFERENCES qualifications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS programmes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    duration VARCHAR(50),
    foundation_fee DECIMAL(12,2) DEFAULT 0,
    undergraduate_fee DECIMAL(12,2) DEFAULT 0,
    stem_bonus TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS entry_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    programme_id INT NOT NULL,
    qual_type ENUM('SPM', 'O-Level', 'IGCSE') NOT NULL,
    subject VARCHAR(100) NOT NULL,
    min_grade VARCHAR(10) NOT NULL,
    weight DECIMAL(3,2) DEFAULT 1.00,
    FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scholarships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    type ENUM('scholarship', 'loan', 'sponsorship', 'financial_aid') DEFAULT 'scholarship',
    budget_min DECIMAL(12,2) DEFAULT 0,
    budget_max DECIMAL(12,2) DEFAULT 0,
    min_fit_percentage INT DEFAULT 50,
    start_date DATE,
    end_date DATE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scholarship_programme (
    scholarship_id INT NOT NULL,
    programme_id INT NOT NULL,
    PRIMARY KEY (scholarship_id, programme_id),
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE CASCADE,
    FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    qualification_id INT NOT NULL,
    programme_id_1 INT,
    programme_id_2 INT,
    programme_id_3 INT,
    scholarship_id INT,
    status ENUM('submitted', 'processing', 'approved', 'rejected') NOT NULL DEFAULT 'submitted',
    admin_notes TEXT,
    reviewed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (qualification_id) REFERENCES qualifications(id) ON DELETE CASCADE,
    FOREIGN KEY (programme_id_1) REFERENCES programmes(id) ON DELETE SET NULL,
    FOREIGN KEY (programme_id_2) REFERENCES programmes(id) ON DELETE SET NULL,
    FOREIGN KEY (programme_id_3) REFERENCES programmes(id) ON DELETE SET NULL,
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS eligibility_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    programme_id INT NOT NULL,
    eligible TINYINT(1) NOT NULL DEFAULT 0,
    fit_percentage DECIMAL(5,2) DEFAULT 0,
    recommendation_text TEXT,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    doc_type ENUM('ic', 'certificate', 'photo') NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    target_type VARCHAR(50),
    target_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Academic fees reference table
CREATE TABLE IF NOT EXISTS fee_structure (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fee_type VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    amount_min DECIMAL(12,2) NOT NULL,
    amount_max DECIMAL(12,2),
    frequency VARCHAR(50) DEFAULT 'one-time',
    effective_date DATE,
    notes TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SEED: Default admin (password generated at runtime in setup_db.php)
-- =====================================================
INSERT INTO users (full_name, email, password_hash, ic_number, phone, role, email_verified)
VALUES ('System Admin', 'admin@utp.edu.my', 'PLACEHOLDER_HASH_REPLACED_AT_SETUP', '000000000000', '0000000000', 'admin', 1);

-- =====================================================
-- SEED: Programmes with ACTUAL UTP fees
-- Fees effective May 2026, subject to change
-- =====================================================

-- Engineering & Science: Group 1 (SPM: BM, Eng, Math, Add Math, Physics, Chemistry)
INSERT INTO programmes (name, category, description, duration, foundation_fee, undergraduate_fee) VALUES
('Integrated Engineering', 'Engineering & Science', 'Foundation programme leading to integrated engineering studies at UTP.', '4 Years', 21000.00, 160000.00),
('Chemical Engineering', 'Engineering & Science', 'Foundation programme leading to chemical engineering studies at UTP.', '4 Years', 21000.00, 110000.00),
('Mechanical Engineering', 'Engineering & Science', 'Foundation programme leading to mechanical engineering studies at UTP.', '4 Years', 21000.00, 110000.00),
('Petroleum Engineering', 'Engineering & Science', 'Foundation programme leading to petroleum engineering studies at UTP.', '4 Years', 21000.00, 110000.00);

-- Engineering & Science: Group 2 (SPM: BM, Eng, Math, Add Math, Physics, Chemistry)
INSERT INTO programmes (name, category, description, duration, foundation_fee, undergraduate_fee) VALUES
('Applied Physics', 'Engineering & Science', 'Foundation programme in applied physics at UTP.', '3 Years 4 Months', 21000.00, 82500.00),
('Civil Engineering', 'Engineering & Science', 'Foundation programme leading to civil engineering studies at UTP.', '4 Years', 21000.00, 104500.00),
('Computer Engineering', 'Engineering & Science', 'Foundation programme leading to computer engineering studies at UTP.', '4 Years', 21000.00, 104500.00),
('Electrical & Electronics Engineering', 'Engineering & Science', 'Foundation programme in electrical and electronics engineering at UTP.', '4 Years', 21000.00, 110000.00);

-- Engineering & Science: Applied Chemistry
INSERT INTO programmes (name, category, description, duration, foundation_fee, undergraduate_fee) VALUES
('Applied Chemistry', 'Engineering & Science', 'Foundation programme in applied chemistry at UTP.', '3 Years 4 Months', 21000.00, 82500.00);

-- Technology (SPM: BM, Eng, Math, Other Non-Language Subject, Other Non-Language Subject II)
INSERT INTO programmes (name, category, description, duration, foundation_fee, undergraduate_fee) VALUES
('Information System', 'Technology', 'Foundation programme in information systems at UTP.', '3 Years 4 Months', 21000.00, 82500.00),
('Information Technology', 'Technology', 'Foundation programme in information technology at UTP.', '3 Years 4 Months', 21000.00, 82500.00);

-- Computer Science
INSERT INTO programmes (name, category, description, duration, foundation_fee, undergraduate_fee) VALUES
('Computer Science', 'Computer Science', 'Foundation programme in computer science at UTP.', '3 Years 4 Months', 21000.00, 82500.00);

-- Business Management
INSERT INTO programmes (name, category, description, duration, foundation_fee, undergraduate_fee) VALUES
('Business Management', 'Business Management', 'Foundation programme in business management at UTP.', '3 Years 4 Months', 21000.00, 73500.00);

-- Geoscience
INSERT INTO programmes (name, category, description, duration, foundation_fee, undergraduate_fee) VALUES
('Geoscience', 'Engineering & Science', 'Foundation programme in geoscience at UTP.', '3 Years 4 Months', 21000.00, 95200.00);

-- New additions
INSERT INTO programmes (name, category, description, duration, foundation_fee, undergraduate_fee, stem_bonus) VALUES
('Materials Engineering', 'Engineering & Science', 'Foundation programme leading to materials engineering studies at UTP.', '4 Years', 21000.00, 110000.00, 1),
('Industrial Physics', 'Engineering & Science', 'Foundation programme leading to industrial physics studies at UTP.', '3 Years 4 Months', 21000.00, 82500.00, 0);

-- =====================================================
-- ENTRY REQUIREMENTS - SPM (Pass with minimum Grade C)
-- Exactly matching the official UTP entry requirements table
-- =====================================================

-- Group 1: Integrated/Chemical/Mechanical/Petroleum Engineering (SPM)
-- Required: Bahasa Melayu, English, Mathematics, Additional Mathematics, Physics, Chemistry
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(1, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(1, 'SPM', 'English', 'C', 0.90),
(1, 'SPM', 'Mathematics', 'C', 1.00),
(1, 'SPM', 'Additional Mathematics', 'C', 1.00),
(1, 'SPM', 'Physics', 'C', 1.00),
(1, 'SPM', 'Chemistry', 'C', 1.00),

(2, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(2, 'SPM', 'English', 'C', 0.90),
(2, 'SPM', 'Mathematics', 'C', 1.00),
(2, 'SPM', 'Additional Mathematics', 'C', 1.00),
(2, 'SPM', 'Physics', 'C', 1.00),
(2, 'SPM', 'Chemistry', 'C', 1.00),

(3, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(3, 'SPM', 'English', 'C', 0.90),
(3, 'SPM', 'Mathematics', 'C', 1.00),
(3, 'SPM', 'Additional Mathematics', 'C', 1.00),
(3, 'SPM', 'Physics', 'C', 1.00),
(3, 'SPM', 'Chemistry', 'C', 1.00),

(4, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(4, 'SPM', 'English', 'C', 0.90),
(4, 'SPM', 'Mathematics', 'C', 1.00),
(4, 'SPM', 'Additional Mathematics', 'C', 1.00),
(4, 'SPM', 'Physics', 'C', 1.00),
(4, 'SPM', 'Chemistry', 'C', 1.00);

-- Group 2: Applied Physics, Civil, Computer, EE Engineering (SPM)
-- Required: Bahasa Melayu, English, Mathematics, Additional Mathematics, Physics, Chemistry
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(5, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(5, 'SPM', 'English', 'C', 0.90),
(5, 'SPM', 'Mathematics', 'C', 1.00),
(5, 'SPM', 'Additional Mathematics', 'C', 1.00),
(5, 'SPM', 'Physics', 'C', 1.00),
(5, 'SPM', 'Chemistry', 'C', 1.00),

(6, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(6, 'SPM', 'English', 'C', 0.90),
(6, 'SPM', 'Mathematics', 'C', 1.00),
(6, 'SPM', 'Additional Mathematics', 'C', 1.00),
(6, 'SPM', 'Physics', 'C', 1.00),
(6, 'SPM', 'Chemistry', 'C', 1.00),

(7, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(7, 'SPM', 'English', 'C', 0.90),
(7, 'SPM', 'Mathematics', 'C', 1.00),
(7, 'SPM', 'Additional Mathematics', 'C', 1.00),
(7, 'SPM', 'Physics', 'C', 1.00),
(7, 'SPM', 'Chemistry', 'C', 1.00),

(8, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(8, 'SPM', 'English', 'C', 0.90),
(8, 'SPM', 'Mathematics', 'C', 1.00),
(8, 'SPM', 'Additional Mathematics', 'C', 1.00),
(8, 'SPM', 'Physics', 'C', 1.00),
(8, 'SPM', 'Chemistry', 'C', 1.00);

-- Applied Chemistry (SPM)
-- Required: Bahasa Melayu, English, Mathematics, Additional Mathematics, Physics, Chemistry
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(9, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(9, 'SPM', 'English', 'C', 0.90),
(9, 'SPM', 'Mathematics', 'C', 1.00),
(9, 'SPM', 'Additional Mathematics', 'C', 1.00),
(9, 'SPM', 'Physics', 'C', 1.00),
(9, 'SPM', 'Chemistry', 'C', 1.00);

-- Technology: Information System & IT (SPM)
-- Required: Bahasa Melayu, English, Mathematics, Other Non-Language Subject, Other Non-Language Subject II
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(10, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(10, 'SPM', 'English', 'C', 0.90),
(10, 'SPM', 'Mathematics', 'C', 1.00),
(10, 'SPM', 'Other Non-Language Subject', 'C', 0.80),
(10, 'SPM', 'Other Non-Language Subject II', 'C', 0.80),

(11, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(11, 'SPM', 'English', 'C', 0.90),
(11, 'SPM', 'Mathematics', 'C', 1.00),
(11, 'SPM', 'Other Non-Language Subject', 'C', 0.80),
(11, 'SPM', 'Other Non-Language Subject II', 'C', 0.80);

-- Computer Science (SPM)
-- Required: Bahasa Melayu, English, Mathematics, Additional Mathematics, Other Subject I
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(12, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(12, 'SPM', 'English', 'C', 0.90),
(12, 'SPM', 'Mathematics', 'C', 1.00),
(12, 'SPM', 'Additional Mathematics', 'C', 1.00),
(12, 'SPM', 'Other Subject I', 'C', 0.80);

-- Business Management (SPM)
-- Required: Bahasa Melayu, English, Mathematics, Other Subject I, Other Subject II
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(13, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(13, 'SPM', 'English', 'C', 0.90),
(13, 'SPM', 'Mathematics', 'C', 1.00),
(13, 'SPM', 'Other Subject I', 'C', 0.80),
(13, 'SPM', 'Other Subject II', 'C', 0.80);

-- Geoscience (SPM) - same as Engineering group
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(14, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(14, 'SPM', 'English', 'C', 0.90),
(14, 'SPM', 'Mathematics', 'C', 1.00),
(14, 'SPM', 'Additional Mathematics', 'C', 1.00),
(14, 'SPM', 'Physics', 'C', 1.00),
(14, 'SPM', 'Chemistry', 'C', 1.00);

-- Materials Engineering (SPM) - same as Engineering group 2
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(15, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(15, 'SPM', 'English', 'C', 0.90),
(15, 'SPM', 'Mathematics', 'C', 1.00),
(15, 'SPM', 'Additional Mathematics', 'C', 1.00),
(15, 'SPM', 'Physics', 'C', 1.00),
(15, 'SPM', 'Chemistry', 'C', 1.00);

-- Industrial Physics (SPM) - same as Engineering group 2
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(16, 'SPM', 'Bahasa Melayu', 'C', 0.80),
(16, 'SPM', 'English', 'C', 0.90),
(16, 'SPM', 'Mathematics', 'C', 1.00),
(16, 'SPM', 'Additional Mathematics', 'C', 1.00),
(16, 'SPM', 'Physics', 'C', 1.00),
(16, 'SPM', 'Chemistry', 'C', 1.00);

-- =====================================================
-- ENTRY REQUIREMENTS - O-Level / IGCSE (Pass with minimum Grade C)
-- =====================================================

-- Group 1: Integrated/Chemical/Mechanical/Petroleum Engineering (O-Level)
-- Required: Mathematics, Physics, Chemistry, Additional Mathematics, Other Subject I
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(1, 'O-Level', 'Mathematics', 'C', 1.00),
(1, 'O-Level', 'Physics', 'C', 1.00),
(1, 'O-Level', 'Chemistry', 'C', 1.00),
(1, 'O-Level', 'Additional Mathematics', 'C', 1.00),
(1, 'O-Level', 'Other Subject I', 'C', 0.80),

(2, 'O-Level', 'Mathematics', 'C', 1.00),
(2, 'O-Level', 'Physics', 'C', 1.00),
(2, 'O-Level', 'Chemistry', 'C', 1.00),
(2, 'O-Level', 'Additional Mathematics', 'C', 1.00),
(2, 'O-Level', 'Other Subject I', 'C', 0.80),

(3, 'O-Level', 'Mathematics', 'C', 1.00),
(3, 'O-Level', 'Physics', 'C', 1.00),
(3, 'O-Level', 'Chemistry', 'C', 1.00),
(3, 'O-Level', 'Additional Mathematics', 'C', 1.00),
(3, 'O-Level', 'Other Subject I', 'C', 0.80),

(4, 'O-Level', 'Mathematics', 'C', 1.00),
(4, 'O-Level', 'Physics', 'C', 1.00),
(4, 'O-Level', 'Chemistry', 'C', 1.00),
(4, 'O-Level', 'Additional Mathematics', 'C', 1.00),
(4, 'O-Level', 'Other Subject I', 'C', 0.80);

-- Group 2: Applied Physics, Civil, Computer, EE (O-Level)
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(5, 'O-Level', 'Mathematics', 'C', 1.00),
(5, 'O-Level', 'Physics', 'C', 1.00),
(5, 'O-Level', 'Chemistry', 'C', 1.00),
(5, 'O-Level', 'Additional Mathematics', 'C', 1.00),
(5, 'O-Level', 'Other Subject I', 'C', 0.80),

(6, 'O-Level', 'Mathematics', 'C', 1.00),
(6, 'O-Level', 'Physics', 'C', 1.00),
(6, 'O-Level', 'Chemistry', 'C', 1.00),
(6, 'O-Level', 'Additional Mathematics', 'C', 1.00),
(6, 'O-Level', 'Other Subject I', 'C', 0.80),

(7, 'O-Level', 'Mathematics', 'C', 1.00),
(7, 'O-Level', 'Physics', 'C', 1.00),
(7, 'O-Level', 'Chemistry', 'C', 1.00),
(7, 'O-Level', 'Additional Mathematics', 'C', 1.00),
(7, 'O-Level', 'Other Subject I', 'C', 0.80),

(8, 'O-Level', 'Mathematics', 'C', 1.00),
(8, 'O-Level', 'Physics', 'C', 1.00),
(8, 'O-Level', 'Chemistry', 'C', 1.00),
(8, 'O-Level', 'Additional Mathematics', 'C', 1.00),
(8, 'O-Level', 'Other Subject I', 'C', 0.80);

-- Applied Chemistry (O-Level)
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(9, 'O-Level', 'Mathematics', 'C', 1.00),
(9, 'O-Level', 'Physics', 'C', 1.00),
(9, 'O-Level', 'Chemistry', 'C', 1.00),
(9, 'O-Level', 'Additional Mathematics', 'C', 1.00),
(9, 'O-Level', 'Other Subject I', 'C', 0.80);

-- Technology (O-Level)
-- Required: Mathematics, Other Non-Language Subject, Other Non-Language Subject II, Other Subject I, Other Subject II
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(10, 'O-Level', 'Mathematics', 'C', 1.00),
(10, 'O-Level', 'Other Non-Language Subject', 'C', 0.80),
(10, 'O-Level', 'Other Non-Language Subject II', 'C', 0.80),
(10, 'O-Level', 'Other Subject I', 'C', 0.80),
(10, 'O-Level', 'Other Subject II', 'C', 0.80),

(11, 'O-Level', 'Mathematics', 'C', 1.00),
(11, 'O-Level', 'Other Non-Language Subject', 'C', 0.80),
(11, 'O-Level', 'Other Non-Language Subject II', 'C', 0.80),
(11, 'O-Level', 'Other Subject I', 'C', 0.80),
(11, 'O-Level', 'Other Subject II', 'C', 0.80);

-- Computer Science (O-Level)
-- Required: Mathematics, Additional Mathematics, Other Non-Language Subject I, Other Non-Language Subject II, Other Subject I
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(12, 'O-Level', 'Mathematics', 'C', 1.00),
(12, 'O-Level', 'Additional Mathematics', 'C', 1.00),
(12, 'O-Level', 'Other Non-Language Subject I', 'C', 0.80),
(12, 'O-Level', 'Other Non-Language Subject II', 'C', 0.80),
(12, 'O-Level', 'Other Subject I', 'C', 0.80);

-- Business Management (O-Level)
-- Required: Mathematics, Other Subject I, Other Subject II, Other Non-Language Subject III, Other Non-Language Subject IV
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(13, 'O-Level', 'Mathematics', 'C', 1.00),
(13, 'O-Level', 'Other Subject I', 'C', 0.80),
(13, 'O-Level', 'Other Subject II', 'C', 0.80),
(13, 'O-Level', 'Other Non-Language Subject III', 'C', 0.80),
(13, 'O-Level', 'Other Non-Language Subject IV', 'C', 0.80);

-- Geoscience (O-Level)
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(14, 'O-Level', 'Mathematics', 'C', 1.00),
(14, 'O-Level', 'Physics', 'C', 1.00),
(14, 'O-Level', 'Chemistry', 'C', 1.00),
(14, 'O-Level', 'Additional Mathematics', 'C', 1.00),
(14, 'O-Level', 'Other Subject I', 'C', 0.80);

-- Materials Engineering (O-Level)
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(15, 'O-Level', 'Mathematics', 'C', 1.00),
(15, 'O-Level', 'Physics', 'C', 1.00),
(15, 'O-Level', 'Chemistry', 'C', 1.00),
(15, 'O-Level', 'Additional Mathematics', 'C', 1.00),
(15, 'O-Level', 'Other Subject I', 'C', 0.80);

-- Industrial Physics (O-Level)
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight) VALUES
(16, 'O-Level', 'Mathematics', 'C', 1.00),
(16, 'O-Level', 'Physics', 'C', 1.00),
(16, 'O-Level', 'Chemistry', 'C', 1.00),
(16, 'O-Level', 'Additional Mathematics', 'C', 1.00),
(16, 'O-Level', 'Other Subject I', 'C', 0.80);

-- Copy O-Level requirements to IGCSE (identical requirements)
INSERT INTO entry_requirements (programme_id, qual_type, subject, min_grade, weight)
SELECT programme_id, 'IGCSE', subject, min_grade, weight
FROM entry_requirements WHERE qual_type = 'O-Level';

-- =====================================================
-- SEED: Official UTP Sponsoring Agencies
-- Source: YUTP (Yayasan Universiti Teknologi PETRONAS) & TAZU
-- =====================================================

INSERT INTO scholarships (name, description, type, budget_min, budget_max, min_fit_percentage, start_date, end_date) VALUES
('PETRONAS Scholarship Loan Fund', 'Full scholarship loan covering tuition fees and living allowance for all foundation and undergraduate programmes at UTP. Funded directly by PETRONAS.', 'loan', 21000.00, 160000.00, 70, '2026-01-01', '2026-12-31'),
('Tabung Amanah Zakat UTP (TAZU)', 'Financial aid from UTP zakat fund for eligible Muslim students. Covers partial tuition and living expenses.', 'financial_aid', 2000.00, 15000.00, 50, '2026-01-01', '2026-12-31'),
('Yayasan Universiti Teknologi PETRONAS (YUTP)', 'Scholarships and grants administered by the UTP Foundation for outstanding students across all programmes.', 'scholarship', 5000.00, 50000.00, 65, '2026-01-01', '2026-12-31'),
('Perbadanan Tabung Pendidikan Tinggi Nasional (PTPTN)', 'National higher education fund providing study loans to Malaysian students for tuition fees.', 'loan', 21000.00, 160000.00, 50, '2026-01-01', '2026-12-31'),
('Jabatan Perkhidmatan Awam (JPA)', 'Public service department scholarship for excellent students. Covers full tuition, allowance, and book fees.', 'scholarship', 30000.00, 160000.00, 80, '2026-01-01', '2026-12-31'),
('Majlis Amanah Rakyat (MARA)', 'Government agency providing educational sponsorship for Bumiputera students in science and technology fields.', 'sponsorship', 20000.00, 160000.00, 70, '2026-01-01', '2026-12-31'),
('Yayasan Peneraju Pendidikan Bumiputera', 'Scholarship for Bumiputera students in critical fields including engineering and technology.', 'scholarship', 15000.00, 100000.00, 65, '2026-01-01', '2026-12-31'),
('Malaysia Rubber Export Promotion Council', 'Sponsorship for students in engineering and science programmes related to the rubber industry.', 'sponsorship', 10000.00, 50000.00, 60, '2026-01-01', '2026-12-31'),
('Lembaga Zakat Selangor', 'Zakat-funded financial aid for eligible students from Selangor.', 'financial_aid', 2000.00, 10000.00, 50, '2026-01-01', '2026-12-31'),
('Biasiswa Kerajaan Negeri Sabah', 'State government scholarship for students from Sabah.', 'scholarship', 5000.00, 30000.00, 55, '2026-01-01', '2026-12-31'),
('Yayasan Sime Darby', 'Scholarship by Sime Darby Foundation for high-achieving students in engineering and science.', 'scholarship', 20000.00, 160000.00, 75, '2026-01-01', '2026-12-31'),
('Yayasan Gamuda', 'Scholarship by Gamuda Foundation for civil engineering and related disciplines.', 'scholarship', 15000.00, 104500.00, 70, '2026-01-01', '2026-12-31'),
('Technip Geoproduction (M) Sdn Bhd', 'Industry sponsorship for petroleum and mechanical engineering students.', 'sponsorship', 10000.00, 110000.00, 65, '2026-01-01', '2026-12-31'),
('Permodalan Nasional Berhad (PNB)', 'National investment corporation scholarship for outstanding academic achievers.', 'scholarship', 20000.00, 160000.00, 75, '2026-01-01', '2026-12-31'),
('Sarawak Energy Berhad', 'Sponsorship for students from Sarawak pursuing engineering programmes.', 'sponsorship', 15000.00, 110000.00, 60, '2026-01-01', '2026-12-31'),
('Penang Future Foundation', 'Scholarship for students from Penang pursuing STEM programmes.', 'scholarship', 10000.00, 50000.00, 60, '2026-01-01', '2026-12-31'),
('YTL Foundation', 'Scholarship by YTL Corporation for engineering and technology students.', 'scholarship', 15000.00, 110000.00, 70, '2026-01-01', '2026-12-31'),
('Velesto Energy Berhad', 'Industry sponsorship for petroleum engineering and geoscience students.', 'sponsorship', 10000.00, 110000.00, 65, '2026-01-01', '2026-12-31'),
('Baker Hughes', 'International oil and gas company sponsorship for engineering students.', 'sponsorship', 15000.00, 160000.00, 75, '2026-01-01', '2026-12-31'),
('Yayasan UTP - Yayasan Tuanku Abdul Rahman Joint Scholarship', 'Joint scholarship programme between UTP Foundation and Yayasan Tuanku Abdul Rahman.', 'scholarship', 10000.00, 82500.00, 60, '2026-01-01', '2026-12-31'),
('Sapura Energy Sdn Bhd', 'Industry sponsorship for petroleum and mechanical engineering students.', 'sponsorship', 10000.00, 110000.00, 65, '2026-01-01', '2026-12-31'),
('Technip Energies Sdn Bhd', 'Industry sponsorship for chemical and petroleum engineering students.', 'sponsorship', 10000.00, 110000.00, 65, '2026-01-01', '2026-12-31'),
('Halliburton Energy Sdn Bhd', 'International energy company sponsorship for engineering and geoscience students.', 'sponsorship', 15000.00, 160000.00, 70, '2026-01-01', '2026-12-31'),
('Schlumberger WTA (M) Sdn Bhd', 'World-leading oilfield services company sponsorship for engineering students.', 'sponsorship', 15000.00, 160000.00, 75, '2026-01-01', '2026-12-31'),
('Murata Electronics (Malaysia) Sdn Bhd', 'Industry sponsorship for electrical & electronics and computer engineering students.', 'sponsorship', 10000.00, 104500.00, 65, '2026-01-01', '2026-12-31');

-- =====================================================
-- Link scholarships to programmes
-- =====================================================

-- PETRONAS, PTPTN, JPA, MARA, PNB - All programmes
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name IN ('PETRONAS Scholarship Loan Fund', 'Perbadanan Tabung Pendidikan Tinggi Nasional (PTPTN)', 'Jabatan Perkhidmatan Awam (JPA)', 'Majlis Amanah Rakyat (MARA)', 'Permodalan Nasional Berhad (PNB)');

-- TAZU, YUTP, Lembaga Zakat, Biasiswa Sabah, YUTP-YTAR - All programmes
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name IN ('Tabung Amanah Zakat UTP (TAZU)', 'Yayasan Universiti Teknologi PETRONAS (YUTP)', 'Lembaga Zakat Selangor', 'Biasiswa Kerajaan Negeri Sabah', 'Yayasan UTP - Yayasan Tuanku Abdul Rahman Joint Scholarship');

-- Yayasan Peneraju - Engineering & Science + Technology + CS
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Yayasan Peneraju Pendidikan Bumiputera' AND p.category IN ('Engineering & Science', 'Technology', 'Computer Science');

-- Malaysia Rubber Export - Engineering & Science only
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Malaysia Rubber Export Promotion Council' AND p.category = 'Engineering & Science';

-- Sime Darby - Engineering & Science
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Yayasan Sime Darby' AND p.category = 'Engineering & Science';

-- Gamuda - Civil Engineering
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Yayasan Gamuda' AND p.name IN ('Civil Engineering', 'Mechanical Engineering', 'Integrated Engineering');

-- Technip Geoproduction - Petroleum/Mechanical
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Technip Geoproduction (M) Sdn Bhd' AND p.name IN ('Petroleum Engineering', 'Mechanical Engineering', 'Chemical Engineering');

-- Sarawak Energy - Engineering
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Sarawak Energy Berhad' AND p.category = 'Engineering & Science';

-- Penang Future - STEM
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Penang Future Foundation' AND p.category IN ('Engineering & Science', 'Technology', 'Computer Science');

-- YTL Foundation - Engineering + Technology
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'YTL Foundation' AND p.category IN ('Engineering & Science', 'Technology');

-- Velesto - Petroleum + Geoscience
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Velesto Energy Berhad' AND p.name IN ('Petroleum Engineering', 'Geoscience', 'Mechanical Engineering');

-- Baker Hughes - Engineering
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Baker Hughes' AND p.category = 'Engineering & Science';

-- Sapura - Petroleum/Mechanical
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Sapura Energy Sdn Bhd' AND p.name IN ('Petroleum Engineering', 'Mechanical Engineering');

-- Technip Energies - Chemical/Petroleum
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Technip Energies Sdn Bhd' AND p.name IN ('Chemical Engineering', 'Petroleum Engineering');

-- Halliburton - Engineering + Geoscience
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Halliburton Energy Sdn Bhd' AND p.name IN ('Petroleum Engineering', 'Mechanical Engineering', 'Chemical Engineering', 'Geoscience');

-- Schlumberger - Engineering
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Schlumberger WTA (M) Sdn Bhd' AND p.category = 'Engineering & Science';

-- Link new programmes to specific scholarships requested
-- (PETRONAS, PTPTN, JPA, MARA, PNB are already linked by earlier query since they select all programmes, same for TAZU etc.)
-- Link Baker Hughes, Schlumberger, Halliburton to Materials Engineering & Industrial Physics
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name IN ('Baker Hughes', 'Schlumberger WTA (M) Sdn Bhd', 'Halliburton Energy Sdn Bhd', 'Yayasan Sime Darby') 
AND p.name IN ('Materials Engineering', 'Industrial Physics');

-- Murata Electronics - EE/Computer Engineering
INSERT IGNORE INTO scholarship_programme (scholarship_id, programme_id)
SELECT s.id, p.id FROM scholarships s, programmes p
WHERE s.name = 'Murata Electronics (Malaysia) Sdn Bhd' AND p.name IN ('Electrical & Electronics Engineering', 'Computer Engineering', 'Computer Science');

-- =====================================================
-- SEED: Fee Structure
-- Fees effective May 2026, subject to change per UTP
-- =====================================================

INSERT INTO fee_structure (fee_type, description, amount_min, amount_max, frequency, effective_date, notes) VALUES
('Foundation Tuition Fee', 'Total tuition fee for 1-year Foundation programme (all programmes)', 21000.00, NULL, 'total', '2026-05-01', 'Applicable to all Foundation programmes'),
('Undergraduate - Integrated Engineering', 'Total tuition fee for 4-year Integrated Engineering degree', 160000.00, NULL, 'total', '2026-05-01', 'Duration: 4 years'),
('Undergraduate - Chemical/EE/Mechanical/Petroleum Engineering', 'Total tuition fee for Chemical, Electrical & Electronics, Mechanical, or Petroleum Engineering', 110000.00, NULL, 'total', '2026-05-01', 'Duration: 4 years'),
('Undergraduate - Civil/Computer Engineering', 'Total tuition fee for Civil or Computer Engineering', 104500.00, NULL, 'total', '2026-05-01', 'Duration: 4 years'),
('Undergraduate - Geoscience', 'Total tuition fee for Geoscience programme', 95200.00, NULL, 'total', '2026-05-01', 'Duration: 4 years'),
('Undergraduate - Applied Physics/Chemistry/CS/IT/IS', 'Total tuition fee for Applied Physics, Applied Chemistry, Computer Science, Information Technology, or Information Systems', 82500.00, NULL, 'total', '2026-05-01', 'Duration: 3 years 4 months'),
('Undergraduate - Business Management', 'Total tuition fee for Business Management', 73500.00, NULL, 'total', '2026-05-01', 'Duration: 3 years 4 months'),
('Registration Fee', 'One-time registration fee upon enrolment', 1300.00, NULL, 'one-time', '2026-05-01', 'Non-refundable'),
('Hostel Fee', 'Monthly hostel accommodation fee, subject to room type and availability', 280.00, 1000.00, 'monthly', '2026-05-01', 'Room type: single, twin-sharing, etc.');
