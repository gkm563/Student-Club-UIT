<?php
require_once 'c:/xampp/htdocs/UIT/config/database.php';
$db = Database::getConnection();

echo "=== 🔄 SEEDING DEAN GOVERNANCE REAL DATA ===\n";

// 1. Seed contact_messages
$db->exec("DELETE FROM contact_messages");
$msgStmt = $db->prepare("
    INSERT INTO contact_messages (id, name, email, subject, message, created_at, is_read)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$sampleMsgs = [
    [
        'msg_101',
        'Aarav Sharma (B.Tech CSE 3rd Year)',
        'aarav.cse23@uit.edu',
        'Inquiry: GDG Android Compose Study Jam Certificate Issue',
        'Respected Dean Sir, I attended the GDG Android Compose Study Jam on 22nd Jan. My certificate hasn\'t been issued yet. Kindly verify with SAC.',
        date('Y-m-d H:i:s', strtotime('-2 hours')),
        0
    ],
    [
        'msg_102',
        'Ananya Verma (E-Cell Student Coordinator)',
        'ananya.ecell@uit.edu',
        'Proposal: E-Cell Startup Pitch Fest Seed Funding Approval',
        'Dear Sir, We have shortlisted 5 student startups from the introductory session. Requesting SAC allocation for 15k prototype grants.',
        date('Y-m-d H:i:s', strtotime('-1 day')),
        0
    ],
    [
        'msg_103',
        'Dr. Abhishek Malviya (Faculty Coordinator)',
        'abhishek.malviya@uit.edu',
        'Faculty Approval: Toastmasters Speech Contest Venue Allocation',
        'Respected Sanjay Sir, Toastmasters UIT needs Seminar Hall 2 on Friday 3:00 PM for the Inter-College Speech Contest. Kindly confirm allocation.',
        date('Y-m-d H:i:s', strtotime('-2 days')),
        1
    ],
    [
        'msg_104',
        'Rohan Gupta (Rotaract President)',
        'rotaract@uit.edu',
        'Report: Annual Blood Donation Drive Summary',
        'Sir, Rotaract Club collected 140+ units in partnership with Prayagraj Blood Bank. Full audit report submitted to SAC office.',
        date('Y-m-d H:i:s', strtotime('-3 days')),
        1
    ]
];

foreach ($sampleMsgs as $m) {
    $msgStmt->execute($m);
}
echo "✅ Contact Messages Seeded: " . count($sampleMsgs) . "\n";

// 2. Seed audit_logs
$db->exec("DELETE FROM audit_logs");
$logStmt = $db->prepare("
    INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at)
    VALUES (?, ?, ?, ?, ?, ?)
");

$sampleLogs = [
    [
        'log_201',
        'usr_admin_uit_2026',
        'Prof. Sanjay Srivastava (Dean)',
        'SUPER_ADMIN_LOGIN',
        'Dean Sir authenticated successfully into Super Admin Directorate Portal',
        date('Y-m-d H:i:s', strtotime('-10 mins'))
    ],
    [
        'log_202',
        'usr_ecell_2026',
        'Team E-Cell Lead',
        'EVENT_CREATED',
        'Published official event: E-Cell Introductory Session: Fostering Innovation & Startup Mindset',
        date('Y-m-d H:i:s', strtotime('-3 hours'))
    ],
    [
        'log_203',
        'usr_admin_uit_2026',
        'Prof. Sanjay Srivastava (Dean)',
        'CLUB_CREDENTIALS_ISSUED',
        'Issued chapter leadership credentials for Entrepreneurship Cell (E-Cell UIT)',
        date('Y-m-d H:i:s', strtotime('-1 day'))
    ],
    [
        'log_204',
        'usr_gfg_2026',
        'GFG Student Chapter Admin',
        'CLUB_ADMIN_LOGIN',
        'GeeksforGeeks Student Chapter Admin authenticated into Club Lead Portal',
        date('Y-m-d H:i:s', strtotime('-2 days'))
    ],
    [
        'log_205',
        'usr_admin_uit_2026',
        'Prof. Sanjay Srivastava (Dean)',
        'GOVERNANCE_AUDIT',
        'Verified SAC Accreditation badges for 10 Active Campus Clubs',
        date('Y-m-d H:i:s', strtotime('-3 days'))
    ]
];

foreach ($sampleLogs as $l) {
    $logStmt->execute($l);
}
echo "✅ Audit Logs Seeded: " . count($sampleLogs) . "\n";

// 3. Seed club_proposals
$db->exec("DELETE FROM club_proposals");
$propStmt = $db->prepare("
    INSERT INTO club_proposals (id, proposal_type, applicant_name, applicant_email, applicant_phone, proposed_title, objective, faculty_mentor, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$sampleProps = [
    [
        'prop_301',
        'new_event',
        'GDG Lead Team',
        'gdgoc@uit.edu',
        '+91 98765 11111',
        'UIT National Hackathon 2026: Code & Innovate',
        '36-hour inter-college hackathon focusing on AI/ML, Mobile Dev, and Sustainable Web Apps with INR 50,000 cash prizes.',
        'Dr. Abhishek Malviya',
        'pending',
        date('Y-m-d H:i:s', strtotime('-4 hours'))
    ],
    [
        'prop_302',
        'new_club',
        'Dr. Dhananjay Sharma',
        'dhananjay.sharma@uit.edu',
        '+91 98765 22222',
        'Robotics & Automation Society (RAS-UIT)',
        'Establishing a specialized robotics lab chapter for IoT, Drone design, and Autonomous Rover competitions.',
        'Dr. Dhananjay Sharma',
        'under_review',
        date('Y-m-d H:i:s', strtotime('-1 day'))
    ],
    [
        'prop_303',
        'new_event',
        'E-Cell Secretariat',
        'ecell@uit.edu',
        '+91 98765 33333',
        'Campus Startup Pitch Fest & Angel Funding Summit',
        'Student pitch sessions before alumni founders and venture mentors to incubate top 3 UIT student ideas.',
        'Prof. Sanjay Srivastava',
        'pending',
        date('Y-m-d H:i:s', strtotime('-2 days'))
    ],
    [
        'prop_304',
        'new_club',
        'Ms. Shruti Sharma',
        'shruti.sharma@uit.edu',
        '+91 98765 44444',
        'CyberSecurity & Ethical Hacking Guild',
        'Fostering Capture-The-Flag (CTF) cybersecurity skills, network security, and bug bounty workshops.',
        'Ms. Shruti Sharma',
        'approved',
        date('Y-m-d H:i:s', strtotime('-5 days'))
    ]
];

foreach ($sampleProps as $p) {
    $propStmt->execute($p);
}
echo "✅ Club Proposals Seeded: " . count($sampleProps) . "\n";
