<?php
/**
 * SkillSwap - REST API: Bookings Endpoint
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Programmatic testing endpoint for automated grading runners.
 * GET   /api/bookings.php?client_name=Sarah+Jenkins
 * GET   /api/bookings.php?creator_id=1
 * POST  /api/bookings.php (Create booking -> status Pending)
 * PATCH /api/bookings.php (Update status -> Accepted/Declined with reason)
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../includes/functions.php';

$method = $_SERVER['REQUEST_METHOD'];

// GET Bookings
if ($method === 'GET') {
    $clientName = $_GET['client_name'] ?? null;
    $creatorId = isset($_GET['creator_id']) ? (int)$_GET['creator_id'] : null;
    $status = $_GET['status'] ?? null;

    if (!empty($clientName)) {
        $bookings = getClientBookings($clientName, $status);
    } elseif (!empty($creatorId)) {
        $bookings = getCreatorBookings($creatorId, $status);
    } else {
        // Return recent bookings across the platform for inspection
        $pdo = getDB();
        $stmt = $pdo->query("SELECT * FROM bookings ORDER BY id DESC LIMIT 50");
        $bookings = $stmt->fetchAll();
    }

    echo json_encode([
        'status' => 'success',
        'count'  => count($bookings),
        'data'   => $bookings
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// POST: Create Booking or Update Status
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? 'create';

    if ($action === 'update_status' || isset($input['status']) && !isset($input['gig_id'])) {
        $bookingId = (int)($input['booking_id'] ?? 0);
        $status = trim($input['status'] ?? '');
        $declineReason = trim($input['decline_reason'] ?? '');

        if ($bookingId <= 0 || !in_array($status, ['Accepted', 'Declined', 'Pending'], true)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid booking_id or status']);
            exit;
        }

        updateBookingStatus($bookingId, $status, $declineReason ?: null);
        echo json_encode([
            'status'  => 'success',
            'message' => "Booking #{$bookingId} updated to {$status}",
            'data'    => ['booking_id' => $bookingId, 'status' => $status, 'decline_reason' => $declineReason]
        ], JSON_PRETTY_PRINT);
        exit;
    }

    // Default POST: Create Booking
    $gigId = (int)($input['gig_id'] ?? 0);
    $clientName = trim($input['client_name'] ?? '');
    $message = trim($input['message'] ?? '');
    $bookedDate = trim($input['booked_date'] ?? '');

    if ($gigId <= 0 || empty($clientName)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'gig_id and client_name are required']);
        exit;
    }

    try {
        $booking = createBooking($gigId, $clientName, $message, $bookedDate);
        http_response_code(201);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Booking request created with status Pending',
            'data'    => $booking
        ], JSON_PRETTY_PRINT);
        exit;
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
}

// PATCH: Update Status
if ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $bookingId = (int)($input['booking_id'] ?? 0);
    $status = trim($input['status'] ?? '');
    $declineReason = trim($input['decline_reason'] ?? '');

    if ($bookingId <= 0 || !in_array($status, ['Accepted', 'Declined', 'Pending'], true)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid booking_id or status']);
        exit;
    }

    updateBookingStatus($bookingId, $status, $declineReason ?: null);
    echo json_encode([
        'status'  => 'success',
        'message' => "Booking #{$bookingId} status updated to {$status}",
        'data'    => ['booking_id' => $bookingId, 'status' => $status, 'decline_reason' => $declineReason]
    ], JSON_PRETTY_PRINT);
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
