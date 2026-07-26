<?php
/**
 * Detailed Event Updates for GDG on Campus UIT (MySQL & SQLite Dual Sync)
 * Updates all 7 real campus events with exact descriptions, venues, RSVP counts, and key themes.
 */

require_once __DIR__ . '/../config/database.php';

function update_gdg_events_pdo(PDO $db, string $dbType) {
    echo "=== Updating Detailed Events in {$dbType} ===\n";
    $clubId = 'clb_gdgoc_uit_2026';

    // Ensure columns exist
    try { $db->exec("ALTER TABLE events ADD COLUMN registered_count INTEGER DEFAULT 0"); } catch (Exception $ex) {}
    try { $db->exec("ALTER TABLE events ADD COLUMN outcomes_summary TEXT"); } catch (Exception $ex) {}

    // Clear existing events for GDGOC
    $db->prepare("DELETE FROM events WHERE club_id = ?")->execute([$clubId]);

    $nowStr = date('Y-m-d H:i:s');
    $stmt = $db->prepare("
        INSERT INTO events (
            id, club_id, title, slug, description, event_date, venue, 
            registration_link, banner, registered_count, outcomes_summary, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)
    ");

    $events = [
        [
            'evt_gdg_1',
            'Kickstart App Development with Flutterflow',
            'kickstart-app-development-flutterflow',
            "Join us for an exciting session, \"Kickstart App Development with Flutterflow,\" designed to empower you with the skills needed to create visually stunning and effective mobile applications without the need for extensive coding knowledge. Whether you're a novice eager to step into the world of app development or an experienced developer looking to simplify the process, this event is for you!\n\nDuring this immersive session, you will learn how to harness the power of Flutterflow to turn your innovative ideas into tangible apps rapidly. Don't miss out on this opportunity to elevate your tech skills and expand your network. Reserve your spot now and let's transform your concepts into reality together!",
            '2025-11-27 15:00:00',
            'UIT Induction Hall, D3, UPSIDC Industrial Area, Naini, Prayagraj 211010',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1551650975-87deedd944c3?q=80&w=1200&auto=format&fit=crop',
            96,
            '96 RSVP\'d | Key Themes: Android, Career Development, Firebase, Flutter, Tech Talk / Meetup | Speaker: Shivaansh Singh (GDGoC Organizer)'
        ],
        [
            'evt_gdg_2',
            'Know your GDG on Campus - UIT',
            'know-your-gdg-on-campus-uit',
            "Know Your GDG on Campus – UIT\nThis session is regarding the official kickoff of the Google Developer Groups on Campus at United Institute of Technology (GDGoC – UIT), where students will get an exclusive introduction to what the community stands for and what lies ahead for this exciting tenure.\n\nOne of the major highlights of the event will be the reveal of the GDGoC – UIT core team. Attendees will get to meet the passionate individuals driving the chapter forward - the ones organizing events, building collaborations, and making things happen behind the scenes.\n\nThe session will also cover:\n• Upcoming Opportunities & Sessions - Overview of tech/non-tech events, coding workshops, Cloud Study Jams, and hackathons.\n• Insights into Google Campaigns - Solution Challenge, Android Study Jam, and global recognition.\n• Introduction to Study Jam - A sneak peek into the upcoming collaborative learning series.",
            '2025-10-05 19:00:00',
            'GDG on Campus United Institute of Technology - Allahabad, India',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=1200&auto=format&fit=crop',
            66,
            '66 RSVP\'d | Key Themes: Community Building, Flutter, Get Certified, Tech Talk / Meetup, UX / UI Design'
        ],
        [
            'evt_gdg_3',
            'HACKQUEST\'25',
            'hackquest-25',
            "Get ready to push the boundaries of innovation at a thrilling two-day software and hardware development hackathon, hosted at the United Institute of Technology on 23rd and 24th April 2025. This high-energy event invites creative minds to build impactful applications based on real-world themes, competing against time to transform ideas into powerful solutions. Participants will be challenged to develop projects inspired by global issues and sustainable development goals, ranging from No Poverty and Zero Hunger to Climate Action, Clean Energy, and Student Innovation.\n\nBeyond the competition, the event promises an unforgettable experience. As the sun sets, attendees can relax at the Gala Night, featuring live music and networking opportunities beneath the stars—perfect for making new connections and celebrating the spirit of innovation. Throughout the hackathon, complimentary meals and snacks will be provided, keeping participants fueled and energized.\n\nThe stakes are high, with impressive cash prizes and recognition up for grabs. The top three teams will be awarded ₹40,000, ₹30,000, and ₹10,000 respectively, along with trophies and certificates. Additionally, special category awards of ₹5,000 each will honor the best in Design, Innovation, Startup Idea, and Hardware Implementation.",
            '2025-04-23 12:00:00',
            'UIT Auditorium, Naini, Prayagraj 211010',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=1200&auto=format&fit=crop',
            24,
            '24-Hour Hackathon | Total Prize Pool: ₹85,000 (1st: ₹40K, 2nd: ₹30K, 3rd: ₹10K + ₹5K Special Awards) | Gala Night & Networking'
        ],
        [
            'evt_gdg_4',
            'Build with AI (Virtual Conference)',
            'build-with-ai-jan-2025',
            "Winning Strategies: A Chat with the Solution Challenge Champion\nParticipating in Google Solution Challenge 2025? Want to learn from the winner himself?\n\nGuess what? We hosted an online session with Krishna Aute — his team secured a top-three spot globally in the Google Solution Challenge 2024, beating out over 100,000 developers worldwide!\n\nKrishna is a pro when it comes to solving real-world problems with tech. In this session, he shared the winning strategies that helped his team achieve this incredible milestone with their app, SpoonShare (tackling surplus food distribution).",
            '2025-01-27 17:45:00',
            'Virtual Main Stage (Bevy Virtual Conference)',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=1200&auto=format&fit=crop',
            41,
            '41 RSVP\'d | Guest Speaker: Krishna Aute (Google Solution Challenge 2024 Global Winner - Top 3 Worldwide among 100,000+ developers)'
        ],
        [
            'evt_gdg_5',
            'Tech Winter Break + GDG On Campus United Institute Of Technology',
            'tech-winter-break-gdg-uit',
            "Join us for an exciting briefing session to explore the incredible opportunities of the Google Solution Challenge 2025. Learn how you can tackle real-world problems using Google technologies, gain insights into the challenge theme, and get expert tips on building impactful projects and effective teams.\n\nAgenda:\n• 10:00 AM - Registration & RSVP Ticket Verification\n• 10:30 AM - Keynote: Google Solution Challenge 2025 Theme & Tech Stack\n• 11:45 AM - Quiz Competition & Swags Distribution",
            '2024-12-12 10:00:00',
            'Induction Hall, 1st Floor, United Institute Of Technology, Prayagraj 211010',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=1200&auto=format&fit=crop',
            57,
            '57 RSVP\'d | Key Themes: Android, Angular, Tech Talk / Meetup, Web | Solution Challenge 2025 Briefing'
        ],
        [
            'evt_gdg_6',
            'Build with AI',
            'build-with-ai-nov-2024',
            "Join us for an engaging and informative session on Artificial Intelligence, specially designed for juniors eager to explore and deepen their understanding of this transformative technology.\n\nParticipants will gain insights into the fundamentals of AI, including a spotlight on generative AI (Gemini API) and its applications across industries. The session also covers AI career roadmaps, real-world project showcases, and live quiz prizes.\n\nAgenda:\n• 2:00 PM - Registration\n• 2:15 PM - Generative AI: Can Machines Create Like Humans?\n• 3:00 PM - Generative AI: Machine Creativity & Industry Applications\n• 3:30 PM - Quiz Time & Swag Prizes",
            '2024-11-08 14:00:00',
            'UIT Induction Hall, 1st Floor, Prayagraj 211010',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1531482615713-2afd69097998?q=80&w=1200&auto=format&fit=crop',
            126,
            '126 RSVP\'d | Key Themes: AI, AI - Gemini, Build with AI, Community Building | Speakers: Sarthak Singh & Abhipsa Srivastava'
        ],
        [
            'evt_gdg_7',
            'TFUG x GDG On-Campus Inaugural',
            'tfug-x-gdg-on-campus-inaugural',
            "We are excited to announce our collaboration with TFUG Prayagraj [ML Prayagraj] for an informative session on Firebase. This session dives deep into how Firebase can be used to build scalable web and mobile applications, along with its integration with TensorFlow to create intelligent solutions. Attendees will learn about key features like real-time databases, authentication, and cloud storage.\n\nAlongside this, we are thrilled to inaugurate the new GDG On-Campus Lead at United Institute of Technology, marking the beginning of a new chapter for our developer community. We also honored the outgoing GDSC Lead for 2023-2024 and his team for their immense contributions.\n\nAgenda:\n• 2:00 PM - Registration\n• 2:10 PM - Keynote by Campus Lead\n• 2:20 PM - Principal Speech\n• 2:30 PM - Speaker Session on Firebase & Google Checks (Ankit Kumar Verma - GDG Prayagraj)\n• 3:30 PM - Quiz Time!\n• 4:00 PM - Food & Networking Exit",
            '2024-10-01 14:00:00',
            'United Institute Of Technology, National Highway 2, D-3, UPSIDC Industrial Area, Naini, Prayagraj 211010',
            'https://gdg.community.dev/',
            'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=1200&auto=format&fit=crop',
            61,
            '61 RSVP\'d | Partner: TensorFlow User Group Prayagraj [ML Prayagraj] | Speaker: Ankit Kumar Verma (GDG Prayagraj)'
        ]
    ];

    foreach ($events as $e) {
        $stmt->execute([
            $e[0], $clubId, $e[1], $e[2], $e[3], $e[4], $e[5], $e[6], $e[7], $e[8], $e[9], $nowStr
        ]);
    }
    echo "[✓] Updated 7 Detailed GDGOC Events with exact RSVP counts, agendas & venues!\n";
}

try {
    $mainDb = Database::getConnection();
    update_gdg_events_pdo($mainDb, "Main Database");
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
        update_gdg_events_pdo($sqliteDb, "SQLite File");
    } catch (Exception $e) {
        echo "[X] SQLite Error: " . $e->getMessage() . "\n";
    }
}
