<?php
/**
 * Update GeeksforGeeks Student Chapter - UIT Real Events & Core Team (MySQL & SQLite Dual Sync)
 */

require_once __DIR__ . '/../config/database.php';

function seed_gfg_to_pdo(PDO $db, string $dbType) {
    echo "=== Updating GFG SC UIT Real Data in {$dbType} ===\n";
    $clubId = 'clb_gfg_sc_uit_2026';

    // Ensure columns exist
    try { $db->exec("ALTER TABLE events ADD COLUMN registered_count INTEGER DEFAULT 0"); } catch (Exception $ex) {}
    try { $db->exec("ALTER TABLE events ADD COLUMN outcomes_summary TEXT"); } catch (Exception $ex) {}

    // 1. Update GFG Club Profile
    $clubStmt = $db->prepare("
        UPDATE clubs SET
            name = ?,
            short_name = ?,
            tagline = ?,
            description = ?,
            mission = ?,
            vision = ?,
            objectives = ?,
            logo = ?,
            cover_image = ?,
            email = ?,
            office_location = ?,
            meeting_location = ?,
            website = ?
        WHERE id = ?
    ");

    $clubName = "GeeksforGeeks Student Chapter - UIT";
    $shortName = "GFG SC UIT";
    $tagline = "Learn, Practice, Build, and Grow — Coding Excellence at UIT";
    $desc = "GeeksforGeeks Student Chapter – UIT is an official student-driven technical community established under the guidance of GeeksforGeeks to promote coding culture, technical excellence, Data Structures & Algorithms (DSA), AI/ML, and peer learning within the campus.";
    $mission = "To create a strong ecosystem where students can enhance their skills in Data Structures & Algorithms (DSA), Competitive Programming, Web Development, AI/ML, and core Computer Science subjects.";
    $vision = "We believe in 'Learn, Practice, Build, and Grow' — empowering students to become industry-ready engineers, problem solvers, and tech leaders.";
    $objectives = "• Orientation & DSA Foundations Workshops\n• AI/ML Mentorship Sessions with Industry Mentors\n• SyntaxClash 2026 Campus Coding Contests\n• Internship & Placement Preparation Bootcamps";
    $logo = "https://media.geeksforgeeks.org/wp-content/uploads/gfg_200X200.png";
    $cover = "https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=1200&auto=format&fit=crop";
    $email = "gfgsc@uit.edu";
    $office = "USC UIT Office, UIT Prayagraj";
    $meetingLoc = "Computer Labs 1 & 2 & Seminar Hall, UIT";
    $website = "https://www.geeksforgeeks.org/";

    $clubStmt->execute([
        $clubName, $shortName, $tagline, $desc, $mission, $vision, $objectives,
        $logo, $cover, $email, $office, $meetingLoc, $website, $clubId
    ]);
    echo "[✓] Updated GFG SC UIT Club Profile\n";

    // 2. Clear existing leadership & re-seed 8 Core Team Members
    $db->prepare("DELETE FROM leadership WHERE club_id = ?")->execute([$clubId]);

    $leadStmt = $db->prepare("
        INSERT INTO leadership (id, club_id, name, role_title, category, term_year, email, phone, avatar, order_index)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $coreTeam = [
        ['ldr_gfg_1', 'Ansh Kumar Gupta', 'Campus Mantri (President)', 'president', '2025-2026', 'ansh.gfg@uit.edu', '+91 98765 22001', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_gfg_2', 'Aarush Garg', 'Vice President', 'vice_president', '2025-2026', 'aarush.gfg@uit.edu', '+91 98765 22002', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_gfg_3', 'Gautam Kumar Maurya', 'Technical Head', 'head', '2025-2026', 'gautam.gfg@uit.edu', '+91 98765 22003', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=400&auto=format&fit=crop', 3],
        ['ldr_gfg_4', 'Arshad Ahmad', 'Finance Head', 'head', '2025-2026', 'arshad.gfg@uit.edu', '+91 98765 22004', 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?q=80&w=400&auto=format&fit=crop', 4],
        ['ldr_gfg_5', 'Priyanshika Upadhyay', 'Event Head', 'head', '2025-2026', 'priyanshika.gfg@uit.edu', '+91 98765 22005', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=400&auto=format&fit=crop', 5],
        ['ldr_gfg_6', 'Vivek Kumar', 'PR & Outreach Head', 'head', '2025-2026', 'vivek.gfg@uit.edu', '+91 98765 22006', 'https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?q=80&w=400&auto=format&fit=crop', 6],
        ['ldr_gfg_7', 'PIYUSH Verma', 'Social Media Head', 'head', '2025-2026', 'piyush.gfg@uit.edu', '+91 98765 22007', 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?q=80&w=400&auto=format&fit=crop', 7],
        ['ldr_gfg_8', 'Shiksha', 'Design & Branding Head', 'head', '2025-2026', 'shiksha.gfg@uit.edu', '+91 98765 22008', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=400&auto=format&fit=crop', 8]
    ];

    foreach ($coreTeam as $member) {
        $leadStmt->execute([
            $member[0], $clubId, $member[1], $member[2], $member[3], $member[4], $member[5], $member[6], $member[7], $member[8]
        ]);
    }
    echo "[✓] Seeded 8 Official GFG Core Team Leaders\n";

    // 3. Clear existing events for GFG & re-seed 3 Real Events
    $db->prepare("DELETE FROM events WHERE club_id = ?")->execute([$clubId]);

    $nowStr = date('Y-m-d H:i:s');
    $eventStmt = $db->prepare("
        INSERT INTO events (
            id, club_id, title, slug, description, event_date, venue, 
            registration_link, banner, registered_count, outcomes_summary, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $events = [
        [
            'evt_gfg_1',
            'Orientation & Initiation Session - GFG Student Chapter UIT',
            'orientation-initiation-session-gfg-uit',
            "The GeeksforGeeks Student Chapter - UIT successfully organized an Orientation & Initiation Session to officially introduce the GeeksforGeeks platform to our college students.\n\nUnder the guidance of our Campus_Mantri (President), Ansh Kumar Gupta, we introduced GeeksforGeeks to the 1st year and 2nd year students, explaining its importance in building strong foundations in Data Structures & Algorithms, problem-solving, competitive programming, and placement preparation.\n\nThe purpose of this session was simple and clear — To bring the GeeksforGeeks learning ecosystem to our campus and encourage students to start their technical journey in a structured and consistent manner.\n\nDuring the session, students were guided on:\n• How GeeksforGeeks helps in mastering DSA\n• The importance of coding practice for internships and placements\n• How the Student Chapter will conduct workshops, contests, and technical events\n• Opportunities to grow through structured learning and peer collaboration\n\nCore Team: Ansh Kumar Gupta (President), Aarush Garg (VP), Gautam Kumar Maurya (Tech Head), Arshad Ahmad (Finance Head), Priyanshika Upadhyay (Event Head), Vivek Kumar (PR Head), PIYUSH Verma (Social Media Head), Shiksha (Design Head).",
            '2026-02-18 10:00:00',
            'United Institute of Technology, Prayagraj',
            'https://www.geeksforgeeks.org/',
            'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=1200&auto=format&fit=crop',
            150,
            'Orientation & Initiation Session introducing 1st & 2nd year students to GFG DSA learning ecosystem & competitive programming.',
            'completed'
        ],
        [
            'evt_gfg_2',
            'AI/ML Mentorship Session by GeeksforGeeks UIT',
            'ai-ml-mentorship-session-gfg-uit',
            "🎉 A Successful Mentorship Session conducted by GeeksforGeeks UIT Student Chapter! 🎉\n\nWe are delighted to share the successful organization of a Mentorship Session conducted by the GeeksforGeeks Student Chapter - UIT, aimed at guiding students from 1st, 2nd, and 3rd year in building a strong foundation for their tech careers.\n\nWith an overwhelming response of 380+ registrations, the session reflected the growing enthusiasm among students to learn, explore, and excel in the field of technology. 🚀\n\n🎯 What made this session impactful?\n• A clear AI/ML roadmap for beginners and enthusiasts\n• Strengthening core technical fundamentals\n• Real-world career insights and industry expectations\n\nMentor: Mr. Parth P. (AI Engineer at GeeksforGeeks)\nFaculty Coordinator: Mr. Gaurav Narayan\nCampus Mantri (President): Ansh Kumar Gupta",
            '2026-03-25 14:00:00',
            'UIT Auditorium & Computer Labs, Prayagraj',
            'https://www.geeksforgeeks.org/',
            'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=1200&auto=format&fit=crop',
            380,
            '380+ Registrations | Guest Mentor: Mr. Parth P. (AI Engineer, GeeksforGeeks) | Faculty Coordinator: Mr. Gaurav Narayan',
            'completed'
        ],
        [
            'evt_gfg_3',
            'SyntaxClash 2026 - 90-Minute Coding Contest',
            'syntaxclash-2026-coding-contest',
            "𝗛𝗲𝗹𝗹𝗼 𝗖𝗼𝗱𝗲𝗿𝘀 👋\n\nReady to test your problem-solving skills?\n\n🚀 GeeksforGeeks Student Chapter - UIT brings you SyntaxClash 2026 — a 90-minute coding contest on GeeksforGeeks designed to challenge your logic, speed, and coding skills.\n\n• 🗓 𝗗𝗮𝘁𝗲: 3 May 2026 (Sunday)\n• ⏰ 𝗦𝘁𝗮𝗿𝘁𝗶𝗻𝗴 𝗧𝗶𝗺𝗲: 8:30 PM (Duration: 90 Minutes)\n• 🌐 𝗣𝗹𝗮𝘁𝗳𝗼𝗿𝗺: GeeksforGeeks\n• 🎯 𝗘𝗹𝗶𝗴𝗶𝗯𝗶𝗹𝗶𝘁𝘆: Only for Prayagraj Coders\n\n🏆 𝗥𝗲𝘄𝗮𝗿𝗱𝘀:\n• Laptop Bags for Winners\n• GFG Official Goodies & Swags\n• Participation Certificate for All\n• Social Media Recognition\n\n⚡ Compete. Solve. Win.\n🔗 Register Now: https://www.geeksforgeeks.org/",
            '2026-05-03 20:30:00',
            'GeeksforGeeks Online Platform (Prayagraj Coders)',
            'https://www.geeksforgeeks.org/',
            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=1200&auto=format&fit=crop',
            210,
            '90-Minute Coding Contest | Rewards: Laptop Bags, GFG Goodies & Certificates | Organizer: Ansh Kumar Gupta (Campus Mantri)',
            'upcoming'
        ]
    ];

    foreach ($events as $e) {
        $eventStmt->execute([
            $e[0], $clubId, $e[1], $e[2], $e[3], $e[4], $e[5], $e[6], $e[7], $e[8], $e[9], $e[10], $nowStr
        ]);
    }
    echo "[✓] Seeded 3 Real GFG SC UIT Events (Orientation, AI/ML Mentorship & SyntaxClash 2026)\n";
}

try {
    $mainDb = Database::getConnection();
    seed_gfg_to_pdo($mainDb, "Main Database");
} catch (Exception $e) {
    echo "[X] Main DB Error: " . $e->getMessage() . "\n";
}

$sqliteFile = __DIR__ . '/ccms.sqlite';
if (file_exists($sqliteFile)) {
    try {
        $sqliteDb = new PDO("sqlite:" . $sqliteFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        seed_gfg_to_pdo($sqliteDb, "SQLite File");
    } catch (Exception $e) {
        echo "[X] SQLite Error: " . $e->getMessage() . "\n";
    }
}
