<?php
/**
 * SkillSwap - Action Handler: Update Booking Status (Accept / Decline)
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Persists status change in MySQL with decline reason for DP1.
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
    header('Location: ../creator.php');
    exit;
}

// Rate limiting
if (!checkRateLimit('update_booking', 50, 300)) {
    http_response_code(429);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Too many status updates. Please wait a moment.']);
    exit;
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
$status = trim((string)($_POST['status'] ?? ''));
$declineReason = mb_substr(trim((string)($_POST['decline_reason'] ?? '')), 0, 255);

if ($bookingId <= 0 || !in_array($status, ['Accepted', 'Declined', 'Pending'], true)) {
    if ($isAjax) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Invalid booking ID or target status.']);
        exit;
    }
    die("Validation Error: Invalid parameters.");
}

// Role Enforcement: Only Creators can update booking statuses
$activePersona = getActivePersona();
if ($activePersona['type'] !== 'creator') {
    if ($isAjax) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'error', 
            'message' => 'Unauthorized: Only Creators can accept or decline bookings. Please switch to a Creator persona.'
        ]);
        exit;
    }
    die("Access Denied: Only Creators can accept or decline bookings.");
}

try {
    $updated = updateBookingStatus($bookingId, $status, $declineReason ?: null);

    // If declined, automatically refund any Escrow payment (DP1)
    if ($status === 'Declined') {
        refundBookingPayment($bookingId, $declineReason);
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'success',
            'message' => "Booking #{$bookingId} updated to {$status}" . ($status === 'Declined' ? " (Escrow funds automatically refunded)" : ""),
            'data'    => [
                'booking_id'     => $bookingId,
                'status'         => $status,
                'decline_reason' => $declineReason
            ]
        ]);
        exit;
    }

    header('Location: ../creator.php?status_updated=1');
    exit;
} catch (RuntimeException $e) {
    // DP2 Capacity Reached or business rule error
    if ($isAjax) {
        http_response_code(409);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    die("Capacity Limit: " . htmlspecialchars($e->getMessage()));
} catch (InvalidArgumentException $e) {
    if ($isAjax) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    die("Validation Error: " . htmlspecialchars($e->getMessage()));
} catch (Exception $e) {
    $errId = logAppError($e, 'action_update_booking');
    if ($isAjax) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Error updating booking status.', 'error_id' => $errId]);
        exit;
    }
    die("Error updating booking status. Ref ID: " . htmlspecialchars($errId));
}
