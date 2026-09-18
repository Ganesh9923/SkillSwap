<?php
/**
 * SkillSwap - Action Handler: Verify Email OTP Code
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Rate limiting (max 10 verification attempts per 10 minutes)
if (!checkRateLimit('verify_otp', 10, 600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many invalid attempts. Please wait a few minutes.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$code = trim((string)($input['code'] ?? ''));

$savedOtp = $_SESSION['verification_otp'] ?? null;
$savedEmail = $_SESSION['verification_email'] ?? null;
$expires = $_SESSION['verification_expires'] ?? 0;

if (empty($savedOtp) || time() > $expires) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Verification code expired or not requested. Please request a new code.']);
    exit;
}

if ($code !== $savedOtp) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid verification code. Please check your email and try again.']);
    exit;
}

// Mark verified
$_SESSION['email_verified'] = true;
$_SESSION['verified_email_address'] = $savedEmail;

// Clear OTP
unset($_SESSION['verification_otp'], $_SESSION['verification_expires']);

echo json_encode([
    'success' => true,
    'message' => "Email {$savedEmail} verified successfully!",
    'email'   => $savedEmail
]);
