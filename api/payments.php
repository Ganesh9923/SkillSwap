<?php
/**
 * SkillSwap - REST API: Payments Verification Endpoint
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Supports programmatic escrow verification for specific bookings.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/payment.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
    
    if ($bookingId <= 0) {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'booking_id query parameter is required to check payment status.'
        ]);
        exit;
    }

    $payment = getPaymentByBookingId($bookingId);
    if (!$payment) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Payment record not found for booking']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'data'   => [
            'booking_id'     => $payment['booking_id'],
            'amount'         => $payment['amount'],
            'currency'       => $payment['currency'],
            'gateway'        => $payment['gateway'],
            'status'         => $payment['status'],
            'transaction_id' => $payment['transaction_id']
        ]
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($method === 'POST') {
    if (!checkRateLimit('api_payments_post', 20, 300)) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'Payment rate limit exceeded']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $bookingId = (int)($input['booking_id'] ?? 0);
    $paymentMethod = trim((string)($input['payment_method'] ?? 'card'));

    if ($bookingId <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'booking_id is required']);
        exit;
    }

    try {
        $result = processBookingPayment($bookingId, $paymentMethod, 'SkillSwap Escrow Vault');
        http_response_code(201);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Payment processed and held in Escrow Vault',
            'data'    => $result
        ], JSON_PRETTY_PRINT);
        exit;
    } catch (Exception $e) {
        $errId = logAppError($e, 'api_payments');
        http_response_code(400);
        echo json_encode([
            'status'   => 'error',
            'message'  => $e->getMessage(),
            'error_id' => $errId
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
