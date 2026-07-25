<?php
/**
 * Seeder Script for All Active Campus Clubs (UIT)
 * Populates real clubs, leadership rosters, real events, and real gallery media.
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();
    echo "===========================================\n";
    echo "  Seeding Campus Clubs & Stats into Database \n";
    echo "===========================================\n";

    // Fetch Category Mapping
    $cats = [];
    $stmt = $db->query("SELECT id, slug FROM categories");
    while ($row = $stmt->fetch()) {
        $cats[$row['slug']] = $row['id'];
    }

    $techCat = $cats['technical'] ?? 1;
    $cultCat = $cats['cultural'] ?? 2;
    $sportsCat = $cats['sports'] ?? 3;
    $socialCat = $cats['social'] ?? 4;
    $ecellCat = $cats['entrepreneurship'] ?? 5;
    $mediaCat = $cats['creative'] ?? 6;
    $academicCat = $cats['academic'] ?? 7;

    // 1. Seed Active Clubs
    $clubs = [
        [
            'clb_gdgoc_uit_2026', 'Google Developer Groups On Campus - UIT', 'GDGOC UIT', 'gdgoc-uit', $techCat,
            'Building, Innovating & Empowering Tech Enthusiasts at UIT',
            'Google Developer Groups On Campus - UIT brings together coders, problem solvers, and innovators to learn, build, and solve real-world problems with Google technologies.',
            'To cultivate developer skills through workshops, Study Jams, and Solution Challenges.',
            'To build the most vibrant student developer ecosystem at UIT.',
            'https://images.unsplash.com/photo-1573164713988-8665fc963095?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=1200&auto=format&fit=crop',
            2023, 'active', 1, '/contact.html', 'gdgoc@uit.edu', '+91 98765 12345'
        ],
        [
            'clb_codecrush', 'CodeCrush Programming Club', 'CodeCrush', 'codecrush-uit', $techCat,
            'Competitive Programming, Web Dev & Open Source',
            'CodeCrush is UIT\'s flagship competitive coding and software engineering community dedicated to mastering algorithms, data structures, and hackathons.',
            'Empower students with algorithmic thinking and industry coding standards.',
            'To cultivate top-tier software engineers and competitive programmers.',
            'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=1200&auto=format&fit=crop',
            2022, 'active', 1, '/contact.html', 'codecrush@uit.edu', '+91 98765 12346'
        ],
        [
            'clb_nritya', 'Nritya Cultural & Performing Arts Club', 'Nritya UIT', 'nritya-uit', $cultCat,
            'Expressing Passion through Dance, Music & Drama',
            'Nritya brings together dancers, musicians, and theater artists to perform at major inter-college fests, cultural nights, and national competitions.',
            'Provide a creative platform for artistic expression and stage performance.',
            'To celebrate diversity, art, and cultural excellence at UIT.',
            'https://images.unsplash.com/photo-1547153760-18fc86324498?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?q=80&w=1200&auto=format&fit=crop',
            2021, 'active', 1, '/contact.html', 'nritya@uit.edu', '+91 98765 12347'
        ],
        [
            'clb_phoenix', 'Phoenix Sports & Athletics Club', 'Phoenix UIT', 'phoenix-uit', $sportsCat,
            'Unleashing Athletic Potential & Team Spirit',
            'Phoenix is the official sports wing of UIT, organizing annual sports meets, inter-branch cricket & football tournaments, and fitness bootcamps.',
            'Promote physical fitness, discipline, and sportsmanship among students.',
            'To win accolades across national university sports championships.',
            'https://images.unsplash.com/photo-1517649763962-0c623266010b?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?q=80&w=1200&auto=format&fit=crop',
            2020, 'active', 1, '/contact.html', 'phoenix@uit.edu', '+91 98765 12348'
        ],
        [
            'clb_ecell', 'E-Cell UIT (Entrepreneurship Cell)', 'E-Cell UIT', 'ecell-uit', $ecellCat,
            'Fostering Startups, Innovation & Business Leadership',
            'E-Cell UIT nurtures student entrepreneurs by organizing Pitch Jams, Founder Talks, Angel Investor Summits, and incubation mentorship.',
            'Turn innovative student ideas into viable commercial startups.',
            'To build an ecosystem of successful student founders at UIT.',
            'https://images.unsplash.com/photo-1559136555-9303baea8ebd?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?q=80&w=1200&auto=format&fit=crop',
            2022, 'active', 1, '/contact.html', 'ecell@uit.edu', '+91 98765 12349'
        ],
        [
            'clb_parivartan', 'Parivartan Social Impact Club', 'Parivartan', 'parivartan-uit', $socialCat,
            'Driving Community Service & Sustainable Change',
            'Parivartan leads blood donation drives, tree plantation campaigns, free education camps for underprivileged children, and environmental awareness.',
            'Serve society through impactful community service and social welfare.',
            'To inspire socially responsible leaders for a sustainable future.',
            'https://images.unsplash.com/photo-1593113598332-cd288d649433?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=1200&auto=format&fit=crop',
            2021, 'active', 1, '/contact.html', 'parivartan@uit.edu', '+91 98765 12350'
        ],
        [
            'clb_prism', 'Prism Media & Design Club', 'Prism Media', 'prism-uit', $mediaCat,
            'Capturing Moments through Photography, Film & Graphic Design',
            'Prism is the creative media backbone of UIT, covering campus events, producing short films, designing fest banners, and running digital media.',
            'Nurture visual storytelling, photography, and digital arts.',
            'To be the premier creative media house across regional institutes.',
            'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?q=80&w=1200&auto=format&fit=crop',
            2023, 'active', 1, '/contact.html', 'prism@uit.edu', '+91 98765 12351'
        ],
        [
            'clb_literary', 'Literary & Debating Society', 'LitSoc UIT', 'litsoc-uit', $academicCat,
            'Mastering Debates, Public Speaking & Creative Writing',
            'LitSoc UIT organizes Parliamentary Debates, Model United Nations (MUN), Poetry Slams, and annual literary symposiums.',
            'Hone critical thinking, eloquence, and intellectual discourse.',
            'To empower articulate voices and global leaders at UIT.',
            'https://images.unsplash.com/photo-1455390582262-044cdead277a?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=1200&auto=format&fit=crop',
            2021, 'active', 1, '/contact.html', 'litsoc@uit.edu', '+91 98765 12352'
        ]
    ];

    $db->exec("DELETE FROM clubs");
    $cStmt = $db->prepare("
        INSERT INTO clubs (
            id, name, short_name, slug, category_id, tagline, description, mission, vision,
            logo, cover_image, founded_year, status, recruitment_open, recruitment_link, email, phone
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($clubs as $c) {
        $cStmt->execute($c);
    }
    echo "[+] Seeded " . count($clubs) . " Active Campus Clubs into database.\n";

    // 2. Seed Real Leadership Roster
    $db->exec("DELETE FROM leadership");
    $leaders = [
        ['ldr_1', 'clb_gdgoc_uit_2026', 'Shivansh Singh', 'GDG Lead & President', 'president', 'shivansh@uit.edu', '+91 98765 00001', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_2', 'clb_gdgoc_uit_2026', 'Sarthak Singh', 'GDG Lead & Past President', 'president', 'sarthak@uit.edu', '+91 98765 00002', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_3', 'clb_codecrush', 'Aman Gupta', 'President', 'president', 'aman@uit.edu', '+91 98765 00003', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_4', 'clb_codecrush', 'Rohan Sharma', 'Vice President', 'vice_president', 'rohan@uit.edu', '+91 98765 00004', 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_5', 'clb_nritya', 'Sneha Iyer', 'President', 'president', 'sneha@uit.edu', '+91 98765 00005', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_6', 'clb_nritya', 'Priya Das', 'Dance Team Lead', 'core_member', 'priya@uit.edu', '+91 98765 00006', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_7', 'clb_phoenix', 'Kabir Malhotra', 'Captain & Sports Lead', 'president', 'kabir@uit.edu', '+91 98765 00007', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_8', 'clb_ecell', 'Riya Sharma', 'E-Cell Convener', 'president', 'riya@uit.edu', '+91 98765 00008', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_9', 'clb_parivartan', 'Ananya Verma', 'Social Lead', 'president', 'ananya@uit.edu', '+91 98765 00009', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_10', 'clb_prism', 'Arjun Mehta', 'Creative Director', 'president', 'arjun@uit.edu', '+91 98765 00010', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_11', 'clb_literary', 'Neha Verma', 'General Secretary', 'secretary', 'neha@uit.edu', '+91 98765 00011', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=400&auto=format&fit=crop', 1]
    ];

    $lStmt = $db->prepare("
        INSERT INTO leadership (id, club_id, name, role_title, category, email, phone, avatar, order_index)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($leaders as $ldr) {
        $lStmt->execute($ldr);
    }
    echo "[+] Seeded " . count($leaders) . " Leadership Members.\n";

    // 3. Seed Events
    $db->exec("DELETE FROM events");
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
            'evt_codecraft_2026', 'clb_codecrush',
            'CodeCraft 2026 Algorithmic Championship',
            'codecraft-2026-algorithmic-championship',
            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=800&auto=format&fit=crop',
            'Annual competitive programming contest hosted by CodeCrush testing data structures & dynamic programming skills.',
            'Computer Lab 1 & 2, UIT', '2026-03-14 10:00:00', '/contact.html', 'completed'
        ],
        [
            'evt_rangmanch_2026', 'clb_nritya',
            'Rangmanch Annual Cultural Fest',
            'rangmanch-annual-cultural-fest',
            'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=800&auto=format&fit=crop',
            'Grand cultural night featuring inter-college dance face-offs, battle of bands, and fashion show.',
            'UIT Open Air Theatre', '2026-02-20 17:00:00', '/contact.html', 'completed'
        ],
        [
            'evt_superleague_2026', 'clb_phoenix',
            'Phoenix Inter-Branch Cricket SuperLeague',
            'phoenix-inter-branch-cricket-superleague',
            'https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?q=80&w=800&auto=format&fit=crop',
            '16-team knockout cricket tournament among CSE, ECE, ME, EN, and Civil branches.',
            'UIT Main Sports Ground', '2026-01-18 09:00:00', '/contact.html', 'completed'
        ],
        [
            'evt_pitchfest_2026', 'clb_ecell',
            'E-Cell PitchFest & Angel Investor Summit',
            'ecell-pitchfest-angel-investor-summit',
            'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=800&auto=format&fit=crop',
            'Student startup pitch competition with prototype funding grants up to ₹1,00,000 for top ideas.',
            'Seminar Hall 2, UIT', '2026-03-28 11:00:00', '/contact.html', 'completed'
        ]
    ];

    $eStmt = $db->prepare("
        INSERT INTO events (id, club_id, title, slug, banner, description, venue, event_date, registration_link, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($events as $ev) {
        $eStmt->execute($ev);
    }
    echo "[+] Seeded " . count($events) . " Real Campus Events into database.\n";

    // 4. Seed Gallery Media Items
    $db->exec("DELETE FROM gallery_items");
    $gallery = [
        ['gal_1', 'clb_gdgoc_uit_2026', 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=800&auto=format&fit=crop', 'Google Cloud Study Jam 2026 & Swags Distribution'],
        ['gal_2', 'clb_nritya', 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=800&auto=format&fit=crop', 'Rangmanch Annual Cultural Fest Performance'],
        ['gal_3', 'clb_gdgoc_uit_2026', 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=800&auto=format&fit=crop', 'HACKQUEST \'25 24-Hour Hackathon Teams'],
        ['gal_4', 'clb_phoenix', 'https://images.unsplash.com/photo-1540747913346-19e32dc3e97e?q=80&w=800&auto=format&fit=crop', 'Inter-Branch Cricket SuperLeague Trophy Ceremony'],
        ['gal_5', 'clb_gdgoc_uit_2026', 'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=800&auto=format&fit=crop', 'Build With AI Summit & Gemini API Demo'],
        ['gal_6', 'clb_gdgoc_uit_2026', 'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?q=80&w=800&auto=format&fit=crop', 'TFUG x GDG On-Campus Inaugural Keynote'],
        ['gal_7', 'clb_codecrush', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=800&auto=format&fit=crop', 'CodeCraft Competitive Coding Championship'],
        ['gal_8', 'clb_ecell', 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=800&auto=format&fit=crop', 'E-Cell PitchFest Startup Presentations']
    ];

    $gStmt = $db->prepare("INSERT INTO gallery_items (id, club_id, media_url, caption) VALUES (?, ?, ?, ?)");
    foreach ($gallery as $g) {
        $gStmt->execute($g);
    }
    echo "[+] Seeded " . count($gallery) . " Gallery Media Items into database.\n";

    echo "===========================================\n";
    echo "  Campus Database Seeded Successfully!     \n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "[-] Seeding Error: " . $e->getMessage() . "\n";
}
