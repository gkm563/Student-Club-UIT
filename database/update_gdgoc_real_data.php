<?php
/**
 * Update GDG on Campus UIT Real Details, Organizers & Past Events (MySQL & SQLite Sync)
 */

require_once __DIR__ . '/../config/database.php';

function seed_gdg_to_pdo(PDO $db, string $dbType) {
    echo "--- Syncing to {$dbType} ---\n";
    $clubId = 'clb_gdgoc_uit_2026';

    // Ensure outcomes_summary column exists in events table
    try {
        $db->exec("ALTER TABLE events ADD COLUMN outcomes_summary TEXT");
    } catch (Exception $ex) {
        // Column may already exist
    }

    // 1. Update Club Master Info
    $stmt = $db->prepare("
        UPDATE clubs SET
            name = ?,
            short_name = ?,
            slug = ?,
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

    $name = "GDG on Campus United Institute of Technology - Allahabad, India";
    $shortName = "GDGOC UIT";
    $slug = "gdgoc-uit";
    $tagline = "Where Innovation Takes Flight";
    $description = "Welcome to Google Developer Group on Campus (GDG) – United Institute of Technology, where innovation takes flight! Our chapter is a dynamic community dedicated to pushing students to the forefront of technology and fostering a culture of creative exploration. At GDG-UIT, our mission is to spark a passion for technology, encourage innovation, and empower students to achieve new heights in the development world.\n\nWhether you’re an experienced coder or just starting your tech journey, GDG-UIT is here for you. Connect with like-minded innovators, learn from industry experts, and embrace continuous growth and discovery. Stay updated with our latest events, workshops, and tech insights by following us on LinkedIn, Instagram, and Twitter. Join GDG-UIT, and let’s transform ideas into action together.\n\nAt GDG-UIT, innovation isn’t just a concept - it’s where it begins.";
    $mission = "Spark a passion for technology, encourage innovation, and empower students to achieve new heights in the development world.";
    $vision = "Where Innovation Takes Flight — At GDG-UIT, innovation isn’t just a concept - it’s where it begins.";
    $objectives = "• FlutterFlow & App Development Workshops\n• Build with AI Virtual Conferences & Gemini API Labs\n• HACKQUEST Campus Hackathons & Solution Challenges\n• TensorFlow & Machine Learning Bootcamps\n• Open Source & Winter Tech Projects";
    $logo = "https://upload.wikimedia.org/wikipedia/commons/thumb/c/c7/Google_Developers_logo.svg/320px-Google_Developers_logo.svg.png";
    $cover = "https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=1200&auto=format&fit=crop";
    $email = "gdgoc@uit.edu";
    $office = "GDG on Campus United Institute of Technology - Allahabad, India";
    $meetingLoc = "Seminar Hall & Computer Labs, UIT, Prayagraj";
    $website = "https://gdg.community.dev/";

    $stmt->execute([
        $name, $shortName, $slug, $tagline, $description, $mission, $vision, $objectives,
        $logo, $cover, $email, $office, $meetingLoc, $website, $clubId
    ]);
    echo "[✓] Updated GDGOC UIT Club Profile (770 Members)\n";

    // 2. Clear existing leadership & re-seed Organizers
    $db->prepare("DELETE FROM leadership WHERE club_id = ?")->execute([$clubId]);

    $leadStmt = $db->prepare("
        INSERT INTO leadership (id, club_id, name, role_title, category, term_year, email, phone, avatar, order_index)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $organizers = [
        ['ldr_gdg_1', 'Shivaansh Singh', 'GDGoC Organizer', 'president', '2025-2026', 'shivaansh.gdg@uit.edu', '+91 98765 12345', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_gdg_2', 'Reeti Singh', 'Social Media Head', 'head', '2025-2026', 'reeti.gdg@uit.edu', '+91 98765 12346', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_gdg_3', 'ANUNAY SRIVASTAVA', 'Media & Content Head', 'head', '2025-2026', 'anunay.gdg@uit.edu', '+91 98765 12347', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop', 3],
        ['ldr_gdg_4', 'Nitesh Kumar Maurya', 'Graphics Design Head', 'head', '2025-2026', 'nitesh.gdg@uit.edu', '+91 98765 12348', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=400&auto=format&fit=crop', 4],
        ['ldr_gdg_5', 'Sakshi Pandey', 'Marketing & Outreach Head', 'head', '2025-2026', 'sakshi.gdg@uit.edu', '+91 98765 12349', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=400&auto=format&fit=crop', 5],
    ];

    foreach ($organizers as $org) {
        $leadStmt->execute([
            $org[0], $clubId, $org[1], $org[2], $org[3], $org[4], $org[5], $org[6], $org[7], $org[8]
        ]);
    }
    echo "[✓] Seeded 5 Official GDGoC Organizers\n";

    // 3. Clear existing events for GDGOC & re-seed 7 Past Events
    $db->prepare("DELETE FROM events WHERE club_id = ?")->execute([$clubId]);

    $eventStmt = $db->prepare("
        INSERT INTO events (id, club_id, title, slug, description, event_date, venue, registration_link, banner, outcomes_summary, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')
    ");

    $events = [
        [
            'evt_gdg_1',
            'Kickstart App Development with Flutterflow',
            'kickstart-app-development-flutterflow',
            'Hands-on workshop introducing visual app development, UI creation, state management, and Firebase integration with FlutterFlow.',
            '2025-11-27 10:00:00',
            'GDG on Campus United Institute of Technology - Allahabad, India',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1551650975-87deedd944c3?q=80&w=1200&auto=format&fit=crop',
            'Free registration workshop building cross-platform mobile apps with FlutterFlow.'
        ],
        [
            'evt_gdg_2',
            'Know your GDG on Campus - UIT',
            'know-your-gdg-on-campus-uit',
            'Orientation and inaugural session welcoming students to the GDG on Campus community, outlining tech tracks, hackathons, and upcoming opportunities.',
            '2025-10-05 11:00:00',
            'GDG on Campus United Institute of Technology - Allahabad, India',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=1200&auto=format&fit=crop',
            'Free registration orientation welcoming 770+ members to GDG-UIT.'
        ],
        [
            'evt_gdg_3',
            'HACKQUEST\'25',
            'hackquest-25',
            'Premier campus hackathon at UIT bringing student developers, designers, and innovators together to build real-world software solutions within 24 hours.',
            '2025-04-23 09:00:00',
            'GDG on Campus United Institute of Technology - Allahabad, India',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=1200&auto=format&fit=crop',
            'Free registration 24-hour flagship campus hackathon.'
        ],
        [
            'evt_gdg_4',
            'Build with AI (Virtual Conference)',
            'build-with-ai-jan-2025',
            'Deep dive into Generative AI, Gemini API, AI Studio, and building intelligent agents with hands-on Google AI tools.',
            '2025-01-27 14:00:00',
            'GDG on Campus United Institute of Technology - Allahabad, India',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=1200&auto=format&fit=crop',
            'Free registration with Bevy Virtual Conference focused on Gemini API.'
        ],
        [
            'evt_gdg_5',
            'Tech Winter Break + GDG On Campus United Institute Of Technology',
            'tech-winter-break-gdg-uit',
            'Special winter tech retreat covering open-source contributions, code reviews, and winter project showcase.',
            '2024-12-12 10:30:00',
            'GDG on Campus United Institute of Technology - Allahabad, India',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=1200&auto=format&fit=crop',
            'Free registration winter break open-source coding retreat.'
        ],
        [
            'evt_gdg_6',
            'Build with AI',
            'build-with-ai-nov-2024',
            'Interactive workshop exploring prompt engineering, LLM integrations, and machine learning fundamentals.',
            '2024-11-08 11:00:00',
            'GDG on Campus United Institute of Technology - Allahabad, India',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=1200&auto=format&fit=crop',
            'Free registration prompt engineering and ML workshop.'
        ],
        [
            'evt_gdg_7',
            'TFUG x GDG On-Campus Inaugural',
            'tfug-x-gdg-on-campus-inaugural',
            'Joint inaugural session by TensorFlow User Group (TFUG) and GDG On-Campus featuring machine learning roadmaps and hands-on TensorFlow models.',
            '2024-10-01 10:00:00',
            'GDG on Campus United Institute of Technology - Allahabad, India',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=1200&auto=format&fit=crop',
            'Free registration collaborative inaugural with TensorFlow User Group.'
        ]
    ];

    foreach ($events as $e) {
        $eventStmt->execute([
            $e[0], $clubId, $e[1], $e[2], $e[3], $e[4], $e[5], $e[6], $e[7], $e[8]
        ]);
    }
    echo "[✓] Seeded 7 Real GDGoC Past Events\n";
}

try {
    $mainDb = Database::getConnection();
    seed_gdg_to_pdo($mainDb, "Main Database");
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
        seed_gdg_to_pdo($sqliteDb, "SQLite File");
    } catch (Exception $e) {
        echo "[X] SQLite Sync Error: " . $e->getMessage() . "\n";
    }
}
