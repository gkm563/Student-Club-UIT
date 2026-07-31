<?php
/**
 * RESTful API Endpoint for Secretariat Contact Messages (ClubHub UIT)
 * Receives Contact Secretariat messages and inserts into contact_messages DB table.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::getConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST;
        }

        $name    = trim($input['name'] ?? '');
        $email   = trim($input['email'] ?? '');
        $subject = trim($input['subject'] ?? '');
        $message = trim($input['message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'All fields (Full Name, Email Address, Subject, and Message) are required.'
            ]);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Please enter a valid email address.'
            ]);
            exit;
        }

        $id = 'msg_' . bin2hex(random_bytes(6));
        $stmt = $db->prepare("
            INSERT INTO contact_messages (id, name, email, subject, message, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$id, $name, $email, $subject, $message]);

        // Log audit
        log_audit($db, '0', $name, 'CONTACT_MESSAGE_RECEIVED', 'message', $id, "Received public helpdesk inquiry from $name ($email): '$subject'");

        echo json_encode([
            'status' => 'success',
            'message' => 'Your message has been sent successfully to the USC UIT team! Our executive office will get back to you shortly.'
        ]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
