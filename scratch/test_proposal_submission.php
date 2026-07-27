<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    // Insert dummy student proposal
    $id = 'prop_test_' . bin2hex(random_bytes(3));
    $stmt = $db->prepare("
        INSERT INTO club_proposals (
            id, proposal_type, applicant_name, applicant_email, applicant_phone, proposed_title, objective, faculty_mentor,
            is_uit_student, student_id_number, student_id_photo, department_branch, academic_year, current_semester, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $stmt->execute([
        $id,
        'new_club',
        'Aarav Sharma',
        'aarav.sharma@uit.edu',
        '+91 98765 43210',
        'AI & Robotics Innovation Club',
        'To build cutting edge autonomous robotics and AI solutions for national hackathons.',
        'Dr. R. K. Mishra',
        1, // is_uit_student toggle ON
        'UIT20241088',
        'uploads/proposals/img_sample_id.jpg',
        'Computer Science & Engineering (CSE)',
        '3rd Year',
        'Semester 5'
    ]);

    echo "Sample Student Proposal Inserted Successfully! ID: $id\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
