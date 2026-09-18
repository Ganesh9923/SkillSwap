<?php
/**
 * SkillSwap - Action Handler: Dispatch Verification OTP
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$email = trim((string)($input['email'] ?? ''));
$name = trim((string)($input['name'] ?? 'Creator / Client'));

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid email address is required']);
    exit;
}

// Generate 6-digit OTP
$otp = (string)random_int(100000, 999999);
$_SESSION['verification_otp'] = $otp;
$_SESSION['verification_email'] = $email;
$_SESSION['verification_expires'] = time() + 600; // 10 minutes

// Send via Hostinger SMTP
$mailRes = sendVerificationOtpEmail($email, $name, $otp);

if ($mailRes['success']) {
    echo json_encode([
        'success' => true,
        'message' => "Verification code sent to {$email} via Hostinger SMTP.",
        'email'   => $email
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $mailRes['message']
    ]);
}
