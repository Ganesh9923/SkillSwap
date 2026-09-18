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

// Check active persona: Creators cannot book their own gigs
$activePersona = getActivePersona();
$gig = getGigById($gigId);
if (!$gig) {
    if ($isAjax) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Gig not found.']);
        exit;
    }
    header('Location: ../index.php?error=' . urlencode("Gig not found."));
    exit;
}

if ($activePersona['type'] === 'creator' && (int)$gig['creator_id'] === (int)$activePersona['id']) {
    if ($isAjax) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'error', 
            'message' => 'You cannot book your own gig! You are the creator of this gig. Manage it from your Creator Hub.'
        ]);
        exit;
    }
    header('Location: ../creator.php?error=' . urlencode("You cannot book your own gig! Manage it from your Creator Hub."));
    exit;
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
    header('Location: ../index.php?error=' . urlencode("Error creating booking. (Ref ID: {$errId})"));
    exit;
}
