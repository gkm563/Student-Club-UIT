<?php
/**
 * Master Official Seeder for the 5 Official Clubs at UIT:
 * 1. Google Developer Groups On Campus - UIT (GDGOC UIT)
 * 2. GeeksforGeeks Student Chapter - UIT (GFG SC UIT)
 * 3. HackerRank UIT
 * 4. E-Cell UIT (Entrepreneurship Cell)
 * 5. WikiClub Tech - UIT
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();
    echo "===========================================\n";
    echo "  Seeding 5 Official Campus Clubs into Database \n";
    echo "===========================================\n";

    // Fetch Category Mapping
    $cats = [];
    $stmt = $db->query("SELECT id, slug FROM categories");
    while ($row = $stmt->fetch()) {
        $cats[$row['slug']] = $row['id'];
    }

    $techCatId = $cats['technical'] ?? 1;
    $ecellCatId = $cats['entrepreneurship'] ?? 5;

    // Clear all existing data
    $db->exec("DELETE FROM gallery_items");
    $db->exec("DELETE FROM events");
    $db->exec("DELETE FROM leadership");
    $db->exec("DELETE FROM clubs");

    // 1. Seed 5 Official Clubs
    $clubs = [
        [
            'clb_gdgoc_uit_2026',
            'Google Developer Groups On Campus - UIT',
            'GDGOC UIT',
            'gdgoc-uit',
            $techCatId,
            'Building, Innovating & Empowering Tech Enthusiasts at UIT',
            'Google Developer Groups On Campus - UIT brings together coders, problem solvers, and innovators to learn, build, and solve real-world problems with Google technologies.',
            'To cultivate developer skills through workshops, Study Jams, and Solution Challenges.',
            'To build the most vibrant student developer ecosystem at UIT.',
            '• Google Solution Challenge\n• Cloud Study Jams & Gen AI Workshops\n• Hackathons & Technical Bootcamps\n• Open Source Contributions',
            'https://images.unsplash.com/photo-1573164713988-8665fc963095?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=1200&auto=format&fit=crop',
            2023, 'active', 1, 'https://gdg.community.dev/', 'gdgoc@uit.edu', '+91 98765 12345', 'UIT, UPSIDC Industrial Area, Naini, Prayagraj 211010', 'https://gdg.community.dev/'
        ],
        [
            'clb_gfg_sc_uit_2026',
            'GeeksforGeeks Student Chapter - UIT',
            'GFG SC UIT',
            'gfgsc-uit',
            $techCatId,
            'Promoting Coding Culture, DSA, Competitive Programming & Tech Excellence',
            'GeeksforGeeks Student Chapter – UIT is an official student-driven technical community established under the guidance of GeeksforGeeks to promote coding culture, technical excellence, and peer learning within the campus.',
            'To create a strong ecosystem where students can enhance their skills in Data Structures & Algorithms (DSA), Competitive Programming, Web Development, Core Computer Science subjects, and emerging technologies.',
            'We believe in "Learn, Practice, Build, and Grow" — empowering students to become industry-ready engineers and problem solvers.',
            '• Technical workshops\n• Coding contests & hackathons\n• Interview preparation sessions\n• Industry expert talks\n• Peer-to-peer learning programs',
            'https://media.geeksforgeeks.org/wp-content/uploads/gfg_200X200.png',
            'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=1200&auto=format&fit=crop',
            2024, 'active', 1, '/contact.html', 'gfgsc@uit.edu', '+91 98765 11111', 'United Institute of Technology Naini, UPSIDC Industrial Area, Naini, Prayagraj, Uttar Pradesh IN', 'https://www.geeksforgeeks.org'
        ],
        [
            'clb_hackerrank_uit_2026',
            'HackerRank UIT',
            'HackerRank UIT',
            'hackerrank-uit',
            $techCatId,
            'Practice. Compete. Improve. Repeat.',
            'HackerRank UIT is the official coding and problem-solving community of the United Institute of Technology, dedicated to building strong programming skills, analytical thinking, and competitive spirit among students.',
            'To bridge the gap between academic learning and real-world technical skills by encouraging consistency, logical thinking, and a growth mindset through coding contests, workshops, practice tracks, and collaborative learning.',
            'Practice. Compete. Improve. Repeat. — Empowering students to become confident developers and future tech professionals.',
            '• Weekly Coding Challenges & Leaderboards\n• Technical Interview Preparation Sessions\n• Problem-Solving & Algorithmic Practice Tracks\n• Peer-to-Peer Code Reviews & Pair Programming',
            'https://hrcdn.net/f2/assets/brand/h_mark_sm.png',
            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=1200&auto=format&fit=crop',
            2026, 'active', 1, '/contact.html', 'hackerrank@uit.edu', '+91 98765 22221', 'United Institute of Technology Naini, UPSIDC Industrial Area, Naini, Prayagraj, Uttar Pradesh IN', 'https://www.hackerrank.com'
        ],
        [
            'clb_ecell_uit_2026',
            'E-Cell UIT (Entrepreneurship Cell)',
            'E-Cell UIT',
            'ecell-uit',
            $ecellCatId,
            'Fostering Startups, Innovation & Business Leadership',
            'E-Cell UIT nurtures student entrepreneurs by organizing Pitch Jams, Founder Talks, Angel Investor Summits, and incubation mentorship.',
            'Turn innovative student ideas into viable commercial startups.',
            'To build an ecosystem of successful student founders at UIT.',
            '• Startup Pitch Jams & Prototype Grants\n• Founder Talks & Mentorship Sessions\n• Angel Investor Summits\n• Incubation Support & Entrepreneurship Workshops',
            'https://images.unsplash.com/photo-1559136555-9303baea8ebd?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?q=80&w=1200&auto=format&fit=crop',
            2022, 'active', 1, '/contact.html', 'ecell@uit.edu', '+91 98765 12349', 'Student Activity Center, UIT, Prayagraj 211010', '/contact.html'
        ],
        [
            'clb_wikiclub_uit_2026',
            'WikiClub Tech - UIT',
            'WikiClub Tech',
            'wikiclub-uit',
            $techCatId,
            'Empowering Open Knowledge, Tech Writing, Wikipedia & Open Source',
            'WikiClub Tech - UIT is the official open knowledge and technology writing student community of United Institute of Technology, dedicated to open-source contributions, technical documentation, Wikipedia editing, and knowledge sharing.',
            'To build a collaborative culture of open knowledge, open source development, technical writing, and peer learning across campus.',
            'Share Knowledge. Build Open Source. Empower Minds. — Creating digital creators and open source leaders at UIT.',
            '• Technical Writing & Documentation Workshops\n• Open Source & Wikipedia Edit-a-thons\n• Knowledge Sharing & Hackathons\n• Student Research & Open Data Drives',
            'https://upload.wikimedia.org/wikipedia/commons/thumb/8/80/Wikipedia-logo-v2.svg/300px-Wikipedia-logo-v2.svg.png',
            'https://images.unsplash.com/photo-1455390582262-044cdead277a?q=80&w=1200&auto=format&fit=crop',
            2026, 'active', 1, '/contact.html', 'wikiclub@uit.edu', '+91 98765 33331', 'United Institute of Technology Naini, UPSIDC Industrial Area, Naini, Prayagraj, Uttar Pradesh IN', 'https://www.wikimedia.org'
        ]
    ];

    $cStmt = $db->prepare("
        INSERT INTO clubs (
            id, name, short_name, slug, category_id, tagline, description, mission, vision, objectives,
            logo, cover_image, founded_year, status, recruitment_open, recruitment_link, email, phone, office_location, website
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    foreach ($clubs as $c) {
        $cStmt->execute($c);
    }
    echo "[+] Seeded " . count($clubs) . " Official Campus Clubs.\n";

    // 2. Seed Official Leadership Roster
    $leaders = [
        ['ldr_1', 'clb_gdgoc_uit_2026', 'Shivansh Singh', 'GDG Lead & President', 'president', 'shivansh@uit.edu', '+91 98765 00001', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_2', 'clb_gdgoc_uit_2026', 'Sarthak Singh', 'GDG Lead & Past President', 'president', 'sarthak@uit.edu', '+91 98765 00002', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_gfg_ansh', 'clb_gfg_sc_uit_2026', 'Ansh Kumar Gupta', 'Campus Mantri & Chapter Lead', 'president', 'ansh.gfg@uit.edu', '+91 98765 11111', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_gfg_vice', 'clb_gfg_sc_uit_2026', 'Harsh Vardhan', 'Tech Lead & DSA Head', 'vice_president', 'harsh.gfg@uit.edu', '+91 98765 11112', 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_hr_lead', 'clb_hackerrank_uit_2026', 'Utkarsh Srivastava', 'HackerRank Campus Lead', 'president', 'utkarsh.hr@uit.edu', '+91 98765 22221', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_hr_vice', 'clb_hackerrank_uit_2026', 'Aditi Mishra', 'Competitive Coding Lead', 'vice_president', 'aditi.hr@uit.edu', '+91 98765 22222', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_ecell_riya', 'clb_ecell_uit_2026', 'Riya Sharma', 'E-Cell Convener & President', 'president', 'riya@uit.edu', '+91 98765 12349', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_wiki_lead', 'clb_wikiclub_uit_2026', 'Divyansh Sharma', 'WikiClub Lead & Convener', 'president', 'divyansh.wiki@uit.edu', '+91 98765 33331', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_wiki_vice', 'clb_wikiclub_uit_2026', 'Priya Singh', 'Content & Tech Editor', 'vice_president', 'priya.wiki@uit.edu', '+91 98765 33332', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=400&auto=format&fit=crop', 2]
    ];

    $lStmt = $db->prepare("
        INSERT INTO leadership (id, club_id, name, role_title, category, email, phone, avatar, order_index)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($leaders as $ldr) {
        $lStmt->execute($ldr);
    }
    echo "[+] Seeded " . count($leaders) . " Official Leadership Members.\n";

    // 3. Seed Official Events (GDGoC + GFG + HackerRank + E-Cell + WikiClub Tech)
    $events = [
        [
            'evt_gdgoc_sol_cloud_recap_2026', 'clb_gdgoc_uit_2026',
            'Unlocking Innovation: Google Solution Challenge & Cloud Study Jam Recap',
            'unlocking-innovation-google-solution-challenge-cloud-study-jam-recap',
            'https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=800&auto=format&fit=crop',
            'GDG-UIT hosted a memorable Info Session on the Google Solution Challenge and Cloud Study Jam Tier - 1 with swags and cloud learning.',
            'UIT, UPSIDC Industrial Area, Naini, Prayagraj 211010', '2026-04-01 12:00:00', 'https://gdg.community.dev/', 'completed'
        ],
        [
            'evt_gdgoc_flutterflow_2025', 'clb_gdgoc_uit_2026',
            'Kickstart App Development with Flutterflow',
            'kickstart-app-development-with-flutterflow',
            'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?q=80&w=800&auto=format&fit=crop',
            'Empower students with skills to build mobile apps with Flutterflow, Firebase, and Flutter.',
            'UIT Induction Hall, D3, UPSIDC Industrial Area, Naini, Prayagraj 211010', '2025-11-27 09:30:00', 'https://gdg.community.dev/', 'completed'
        ],
        [
            'evt_gdgoc_know_your_gdg_2025', 'clb_gdgoc_uit_2026',
            'Know your GDG on Campus - UIT',
            'know-your-gdg-on-campus-uit',
            'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=800&auto=format&fit=crop',
            'Official kickoff session of GDGoC UIT introducing core team, upcoming Google campaigns, and Study Jam.',
            'Virtual Event Venue & UIT Seminar Hall', '2025-10-05 19:00:00', 'https://gdg.community.dev/', 'completed'
        ],
        [
            'evt_gdgoc_hackquest_2025', 'clb_gdgoc_uit_2026',
            'HACKQUEST\'25',
            'hackquest-25',
            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=800&auto=format&fit=crop',
            'Thrilling two-day hackathon at UIT with ₹80,000+ cash prizes, live Gala Night under the stars, and networking.',
            'UIT Auditorium, Naini, Prayagraj 211010', '2025-04-23 12:00:00', 'https://gdg.community.dev/', 'completed'
        ],
        [
            'evt_gdgoc_build_with_ai_jan_2025', 'clb_gdgoc_uit_2026',
            'Build with AI: Winning Strategies with Solution Challenge Champion',
            'build-with-ai-winning-strategies-jan-2025',
            'https://images.unsplash.com/photo-1677442136019-21780efad99a?q=80&w=800&auto=format&fit=crop',
            'Online session with Krishna Aute (Global Top 3 Solution Challenge Winner) sharing winning strategies.',
            'Bevy Virtual Conference Platform', '2025-01-27 17:45:00', 'https://gdg.community.dev/', 'completed'
        ],
        [
            'evt_gdgoc_tech_winter_break_2024', 'clb_gdgoc_uit_2026',
            'Tech Winter Break + GDG On Campus United Institute Of Technology',
            'tech-winter-break-gdg-on-campus-uit',
            'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop',
            'Briefing session on Google Solution Challenge 2025 problem-solving and project building.',
            'Induction Hall, 1st Floor, UIT, Prayagraj 211010', '2024-12-12 10:00:00', 'https://gdg.community.dev/', 'completed'
        ],
        [
            'evt_gdgoc_build_with_ai_nov_2024', 'clb_gdgoc_uit_2026',
            'Build with AI',
            'build-with-ai-nov-2024',
            'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=800&auto=format&fit=crop',
            'Generative AI workshop for beginners exploring Gemini API, career opportunities, and live quiz prizes.',
            'UIT Induction Hall, 1st Floor, Prayagraj 211010', '2024-11-08 14:00:00', 'https://gdg.community.dev/', 'completed'
        ],
        [
            'evt_gdgoc_tfug_inaugural_2024', 'clb_gdgoc_uit_2026',
            'TFUG x GDG On-Campus Inaugural',
            'tfug-x-gdg-on-campus-inaugural',
            'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=800&auto=format&fit=crop',
            'Collaboration with TFUG Prayagraj on Firebase, TensorFlow, and GDSC Lead inauguration.',
            'United Institute Of Technology, NH 2, Naini, Prayagraj 211010', '2024-10-01 14:00:00', 'https://gdg.community.dev/', 'completed'
        ],
        [
            'evt_gfg_dsa_bootcamp_2026', 'clb_gfg_sc_uit_2026',
            'Mastering Data Structures & Algorithms with GeeksforGeeks',
            'mastering-dsa-with-geeksforgeeks',
            'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=800&auto=format&fit=crop',
            'Intensive hands-on DSA bootcamp covering Trees, Graphs, Dynamic Programming & interview problem-solving with GFG.',
            'Computer Lab 2 & Seminar Hall, UIT', '2026-03-20 14:00:00', 'https://www.geeksforgeeks.org', 'completed'
        ],
        [
            'evt_gfg_coding_contest_2026', 'clb_gfg_sc_uit_2026',
            'GFG UIT CodeSprint 2026 (Algorithmic Battle)',
            'gfg-uit-codesprint-2026',
            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=800&auto=format&fit=crop',
            'Inter-branch coding competition hosted on GeeksforGeeks platform with certificates, goodies & placement perks.',
            'UIT Main Computer Lab', '2026-04-10 11:00:00', 'https://www.geeksforgeeks.org', 'completed'
        ],
        [
            'evt_hr_codesprint_2026', 'clb_hackerrank_uit_2026',
            'HackerRank UIT 30-Day CodeSprint Challenge',
            'hackerrank-uit-30-day-codesprint',
            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=800&auto=format&fit=crop',
            'Month-long algorithmic problem solving marathon with daily practice badges, leaderboard rankings, and tech interview prep.',
            'Online HackerRank Platform & UIT Computer Labs', '2026-03-01 10:00:00', 'https://www.hackerrank.com', 'completed'
        ],
        [
            'evt_pitchfest_2026', 'clb_ecell_uit_2026',
            'E-Cell PitchFest & Angel Investor Summit',
            'ecell-pitchfest-angel-investor-summit',
            'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=800&auto=format&fit=crop',
            'Student startup pitch competition with prototype funding grants up to ₹1,00,000 for top ideas.',
            'Seminar Hall 2, UIT', '2026-03-28 11:00:00', '/contact.html', 'completed'
        ],
        [
            'evt_wiki_tech_writing_2026', 'clb_wikiclub_uit_2026',
            'WikiClub Open Knowledge & Technical Writing Edit-a-thon',
            'wikiclub-open-knowledge-tech-writing-editathon',
            'https://images.unsplash.com/photo-1455390582262-044cdead277a?q=80&w=800&auto=format&fit=crop',
            'Hands-on technical documentation, Wikipedia article creation, and open-source contribution workshop at UIT.',
            'Seminar Hall 1, UIT', '2026-04-15 11:00:00', 'https://www.wikimedia.org', 'completed'
        ]
    ];

    $eStmt = $db->prepare("
        INSERT INTO events (id, club_id, title, slug, banner, description, venue, event_date, registration_link, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($events as $ev) {
        $eStmt->execute($ev);
    }
    echo "[+] Seeded " . count($events) . " Official Events.\n";

    // 4. Seed Official Gallery Items
    $gallery = [
        ['gal_1', 'clb_gdgoc_uit_2026', 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=800&auto=format&fit=crop', 'Google Cloud Study Jam 2026 & Swags Distribution'],
        ['gal_3', 'clb_gdgoc_uit_2026', 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=800&auto=format&fit=crop', 'HACKQUEST \'25 24-Hour Hackathon Teams'],
        ['gal_5', 'clb_gdgoc_uit_2026', 'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=800&auto=format&fit=crop', 'Build With AI Summit & Gemini API Demo'],
        ['gal_6', 'clb_gdgoc_uit_2026', 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=800&auto=format&fit=crop', 'TFUG x GDG On-Campus Inaugural Keynote'],
        ['gal_gfg_1', 'clb_gfg_sc_uit_2026', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=800&auto=format&fit=crop', 'GeeksforGeeks Student Chapter UIT - DSA Workshop & Coding Bootcamp'],
        ['gal_hr_1', 'clb_hackerrank_uit_2026', 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=800&auto=format&fit=crop', 'HackerRank UIT - CodeSprint Practice Session & Contest'],
        ['gal_ecell_1', 'clb_ecell_uit_2026', 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=800&auto=format&fit=crop', 'E-Cell PitchFest Startup Presentations'],
        ['gal_wiki_1', 'clb_wikiclub_uit_2026', 'https://images.unsplash.com/photo-1455390582262-044cdead277a?q=80&w=800&auto=format&fit=crop', 'WikiClub Tech - Open Knowledge & Edit-a-thon Session']
    ];

    $gStmt = $db->prepare("INSERT INTO gallery_items (id, club_id, media_url, caption) VALUES (?, ?, ?, ?)");
    foreach ($gallery as $g) {
        $gStmt->execute($g);
    }
    echo "[+] Seeded " . count($gallery) . " Gallery Items.\n";

    echo "===========================================\n";
    echo "  Official 5 Clubs Database Seeded!        \n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "[-] Error seeding official 5 clubs: " . $e->getMessage() . "\n";
}
