<?php
/**
 * Add HackerRank UIT to Database
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();
    echo "===========================================\n";
    echo "  Adding HackerRank UIT Club to Database    \n";
    echo "===========================================\n";

    // 1. Get Technical Category ID
    $catStmt = $db->query("SELECT id FROM categories WHERE slug = 'technical' LIMIT 1");
    $techCatId = $catStmt->fetchColumn() ?: 1;

    $clubId = 'clb_hackerrank_uit_2026';
    $slug = 'hackerrank-uit';

    // Delete existing if any
    $db->prepare("DELETE FROM clubs WHERE slug = ? OR id = ?")->execute([$slug, $clubId]);

    // 2. Insert HackerRank UIT Club
    $cStmt = $db->prepare("
        INSERT INTO clubs (
            id, name, short_name, slug, category_id, tagline, description, mission, vision, objectives,
            logo, cover_image, founded_year, status, recruitment_open, recruitment_link, email, office_location, website
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    $cStmt->execute([
        $clubId,
        'HackerRank UIT',
        'HackerRank UIT',
        $slug,
        $techCatId,
        'Practice. Compete. Improve. Repeat.',
        'HackerRank UIT is the official coding and problem-solving community of the United Institute of Technology, dedicated to building strong programming skills, analytical thinking, and competitive spirit among students.',
        'To bridge the gap between academic learning and real-world technical skills by encouraging consistency, logical thinking, and a growth mindset through coding contests, workshops, practice tracks, and collaborative learning.',
        'Practice. Compete. Improve. Repeat. — Empowering students to become confident developers and future tech professionals.',
        '• Weekly Coding Challenges & Leaderboards\n• Technical Interview Preparation Sessions\n• Problem-Solving & Algorithmic Practice Tracks\n• Peer-to-Peer Code Reviews & Pair Programming',
        'https://hrcdn.net/f2/assets/brand/h_mark_sm.png',
        'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=1200&auto=format&fit=crop',
        2026,
        'active',
        1,
        '/contact.html',
        'hackerrank@uit.edu',
        'United Institute of Technology Naini, UPSIDC Industrial Area, Naini, Prayagraj, Uttar Pradesh IN',
        'https://www.hackerrank.com'
    ]);

    echo "[+] HackerRank UIT added successfully.\n";

    // 3. Add Leadership Roster
    $db->prepare("DELETE FROM leadership WHERE club_id = ?")->execute([$clubId]);

    $lStmt = $db->prepare("
        INSERT INTO leadership (id, club_id, name, role_title, category, email, phone, avatar, order_index)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $lStmt->execute([
        'ldr_hr_lead',
        $clubId,
        'Utkarsh Srivastava',
        'HackerRank Campus Lead',
        'president',
        'utkarsh.hr@uit.edu',
        '+91 98765 22221',
        'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=400&auto=format&fit=crop',
        1
    ]);
    $lStmt->execute([
        'ldr_hr_vice',
        $clubId,
        'Aditi Mishra',
        'Competitive Coding Lead',
        'vice_president',
        'aditi.hr@uit.edu',
        '+91 98765 22222',
        'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=400&auto=format&fit=crop',
        2
    ]);

    echo "[+] Leadership roster added.\n";

    // 4. Add Events
    $db->prepare("DELETE FROM events WHERE club_id = ?")->execute([$clubId]);

    $eStmt = $db->prepare("
        INSERT INTO events (id, club_id, title, slug, banner, description, venue, event_date, registration_link, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $eStmt->execute([
        'evt_hr_codesprint_2026',
        $clubId,
        'HackerRank UIT 30-Day CodeSprint Challenge',
        'hackerrank-uit-30-day-codesprint',
        'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=800&auto=format&fit=crop',
        'Month-long algorithmic problem solving marathon with daily practice badges, leaderboard rankings, and tech interview prep.',
        'Online HackerRank Platform & UIT Computer Labs',
        '2026-03-01 10:00:00',
        'https://www.hackerrank.com',
        'completed'
    ]);

    echo "[+] Seeded HackerRank UIT events.\n";

    // 5. Add Gallery Items
    $db->prepare("DELETE FROM gallery_items WHERE club_id = ?")->execute([$clubId]);

    $gStmt = $db->prepare("INSERT INTO gallery_items (id, club_id, media_url, caption) VALUES (?, ?, ?, ?)");
    $gStmt->execute([
        'gal_hr_1',
        $clubId,
        'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=800&auto=format&fit=crop',
        'HackerRank UIT - CodeSprint Practice Session & Contest'
    ]);

    echo "[+] Seeded HackerRank gallery media.\n";

    echo "===========================================\n";
    echo "  HackerRank UIT Added Successfully!        \n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "[-] Error adding HackerRank club: " . $e->getMessage() . "\n";
}
