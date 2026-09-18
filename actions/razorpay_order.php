<?php
/**
 * SkillSwap - Action Handler: Create Razorpay Order
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/razorpay.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$bookingId = (int)($input['booking_id'] ?? 0);
$gigId = (int)($input['gig_id'] ?? 0);

$pdo = getDB();
$amount = 0.0;
$title = "SkillSwap Service";

if ($bookingId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
    $stmt->execute([':id' => $bookingId]);
    $booking = $stmt->fetch();
    if (!$booking) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    $amount = (float)$booking['rate'];
    $title = $booking['gig_title'];
} elseif ($gigId > 0) {
    $gig = getGigById($gigId);
    if (!$gig) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Gig not found']);
        exit;
    }
    $amount = (float)$gig['rate'];
    $title = $gig['title'];
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing booking_id or gig_id']);
    exit;
}

$receipt = 'rcpt_' . ($bookingId > 0 ? "b{$bookingId}_" : "g{$gigId}_") . time();
$order = createRazorpayOrder($amount, $receipt, [
    'booking_id' => (string)$bookingId,
    'gig_id'     => (string)$gigId,
    'title'      => substr($title, 0, 30)
]);

if ($order['success']) {
    echo json_encode($order);
} else {
    http_response_code(400);
    echo json_encode($order);
}
