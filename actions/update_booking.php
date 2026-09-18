<?php
/**
 * SkillSwap - Action Handler: Update Booking Status (Accept / Decline)
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Persists status change in MySQL with decline reason for DP1.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
          || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
        exit;
    }
    header('Location: ../creator.php');
    exit;
}

$bookingId = (int)($_POST['booking_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$declineReason = trim($_POST['decline_reason'] ?? '');

if ($bookingId <= 0 || !in_array($status, ['Accepted', 'Declined', 'Pending'], true)) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid booking ID or status.']);
        exit;
    }
    die("Validation Error: Invalid parameters.");
}

try {
    $updated = updateBookingStatus($bookingId, $status, $declineReason ?: null);

    if ($isAjax) {
        echo json_encode([
            'status'  => 'success',
            'message' => "Booking #{$bookingId} updated to {$status}",
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
} catch (Exception $e) {
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    die("Error updating booking status: " . $e->getMessage());
}
