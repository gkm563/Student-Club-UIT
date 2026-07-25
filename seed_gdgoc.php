<?php
/**
 * Seeder Script for GDGOC UIT (Google Developer Groups On Campus - UIT)
 * Includes all official GDGoC UIT past & upcoming events!
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
    $logo = 'https://images.unsplash.com/photo-1573164713988-8665fc963095?q=80&w=400&auto=format&fit=crop';
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

    // 4. Seed Annual Leadership & Tenure Roster
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
    echo "[+] Seeded 4 Leadership Roster Members across 3 Tenures.\n";

    // 5. Seed Official GDGOC Events
    $db->exec("DELETE FROM events WHERE club_id = '$clubId'");

    $events = [
        [
            'evt_gdgoc_sol_cloud_recap_2026',
            $clubId,
            'Unlocking Innovation: Google Solution Challenge & Cloud Study Jam Recap',
            'unlocking-innovation-google-solution-challenge-cloud-study-jam-recap',
            'https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=800&auto=format&fit=crop',
            'GDG-UIT hosted a memorable Info Session on the Google Solution Challenge and Cloud Study Jam Tier - 1. Participants gained insights into how to innovate and solve real-world problems using Google technologies. Attendees received cloud insights, swags, and networking opportunities.',
            'UIT, UPSIDC Industrial Area, Naini, Prayagraj 211010',
            '2026-04-01 12:00:00',
            'https://gdg.community.dev/united-institute-of-technology/',
            'completed'
        ],
        [
            'evt_gdgoc_flutterflow_2025',
            $clubId,
            'Kickstart App Development with Flutterflow',
            'kickstart-app-development-with-flutterflow',
            'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?q=80&w=800&auto=format&fit=crop',
            'Join us for an exciting session, "Kickstart App Development with Flutterflow," designed to empower you with the skills needed to create visually stunning and effective mobile applications using Flutterflow, Firebase, and Flutter without extensive coding knowledge.',
            'UIT Induction Hall, D3, UPSIDC Industrial Area, Naini, Prayagraj 211010',
            '2025-11-27 09:30:00',
            'https://gdg.community.dev/united-institute-of-technology/',
            'completed'
        ],
        [
            'evt_gdgoc_know_your_gdg_2025',
            $clubId,
            'Know your GDG on Campus - UIT',
            'know-your-gdg-on-campus-uit',
            'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=800&auto=format&fit=crop',
            'Official kickoff session of Google Developer Groups on Campus at United Institute of Technology (GDGoC – UIT). Overview of the community, reveal of the core team, upcoming tech/non-tech sessions, Google Campaigns (Solution Challenge, Study Jam), and UI/UX & Flutter insights.',
            'Virtual Event Venue & UIT Seminar Hall',
            '2025-10-05 19:00:00',
            'https://gdg.community.dev/united-institute-of-technology/',
            'completed'
        ],
        [
            'evt_gdgoc_hackquest_2025',
            $clubId,
            'HACKQUEST\'25',
            'hackquest-25',
            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=800&auto=format&fit=crop',
            'Thrilling two-day software and hardware hackathon at UIT on 23rd & 24th April 2025. Build impactful applications based on UN Sustainable Development Goals. Features cash prizes worth ₹80,000+, Gala Night with live music beneath the stars, and networking.',
            'UIT Auditorium, Naini, Prayagraj 211010',
            '2025-04-23 12:00:00',
            'https://gdg.community.dev/united-institute-of-technology/',
            'completed'
        ],
        [
            'evt_gdgoc_build_with_ai_jan_2025',
            $clubId,
            'Build with AI: Winning Strategies with Solution Challenge Champion',
            'build-with-ai-winning-strategies-jan-2025',
            'https://images.unsplash.com/photo-1677442136019-21780efad99a?q=80&w=800&auto=format&fit=crop',
            'Exclusive online session with Krishna Aute, global Top 3 winner of the Google Solution Challenge 2024 (SpoonShare). Learn winning strategies to build real-world solutions using Android, Firebase, and Flutter.',
            'Bevy Virtual Conference Platform',
            '2025-01-27 17:45:00',
            'https://gdg.community.dev/united-institute-of-technology/',
            'completed'
        ],
        [
            'evt_gdgoc_tech_winter_break_2024',
            $clubId,
            'Tech Winter Break + GDG On Campus United Institute Of Technology',
            'tech-winter-break-gdg-on-campus-uit',
            'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop',
            'Briefing session to explore opportunities for the Google Solution Challenge 2025. Learn how to tackle real-world problems using Google technologies (Android, Angular, Web), gain insights into challenge themes, and team building.',
            'Induction Hall, 1st Floor, United Institute Of Technology, Prayagraj 211010',
            '2024-12-12 10:00:00',
            'https://gdg.community.dev/united-institute-of-technology/',
            'completed'
        ],
        [
            'evt_gdgoc_build_with_ai_nov_2024',
            $clubId,
            'Build with AI',
            'build-with-ai-nov-2024',
            'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=800&auto=format&fit=crop',
            'Engaging session on Artificial Intelligence designed for beginners and juniors. Explores Generative AI (Can Machines Create Like Humans?), real-world AI applications, career opportunities, Gemini API, and live quiz with exciting prizes.',
            'UIT Induction Hall, 1st Floor, Prayagraj 211010',
            '2024-11-08 14:00:00',
            'https://gdg.community.dev/united-institute-of-technology/',
            'completed'
        ],
        [
            'evt_gdgoc_tfug_inaugural_2024',
            $clubId,
            'TFUG x GDG On-Campus Inaugural',
            'tfug-x-gdg-on-campus-inaugural',
            'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=800&auto=format&fit=crop',
            'Inaugural collaboration with TFUG Prayagraj [ML Prayagraj] featuring an informative session on Firebase, TensorFlow integration, and Google Checks, alongside inaugurating the new GDG On-Campus Lead for UIT.',
            'United Institute Of Technology, NH 2, D-3, UPSIDC Industrial Area, Naini, Prayagraj 211010',
            '2024-10-01 14:00:00',
            'https://gdg.community.dev/united-institute-of-technology/',
            'completed'
        ]
    ];

    $eStmt = $db->prepare("
        INSERT INTO events (id, club_id, title, slug, banner, description, venue, event_date, registration_link, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($events as $ev) {
        $eStmt->execute($ev);
    }
    echo "[+] Seeded 8 Official GDGOC UIT Events into database successfully!\n";

    echo "===========================================\n";
    echo "  GDGOC UIT Seeded Successfully!            \n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "[-] Seeding Error: " . $e->getMessage() . "\n";
}
