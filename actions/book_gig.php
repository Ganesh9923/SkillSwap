<?php
/**
 * SkillSwap - Action Handler: Book a Gig
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Creates a booking with status 'Pending' using PDO prepared statements.
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
    header('Location: ../index.php');
    exit;
}

$gigId = (int)($_POST['gig_id'] ?? 0);
$clientName = trim($_POST['client_name'] ?? '');
$message = trim($_POST['message'] ?? '');
$bookedDate = trim($_POST['booked_date'] ?? '');

if ($gigId <= 0 || empty($clientName)) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Gig ID and Client Name are required.']);
        exit;
    }
    die("Validation Error: Missing Gig ID or Client Name");
}

try {
    $booking = createBooking($gigId, $clientName, $message, $bookedDate);

    // Save client name in session for seamless tracking
    $_SESSION['active_role'] = 'client';
    $_SESSION['client_name'] = $clientName;

    if ($isAjax) {
        echo json_encode([
            'status'  => 'success',
            'message' => 'Booking created with status Pending!',
            'data'    => $booking
        ]);
        exit;
    }

    header('Location: ../my_bookings.php?client_name=' . urlencode($clientName) . '&booked=1');
    exit;
} catch (Exception $e) {
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    die("Error creating booking: " . $e->getMessage());
}
