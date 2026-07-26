<?php
/**
 * Database Setup & Seeder Script for ClubHub (UIT) V5.0
 * Resets database to a clean, production-ready schema with Super Admin (Dean Sir)
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $db = Database::getConnection();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "===========================================\n";
    echo "  ClubHub Database Setup & Migration Script \n";
    echo "===========================================\n";
    echo "PDO Driver: " . strtoupper($driver) . "\n\n";

    // Disable Foreign Keys for dropping tables cleanly
    if ($driver === 'sqlite') {
        $db->exec("PRAGMA foreign_keys = OFF;");
    } else {
        $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
    }
    
    $tables = ['club_proposals', 'management_committee', 'contact_messages', 'audit_logs', 'gallery_items', 'gallery_albums', 'events', 'leadership', 'club_admins', 'clubs', 'categories', 'users'];
    foreach ($tables as $table) {
        $db->exec("DROP TABLE IF EXISTS `$table`;");
    }
    
    if ($driver === 'sqlite') {
        $db->exec("PRAGMA foreign_keys = ON;");
    } else {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
    }

    // 1. Users Table
    if ($driver === 'sqlite') {
        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id TEXT PRIMARY KEY,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                full_name TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'student',
                status TEXT NOT NULL DEFAULT 'active',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                slug TEXT NOT NULL UNIQUE,
                icon TEXT DEFAULT 'bi-collection-fill',
                description TEXT
            );

            CREATE TABLE IF NOT EXISTS clubs (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                short_name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                category_id INTEGER NOT NULL,
                tagline TEXT,
                description TEXT,
                mission TEXT,
                vision TEXT,
                objectives TEXT,
                logo TEXT DEFAULT '/assets/United Logo.webp',
                cover_image TEXT DEFAULT 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=1200&auto=format&fit=crop',
                founded_year INTEGER DEFAULT 2024,
                status TEXT NOT NULL DEFAULT 'active',
                recruitment_open INTEGER DEFAULT 1,
                recruitment_link TEXT DEFAULT '/contact.html',
                recruitment_deadline DATE DEFAULT NULL,
                recruitment_eligibility TEXT DEFAULT 'Open for all engineering and technology students',
                email TEXT,
                phone TEXT,
                office_location TEXT DEFAULT 'Student Activity Center, UIT',
                meeting_time TEXT DEFAULT 'Wednesdays 04:00 PM',
                meeting_location TEXT DEFAULT 'Seminar Hall, UIT',
                website TEXT,
                instagram TEXT,
                linkedin TEXT,
                github TEXT,
                deleted_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS club_admins (
                club_id TEXT NOT NULL,
                user_id TEXT NOT NULL,
                assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (club_id, user_id),
                FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS leadership (
                id TEXT PRIMARY KEY,
                club_id TEXT NOT NULL,
                name TEXT NOT NULL,
                role_title TEXT NOT NULL,
                category TEXT NOT NULL DEFAULT 'core_member',
                term_year TEXT DEFAULT '2025-2026',
                email TEXT,
                phone TEXT,
                avatar TEXT DEFAULT 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop',
                order_index INTEGER DEFAULT 0,
                FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS events (
                id TEXT PRIMARY KEY,
                club_id TEXT NOT NULL,
                title TEXT NOT NULL,
                slug TEXT NOT NULL,
                banner TEXT DEFAULT 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop',
                description TEXT,
                venue TEXT NOT NULL,
                event_date DATETIME NOT NULL,
                registration_link TEXT DEFAULT 'contact.html',
                status TEXT NOT NULL DEFAULT 'upcoming',
                registered_count INTEGER DEFAULT 0,
                actual_attended INTEGER DEFAULT 0,
                outcomes_summary TEXT,
                budget_utilized REAL DEFAULT 0.0,
                approval_status TEXT DEFAULT 'approved',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS management_committee (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                designation TEXT NOT NULL,
                role_title TEXT NOT NULL,
                photo TEXT DEFAULT 'assets/United Logo.webp',
                bio TEXT,
                order_index INTEGER DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS club_proposals (
                id TEXT PRIMARY KEY,
                proposal_type TEXT NOT NULL DEFAULT 'new_club',
                applicant_name TEXT NOT NULL,
                applicant_email TEXT NOT NULL,
                applicant_phone TEXT,
                proposed_title TEXT NOT NULL,
                objective TEXT NOT NULL,
                faculty_mentor TEXT,
                status TEXT NOT NULL DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS gallery_items (
                id TEXT PRIMARY KEY,
                club_id TEXT NOT NULL,
                media_url TEXT NOT NULL,
                caption TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS contact_messages (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                subject TEXT NOT NULL,
                message TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                is_read INTEGER DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS audit_logs (
                id TEXT PRIMARY KEY,
                user_id TEXT,
                user_name TEXT,
                action TEXT NOT NULL,
                details TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } else {
        // MySQL Tables Creation
        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(36) PRIMARY KEY,
                email VARCHAR(191) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(191) NOT NULL,
                role ENUM('super_admin', 'club_admin', 'student') NOT NULL DEFAULT 'student',
                status ENUM('active', 'suspended', 'inactive') NOT NULL DEFAULT 'active',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL UNIQUE,
                slug VARCHAR(100) NOT NULL UNIQUE,
                icon VARCHAR(50) DEFAULT 'bi-collection-fill',
                description TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS clubs (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(191) NOT NULL,
                short_name VARCHAR(50) NOT NULL,
                slug VARCHAR(191) NOT NULL UNIQUE,
                category_id INT NOT NULL,
                tagline VARCHAR(255),
                description TEXT,
                mission TEXT,
                vision TEXT,
                objectives TEXT,
                logo VARCHAR(255) DEFAULT '/assets/United Logo.webp',
                cover_image VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=1200&auto=format&fit=crop',
                founded_year INT DEFAULT 2024,
                status ENUM('active', 'recruiting', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
                recruitment_open TINYINT(1) DEFAULT 1,
                recruitment_link VARCHAR(255) DEFAULT '/contact.html',
                recruitment_deadline DATE DEFAULT NULL,
                recruitment_eligibility TEXT,
                email VARCHAR(191),
                phone VARCHAR(50),
                office_location VARCHAR(191) DEFAULT 'Student Activity Center, UIT',
                meeting_time VARCHAR(191) DEFAULT 'Wednesdays 04:00 PM',
                meeting_location VARCHAR(191) DEFAULT 'Seminar Hall, UIT',
                website VARCHAR(255),
                instagram VARCHAR(255),
                linkedin VARCHAR(255),
                github VARCHAR(255),
                deleted_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS club_admins (
                club_id VARCHAR(36) NOT NULL,
                user_id VARCHAR(36) NOT NULL,
                assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (club_id, user_id),
                FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS leadership (
                id VARCHAR(36) PRIMARY KEY,
                club_id VARCHAR(36) NOT NULL,
                name VARCHAR(191) NOT NULL,
                role_title VARCHAR(100) NOT NULL,
                category ENUM('faculty_coordinator', 'president', 'vice_president', 'secretary', 'treasurer', 'core_member') NOT NULL DEFAULT 'core_member',
                term_year VARCHAR(50) DEFAULT '2025-2026',
                email VARCHAR(191),
                phone VARCHAR(50),
                avatar VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop',
                order_index INT DEFAULT 0,
                FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS events (
                id VARCHAR(36) PRIMARY KEY,
                club_id VARCHAR(36) NOT NULL,
                title VARCHAR(191) NOT NULL,
                slug VARCHAR(191) NOT NULL,
                banner VARCHAR(255) DEFAULT 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop',
                description TEXT,
                venue VARCHAR(191) NOT NULL,
                event_date DATETIME NOT NULL,
                registration_link VARCHAR(255) DEFAULT 'contact.html',
                status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
                registered_count INT DEFAULT 0,
                actual_attended INT DEFAULT 0,
                outcomes_summary TEXT,
                budget_utilized DECIMAL(10,2) DEFAULT 0.00,
                approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS management_committee (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(191) NOT NULL,
                designation VARCHAR(191) NOT NULL,
                role_title VARCHAR(191) NOT NULL,
                photo VARCHAR(255) DEFAULT 'assets/United Logo.webp',
                bio TEXT,
                order_index INT DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS club_proposals (
                id VARCHAR(36) PRIMARY KEY,
                proposal_type ENUM('new_club', 'new_event') NOT NULL DEFAULT 'new_club',
                applicant_name VARCHAR(191) NOT NULL,
                applicant_email VARCHAR(191) NOT NULL,
                applicant_phone VARCHAR(50),
                proposed_title VARCHAR(191) NOT NULL,
                objective TEXT NOT NULL,
                faculty_mentor VARCHAR(191),
                status ENUM('pending', 'under_review', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS gallery_items (
                id VARCHAR(36) PRIMARY KEY,
                club_id VARCHAR(36) NOT NULL,
                media_url VARCHAR(255) NOT NULL,
                caption VARCHAR(255),
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS contact_messages (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(191) NOT NULL,
                email VARCHAR(191) NOT NULL,
                subject VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                is_read TINYINT(1) DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS audit_logs (
                id VARCHAR(36) PRIMARY KEY,
                user_id VARCHAR(36),
                user_name VARCHAR(191),
                action VARCHAR(100) NOT NULL,
                details TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    echo "[+] Database schema re-initialized successfully.\n";

    // Seed Categories
    $categories = [
        ['Technical', 'technical', 'bi-code-slash', 'Coding, Robotics, AI, Web Dev and Tech Innovation'],
        ['Cultural', 'cultural', 'bi-masks', 'Dance, Music, Drama and Creative Arts'],
        ['Sports', 'sports', 'bi-trophy', 'Athletics, Cricket, Football and Outdoor Games'],
        ['Social Impact', 'social', 'bi-heart', 'Community Service, Volunteering and CSR'],
        ['Entrepreneurship', 'entrepreneurship', 'bi-lightbulb', 'Startups, Innovation and E-Cell Drives'],
        ['Media & Creative', 'creative', 'bi-camera', 'Photography, Videography and Design'],
        ['Academic', 'academic', 'bi-journal-text', 'Literature, Debating, Quiz and Finance']
    ];

    $catStmt = $db->prepare("INSERT INTO categories (name, slug, icon, description) VALUES (?, ?, ?, ?)");
    foreach ($categories as $cat) {
        $catStmt->execute($cat);
    }
    echo "[+] Seeded 7 categories.\n";

    // Seed Dean Sir (Super Admin)
    $deanId = 'usr_dean_' . bin2hex(random_bytes(4));
    $deanEmail = 'admin@uit.edu';
    $deanPasswordHash = password_hash('AdminPassword123!', PASSWORD_DEFAULT);
    $deanName = 'Dean of Student Affairs';

    $userStmt = $db->prepare("INSERT INTO users (id, email, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, 'super_admin', 'active')");
    $userStmt->execute([$deanId, $deanEmail, $deanPasswordHash, $deanName]);

    echo "[+] Super Admin (Dean Sir) created:\n";
    echo "    Email: $deanEmail\n";
    echo "    Password: AdminPassword123!\n\n";

    echo "===========================================\n";
    echo "  Setup Completed Successfully! \n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "[-] Setup Error: " . $e->getMessage() . "\n";
}
