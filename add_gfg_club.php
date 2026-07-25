<?php
/**
 * Add GeeksforGeeks Student Chapter - UIT to Database
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();
    echo "===========================================\n";
    echo "  Adding GeeksforGeeks Student Chapter - UIT \n";
    echo "===========================================\n";

    // 1. Get Technical Category ID
    $catStmt = $db->query("SELECT id FROM categories WHERE slug = 'technical' LIMIT 1");
    $techCatId = $catStmt->fetchColumn() ?: 1;

    $clubId = 'clb_gfg_sc_uit_2026';
    $slug = 'gfgsc-uit';

    // Delete existing if any
    $db->prepare("DELETE FROM clubs WHERE slug = ? OR id = ?")->execute([$slug, $clubId]);

    // 2. Insert GFG SC UIT Club
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
        'GeeksforGeeks Student Chapter - UIT',
        'GFG SC UIT',
        $slug,
        $techCatId,
        'Promoting Coding Culture, DSA, Competitive Programming & Tech Excellence',
        'GeeksforGeeks Student Chapter – UIT is an official student-driven technical community established under the guidance of GeeksforGeeks to promote coding culture, technical excellence, and peer learning within the campus.',
        'To create a strong ecosystem where students can enhance their skills in Data Structures & Algorithms (DSA), Competitive Programming, Web Development, Core Computer Science subjects, and emerging technologies.',
        'We believe in "Learn, Practice, Build, and Grow" — empowering students to become industry-ready engineers and problem solvers.',
        '• Technical workshops\n• Coding contests & hackathons\n• Interview preparation sessions\n• Industry expert talks\n• Peer-to-peer learning programs',
        'https://media.geeksforgeeks.org/wp-content/uploads/gfg_200X200.png',
        'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=1200&auto=format&fit=crop',
        2024,
        'active',
        1,
        '/contact.html',
        'gfgsc@uit.edu',
        'United Institute of Technology Naini, UPSIDC Industrial Area, Naini, Prayagraj, Uttar Pradesh IN',
        'https://www.geeksforgeeks.org'
    ]);

    echo "[+] GeeksforGeeks Student Chapter - UIT added successfully.\n";

    // 3. Add Leadership: Campus Mantri - Ansh Kumar Gupta
    $db->prepare("DELETE FROM leadership WHERE club_id = ?")->execute([$clubId]);

    $lStmt = $db->prepare("
        INSERT INTO leadership (id, club_id, name, role_title, category, email, phone, avatar, order_index)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $lStmt->execute([
        'ldr_gfg_ansh',
        $clubId,
        'Ansh Kumar Gupta',
        'Campus Mantri & Chapter Lead',
        'president',
        'ansh.gfg@uit.edu',
        '+91 98765 11111',
        'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop',
        1
    ]);
    $lStmt->execute([
        'ldr_gfg_vice',
        $clubId,
        'Harsh Vardhan',
        'Tech Lead & DSA Head',
        'vice_president',
        'harsh.gfg@uit.edu',
        '+91 98765 11112',
        'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop',
        2
    ]);

    echo "[+] Leadership roster added (Campus Mantri - Ansh Kumar Gupta).\n";

    // 4. Add GFG Events
    $db->prepare("DELETE FROM events WHERE club_id = ?")->execute([$clubId]);

    $eStmt = $db->prepare("
        INSERT INTO events (id, club_id, title, slug, banner, description, venue, event_date, registration_link, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $eStmt->execute([
        'evt_gfg_dsa_bootcamp_2026',
        $clubId,
        'Mastering Data Structures & Algorithms with GeeksforGeeks',
        'mastering-dsa-with-geeksforgeeks',
        'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=800&auto=format&fit=crop',
        'Intensive hands-on DSA bootcamp covering Trees, Graphs, Dynamic Programming & interview problem-solving with GFG.',
        'Computer Lab 2 & Seminar Hall, UIT',
        '2026-03-20 14:00:00',
        'https://www.geeksforgeeks.org',
        'completed'
    ]);

    $eStmt->execute([
        'evt_gfg_coding_contest_2026',
        $clubId,
        'GFG UIT CodeSprint 2026 (Algorithmic Battle)',
        'gfg-uit-codesprint-2026',
        'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=800&auto=format&fit=crop',
        'Inter-branch coding competition hosted on GeeksforGeeks platform with certificates, goodies & placement perks.',
        'UIT Main Computer Lab',
        '2026-04-10 11:00:00',
        'https://www.geeksforgeeks.org',
        'completed'
    ]);

    echo "[+] Seeded GFG events.\n";

    // 5. Add Gallery Items
    $db->prepare("DELETE FROM gallery_items WHERE club_id = ?")->execute([$clubId]);

    $gStmt = $db->prepare("INSERT INTO gallery_items (id, club_id, media_url, caption) VALUES (?, ?, ?, ?)");
    $gStmt->execute([
        'gal_gfg_1',
        $clubId,
        'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=800&auto=format&fit=crop',
        'GeeksforGeeks Student Chapter UIT - DSA Workshop & Coding Bootcamp'
    ]);

    echo "[+] Seeded GFG gallery media.\n";

    echo "===========================================\n";
    echo "  GFG SC - UIT Added Successfully!         \n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "[-] Error adding GFG club: " . $e->getMessage() . "\n";
}
