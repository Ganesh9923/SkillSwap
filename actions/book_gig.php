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
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
        exit;
    }
    header('Location: ../index.php');
    exit;
}

// 1. Honeypot check
if (!validateHoneypot()) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Spam verification failed']);
    exit;
}

// 2. Rate limiting (max 30 per 5 min)
if (!checkRateLimit('book_gig', 30, 300)) {
    http_response_code(429);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Booking request limit reached. Please wait before submitting more inquiries.']);
    exit;
}

$gigId = (int)($_POST['gig_id'] ?? 0);
$rawClient = trim((string)($_POST['client_name'] ?? ''));
$clientName = preg_replace('/[^\p{L}\p{N}\s\.\-\'\@]/u', '', substr($rawClient, 0, 100));
$message = mb_substr(trim((string)($_POST['message'] ?? '')), 0, 1000);
$bookedDate = trim((string)($_POST['booked_date'] ?? ''));

if ($gigId <= 0 || mb_strlen($clientName) < 2) {
    if ($isAjax) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Valid Gig ID and Client Name (2+ characters) are required.']);
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
        header('Content-Type: application/json; charset=utf-8');
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
    $errId = logAppError($e, 'action_book_gig');
    if ($isAjax) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while creating booking.', 'error_id' => $errId]);
        exit;
    }
    die("Error creating booking. Ref ID: " . htmlspecialchars($errId));
}
