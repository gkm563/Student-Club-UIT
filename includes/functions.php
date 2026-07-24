<?php
/**
 * Global Helper Functions for CCMS V1.0
 */

if (!function_exists('generate_uuid')) {
    function generate_uuid(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

if (!function_exists('e')) {
    function e(?string $str): string {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        return empty($text) ? 'n-a' : $text;
    }
}

if (!function_exists('log_audit')) {
    function log_audit(PDO $db, ?string $userId, ?string $userName, string $action, ?string $targetType = null, ?string $targetId = null, ?string $details = null): void {
        try {
            $stmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, target_type, target_id, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $stmt->execute([generate_uuid(), $userId, $userName, $action, $targetType, $targetId, $details, $ip]);
        } catch (Exception $ex) {
            // Fail safe on audit logging error
        }
    }
}

if (!function_exists('time_ago')) {
    function time_ago(string $datetime): string {
        $timestamp = strtotime($datetime);
        $difference = time() - $timestamp;
        if ($difference < 60) return 'Just now';
        $periods = ['min' => 60, 'hr' => 3600, 'day' => 86400, 'month' => 2592000, 'year' => 31536000];
        if ($difference < 3600) return floor($difference / 60) . ' mins ago';
        if ($difference < 86400) return floor($difference / 3600) . ' hrs ago';
        if ($difference < 2592000) return floor($difference / 86400) . ' days ago';
        return date('M j, Y', $timestamp);
    }
}

if (!function_exists('get_status_badge')) {
    function get_status_badge(string $status): string {
        switch (strtolower($status)) {
            case 'active':
                return '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Active</span>';
            case 'recruiting':
                return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1 rounded-pill"><i class="bi bi-person-plus-fill me-1"></i>Recruiting</span>';
            case 'inactive':
                return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1 rounded-pill"><i class="bi bi-dash-circle-fill me-1"></i>Inactive</span>';
            case 'suspended':
                return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i>Suspended</span>';
            case 'upcoming':
                return '<span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1 rounded-pill"><i class="bi bi-calendar-event me-1"></i>Upcoming</span>';
            case 'ongoing':
                return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill"><i class="bi bi-lightning-fill me-1"></i>Ongoing</span>';
            case 'completed':
                return '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill"><i class="bi bi-check2-all me-1"></i>Completed</span>';
            default:
                return '<span class="badge bg-light text-dark border px-3 py-1 rounded-pill">' . e(ucfirst($status)) . '</span>';
        }
    }
}
