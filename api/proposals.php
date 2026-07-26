<?php
/**
 * RESTful API Endpoint for Club & Event Proposal Submissions (ClubHub UIT)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $type            = trim($input['proposal_type'] ?? 'new_club');
        $applicantName   = trim($input['applicant_name'] ?? '');
        $applicantEmail  = trim($input['applicant_email'] ?? '');
        $applicantPhone  = trim($input['applicant_phone'] ?? '');
        $proposedTitle   = trim($input['proposed_title'] ?? '');
        $objective       = trim($input['objective'] ?? '');
        $facultyMentor   = trim($input['faculty_mentor'] ?? '');

        if (empty($applicantName) || empty($applicantEmail) || empty($proposedTitle) || empty($objective)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please fill in all required fields (Name, Email, Title & Objectives).'
            ]);
            exit;
        }

        $id = 'prop_' . bin2hex(random_bytes(6));
        $stmt = $db->prepare("
            INSERT INTO club_proposals (id, proposal_type, applicant_name, applicant_email, applicant_phone, proposed_title, objective, faculty_mentor, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$id, $type, $applicantName, $applicantEmail, $applicantPhone, $proposedTitle, $objective, $facultyMentor]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Proposal submitted successfully to the Dean Student Welfare & Management Committee.'
        ]);
        exit;
    }

    // GET Request - List Proposals for Admin
    $stmt = $db->query("SELECT * FROM club_proposals ORDER BY created_at DESC");
    $proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $proposals
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
