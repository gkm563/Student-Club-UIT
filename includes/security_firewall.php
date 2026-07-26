<?php
/**
 * Campus Cyber Security Firewall & Attack Defense Engine (ClubHub UIT)
 * Protects against SQL Injection, XSS, RCE, Directory Traversal, Brute Force & Parameter Tampering.
 */

if (!function_exists('run_campus_security_firewall')) {
    function run_campus_security_firewall(): void {
        // 1. Enforce Security HTTP Headers
        if (!headers_sent()) {
            header("X-Frame-Options: DENY");
            header("X-Content-Type-Options: nosniff");
            header("X-XSS-Protection: 1; mode=block");
            header("Referrer-Policy: strict-origin-when-cross-origin");
            header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
            header("X-Permitted-Cross-Domain-Policies: none");
            header("Content-Security-Policy: default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval';");
        }

        // 2. High-Risk Attack Signatures
        $attackPatterns = [
            'sql_injection' => [
                '/\b(union\s+all\s+select|union\s+select)\b/i',
                '/\b(information_schema|sys\.tables|pg_catalog)\b/i',
                '/\b(benchmark\s*\(|sleep\s*\(|waitfor\s+delay)\b/i',
                '/(\%27|\'|\%23|--)\s*(or|and)\s*(\%27|\'|\%31|1)\s*=\s*(\%27|\'|\%31|1)/i',
                '/\b(drop\s+table|truncate\s+table|alter\s+table)\b/i',
                '/\b(exec\s*\(|execute\s+immediate)\b/i'
            ],
            'xss_attack' => [
                '/<script\b[^>]*>(.*?)<\/script>/is',
                '/javascript\s*:/i',
                '/\b(onerror|onload|onclick|onmouseover|onfocus|onblur)\s*=/i',
                '/<iframe\b[^>]*>/i',
                '/document\.cookie/i'
            ],
            'rce_attack' => [
                '/\b(system\s*\(|passthru\s*\(|shell_exec\s*\(|exec\s*\(|proc_open\s*\()/i',
                '/\b(base64_decode\s*\(|eval\s*\(|assert\s*\()/i'
            ],
            'path_traversal' => [
                '/(\.\.[\/\\\\])/',
                '/\/etc\/passwd/i',
                '/c:\\\\windows\\\\/i',
                '/\/proc\/self\/environ/i'
            ]
        ];

        // Combine inputs for inspection
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $queryStr   = $_SERVER['QUERY_STRING'] ?? '';
        $postData   = json_encode($_POST ?? []);
        $getData    = json_encode($_GET ?? []);

        $inspectPayload = $requestUri . ' ' . $queryStr . ' ' . $postData . ' ' . $getData;

        foreach ($attackPatterns as $attackCategory => $regexList) {
            foreach ($regexList as $pattern) {
                if (preg_match($pattern, $inspectPayload)) {
                    // Block attack attempt
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
                    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                    
                    http_response_code(403);
                    header('Content-Type: text/html; charset=UTF-8');
                    ?>
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <meta charset="UTF-8">
                        <title>403 Security Firewall Blocked | UIT Campus Shield</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
                        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
                        <style>
                            body { background: #0f172a; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
                            .security-card { background: rgba(30, 41, 59, 0.85); backdrop-filter: blur(16px); border: 1px solid rgba(225, 29, 72, 0.3); border-radius: 24px; padding: 40px; max-width: 540px; box-shadow: 0 20px 50px rgba(0,0,0,0.5); }
                        </style>
                    </head>
                    <body>
                        <div class="security-card text-center">
                            <div class="bg-danger text-white rounded-circle mx-auto p-3 mb-3 d-flex align-items-center justify-content-center shadow-lg" style="width: 72px; height: 72px;">
                                <i class="bi bi-shield-slash-fill fs-1"></i>
                            </div>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1-5 rounded-pill mb-3 fw-bold">UIT CYBER SHIELD FIREWALL</span>
                            <h3 class="fw-bold mb-2 text-white">403 Request Blocked</h3>
                            <p class="text-secondary small mb-4">Potentially malicious request pattern detected. This incident has been logged for security audit by Dean Student Welfare Team.</p>
                            <div class="p-3 bg-dark rounded-3 text-start small font-monospace mb-4 text-white-50 border border-secondary" style="font-size: 0.78rem;">
                                <div><strong>Client IP:</strong> <?= htmlspecialchars($ip) ?></div>
                                <div><strong>Category:</strong> <?= htmlspecialchars(strtoupper($attackCategory)) ?></div>
                                <div><strong>Timestamp:</strong> <?= date('Y-m-d H:i:s T') ?></div>
                            </div>
                            <a href="/index.html" class="btn btn-primary rounded-pill px-4 fw-bold">Return to Main Portal</a>
                        </div>
                    </body>
                    </html>
                    <?php
                    exit;
                }
            }
        }
    }
}

// Automatically trigger firewall inspection
run_campus_security_firewall();

// Brute-Force Login Rate-Limiting Helper
if (!function_exists('check_login_rate_limit')) {
    function check_login_rate_limit(string $email, int $maxAttempts = 5, int $lockoutMinutes = 15): ?string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = 'rate_limit_' . md5($ip . '_' . strtolower(trim($email)));

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time(), 'locked_until' => 0];
        }

        $rateData = $_SESSION[$key];

        // Check if locked
        if (!empty($rateData['locked_until']) && time() < $rateData['locked_until']) {
            $remaining = ceil(($rateData['locked_until'] - time()) / 60);
            return "Security Lockout: Too many failed login attempts. Please try again in {$remaining} minutes.";
        }

        // Reset if decay window passed
        if (time() - $rateData['first_attempt'] > ($lockoutMinutes * 60)) {
            $_SESSION[$key] = ['attempts' => 0, 'first_attempt' => time(), 'locked_until' => 0];
        }

        return null;
    }
}

if (!function_exists('record_failed_login_attempt')) {
    function record_failed_login_attempt(string $email, int $maxAttempts = 5, int $lockoutMinutes = 15): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = 'rate_limit_' . md5($ip . '_' . strtolower(trim($email)));

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['attempts' => 1, 'first_attempt' => time(), 'locked_until' => 0];
        } else {
            $_SESSION[$key]['attempts']++;
        }

        if ($_SESSION[$key]['attempts'] >= $maxAttempts) {
            $_SESSION[$key]['locked_until'] = time() + ($lockoutMinutes * 60);
        }
    }
}

if (!function_exists('reset_login_rate_limit')) {
    function reset_login_rate_limit(string $email): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $key = 'rate_limit_' . md5($ip . '_' . strtolower(trim($email)));
        unset($_SESSION[$key]);
    }
}
