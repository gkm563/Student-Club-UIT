<?php
/**
 * Seeder Script for GDGOC UIT (Google Developer Groups On Campus - UIT)
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();
    echo "===========================================\n";
    echo "  Seeding GDGOC UIT Data into Database     \n";
    echo "===========================================\n";

    // 1. Get Technical Category ID
    $catStmt = $db->prepare("SELECT id FROM categories WHERE slug = 'technical' LIMIT 1");
    $catStmt->execute();
    $catId = $catStmt->fetchColumn() ?: 1;

    // 2. Insert GDGOC UIT Club
    $clubId = 'clb_gdgoc_uit_2026';
    $slug = 'gdgoc-uit';
    $name = 'Google Developer Groups On Campus - UIT';
    $shortName = 'GDGOC UIT';
    $tagline = 'Building, Innovating & Empowering Tech Enthusiasts at UIT';
    $description = 'Google Developer Groups On Campus - UIT is a university-based community group for students interested in Google developer technologies. From beginners to expert developers, GDGOC UIT brings together passionate coders, problem solvers, and innovators to learn, build, and solve real-world problems through hackathons, workshops, and study jams.';
    $mission = 'To bridge the gap between theory and practice by cultivating developer skills, hosting hands-on workshops on AI, Cloud, and Web, and solving community challenges.';
    $vision = 'To build the most vibrant, inclusive, and innovative student developer ecosystem at United Institute of Technology.';
    $logo = 'https://images.unsplash.com/photo-1573164713988-8665fc963095?q=80&w=400&auto=format&fit=crop'; // Google/Tech aesthetic logo fallback or asset
    $coverImage = 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=1200&auto=format&fit=crop';

    // Delete existing GDGOC if re-running
    $db->exec("DELETE FROM clubs WHERE id = '$clubId' OR slug = '$slug'");
    $db->exec("DELETE FROM users WHERE email = 'gdgoc@uit.edu'");

    $stmt = $db->prepare("
        INSERT INTO clubs (
            id, name, short_name, slug, category_id, tagline, description, mission, vision,
            logo, cover_image, founded_year, status, recruitment_open, recruitment_link,
            email, phone, office_location, meeting_time, meeting_location, website, instagram, linkedin, github
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, 2023, 'active', 1, '/contact.html',
            'gdgoc@uit.edu', '+91 98765 12345', 'Lab 3, Computer Science Dept, UIT', 'Fridays 04:00 PM', 'Computer Center Auditorium, UIT',
            'https://gdg.community.dev/united-institute-of-technology/', 'https://instagram.com/gdgoc_uit', 'https://linkedin.com/company/gdgoc-uit', 'https://github.com/gdgoc-uit'
        )
    ");
    $stmt->execute([
        $clubId, $name, $shortName, $slug, $catId, $tagline, $description, $mission, $vision,
        $logo, $coverImage
    ]);
    echo "[+] Created Club: $name ($shortName)\n";

    // 3. Create Admin Account for GDGOC Leadership
    $userId = 'usr_gdgoc_admin';
    $email = 'gdgoc@uit.edu';
    $passHash = password_hash('GdgocUIT2026!', PASSWORD_DEFAULT);
    $adminName = 'Shivansh Singh (GDGOC Lead)';

    $uStmt = $db->prepare("
        INSERT INTO users (id, email, password_hash, full_name, role, status)
        VALUES (?, ?, ?, ?, 'club_admin', 'active')
    ");
    $uStmt->execute([$userId, $email, $passHash, $adminName]);

    $caStmt = $db->prepare("INSERT INTO club_admins (club_id, user_id) VALUES (?, ?)");
    $caStmt->execute([$clubId, $userId]);
    echo "[+] Created User Credentials for GDGOC: $email / GdgocUIT2026!\n";

    // 4. Seed Annual Leadership & Tenure Roster (Founding Lead 2023-24, Lead 2024-25, Lead 2025-26, Faculty)
    $db->exec("DELETE FROM leadership WHERE club_id = '$clubId'");

    $roster = [
        [
            'ldr_shivansh', $clubId, 'Shivansh Singh', 'GDG Lead & President', 'president', '2025-2026',
            'shivansh@uit.edu', '+91 98765 00001', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop', 1
        ],
        [
            'ldr_sarthak', $clubId, 'Sarthak Singh', 'GDG Lead & Past President', 'president', '2024-2025',
            'sarthak@uit.edu', '+91 98765 00002', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop', 2
        ],
        [
            'ldr_hridyesh', $clubId, 'Hridyesh Gupta', 'Founding GDG Lead', 'president', '2023-2024',
            'hridyesh@uit.edu', '+91 98765 00003', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=400&auto=format&fit=crop', 3
        ],
        [
            'ldr_faculty', $clubId, 'Dr. A. K. Sharma', 'Faculty Coordinator & Advisor', 'faculty_coordinator', '2025-2026',
            'aksharma@uit.edu', '+91 98765 00004', 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?q=80&w=400&auto=format&fit=crop', 4
        ]
    ];

    $lStmt = $db->prepare("
        INSERT INTO leadership (id, club_id, name, role_title, category, term_year, email, phone, avatar, order_index)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($roster as $r) {
        $lStmt->execute($r);
    }
    echo "[+] Seeded 4 Leadership Roster Members across 3 Tenures (2023-24, 2024-25, 2025-26).\n";

    // 5. Seed GDGOC Events (Study Jam, Solution Challenge, Mo Byte, TechSprint, DevFest, Build with AI)
    $db->exec("DELETE FROM events WHERE club_id = '$clubId'");

    $events = [
        [
            'evt_studyjam_2026', $clubId, 'Google Cloud Study Jam 2026', 'google-cloud-study-jam-2026',
            'https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=800&auto=format&fit=crop',
            'Hands-on cloud computing workshops, Google Cloud Skills Boost badges, and Generative AI training.',
            'Computer Lab 2 & Online', '2026-08-15 10:00:00', '/contact.html', 'upcoming'
        ],
        [
            'evt_solchallenge_2026', $clubId, 'Google Solution Challenge 2026', 'google-solution-challenge-2026',
            'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=800&auto=format&fit=crop',
            'Build solutions for one or more of the United Nations 17 Sustainable Development Goals using Google tech.',
            'Seminar Hall 1, UIT', '2026-09-05 09:30:00', '/contact.html', 'upcoming'
        ],
        [
            'evt_buildwithai_2026', $clubId, 'Build With AI Workshop', 'build-with-ai-workshop',
            'https://images.unsplash.com/photo-1677442136019-21780efad99a?q=80&w=800&auto=format&fit=crop',
            'Deep dive into Gemini API, Firebase Genkit, and building intelligent web & mobile applications.',
            'Auditorium, UIT', '2026-10-10 11:00:00', '/contact.html', 'upcoming'
        ],
        [
            'evt_devfest_2025', $clubId, 'DevFest UIT 2025', 'devfest-uit-2025',
            'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop',
            'Our flagship annual developer conference featuring industry speakers, live coding sessions, and tech talks.',
            'Main Campus Auditorium', '2025-11-20 09:00:00', '/contact.html', 'completed'
        ],
        [
            'evt_techsprint_2025', $clubId, 'TechSprint Hackathon', 'techsprint-hackathon-2025',
            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=800&auto=format&fit=crop',
            '24-Hour non-stop coding hackathon challenging students to innovate in AI, Web3, and Mobile Apps.',
            'Student Activity Center', '2025-04-12 10:00:00', '/contact.html', 'completed'
        ],
        [
            'evt_mobyte_2024', $clubId, 'Mo Byte Mobile Dev Summit', 'mo-byte-mobile-dev-summit',
            'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?q=80&w=800&auto=format&fit=crop',
            'Mobile app development showcase with Flutter and Android Jetpack Compose fundamentals.',
            'Computer Lab 1', '2024-10-18 14:00:00', '/contact.html', 'completed'
        ]
    ];

    $eStmt = $db->prepare("
        INSERT INTO events (id, club_id, title, slug, banner, description, venue, event_date, registration_link, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($events as $ev) {
        $eStmt->execute($ev);
    }
    echo "[+] Seeded 6 GDGOC Events (Cloud Study Jam, Solution Challenge, Build With AI, DevFest, TechSprint, Mo Byte).\n";

    echo "===========================================\n";
    echo "  GDGOC UIT Seeded Successfully!            \n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "[-] Seeding Error: " . $e->getMessage() . "\n";
}
