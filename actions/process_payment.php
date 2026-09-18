<?php
/**
 * SkillSwap - Action Handler: Process Payment & Escrow Lock
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Strict PDO Prepared Statements, Escrow Vault Guarantee
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payment.php';

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
          || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
        exit;
    }
    header('Location: ../index.php');
    exit;
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
$gigId = (int)($_POST['gig_id'] ?? 0);
$paymentMethod = trim($_POST['payment_method'] ?? 'card');

// If payment initiated directly with gig_id, create the booking first
if ($bookingId <= 0 && $gigId > 0) {
    $activePersona = getActivePersona();
    $clientName = trim($_POST['card_name'] ?? $activePersona['name']);
    $newBooking = createBooking($gigId, $clientName, 'Instant checkout with Escrow protection');
    $bookingId = (int)$newBooking['id'];
}

if ($bookingId <= 0) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid booking ID']);
        exit;
    }
    die("Validation Error: Invalid booking ID");
}

try {
    $result = processBookingPayment($bookingId, $paymentMethod);

    if ($isAjax) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Payment secured in Escrow Vault',
            'data'    => $result
        ]);
        exit;
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT client_name FROM bookings WHERE id = :id");
    $stmt->execute([':id' => $bookingId]);
    $clientName = $stmt->fetchColumn() ?: 'Sarah Jenkins';

    header('Location: ../my_bookings.php?client_name=' . urlencode((string)$clientName) . '&paid=' . $bookingId);
    exit;
} catch (Exception $e) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    die("Payment Error: " . $e->getMessage());
}
