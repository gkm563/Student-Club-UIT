<?php
/**
 * RESTful API Endpoint for Club & Event Proposal Submissions (ClubHub UIT)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Read either JSON body or POST form data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST;
        }

        $type            = trim($input['proposal_type'] ?? 'new_club');
        $applicantName   = trim($input['applicant_name'] ?? '');
        $applicantEmail  = trim($input['applicant_email'] ?? '');
        $applicantPhone  = trim($input['applicant_phone'] ?? '');
        $proposedTitle   = trim($input['proposed_title'] ?? '');
        $objective       = trim($input['objective'] ?? '');
        $facultyMentor   = trim($input['faculty_mentor'] ?? '');

        // UIT College Student Verification Fields
        $isUitStudent    = isset($input['is_uit_student']) && ($input['is_uit_student'] == '1' || $input['is_uit_student'] == 'true') ? 1 : 0;
        $studentIdNumber = trim($input['student_id_number'] ?? '');
        $departmentBranch= trim($input['department_branch'] ?? '');
        $academicYear    = trim($input['academic_year'] ?? '');
        $currentSemester = trim($input['current_semester'] ?? '');

        // Handle Upload for Student ID Photo
        $studentIdPhoto = '';
        if ($isUitStudent && isset($_FILES['student_id_photo']) && $_FILES['student_id_photo']['error'] === UPLOAD_ERR_OK) {
            $studentIdPhoto = upload_image_file($_FILES['student_id_photo'], 'proposals');
        } elseif (isset($input['student_id_photo_url'])) {
            $studentIdPhoto = trim($input['student_id_photo_url']);
        }

        // Handle Upload for Proposal PDF Document
        $proposalPdf = '';
        if (isset($_FILES['proposal_pdf']) && $_FILES['proposal_pdf']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['proposal_pdf'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx', 'jpg', 'png', 'webp'];
            if (in_array($ext, $allowed)) {
                $uploadDir = __DIR__ . '/../uploads/proposals/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = 'doc_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $target = $uploadDir . $filename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $proposalPdf = 'uploads/proposals/' . $filename;
                }
            }
        }

        // Basic validation
        if (empty($applicantName) || empty($applicantEmail) || empty($proposedTitle) || empty($objective)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please fill in all required fields (Name, Email, Title & Objectives).'
            ]);
            exit;
        }

        // Student validation if toggle is ON
        if ($isUitStudent) {
            if (empty($studentIdNumber) || empty($departmentBranch) || empty($academicYear) || empty($currentSemester)) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'As a registered UIT student, College ID Number, Department, Academic Year, and Semester are mandatory.'
                ]);
                exit;
            }
        }

        $id = 'prop_' . bin2hex(random_bytes(6));
        $stmt = $db->prepare("
            INSERT INTO club_proposals (
                id, proposal_type, applicant_name, applicant_email, applicant_phone, proposed_title, objective, faculty_mentor,
                is_uit_student, student_id_number, student_id_photo, department_branch, academic_year, current_semester, proposal_pdf, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $id, $type, $applicantName, $applicantEmail, $applicantPhone, $proposedTitle, $objective, $facultyMentor,
            $isUitStudent, $studentIdNumber, $studentIdPhoto, $departmentBranch, $academicYear, $currentSemester, $proposalPdf
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Official proposal submitted successfully! The Dean of Student Welfare & Advisory Committee will review your application.'
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
