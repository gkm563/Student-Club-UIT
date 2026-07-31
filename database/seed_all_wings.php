<?php
/**
 * Seeder to populate all official sub-clubs under the 2 Main Wings in ccms.sqlite
 * Wing 1: Developers Club UIT (Technical Umbrella Council)
 * Wing 2: Cultural Club UIT (Cultural Umbrella Council)
 */

$sqlitePath = __DIR__ . '/ccms.sqlite';
$db = new PDO('sqlite:' . $sqlitePath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

echo "Seeding/Verifying clubs in SQLite...\n";

// Ensure categories exist
$db->exec("INSERT OR IGNORE INTO categories (id, name, slug, icon, description) VALUES
(1, 'Technical', 'technical', 'bi-code-slash', 'Coding, Robotics, AI, Web Dev and Tech Innovation'),
(2, 'Cultural', 'cultural', 'bi-masks', 'Dance, Music, Drama and Creative Arts'),
(3, 'Sports', 'sports', 'bi-trophy', 'Athletics, Cricket, Football and Outdoor Games'),
(4, 'Social Impact', 'social', 'bi-heart', 'Community Service, Volunteering and CSR'),
(5, 'Entrepreneurship', 'entrepreneurship', 'bi-lightbulb', 'Startups, Innovation and E-Cell Drives'),
(6, 'Media & Creative', 'creative', 'bi-camera', 'Photography, Videography and Design'),
(7, 'Academic & Literary', 'academic', 'bi-journal-text', 'Literature, Debating, Toastmasters and Speech')
");

// Sub-clubs under Developers Club UIT (Technical Council)
$techClubs = [
    [
        'clb_gdgoc_uit_2026', 'Google Developer Groups On Campus - UIT', 'GDGOC UIT', 'gdgoc-uit', 1,
        'Building, Innovating & Empowering Tech Enthusiasts at UIT',
        'Official Google Developer Groups chapter at UIT for Web, Cloud, AI, and Android development.',
        'https://images.unsplash.com/photo-1573164713988-8665fc963095?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=1200&auto=format&fit=crop'
    ],
    [
        'clb_gfg_sc_uit_2026', 'GeeksforGeeks Student Chapter - UIT', 'GFG SC UIT', 'gfgsc-uit', 1,
        'Promoting Coding Culture, DSA, Competitive Programming & Tech Excellence',
        'Official GeeksforGeeks chapter promoting Data Structures, Algorithms, and interview prep.',
        'https://media.geeksforgeeks.org/wp-content/uploads/gfg_200X200.png',
        'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=1200&auto=format&fit=crop'
    ],
    [
        'clb_hackerrank_uit_2026', 'HackerRank UIT', 'HackerRank UIT', 'hackerrank-uit', 1,
        'Practice. Compete. Improve. Repeat.',
        'Official HackerRank coding chapter for competitive programming and monthly leaderboards.',
        'https://hrcdn.net/f2/assets/brand/h_mark_sm.png',
        'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?q=80&w=1200&auto=format&fit=crop'
    ],
    [
        'clb_foss_uit_2026', 'FOSS (Free & Open Source) UIT', 'FOSS UIT', 'foss-uit', 1,
        'Promoting Open Source Software, Linux, Kernel & Git',
        'Student-led open-source community dedicated to Linux, Git/GitHub, and GSoC preparation.',
        'https://images.unsplash.com/photo-1618401471353-b98afee0b2eb?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=1200&auto=format&fit=crop'
    ],
    [
        'clb_gemini_builders', 'Gemini Builders Community – UIT', 'Gemini Builders', 'gemini-builders-uit', 1,
        'Building Next-Gen AI Applications with Google Gemini & LLMs',
        'Exclusive AI builder chapter focused on building real-world AI applications with Gemini APIs.',
        'https://images.unsplash.com/photo-1677442136019-21780efad99a?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=1200&auto=format&fit=crop'
    ]
];

// Sub-clubs under Cultural Club UIT (Cultural Council)
$cultClubs = [
    [
        'clb_nritya', 'Nritya Cultural & Dance Club', 'Nritya UIT', 'nritya-uit', 2,
        'Expressing Passion through Dance, Choreography & Stage Performance',
        'Official dance and performing arts chapter representing UIT in inter-college cultural fests.',
        'https://images.unsplash.com/photo-1547153760-18fc86324498?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?q=80&w=1200&auto=format&fit=crop'
    ],
    [
        'clb_harmony_music', 'Harmony Music & Band Club', 'Harmony Music', 'harmony-music-uit', 2,
        'Vocal Jams, Instruments & Live Campus Concerts',
        'Music chapter for vocalists, instrumentalists, sound enthusiasts, and campus bands.',
        'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?q=80&w=1200&auto=format&fit=crop'
    ],
    [
        'clb_toastmasters_ugi_2026', 'UGI Toastmasters & Public Speaking', 'Toastmasters UGI', 'toastmasters-ugi', 7,
        'Where Voices Find Their Power & Confidence Soars',
        'Official public speaking and communication chapter for speeches, debates, and leadership.',
        'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=1200&auto=format&fit=crop'
    ],
    [
        'clb_rangmanch_drama', 'Rangmanch Theatre & Drama Club', 'Rangmanch UIT', 'rangmanch-uit', 2,
        'Bringing Stories Alive through Nukkad Natak & Stage Theatre',
        'Drama and theatre society dedicated to street plays, stage acts, scriptwriting, and mime.',
        'https://images.unsplash.com/photo-1507676184212-d03ab07a01bf?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1460723237483-7a6dc9d0b212?q=80&w=1200&auto=format&fit=crop'
    ],
    [
        'clb_prism', 'Prism Media & Fine Arts Club', 'Prism Media', 'prism-uit', 6,
        'Capturing Moments through Fine Arts, Photography & Film',
        'Visual arts, digital design, photography, and fine arts chapter of UIT.',
        'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?q=80&w=400&auto=format&fit=crop',
        'https://images.unsplash.com/photo-1492691527719-9d1e07e534b4?q=80&w=1200&auto=format&fit=crop'
    ]
];

$stmt = $db->prepare("
    INSERT OR REPLACE INTO clubs (id, name, short_name, slug, category_id, tagline, description, logo, cover_image, status, founded_year)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 2023)
");

foreach (array_merge($techClubs, $cultClubs) as $c) {
    $stmt->execute($c);
}

echo "Successfully seeded/updated " . (count($techClubs) + count($cultClubs)) . " clubs across both wings in SQLite!\n";
