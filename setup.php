<?php
/**
 * Database Setup & Seeder Script for CCMS V1.0
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

try {
    $db = Database::getConnection();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "Using PDO driver: " . strtoupper($driver) . "\n";

    // 1. Create Tables
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
                logo TEXT DEFAULT 'assets/images/default-logo.png',
                cover_image TEXT DEFAULT 'assets/images/default-cover.jpg',
                founded_year INTEGER DEFAULT 2024,
                status TEXT NOT NULL DEFAULT 'active',
                recruitment_open INTEGER DEFAULT 0,
                recruitment_link TEXT DEFAULT '',
                recruitment_deadline DATE DEFAULT NULL,
                recruitment_eligibility TEXT,
                email TEXT,
                phone TEXT,
                office_location TEXT,
                meeting_time TEXT,
                meeting_location TEXT,
                website TEXT,
                instagram TEXT,
                linkedin TEXT,
                github TEXT,
                discord TEXT,
                whatsapp TEXT,
                deleted_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS club_admins (
                club_id TEXT NOT NULL,
                user_id TEXT NOT NULL,
                assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (club_id, user_id)
            );

            CREATE TABLE IF NOT EXISTS leadership (
                id TEXT PRIMARY KEY,
                club_id TEXT NOT NULL,
                name TEXT NOT NULL,
                role_title TEXT NOT NULL,
                category TEXT NOT NULL,
                email TEXT,
                phone TEXT,
                avatar TEXT DEFAULT 'assets/images/default-avatar.png',
                order_index INTEGER DEFAULT 0
            );

            CREATE TABLE IF NOT EXISTS events (
                id TEXT PRIMARY KEY,
                club_id TEXT NOT NULL,
                title TEXT NOT NULL,
                slug TEXT NOT NULL,
                banner TEXT DEFAULT 'assets/images/default-event.jpg',
                description TEXT,
                venue TEXT NOT NULL,
                event_date DATETIME NOT NULL,
                registration_link TEXT,
                status TEXT NOT NULL DEFAULT 'upcoming',
                attendance_count INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS activities (
                id TEXT PRIMARY KEY,
                club_id TEXT NOT NULL,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                image TEXT,
                tag TEXT DEFAULT 'General',
                status TEXT DEFAULT 'published',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS gallery_albums (
                id TEXT PRIMARY KEY,
                club_id TEXT NOT NULL,
                title TEXT NOT NULL,
                description TEXT,
                cover_image TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS gallery_items (
                id TEXT PRIMARY KEY,
                album_id TEXT NOT NULL,
                media_url TEXT NOT NULL,
                media_type TEXT DEFAULT 'image',
                caption TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS achievements (
                id TEXT PRIMARY KEY,
                club_id TEXT NOT NULL,
                title TEXT NOT NULL,
                achievement_date DATE NOT NULL,
                description TEXT,
                certificate_url TEXT
            );

            CREATE TABLE IF NOT EXISTS audit_logs (
                id TEXT PRIMARY KEY,
                user_id TEXT,
                user_name TEXT,
                action TEXT NOT NULL,
                target_type TEXT,
                target_id TEXT,
                details TEXT,
                ip_address TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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

            CREATE TABLE IF NOT EXISTS settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT
            );
        ");
    } else {
        $sql = file_get_contents(__DIR__ . '/database/schema.sql');
        $db->exec($sql);
    }
    echo "Tables initialized successfully.\n";

    // Seed Categories
    $categories = [
        ['Coding & Dev', 'coding', 'bi-code-slash', 'Software engineering, competitive programming & web development.'],
        ['Technical & Innovation', 'technical', 'bi-cpu', 'Robotics, AI, hardware & emerging technologies.'],
        ['Cultural & Arts', 'cultural', 'bi-palette', 'Music, dance, drama, fine arts, and literary events.'],
        ['Sports & Athletics', 'sports', 'bi-trophy', 'Intramural, competitive, and recreational sports.'],
        ['Entrepreneurship', 'entrepreneurship', 'bi-rocket-takeoff', 'Startups, pitch competitions, and business incubators.'],
        ['Social Service & NSS', 'social-service', 'bi-heart-fill', 'Community outreach, blood drives, and social welfare.']
    ];

    $catMap = [];
    foreach ($categories as $cat) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ?");
        $stmt->execute([$cat[1]]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            $stmtInsert = $db->prepare("INSERT INTO categories (name, slug, icon, description) VALUES (?, ?, ?, ?)");
            $stmtInsert->execute([$cat[0], $cat[1], $cat[2], $cat[3]]);
            $id = $db->lastInsertId();
        }
        $catMap[$cat[1]] = $id;
    }

    // Seed Super Admin
    $superAdminEmail = 'admin@uit.edu';
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$superAdminEmail]);
    $superAdminId = $stmt->fetchColumn();
    if (!$superAdminId) {
        $superAdminId = generate_uuid();
        $hash = password_hash('AdminPassword123!', PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (id, email, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, 'super_admin', 'active')");
        $stmt->execute([$superAdminId, $superAdminEmail, $hash, 'Dr. Rajesh Verma (Super Admin)']);
        echo "Super Admin created (admin@uit.edu / AdminPassword123!).\n";
    }

    // Seed Demo Club Admin
    $gfgAdminEmail = 'geeksforgeeks@uit.edu';
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$gfgAdminEmail]);
    $gfgAdminId = $stmt->fetchColumn();
    if (!$gfgAdminId) {
        $gfgAdminId = generate_uuid();
        $hash = password_hash('ClubPassword123!', PASSWORD_BCRYPT);
        $stmt = $db->prepare("INSERT INTO users (id, email, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, 'club_admin', 'active')");
        $stmt->execute([$gfgAdminId, $gfgAdminEmail, $hash, 'Aarav Sharma (President)']);
        echo "Club Admin created (geeksforgeeks@uit.edu / ClubPassword123!).\n";
    }

    // Seed Demo Clubs
    $clubsData = [
        [
            'name' => 'GeeksforGeeks Student Chapter',
            'short_name' => 'GFG Chapter',
            'slug' => 'geeksforgeeks',
            'category_slug' => 'coding',
            'tagline' => 'Empowering coders, algorithms enthusiasts, and tech leaders on campus.',
            'description' => 'The official GeeksforGeeks Student Chapter at UIT is dedicated to fostering a vibrant coding culture through hackathons, DSA bootcamps, web development workshops, and mock interview preparations.',
            'mission' => 'To bridge the gap between academic theory and industry engineering standards for every student.',
            'vision' => 'To become the premier hub for technical talent and competitive programmers in the region.',
            'objectives' => 'Organize weekly coding challenges, mentor juniors in algorithms, and host guest lectures from top tech engineers.',
            'founded_year' => 2022,
            'status' => 'recruiting',
            'recruitment_open' => 1,
            'recruitment_link' => 'https://forms.gle/uit-gfg-2026',
            'recruitment_deadline' => '2026-08-30',
            'recruitment_eligibility' => 'Open to all 1st, 2nd, and 3rd year B.Tech students with a passion for software development.',
            'email' => 'gfg@uit.edu',
            'phone' => '+91 98765 43210',
            'office_location' => 'Tech Block B, Room 304',
            'meeting_time' => 'Every Wednesday at 5:00 PM',
            'meeting_location' => 'Lab 4, Computer Center',
            'website' => 'https://gfg.uit.edu',
            'github' => 'https://github.com/uit-gfg',
            'linkedin' => 'https://linkedin.com/company/uit-gfg',
            'instagram' => 'https://instagram.com/uit_gfg',
            'admin_id' => $gfgAdminId
        ],
        [
            'name' => 'Robotics & AI Society',
            'short_name' => 'RoboAI',
            'slug' => 'robotics-ai',
            'category_slug' => 'technical',
            'tagline' => 'Building tomorrow’s autonomous systems, drones, and AI models.',
            'description' => 'Robotics & AI Society brings together electronics, mechanical, and computer science students to design competitive autonomous robots, IoT hardware, and computer vision projects.',
            'mission' => 'Incur innovation through hands-on hardware and embedded system engineering.',
            'vision' => 'Winning national inter-collegiate robotics leagues and AI hackathons.',
            'objectives' => 'Conduct Arduino/ESP32 workshops, build autonomous bots, and submit research papers.',
            'founded_year' => 2021,
            'status' => 'active',
            'recruitment_open' => 0,
            'recruitment_link' => '',
            'recruitment_deadline' => null,
            'recruitment_eligibility' => 'Engineering students across all branches.',
            'email' => 'robotics@uit.edu',
            'phone' => '+91 98765 43211',
            'office_location' => 'Mechanical Wing Lab 2',
            'meeting_time' => 'Fridays at 4:30 PM',
            'meeting_location' => 'Robotics Workshop Room',
            'website' => 'https://robotics.uit.edu',
            'github' => 'https://github.com/uit-robotics',
            'linkedin' => 'https://linkedin.com/company/uit-robotics',
            'instagram' => 'https://instagram.com/uit_robotics',
            'admin_id' => null
        ],
        [
            'name' => 'Kala-Kriti Cultural Club',
            'short_name' => 'KalaKriti',
            'slug' => 'kala-kriti',
            'category_slug' => 'cultural',
            'tagline' => 'Celebrating art, drama, rhythm, and campus cultural diversity.',
            'description' => 'Kala-Kriti is the heart of cultural expression at UIT, organizing annual fests, street plays, classical music nights, and art exhibitions.',
            'mission' => 'Nurturing artistic expression and promoting cultural harmony.',
            'vision' => 'Making college life vibrant, creative, and unforgettable.',
            'objectives' => 'Host annual cultural fest, conduct drama rehearsals, and represent UIT in youth festivals.',
            'founded_year' => 2019,
            'status' => 'active',
            'recruitment_open' => 1,
            'recruitment_link' => 'https://forms.gle/kalakriti-auditions',
            'recruitment_deadline' => '2026-09-15',
            'recruitment_eligibility' => 'Dancers, singers, actors, artists, and event organizers.',
            'email' => 'kalakriti@uit.edu',
            'phone' => '+91 98765 43212',
            'office_location' => 'Student Activity Center (SAC) 101',
            'meeting_time' => 'Saturdays at 3:00 PM',
            'meeting_location' => 'Open Air Theatre (OAT)',
            'website' => '',
            'github' => '',
            'linkedin' => '',
            'instagram' => 'https://instagram.com/kalakriti_uit',
            'admin_id' => null
        ]
    ];

    foreach ($clubsData as $c) {
        $stmt = $db->prepare("SELECT id FROM clubs WHERE slug = ?");
        $stmt->execute([$c['slug']]);
        $clubId = $stmt->fetchColumn();

        if (!$clubId) {
            $clubId = generate_uuid();
            $stmtIns = $db->prepare("INSERT INTO clubs 
                (id, name, short_name, slug, category_id, tagline, description, mission, vision, objectives, founded_year, status, recruitment_open, recruitment_link, recruitment_deadline, recruitment_eligibility, email, phone, office_location, meeting_time, meeting_location, website, github, linkedin, instagram)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([
                $clubId, $c['name'], $c['short_name'], $c['slug'], $catMap[$c['category_slug']], $c['tagline'],
                $c['description'], $c['mission'], $c['vision'], $c['objectives'], $c['founded_year'], $c['status'],
                $c['recruitment_open'], $c['recruitment_link'], $c['recruitment_deadline'], $c['recruitment_eligibility'],
                $c['email'], $c['phone'], $c['office_location'], $c['meeting_time'], $c['meeting_location'],
                $c['website'], $c['github'], $c['linkedin'], $c['instagram']
            ]);

            if ($c['admin_id']) {
                $stmtAdmin = $db->prepare("INSERT INTO club_admins (club_id, user_id) VALUES (?, ?)");
                $stmtAdmin->execute([$clubId, $c['admin_id']]);
            }

            // Seed Leadership for this club
            $leadershipData = [
                [$clubId, 'Dr. S. K. Gupta', 'Faculty Advisor', 'faculty_coordinator', 'sk.gupta@uit.edu', '+91 99999 11111', 1],
                [$clubId, 'Aarav Sharma', 'President', 'president', 'aarav.sharma@uit.edu', '+91 98765 00001', 2],
                [$clubId, 'Ananya Singh', 'Vice President', 'vice_president', 'ananya.singh@uit.edu', '+91 98765 00002', 3],
                [$clubId, 'Rohan Verma', 'Secretary', 'secretary', 'rohan.verma@uit.edu', '+91 98765 00003', 4]
            ];
            foreach ($leadershipData as $lead) {
                $stmtL = $db->prepare("INSERT INTO leadership (id, club_id, name, role_title, category, email, phone, order_index) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtL->execute([generate_uuid(), $lead[0], $lead[1], $lead[2], $lead[3], $lead[4], $lead[5], $lead[6]]);
            }

            // Seed Events
            $events = [
                [$clubId, 'CodeBlitz 2026 Hackathon', 'codeblitz-2026', 'A 24-hour inter-collegiate coding marathons featuring AI, Web, and Algorithmic challenges with prizes worth 50,000 INR.', 'Auditorium Hall A', '2026-08-15 09:00:00', 'https://codeblitz.uit.edu', 'upcoming', 150],
                [$clubId, 'Data Structures & Algorithms Bootcamp', 'dsa-bootcamp-2026', 'A hands-on workshop covering arrays, trees, graphs, and dynamic programming for campus placements.', 'Computer Center Lab 3', '2026-06-10 14:00:00', '', 'completed', 85]
            ];
            foreach ($events as $ev) {
                $stmtE = $db->prepare("INSERT INTO events (id, club_id, title, slug, description, venue, event_date, registration_link, status, attendance_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtE->execute([generate_uuid(), $ev[0], $ev[1], $ev[2], $ev[3], $ev[4], $ev[5], $ev[6], $ev[7], $ev[8]]);
            }

            // Seed Activities
            $activities = [
                [$clubId, 'Recruitment Drive 2026 Announced!', 'We are excited to announce that applications for 2026 core team roles and technical leads are now OPEN! Apply via the link on our profile before August 30.', 'Recruitment', 'published'],
                [$clubId, 'DSA Workshop Series Completed Successfully', 'Over 85 students participated in our 5-day placement prep series. Certificates have been issued to all active participants.', 'Workshop', 'published']
            ];
            foreach ($activities as $act) {
                $stmtA = $db->prepare("INSERT INTO activities (id, club_id, title, content, tag, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtA->execute([generate_uuid(), $act[0], $act[1], $act[2], $act[3], $act[4]]);
            }

            // Seed Achievements
            $achievements = [
                [$clubId, '1st Place in Regional Inter-College Hackathon', '2026-03-20', 'Team UIT GFG secured 1st prize out of 45 competing colleges in national AI buildathon.'],
                [$clubId, 'Best Student Chapter Award 2025', '2025-12-15', 'Recognized by college administration for highest student engagement and technical events count.']
            ];
            foreach ($achievements as $ach) {
                $stmtAch = $db->prepare("INSERT INTO achievements (id, club_id, title, achievement_date, description) VALUES (?, ?, ?, ?, ?)");
                $stmtAch->execute([generate_uuid(), $ach[0], $ach[1], $ach[2], $ach[3]]);
            }

            echo "Seeded club: {$c['name']}\n";
        }
    }

    // Seed Settings
    $settings = [
        'site_title' => 'College Club Management System',
        'college_name' => 'University Institute of Technology (UIT)',
        'contact_email' => 'clubs@uit.edu',
        'contact_phone' => '+91 (0522) 2345678',
        'featured_club_slugs' => 'geeksforgeeks,robotics-ai,kala-kriti'
    ];
    foreach ($settings as $k => $v) {
        if ($driver === 'sqlite') {
            $stmt = $db->prepare("INSERT OR REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$k, $v]);
        } else {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute([$k, $v]);
        }
    }

    echo "Setup completed successfully!\n";

} catch (Exception $e) {
    echo "Setup Failed: " . $e->getMessage() . "\n";
}
