<?php
// tests/bootstrap.php

// Define test environment
define('APP_ENV', 'testing');
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTPS'] = 'off';

// In-memory PDO connection for SQLite
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Mock the getDB() function globally to return the SQLite memory instance
if (!function_exists('getDB')) {
    function getDB() {
        global $db;
        return $db;
    }
}

// Create minimal schema mirroring MySQL
$db->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        ic_number TEXT NOT NULL UNIQUE,
        phone TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'student',
        email_verified INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE programmes (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        category TEXT NOT NULL,
        description TEXT,
        duration TEXT,
        is_active INTEGER DEFAULT 1
    );

    CREATE TABLE entry_requirements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        programme_id INTEGER NOT NULL,
        qual_type TEXT NOT NULL,
        subject TEXT NOT NULL,
        min_grade TEXT NOT NULL,
        weight REAL DEFAULT 1.00,
        FOREIGN KEY (programme_id) REFERENCES programmes(id)
    );

    CREATE TABLE scholarships (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        type TEXT DEFAULT 'scholarship',
        min_fit_percentage INTEGER DEFAULT 50,
        is_active INTEGER DEFAULT 1
    );

    CREATE TABLE scholarship_programme (
        scholarship_id INTEGER NOT NULL,
        programme_id INTEGER NOT NULL,
        PRIMARY KEY (scholarship_id, programme_id)
    );
    
    CREATE TABLE qualifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        qual_type TEXT NOT NULL
    );

    CREATE TABLE grades (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        qualification_id INTEGER NOT NULL,
        subject TEXT NOT NULL,
        grade TEXT NOT NULL
    );
    
    CREATE TABLE applications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        qualification_id INTEGER NOT NULL,
        programme_id_1 INTEGER,
        programme_id_2 INTEGER,
        programme_id_3 INTEGER,
        status TEXT NOT NULL DEFAULT 'submitted'
    );
    
    CREATE TABLE eligibility_results (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        application_id INTEGER NOT NULL,
        programme_id INTEGER NOT NULL,
        eligible INTEGER NOT NULL DEFAULT 0,
        fit_percentage REAL DEFAULT 0,
        recommendation_text TEXT
    );
    
    CREATE TABLE login_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT NOT NULL,
        attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE TABLE documents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        doc_type TEXT NOT NULL,
        filename TEXT NOT NULL,
        original_name TEXT NOT NULL,
        file_size INTEGER NOT NULL
    );
");

// Seed minimal test data
// 1 admin
$db->exec("INSERT INTO users (full_name, email, password_hash, ic_number, phone, role, email_verified) VALUES ('Admin', 'admin@test.com', 'HASH', '0000', '000', 'admin', 1)");
// 1 student
$db->exec("INSERT INTO users (full_name, email, password_hash, ic_number, phone, role, email_verified) VALUES ('Student', 'student@test.com', 'HASH', '1111', '111', 'student', 1)");

// 2 programmes
$db->exec("INSERT INTO programmes (name, category) VALUES ('Computer Science', 'Technology')");
$db->exec("INSERT INTO programmes (name, category) VALUES ('Mechanical Engineering', 'Engineering')");

// 1 scholarship
$db->exec("INSERT INTO scholarships (name, min_fit_percentage) VALUES ('Test Scholarship', 70)");
$db->exec("INSERT INTO scholarship_programme (scholarship_id, programme_id) VALUES (1, 1), (1, 2)");
