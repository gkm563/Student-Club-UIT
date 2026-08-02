<?php
/**
 * Master Official Seeder for CCMS (United Institute of Technology)
 * Seeds the 10 Official Campus Clubs:
 * 1. Google Developer Groups On Campus - UIT (GDGOC UIT)
 * 2. GeeksforGeeks Student Chapter - UIT (GFG SC UIT)
 * 3. HackerRank UIT
 * 4. E-Cell UIT (Entrepreneurship Cell)
 * 5. WikiClub Tech - UIT
 * 6. FOSS (Free and Open Source Software) UIT
 * 7. Rotaract Club of UIT
 * 8. UGI Toastmasters Club
 * 9. FlutterFlow Student Chapter - UIT
 * 10. TEDxUIT
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    echo "===========================================\n";
    echo "  Seeding 10 Official Campus Clubs into Database \n";
    echo "===========================================\n";

    // Fetch Category Mapping
    $cats = [];
    $stmt = $db->query("SELECT id, slug FROM categories");
    while ($row = $stmt->fetch()) {
        $cats[$row['slug']] = $row['id'];
    }

    $techCatId = $cats['technical'] ?? 1;
    $cultCatId = $cats['cultural'] ?? 2;
    $ecellCatId = $cats['entrepreneurship'] ?? 5;
    $socialCatId = $cats['social'] ?? 4;
    $academicCatId = $cats['academic'] ?? 7;

    // Clear all existing data cleanly
    $db->exec("DELETE FROM management_committee");
    $db->exec("DELETE FROM gallery_items");
    $db->exec("DELETE FROM events");
    $db->exec("DELETE FROM leadership");
    $db->exec("DELETE FROM clubs");

    // 0. Seed Management Committee & Institutional Leadership
    $committeeStmt = $db->prepare("
        INSERT INTO management_committee (name, designation, role_title, photo, bio, order_index)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $committeeMembers = [
        ['Dr. G. G. Gulati', 'CHAIRMAN', 'Management Committee, UGI', 'assets/img/committee/girdhar-gopal-gulati.webp', 'Visionary leader driving educational excellence across United Group of Institutions.', 1],
        ['Er. Satpal Gulati', 'PRESIDENT', 'United Group of Institutions', 'assets/img/committee/satpal-gulati.webp', 'Guiding institutional growth, technical education innovation, and student welfare.', 2],
        ['Dr. Jagdish Gulati', 'VICE CHAIRMAN', 'Management Committee, UGI', 'assets/img/committee/jagdish-gulati.webp', 'Pioneering student activity frameworks and campus infrastructure development.', 3],
        ['Dr. Gaurav Gulati', 'SENIOR VICE PRESIDENT', 'Management Committee, UGI', 'assets/img/committee/gaurav-gulati.webp', 'Fostering industry-academia collaboration and student co-curricular affairs.', 4],
        ['Prof. (Dr.) Sanjay Srivastava', 'PRINCIPAL', 'United Institute of Technology', 'assets/img/committee/sanjay-srivastava.webp', 'Overseeing official student clubs, co-curricular governance, and campus events.', 5],
        ['Dr. Manas Pandey', 'DEAN STUDENT WELFARE (DSW)', 'Student Club Affairs, UIT', 'assets/img/committee/manas-pandey.jpg', 'Coordinating official club registrations, event approvals, and student leadership.', 6]
    ];

    foreach ($committeeMembers as $cm) {
        $committeeStmt->execute($cm);
    }
    echo "[+] Seeded 6 Institutional Management Committee Leaders.\n";

    // 1. Seed 10 Official Clubs
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
            2022, 'active', 1, '/contact.html', 'ecell@uit.edu', '+91 98765 12349', 'USC UIT Office, UIT, Prayagraj 211010', '/contact.html'
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
        ],
        [
            'clb_foss_uit_2026',
            'FOSS (Free and Open Source Software) UIT',
            'FOSS UIT',
            'foss-uit',
            $techCatId,
            'Promoting Free & Open Source Software, Linux, Kernel & Open Tech',
            'FOSS UIT is the official Free and Open Source Software community of United Institute of Technology, dedicated to promoting open-source software, Linux operating systems, Git/GitHub mastery, and collaborative software development among students.',
            'To empower students with open-source tools, collaborative development skills, Linux proficiency, and active participation in global open-source programs like GSoC and Hacktoberfest.',
            'Freedom to Learn. Freedom to Build. Freedom to Share. — Creating a thriving ecosystem of open-source contributors and Linux enthusiasts at UIT.',
            '• Open Source Bootcamps & Hacktoberfest Drives\n• Linux & Command-Line Masterclasses\n• Git & GitHub Version Control Workshops\n• Google Summer of Code (GSoC) Guidance Sessions',
            'https://images.unsplash.com/photo-1618401471353-b98afee0b2eb?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=1200&auto=format&fit=crop',
            2026, 'active', 1, '/contact.html', 'foss@uit.edu', '+91 98765 44441', 'United Institute of Technology Naini, UPSIDC Industrial Area, Naini, Prayagraj, Uttar Pradesh IN', 'https://foss.in'
        ],
        [
            'clb_rotaract_uit_2026',
            'Rotaract Club of UIT',
            'Rotaract UIT',
            'rotaract-uit',
            $socialCatId,
            'Service Above Self — Social Welfare, Community Outreach & Charity',
            'Rotaract Club of UIT conducts social welfare initiatives, community outreach programs, blood donation camps, and charitable events like Project Smile across United Institute of Technology.',
            'To inspire young leaders to serve society through impactful community development, environmental awareness, educational support for underprivileged children, and social welfare drives.',
            'Fellowship Through Service. — Creating a compassionate and socially responsible student community at UIT.',
            '• Project Smile & Charitable Outreach\n• Blood Donation & Health Camps\n• Environmental Awareness & Tree Plantation Drives\n• Free Educational Kits for Underprivileged Children',
            'https://images.unsplash.com/photo-1593113598332-cd288d649433?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?q=80&w=1200&auto=format&fit=crop',
            2024, 'active', 1, '/contact.html', 'rotaract@uit.edu', '+91 98765 55551', 'USC UIT Office, UIT, Prayagraj 211010', '/contact.html'
        ],
        [
            'clb_toastmasters_ugi_2026',
            'UGI Toastmasters Club',
            'Toastmasters UGI',
            'toastmasters-ugi',
            $academicCatId,
            'Where Voices Find Their Power',
            'Since June 01st 2023, UGI Toastmasters Club stands as a lively stage for students eager to turn their voices into something extraordinary. Here, under laughter and learning, students discover that public speaking is an art, a journey, and a chance to grow. Each of our meetings is a little theatre of ideas and confidence-building that transforms the shy into the bold, preparing members for interview rooms and boardrooms alike.',
            'To empower students with public speaking, effective communication, storytelling, and leadership skills through structured speeches and constructive peer feedback.',
            'Where Voices Find Their Power — Transforming hesitant speakers into poised, confident global leaders.',
            '• Speech & Storytelling Masterclasses\n• Table Topics & Impromptu Speaking Drills\n• Interview & Boardroom Presentation Preparation\n• Peer Evaluation & Communication Leadership',
            'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=1200&auto=format&fit=crop',
            2023, 'active', 1, '/contact.html', 'toastmasters@uit.edu', '+91 98765 66661', 'Seminar Hall & USC UIT Office, UIT, Prayagraj 211010', '/contact.html'
        ],
        [
            'clb_flutterflow_uit_2026',
            'FlutterFlow Student Chapter - UIT',
            'FlutterFlow UIT',
            'flutterflow-uit',
            $techCatId,
            'Build Visual Cross-Platform Apps with Flutter & Low-Code Tech',
            'FlutterFlow Student Chapter - UIT is the official student developer community at United Institute of Technology dedicated to visual mobile & web app development, UI/UX design, Firebase integration, and building cross-platform applications with FlutterFlow.',
            'To empower students to turn creative app ideas into production-ready mobile and web applications without coding friction, leveraging FlutterFlow, Flutter, and cloud backends.',
            'Design. Build. Launch. — Accelerating app innovation and empowering student app creators at UIT.',
            '• FlutterFlow & Mobile App Development Bootcamps\n• Low-Code UI/UX & Firebase Hackathons\n• App Design Sprints & Prototype Demos\n• Flutter & Dart Integration Workshops',
            'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?q=80&w=1200&auto=format&fit=crop',
            2025, 'active', 1, '/contact.html', 'flutterflow@uit.edu', '+91 98765 77771', 'UIT Induction Hall & Computer Lab 1, Prayagraj 211010', 'https://flutterflow.io'
        ],
        [
            'clb_tedx_uit_2026',
            'TEDxUIT',
            'TEDxUIT',
            'tedx-uit',
            $academicCatId,
            'Ideas Worth Spreading — Inspiring Talks, Innovation & Leadership',
            'TEDxUIT is an independently organized TED event student community at United Institute of Technology, bringing together visionary thinkers, storytellers, innovators, and leaders to share Ideas Worth Spreading across technology, science, art, and humanity.',
            'To spark deep discussion, inspire action, and showcase extraordinary ideas from students, faculty, and industry pioneers on the TEDx stage.',
            'Ideas Worth Spreading. — Elevating campus voices and fostering intellectual curiosity at UIT.',
            '• Annual TEDxUIT Flagship Conference\n• Salon Speaker Sessions & Panel Discussions\n• Student Speaker Curation & Public Speaking Coaching\n• Idea Networking & Youth Empowerment Drives',
            'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=1200&auto=format&fit=crop',
            2024, 'active', 1, '/contact.html', 'tedx@uit.edu', '+91 98765 88881', 'Main Auditorium & USC UIT Office, UIT, Prayagraj 211010', 'https://www.ted.com/tedx'
        ],
        [
            'clb_cultural_uit',
            'Cultural Club UIT',
            'Cultural Club',
            'cultural-club-uit',
            $cultCatId,
            'Feel it. Express it. Own it. — Official Cultural Umbrella Council of UIT',
            'Cultural Club UIT is the official cultural umbrella council of United Institute of Technology (UIT). It brings together music, dance, theatre, fine arts, public speaking, literary societies, and campus fests into a vibrant co-curricular performing arts community.',
            'To nurture creative talent, stage presence, artistic expression, and cultural governance across every branch and batch at UIT.',
            'To establish the most vibrant and inclusive college cultural eco-system in the region.',
            '• Annual College Fest & Cultural Nights\n• Music, Vocal & Live Band Competitions\n• Street Play & Nukkad Natak Workshops\n• Fine Arts & Live Canvas Exhibitions',
            'https://images.unsplash.com/photo-1465847899084-d164df4dedc6?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?q=80&w=1200&auto=format&fit=crop',
            2023, 'active', 1, '/contact.html', 'dsw@uit.edu', '+91 9999707942', 'USC UIT Executive Office, Ground Floor, UIT Prayagraj', '/contact.html'
        ],
        [
            'clb_developers_uit',
            'Developers Club UIT',
            'Developers Club',
            'developers-club-uit',
            $techCatId,
            'Build, Code & Innovate — Official Technical Umbrella Council of UIT',
            'Developers Club UIT is the official technical umbrella council overseeing student developer chapters, competitive coding guilds, open-source societies, and hackathons at United Institute of Technology.',
            'To foster technical excellence, software engineering skills, and problem-solving through workshops, hackathons, and industry mentorship.',
            'To lead technological innovation and produce top-tier software engineers and innovators.',
            '• Annual Hackathons & CodeSprints\n• AI/ML & Cloud Computing Bootcamps\n• Open Source Contributions & Project Expos\n• Competitive Programming Mentorship',
            'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?q=80&w=400&auto=format&fit=crop',
            'https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=1200&auto=format&fit=crop',
            2023, 'active', 1, '/contact.html', 'dsw@uit.edu', '+91 9999707942', 'USC UIT Executive Office, Ground Floor, UIT Prayagraj', '/contact.html'
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
        ['ldr_cult_ankit', 'clb_cultural_uit', 'Dr. Ankit Gupta', 'Faculty Coordinator — Cultural Club UIT', 'faculty_coordinator', '2025–2026', 'ankit.gupta@uit.edu', '+91 98765 99901', 'assets/img/committee/ankit-gupta.jpg', 1],
        ['ldr_cult_arya', 'clb_cultural_uit', 'Arya Keshari', 'Student President & Cultural Lead', 'president', '2025–2026', 'arya.keshari@student.uit.edu', '+91 98765 99902', 'assets/img/committee/arya-keshari.jpg', 2],
        ['ldr_cult_riya', 'clb_cultural_uit', 'Riya Verma', 'Vice President — Cultural Affairs', 'vice_president', '2025–2026', 'riya.verma@student.uit.edu', '+91 98765 99903', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop', 3],
        ['ldr_1', 'clb_gdgoc_uit_2026', 'Shivansh Singh', 'GDG Lead & President', 'president', '2025–2026', 'shivansh@uit.edu', '+91 98765 00001', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_2', 'clb_gdgoc_uit_2026', 'Sarthak Singh', 'GDG Lead & Past President', 'president', '2024–2025', 'sarthak@uit.edu', '+91 98765 00002', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_gfg_ansh', 'clb_gfg_sc_uit_2026', 'Ansh Kumar Gupta', 'Campus Mantri & Chapter Lead', 'president', '2025–2026', 'ansh.gfg@uit.edu', '+91 98765 11111', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_gfg_vice', 'clb_gfg_sc_uit_2026', 'Harsh Vardhan', 'Tech Lead & DSA Head', 'vice_president', '2025–2026', 'harsh.gfg@uit.edu', '+91 98765 11112', 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_hr_lead', 'clb_hackerrank_uit_2026', 'Utkarsh Srivastava', 'HackerRank Campus Lead', 'president', '2025–2026', 'utkarsh.hr@uit.edu', '+91 98765 22221', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_hr_vice', 'clb_hackerrank_uit_2026', 'Aditi Mishra', 'Competitive Coding Lead', 'vice_president', '2025–2026', 'aditi.hr@uit.edu', '+91 98765 22222', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_ecell_riya', 'clb_ecell_uit_2026', 'Riya Sharma', 'E-Cell Convener & President', 'president', '2025–2026', 'riya@uit.edu', '+91 98765 12349', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_wiki_lead', 'clb_wikiclub_uit_2026', 'Divyansh Sharma', 'WikiClub Lead & Convener', 'president', '2025–2026', 'divyansh.wiki@uit.edu', '+91 98765 33331', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_wiki_vice', 'clb_wikiclub_uit_2026', 'Priya Singh', 'Content & Tech Editor', 'vice_president', '2025–2026', 'priya.wiki@uit.edu', '+91 98765 33332', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_foss_lead', 'clb_foss_uit_2026', 'Yash Vardhan', 'FOSS Campus Lead', 'president', '2025–2026', 'yash.foss@uit.edu', '+91 98765 44441', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_foss_vice', 'clb_foss_uit_2026', 'Alok Kumar', 'Linux & Kernel Lead', 'vice_president', '2025–2026', 'alok.foss@uit.edu', '+91 98765 44442', 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_rota_ananya', 'clb_rotaract_uit_2026', 'Ananya Verma', 'Rotaract President & Social Lead', 'president', '2025–2026', 'ananya.rotaract@uit.edu', '+91 98765 55551', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_rota_saurabh', 'clb_rotaract_uit_2026', 'Saurabh Mishra', 'Community Outreach Lead', 'vice_president', '2025–2026', 'saurabh.rotaract@uit.edu', '+91 98765 55552', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_toast_neha', 'clb_toastmasters_ugi_2026', 'Neha Verma', 'Toastmasters President', 'president', '2025–2026', 'neha.toast@uit.edu', '+91 98765 66661', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_toast_tanmay', 'clb_toastmasters_ugi_2026', 'Tanmay Kapoor', 'VP Education & Speech Lead', 'vice_president', '2025–2026', 'tanmay.toast@uit.edu', '+91 98765 66662', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_ff_rishabh', 'clb_flutterflow_uit_2026', 'Rishabh Pandey', 'FlutterFlow Chapter Lead', 'president', '2025–2026', 'rishabh.ff@uit.edu', '+91 98765 77771', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_ff_kriti', 'clb_flutterflow_uit_2026', 'Kriti Saxena', 'App Design & UI/UX Lead', 'vice_president', '2025–2026', 'kriti.ff@uit.edu', '+91 98765 77772', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=400&auto=format&fit=crop', 2],
        ['ldr_tedx_siddharth', 'clb_tedx_uit_2026', 'Siddharth Tripathy', 'TEDx Licensee & Organizer', 'president', '2025–2026', 'siddharth.tedx@uit.edu', '+91 98765 88881', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=400&auto=format&fit=crop', 1],
        ['ldr_tedx_mehak', 'clb_tedx_uit_2026', 'Mehak Srivastava', 'Executive Producer & Lead Curator', 'vice_president', '2025–2026', 'mehak.tedx@uit.edu', '+91 98765 88882', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?q=80&w=400&auto=format&fit=crop', 2]
    ];

    $lStmt = $db->prepare("
        INSERT INTO leadership (id, club_id, name, role_title, category, term_year, email, phone, avatar, order_index)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($leaders as $ldr) {
        $lStmt->execute($ldr);
    }
    echo "[+] Seeded " . count($leaders) . " Official Leadership Members.\n";
    echo "[+] Seeded " . count($leaders) . " Official Leadership Members.\n";

    // 3. Seed Official Events
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
            'evt_ecell_intro_session_2026', 'clb_ecell_uit_2026',
            'E-Cell Introductory Session: Fostering Innovation & Startup Mindset',
            'ecell-introductory-session-fostering-innovation-2026',
            'https://images.unsplash.com/photo-1559136555-9303baea8ebd?q=80&w=800&auto=format&fit=crop',
            'United Institute of Technology (UIT) successfully organized an engaging Entrepreneurship Cell (E-Cell) Introductory Session, bringing together aspiring innovators to explore the institute\'s dynamic entrepreneurial ecosystem. The session highlighted the importance of cultivating a startup mindset, encouraging students to transform innovative ideas into impactful solutions through collaboration, creativity, and practical learning. Students were also introduced to exciting opportunities through IEEE and the Institution\'s Innovation Council (IIC), aimed at supporting and nurturing student-led ventures. Principal Prof. (Dr.) Sanjay Srivastava applauded Team E-Cell for its efforts and inspired students to embrace entrepreneurship as a pathway to innovation and leadership. The session was enriched by valuable insights from Dr. Abhishek Malviya, Dr. Dhananjay Sharma, Dr. Manas Pandey (IITian), Ms. Shruti Sharma, Mr. Amitabh Srivastava, and Dr. Rehan Haider. Led by Prakhar Pandey with over 60 enthusiastic student participants across departments.',
            'Main Seminar Hall & USC UIT Office, UIT Prayagraj', '2026-02-10 10:30:00', '/contact.html', 'completed'
        ],

        [
            'evt_wiki_tech_writing_2026', 'clb_wikiclub_uit_2026',
            'WikiClub Open Knowledge & Technical Writing Edit-a-thon',
            'wikiclub-open-knowledge-tech-writing-editathon',
            'https://images.unsplash.com/photo-1455390582262-044cdead277a?q=80&w=800&auto=format&fit=crop',
            'Hands-on technical documentation, Wikipedia article creation, and open-source contribution workshop at UIT.',
            'Seminar Hall 1, UIT', '2026-04-15 11:00:00', 'https://www.wikimedia.org', 'completed'
        ],
        [
            'evt_foss_linux_bootcamp_2026', 'clb_foss_uit_2026',
            'FOSS UIT Linux & Git/GitHub Masterclass Bootcamp',
            'foss-uit-linux-git-github-masterclass',
            'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=800&auto=format&fit=crop',
            'Hands-on workshop on Linux command-line, Git branching, open-source pull requests, and GSoC preparation.',
            'Computer Lab 3, UIT', '2026-03-18 14:00:00', '/contact.html', 'completed'
        ],
        [
            'evt_rotaract_project_smile_2026', 'clb_rotaract_uit_2026',
            'Project Smile: Community Outreach & Charity Drive',
            'rotaract-project-smile-community-outreach',
            'https://images.unsplash.com/photo-1593113598332-cd288d649433?q=80&w=800&auto=format&fit=crop',
            'Annual flagship social welfare drive organizing free stationery kits, health checkups, and clothes distribution.',
            'UIT Open Ground & Nearby Community School', '2026-02-14 10:00:00', '/contact.html', 'completed'
        ],
        [
            'evt_toastmasters_44th_meeting_2026', 'clb_toastmasters_ugi_2026',
            'UGI Toastmasters 44th Meeting: Where Voices Find Their Power',
            'ugi-toastmasters-44th-meeting-public-speaking',
            'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=800&auto=format&fit=crop',
            'Lively public speaking session featuring Table Topics, prepared speeches, and constructive peer evaluation.',
            'Seminar Hall & USC UIT Office, UIT', '2026-03-25 15:30:00', '/contact.html', 'completed'
        ],
        [
            'evt_flutterflow_app_bootcamp_2026', 'clb_flutterflow_uit_2026',
            'FlutterFlow Visual App Building & Firebase Bootcamp',
            'flutterflow-visual-app-building-firebase-bootcamp',
            'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?q=80&w=800&auto=format&fit=crop',
            'Hands-on workshop building production cross-platform mobile apps using FlutterFlow low-code UI and Firebase backend.',
            'UIT Induction Hall & Computer Lab 1', '2026-04-05 10:00:00', 'https://flutterflow.io', 'completed'
        ],
        [
            'evt_tedx_flagship_conference_2026', 'clb_tedx_uit_2026',
            'TEDxUIT Flagship Conference 2026: Ideas Worth Spreading',
            'tedxuit-flagship-conference-2026',
            'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=800&auto=format&fit=crop',
            'Inspiring annual conference featuring live talks by student innovators, industry pioneers, and visionary thinkers.',
            'Main Auditorium, UIT', '2026-04-18 09:30:00', 'https://www.ted.com/tedx', 'completed'
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
        ['gal_wiki_1', 'clb_wikiclub_uit_2026', 'https://images.unsplash.com/photo-1455390582262-044cdead277a?q=80&w=800&auto=format&fit=crop', 'WikiClub Tech - Open Knowledge & Edit-a-thon Session'],
        ['gal_foss_1', 'clb_foss_uit_2026', 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?q=80&w=800&auto=format&fit=crop', 'FOSS UIT - Linux Command Line & Git Open Source Bootcamp'],
        ['gal_rota_1', 'clb_rotaract_uit_2026', 'https://images.unsplash.com/photo-1593113598332-cd288d649433?q=80&w=800&auto=format&fit=crop', 'Rotaract Club of UIT - Project Smile Social Welfare Drive'],
        ['gal_toast_1', 'clb_toastmasters_ugi_2026', 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=800&auto=format&fit=crop', 'UGI Toastmasters Club - Public Speaking & Table Topics Session'],
        ['gal_ff_1', 'clb_flutterflow_uit_2026', 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?q=80&w=800&auto=format&fit=crop', 'FlutterFlow Student Chapter UIT - Mobile App UI/UX Hackathon'],
        ['gal_tedx_1', 'clb_tedx_uit_2026', 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?q=80&w=800&auto=format&fit=crop', 'TEDxUIT Flagship Stage & Inspiring Talks Session'],
        ['gal_nritya_1', 'clb_nritya', 'https://images.unsplash.com/photo-1547153760-18fc86324498?q=80&w=800&auto=format&fit=crop', 'Nritya Cultural Dance Club - Annual Fest Choreography Showcase'],
        ['gal_music_1', 'clb_harmony_music', 'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?q=80&w=800&auto=format&fit=crop', 'Harmony Music Club - Campus Band Night & Acoustic Jams'],
        ['gal_rang_1', 'clb_rangmanch_drama', 'https://images.unsplash.com/photo-1507676184212-d03ab07a01bf?q=80&w=800&auto=format&fit=crop', 'Rangmanch Drama Club - Nukkad Natak & Street Play Performance'],
        ['gal_prism_1', 'clb_prism', 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?q=80&w=800&auto=format&fit=crop', 'Prism Fine Arts - Live Painting & Photo Exhibition']
    ];

    $gStmt = $db->prepare("INSERT INTO gallery_items (id, club_id, media_url, caption) VALUES (?, ?, ?, ?)");
    foreach ($gallery as $g) {
        $gStmt->execute($g);
    }
    echo "[+] Seeded " . count($gallery) . " Gallery Items.\n";

    // 5. Seed 10 Official Club Lead Accounts
    $clubAccounts = [
        ['usr_gdgoc_admin', 'gdgoc@uit.edu', 'GdgocPass123!', 'GDGOC UIT Lead', 'clb_gdgoc_uit_2026'],
        ['usr_gfg_admin', 'gfgsc@uit.edu', 'GfgscPass123!', 'GFG SC Lead', 'clb_gfg_sc_uit_2026'],
        ['usr_hr_admin', 'hackerrank@uit.edu', 'HackerPass123!', 'HackerRank Lead', 'clb_hackerrank_uit_2026'],
        ['usr_ecell_admin', 'ecell@uit.edu', 'EcellPass123!', 'E-Cell Lead', 'clb_ecell_uit_2026'],
        ['usr_wiki_admin', 'wikiclub@uit.edu', 'WikiclubPass123!', 'WikiClub Tech Lead', 'clb_wikiclub_uit_2026'],
        ['usr_foss_admin', 'foss@uit.edu', 'FossPass123!', 'FOSS UIT Lead', 'clb_foss_uit_2026'],
        ['usr_rota_admin', 'rotaract@uit.edu', 'RotaractPass123!', 'Rotaract Lead', 'clb_rotaract_uit_2026'],
        ['usr_toast_admin', 'toastmasters@uit.edu', 'ToastPass123!', 'Toastmasters Lead', 'clb_toastmasters_ugi_2026'],
        ['usr_ff_admin', 'flutterflow@uit.edu', 'FlutterPass123!', 'FlutterFlow Lead', 'clb_flutterflow_uit_2026'],
        ['usr_tedx_admin', 'tedx@uit.edu', 'TedxPass123!', 'TEDxUIT Lead', 'clb_tedx_uit_2026']
    ];

    $uStmt = $db->prepare("INSERT INTO users (id, email, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, 'club_admin', 'active')");
    $caStmt = $db->prepare("INSERT INTO club_admins (club_id, user_id) VALUES (?, ?)");

    foreach ($clubAccounts as $acc) {
        // Check if user exists
        $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$acc[1]]);
        $existingId = $chk->fetchColumn();

        $uid = $existingId ?: $acc[0];
        $pwdHash = password_hash($acc[2], PASSWORD_DEFAULT);

        if (!$existingId) {
            $uStmt->execute([$uid, $acc[1], $pwdHash, $acc[3]]);
        } else {
            $upd = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $upd->execute([$pwdHash, $uid]);
        }

        // Link in club_admins
        $chkCa = $db->prepare("SELECT user_id FROM club_admins WHERE user_id = ? AND club_id = ?");
        $chkCa->execute([$uid, $acc[4]]);
        if (!$chkCa->fetchColumn()) {
            $caStmt->execute([$acc[4], $uid]);
        }
    }
    echo "[+] Seeded 10 Official Club Lead User Accounts.\n";

    echo "===========================================\n";
    echo "  Official 10 Clubs Database Seeded!       \n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "[-] Error seeding official database: " . $e->getMessage() . "\n";
}
