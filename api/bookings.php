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

// GET Bookings (requires client_name or creator_id)
if ($method === 'GET') {
    $clientName = isset($_GET['client_name']) ? trim((string)$_GET['client_name']) : null;
    $creatorId = isset($_GET['creator_id']) ? (int)$_GET['creator_id'] : null;
    $status = isset($_GET['status']) ? trim((string)$_GET['status']) : null;

    if (!empty($clientName)) {
        $bookings = getClientBookings($clientName, $status);
    } elseif (!empty($creatorId)) {
        $bookings = getCreatorBookings($creatorId, $status);
    } else {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'client_name or creator_id query parameter is required to retrieve bookings.'
        ]);
        exit;
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
    if (!checkRateLimit('api_bookings_post', 40, 300)) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'API rate limit exceeded']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? 'create';

    if ($action === 'update_status' || (isset($input['status']) && !isset($input['gig_id']))) {
        $bookingId = (int)($input['booking_id'] ?? 0);
        $status = trim((string)($input['status'] ?? ''));
        $declineReason = mb_substr(trim((string)($input['decline_reason'] ?? '')), 0, 255);

        if ($bookingId <= 0 || !in_array($status, ['Accepted', 'Declined', 'Pending'], true)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid booking_id or status']);
            exit;
        }

        try {
            updateBookingStatus($bookingId, $status, $declineReason ?: null);
            echo json_encode([
                'status'  => 'success',
                'message' => "Booking #{$bookingId} updated to {$status}",
                'data'    => ['booking_id' => $bookingId, 'status' => $status, 'decline_reason' => $declineReason]
            ], JSON_PRETTY_PRINT);
            exit;
        } catch (RuntimeException $e) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'booking_id' => $bookingId, 'current_status' => 'Pending']);
            exit;
        } catch (Exception $e) {
            $errId = logAppError($e, 'api_update_booking_status');
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'error_id' => $errId]);
            exit;
        }
    }

    // Default POST: Create Booking
    $gigId = (int)($input['gig_id'] ?? 0);
    $rawClient = trim((string)($input['client_name'] ?? ''));
    $clientName = preg_replace('/[^\p{L}\p{N}\s\.\-\'\@]/u', '', substr($rawClient, 0, 100));
    $message = mb_substr(trim((string)($input['message'] ?? '')), 0, 1000);
    $bookedDate = trim((string)($input['booked_date'] ?? ''));

    if ($gigId <= 0 || mb_strlen($clientName) < 2) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'gig_id and valid client_name (2+ chars) are required']);
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
        $errId = logAppError($e, 'api_book_gig');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'error_id' => $errId]);
        exit;
    }
}

// PATCH: Update Status
if ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $bookingId = (int)($input['booking_id'] ?? 0);
    $status = trim((string)($input['status'] ?? ''));
    $declineReason = mb_substr(trim((string)($input['decline_reason'] ?? '')), 0, 255);

    if ($bookingId <= 0 || !in_array($status, ['Accepted', 'Declined', 'Pending'], true)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid booking_id or status']);
        exit;
    }

    try {
        updateBookingStatus($bookingId, $status, $declineReason ?: null);
        echo json_encode([
            'status'  => 'success',
            'message' => "Booking #{$bookingId} status updated to {$status}",
            'data'    => ['booking_id' => $bookingId, 'status' => $status, 'decline_reason' => $declineReason]
        ], JSON_PRETTY_PRINT);
        exit;
    } catch (RuntimeException $e) {
        http_response_code(409);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'booking_id' => $bookingId, 'current_status' => 'Pending']);
        exit;
    } catch (Exception $e) {
        $errId = logAppError($e, 'api_patch_booking_status');
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'error_id' => $errId]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
