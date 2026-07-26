<?php
/**
 * Seed Gemini Builders Community – UIT Official Campus Club (MySQL & SQLite Dual Sync)
 */

require_once __DIR__ . '/../config/database.php';

function seed_gemini_builders(PDO $db, string $dbType) {
    echo "=== Seeding Gemini Builders Community – UIT in {$dbType} ===\n";
    $clubId = 'clb_gemini_builders_uit_2026';

    // 1. Get Technical Category ID
    $catStmt = $db->query("SELECT id FROM categories WHERE slug = 'technical' LIMIT 1");
    $catRow = $catStmt->fetch(PDO::FETCH_ASSOC);
    $catId = $catRow ? $catRow['id'] : 1;

    // Check if club already exists
    $chkStmt = $db->prepare("SELECT id FROM clubs WHERE id = ?");
    $chkStmt->execute([$clubId]);

    $nowStr = date('Y-m-d H:i:s');
    $name = "Gemini Builders Community – UIT";
    $shortName = "Gemini Builders UIT";
    $slug = "gemini-builders-uit";
    $tagline = "Premier Student-Led AI Innovation Hub at UIT (GSA Network)";
    $description = "Welcome to Gemini Builders Community – UIT. We are the premier student-led AI innovation hub at United Institute of Technology, operating under the Google Student Ambassador (GSA) network.\n\nMoving beyond passive learning, our community is built on three core pillars:\n🚀 BUILD: Empowering students to create real-world AI applications using Google's technologies.\n🔄 SCALE: Implementing structured systems for consistent coding flow and collaborative project development.\n💡 IMPACT: Transforming the campus tech ecosystem into a thriving hub for AI innovation and product building.\n\nIf you are a builder, a thinker, or an innovator ready to leverage AI, you belong here.\n\n#TeamGemini #UIT #AIBuilders #CommunityDriven";
    $mission = "To empower students to build real-world AI applications using Google Gemini API, scale collaborative developer systems, and transform the campus into a thriving AI product innovation hub.";
    $vision = "Moving beyond passive learning — to create an elite campus builder ecosystem for artificial intelligence and product building.";
    $objectives = "• Gemini API & LLM Agent Workshops\n• Build with AI Hackathons & Google Solution Challenges\n• Student Product Incubators & Open Source AI Repos\n• Google Student Ambassador (GSA) Network Sessions";
    $logo = "https://upload.wikimedia.org/wikipedia/commons/8/8a/Google_Gemini_logo.svg";
    $cover = "https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=1200&auto=format&fit=crop";
    $email = "geminibuilders@uit.edu.in";
    $office = "Headquarters: Prayagraj, Uttar Pradesh IN (SAC, UIT)";
    $meetingLoc = "Computer Lab 3 & Seminar Hall, UIT Prayagraj";
    $website = "https://gemini.google.com/";

    if ($chkStmt->fetch()) {
        $stmt = $db->prepare("
            UPDATE clubs SET
                name = ?, short_name = ?, slug = ?, category_id = ?, tagline = ?, description = ?,
                mission = ?, vision = ?, objectives = ?, logo = ?, cover_image = ?, founded_year = 2025,
                email = ?, office_location = ?, meeting_location = ?, website = ?, member_count = 45
            WHERE id = ?
        ");
        $stmt->execute([
            $name, $shortName, $slug, $catId, $tagline, $description,
            $mission, $vision, $objectives, $logo, $cover,
            $email, $office, $meetingLoc, $website, $clubId
        ]);
        echo "[✓] Updated Gemini Builders Community – UIT Profile\n";
    } else {
        $stmt = $db->prepare("
            INSERT INTO clubs (
                id, name, short_name, slug, category_id, tagline, description,
                mission, vision, objectives, logo, cover_image, founded_year, status,
                recruitment_open, email, office_location, meeting_location, website, member_count, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 2025, 'active', 1, ?, ?, ?, ?, 45, ?)
        ");
        $stmt->execute([
            $clubId, $name, $shortName, $slug, $catId, $tagline, $description,
            $mission, $vision, $objectives, $logo, $cover,
            $email, $office, $meetingLoc, $website, $nowStr
        ]);
        echo "[✓] Inserted Gemini Builders Community – UIT Profile\n";
    }

    // 2. Add Core Team Members (Google Student Ambassador Lead & Core AI Builders)
    $db->prepare("DELETE FROM leadership WHERE club_id = ?")->execute([$clubId]);
    $leadStmt = $db->prepare("
        INSERT INTO leadership (id, club_id, name, role_title, category, term_year, email, phone, avatar, order_index)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $leaders = [
        ['ldr_gemini_1', 'Google Student Ambassador Lead', 'GSA Chapter Ambassador', 'president', '2025-2026', 'gsa.lead@uit.edu.in', '+91 98765 33001', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_gemini_2', 'AI Applications Head', 'Gemini API Tech Lead', 'head', '2025-2026', 'ai.tech@uit.edu.in', '+91 98765 33002', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_gemini_3', 'Product & Scaling Lead', 'Systems & Architecture Lead', 'head', '2025-2026', 'product.scaling@uit.edu.in', '+91 98765 33003', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=400&auto=format&fit=crop', 3],
        ['ldr_gemini_4', 'Community & Ecosystem Lead', 'Outreach & Growth Lead', 'head', '2025-2026', 'community.impact@uit.edu.in', '+91 98765 33004', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=400&auto=format&fit=crop', 4]
    ];

    foreach ($leaders as $l) {
        $leadStmt->execute([$l[0], $clubId, $l[1], $l[2], $l[3], $l[4], $l[5], $l[6], $l[7], $l[8]]);
    }
    echo "[✓] Seeded Gemini Builders Leadership Roster\n";

    // 3. Add Inaugural Event
    $db->prepare("DELETE FROM events WHERE club_id = ?")->execute([$clubId]);
    $eventStmt = $db->prepare("
        INSERT INTO events (
            id, club_id, title, slug, description, event_date, venue, 
            registration_link, banner, registered_count, outcomes_summary, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $eventStmt->execute([
        'evt_gemini_1',
        $clubId,
        'Gemini API Builder Bootcamp & GSA AI Launch 2026',
        'gemini-api-builder-bootcamp-gsa-launch-2026',
        "Join Gemini Builders Community – UIT for our flagship launch bootcamp! Learn to integrate Google Gemini 1.5 Pro API, build multi-modal AI agents, and submit projects for Google Solution Challenge 2026 under the GSA Network.",
        '2026-04-15 11:00:00',
        'Headquarters: Prayagraj, Uttar Pradesh IN (Seminar Hall & Computer Lab 3, UIT)',
        'https://gemini.google.com/',
        'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=1200&auto=format&fit=crop',
        185,
        'Flagship AI Builder Bootcamp introducing Google Gemini 1.5 Pro API, multi-modal LLM agents & GSA Network incubator projects.',
        'completed',
        $nowStr
    ]);
    echo "[✓] Seeded Gemini Builders Launch Event\n";
}

try {
    $mainDb = Database::getConnection();
    seed_gemini_builders($mainDb, "Main Database");
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
        seed_gemini_builders($sqliteDb, "SQLite File");
    } catch (Exception $e) {
        echo "[X] SQLite Error: " . $e->getMessage() . "\n";
    }
}
