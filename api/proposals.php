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
        // Anti-Bot Rate Limiting (Max 3 proposal submissions per IP per 10 mins)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rateKey = 'proposal_rate_' . md5($ip);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $attempts = $_SESSION[$rateKey]['count'] ?? 0;
        $firstTime = $_SESSION[$rateKey]['time'] ?? time();

        if (time() - $firstTime > 600) {
            $attempts = 0;
            $firstTime = time();
        }

        if ($attempts >= 3) {
            http_response_code(429);
            echo json_encode([
                'status' => 'error',
                'message' => 'Security Rate Limit Exceeded: Too many proposal submissions from your IP. Please try again in 10 minutes.'
            ]);
            exit;
        }

        $_SESSION[$rateKey] = ['count' => $attempts + 1, 'time' => $firstTime];

        // Read either JSON body or POST form data
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST;
        }

        // Verify CAPTCHA or CSRF if passed
        if (isset($input['captcha_code']) && !verify_captcha_code($input['captcha_code'])) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Incorrect verification code (CAPTCHA). Please try again.'
            ]);
            exit;
        }

        $type            = substr(trim(strip_tags($input['proposal_type'] ?? 'new_club')), 0, 50);
        $applicantName   = substr(trim(strip_tags($input['applicant_name'] ?? '')), 0, 100);
        $applicantEmail  = filter_var(trim($input['applicant_email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $applicantPhone  = substr(trim(strip_tags($input['applicant_phone'] ?? '')), 0, 20);
        $proposedTitle   = substr(trim(strip_tags($input['proposed_title'] ?? '')), 0, 150);
        $objective       = substr(trim(strip_tags($input['objective'] ?? '')), 0, 2000);
        $facultyMentor   = substr(trim(strip_tags($input['faculty_mentor'] ?? '')), 0, 100);

        // UIT College Student Verification Fields
        $isUitStudent    = isset($input['is_uit_student']) && ($input['is_uit_student'] == '1' || $input['is_uit_student'] == 'true') ? 1 : 0;
        $studentIdNumber = substr(trim(strip_tags($input['student_id_number'] ?? '')), 0, 50);
        $departmentBranch= substr(trim(strip_tags($input['department_branch'] ?? '')), 0, 100);
        $academicYear    = substr(trim(strip_tags($input['academic_year'] ?? '')), 0, 50);
        $currentSemester = substr(trim(strip_tags($input['current_semester'] ?? '')), 0, 20);

        // Handle Upload for Student ID Photo
        $studentIdPhoto = '';
        if ($isUitStudent && isset($_FILES['student_id_photo']) && $_FILES['student_id_photo']['error'] === UPLOAD_ERR_OK) {
            $studentIdPhoto = upload_image_file($_FILES['student_id_photo'], 'proposals');
        } elseif (isset($input['student_id_photo_url'])) {
            $studentIdPhoto = substr(trim(strip_tags($input['student_id_photo_url'])), 0, 255);
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

        if (!filter_var($applicantEmail, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please enter a valid email address.'
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

        log_audit($db, '0', $applicantName, 'PROPOSAL_SUBMITTED', 'proposal', $id, "New proposal '$proposedTitle' submitted by $applicantName ($applicantEmail)");

        echo json_encode([
            'status' => 'success',
            'message' => 'Official proposal submitted successfully! The Dean of Student Welfare & Advisory Committee will review your application.'
        ]);
        exit;
    }

    // GET Request - Strictly require Super Admin or College Authority Authentication
    if (!is_logged_in() || !in_array(get_current_user_role(), ['super_admin', 'dean', 'college_authority'])) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => '403 Forbidden: Security Violation. Executive Admin or Dean Sir authentication required to view applicant proposals.'
        ]);
        exit;
    }

    $stmt = $db->query("SELECT * FROM club_proposals ORDER BY created_at DESC");
    $proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $proposals
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'System error processing request.'
    ]);
}
