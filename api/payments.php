<?php
/**
 * SkillSwap - REST API: Payments Endpoint
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Supports programmatic checkout, escrow holding, and refund queries.
 * GET  /api/payments.php?booking_id=1
 * POST /api/payments.php (JSON payload: booking_id, payment_method, gateway)
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
    
    if ($bookingId > 0) {
        $payment = getPaymentByBookingId($bookingId);
        echo json_encode([
            'status' => 'success',
            'data'   => $payment
        ], JSON_PRETTY_PRINT);
        exit;
    }

    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM payments ORDER BY id DESC LIMIT 50");
    $payments = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'count'  => count($payments),
        'data'   => $payments
    ], JSON_PRETTY_PRINT);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $bookingId = (int)($input['booking_id'] ?? 0);
    $paymentMethod = trim($input['payment_method'] ?? 'card');
    $gateway = trim($input['gateway'] ?? 'SkillSwap Glacial Sandbox');

    if ($bookingId <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'booking_id is required']);
        exit;
    }

    try {
        $result = processBookingPayment($bookingId, $paymentMethod, $gateway);
        http_response_code(201);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Payment processed and held in Escrow',
            'data'    => $result
        ], JSON_PRETTY_PRINT);
        exit;
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
