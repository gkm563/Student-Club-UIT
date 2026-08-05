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
        // Anti-Spam Rate Limiting (Max 5 contact messages per IP per 10 mins)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rateKey = 'contact_rate_' . md5($ip);
        if (session_status() === PHP_SESSION_NONE) session_start();
        $attempts = $_SESSION[$rateKey]['count'] ?? 0;
        $firstTime = $_SESSION[$rateKey]['time'] ?? time();

        if (time() - $firstTime > 600) {
            $attempts = 0;
            $firstTime = time();
        }

        if ($attempts >= 5) {
            http_response_code(429);
            echo json_encode([
                'status' => 'error',
                'message' => 'Rate Limit Exceeded: Too many messages sent from your IP. Please try again in 10 minutes.'
            ]);
            exit;
        }

        $_SESSION[$rateKey] = ['count' => $attempts + 1, 'time' => $firstTime];

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST;
        }

        // Verify CAPTCHA Code
        $captchaInput = $input['captcha_code'] ?? '';
        if (!verify_captcha_code($captchaInput)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Incorrect verification code (CAPTCHA). Please check the image and try again.'
            ]);
            exit;
        }

        $name    = substr(trim(strip_tags($input['name'] ?? '')), 0, 100);
        $email   = filter_var(trim($input['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $subject = substr(trim(strip_tags($input['subject'] ?? '')), 0, 150);
        $message = substr(trim(strip_tags($input['message'] ?? '')), 0, 2000);

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
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("
            INSERT INTO contact_messages (id, name, email, subject, message, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, 0, ?)
        ");
        $stmt->execute([$id, $name, $email, $subject, $message, $now]);

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
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while submitting your message. Please try again.']);
}
