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
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
        exit;
    }
    header('Location: ../index.php');
    exit;
}

if (!checkRateLimit('process_payment', 25, 300)) {
    http_response_code(429);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Payment rate limit exceeded. Please wait a moment.']);
    exit;
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
$gigId = (int)($_POST['gig_id'] ?? 0);
$paymentMethod = trim((string)($_POST['payment_method'] ?? 'card'));

// If payment initiated directly with gig_id, create the booking first
if ($bookingId <= 0 && $gigId > 0) {
    $activePersona = getActivePersona();
    $rawClient = trim((string)($_POST['card_name'] ?? $activePersona['name']));
    $clientName = preg_replace('/[^\p{L}\p{N}\s\.\-\'\@]/u', '', substr($rawClient, 0, 100));
    $newBooking = createBooking($gigId, $clientName, 'Instant checkout with Escrow protection');
    $bookingId = (int)$newBooking['id'];
}

if ($bookingId <= 0) {
    if ($isAjax) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Invalid booking ID']);
        exit;
    }
    die("Validation Error: Invalid booking ID");
}

try {
    $result = processBookingPayment($bookingId, $paymentMethod, 'SkillSwap Escrow Vault');

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT client_name FROM bookings WHERE id = :id");
    $stmt->execute([':id' => $bookingId]);
    $clientName = $stmt->fetchColumn() ?: 'Sarah Jenkins';

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'success',
            'message' => 'Payment secured in Escrow Vault',
            'data'    => $result
        ]);
        exit;
    }

    header('Location: ../my_bookings.php?client_name=' . urlencode((string)$clientName) . '&paid=' . $bookingId);
    exit;
} catch (Exception $e) {
    $errId = logAppError($e, 'action_process_payment');
    if ($isAjax) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'error_id' => $errId]);
        exit;
    }
    die("Payment Error: " . htmlspecialchars($e->getMessage()));
}
