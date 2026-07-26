<?php
/**
 * Dynamic CAPTCHA Image Renderer (ClubHub UIT)
 * Renders an SVG or PNG verification badge with noise lines for secure login forms.
 */
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: image/svg+xml');
header('Cache-Control: no-cache, no-store, must-revalidate');

$action = $_GET['action'] ?? '';
if ($action === 'refresh') {
    $code = generate_captcha_code();
} else {
    $code = get_captcha_code();
}

$bgGradients = [
    ['#1e3a8a', '#3b82f6'],
    ['#0f172a', '#1e293b'],
    ['#312e81', '#4338ca']
];
$grad = $bgGradients[array_rand($bgGradients)];

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<svg xmlns="http://www.w3.org/2000/svg" width="160" height="50" viewBox="0 0 160 50">
    <defs>
        <linearGradient id="captchaGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="<?= $grad[0] ?>" />
            <stop offset="100%" stop-color="<?= $grad[1] ?>" />
        </linearGradient>
    </defs>
    
    <!-- Background Card -->
    <rect width="160" height="50" rx="10" fill="url(#captchaGrad)" />
    
    <!-- Security Noise Lines -->
    <line x1="10" y1="35" x2="150" y2="15" stroke="rgba(255,255,255,0.25)" stroke-width="2" />
    <line x1="20" y1="10" x2="140" y2="40" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" />
    <circle cx="30" cy="15" r="3" fill="rgba(255,255,255,0.15)" />
    <circle cx="130" cy="35" r="4" fill="rgba(255,255,255,0.15)" />
    
    <!-- CAPTCHA Verification Code Text -->
    <text x="50%" y="58%" font-family="Arial, sans-serif" font-weight="900" font-size="24" fill="#ffffff" letter-spacing="6" text-anchor="middle" dominant-baseline="middle" style="text-shadow: 0 2px 8px rgba(0,0,0,0.5);">
        <?= htmlspecialchars($code) ?>
    </text>
</svg>
