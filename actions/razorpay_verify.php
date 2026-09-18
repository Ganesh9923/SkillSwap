<?php
/**
 * SkillSwap - Action Handler: Verify Razorpay Payment Signature
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Verifies Razorpay live signature, updates Escrow state, and dispatches confirmation email.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payment.php';
require_once __DIR__ . '/../includes/razorpay.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$orderId = trim((string)($input['razorpay_order_id'] ?? ''));
$paymentId = trim((string)($input['razorpay_payment_id'] ?? ''));
$signature = trim((string)($input['razorpay_signature'] ?? ''));
$bookingId = (int)($input['booking_id'] ?? 0);
$gigId = (int)($input['gig_id'] ?? 0);
$clientEmail = trim((string)($input['client_email'] ?? 'support@dalavix.com'));

if (empty($orderId) || empty($paymentId) || empty($signature)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing Razorpay signature verification parameters']);
    exit;
}

$isValid = verifyRazorpaySignature($orderId, $paymentId, $signature);

if (!$isValid) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Razorpay payment signature']);
    exit;
}

$pdo = getDB();

// If booking doesn't exist yet, create one
if ($bookingId <= 0 && $gigId > 0) {
    $gig = getGigById($gigId);
    if ($gig) {
        $activePersona = getActivePersona();
        $newBooking = createBooking($gigId, $activePersona['name'], 'Direct checkout via Razorpay Live');
        $bookingId = (int)$newBooking['id'];
    }
}

// Fetch booking
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
$stmt->execute([':id' => $bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit;
}

$amount = (float)$booking['rate'];

// Insert into payments table
$paySql = "INSERT INTO payments (booking_id, gig_id, client_name, creator_id, creator_name, amount, currency, gateway, transaction_id, payment_method, status, created_at)
           VALUES (:booking_id, :gig_id, :client_name, :creator_id, :creator_name, :amount, 'INR', 'Razorpay Live', :txn_id, 'UPI / Card / Netbanking (Razorpay)', 'Held_In_Escrow', NOW())";

$payStmt = $pdo->prepare($paySql);
$payStmt->execute([
    ':booking_id'   => $booking['id'],
    ':gig_id'       => $booking['gig_id'],
    ':client_name'  => $booking['client_name'],
    ':creator_id'   => $booking['creator_id'],
    ':creator_name' => $booking['creator_name'],
    ':amount'       => $amount,
    ':txn_id'       => $paymentId
]);

$paymentIdDb = (int)$pdo->lastInsertId();

// Update booking status
$updStmt = $pdo->prepare("UPDATE bookings SET payment_status = 'Held_In_Escrow', updated_at = NOW() WHERE id = :id");
$updStmt->execute([':id' => $booking['id']]);

// Send automated confirmation email via Hostinger SMTP
try {
    $paymentRecord = [
        'amount'         => $amount,
        'transaction_id' => $paymentId,
        'gateway'        => 'Razorpay Live Gateway'
    ];
    sendPaymentConfirmationEmail($clientEmail, $booking['client_name'], $booking, $paymentRecord);
} catch (Exception $e) {
    // Silently continue if mail delivery has network hiccups
}

echo json_encode([
    'success'        => true,
    'message'        => 'Payment verified successfully and funds locked in Escrow Vault',
    'booking_id'     => $booking['id'],
    'transaction_id' => $paymentId,
    'client_name'    => $booking['client_name']
]);
