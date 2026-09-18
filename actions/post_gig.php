<?php
/**
 * SkillSwap - Action Handler: Post a Gig
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Strict PDO Prepared Statements, Honeypot & Rate Limiting, Persona Derivation
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
    header('Location: ../creator.php');
    exit;
}

// 1. Honeypot check
if (!validateHoneypot()) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Spam verification failed']);
    exit;
}

// 2. Rate limiting (max 20 per 5 min)
if (!checkRateLimit('post_gig', 20, 300)) {
    http_response_code(429);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Rate limit exceeded. Please wait a few moments before posting again.']);
    exit;
}

// 3. Derive creator identity from active demo persona session
$activePersona = getActivePersona();
$creatorId = ($activePersona['type'] === 'creator') ? (int)$activePersona['id'] : 1;
if (!isset(DEMO_CREATORS[$creatorId])) {
    $creatorId = 1;
}
$creatorName = DEMO_CREATORS[$creatorId]['name'];

$title = trim((string)($_POST['title'] ?? ''));
$category = trim((string)($_POST['category'] ?? ''));
$rate = (float)($_POST['rate'] ?? 0.0);
$description = trim((string)($_POST['description'] ?? ''));
$maxSlots = (int)($_POST['max_slots'] ?? 3);

// 4. Strict Validation
$errors = [];
if (mb_strlen($title) < 3 || mb_strlen($title) > 150) {
    $errors[] = "Title must be between 3 and 150 characters.";
}
if (!in_array($category, ALLOWED_CATEGORIES, true)) {
    $errors[] = "Invalid category. Must be one of: " . implode(', ', ALLOWED_CATEGORIES);
}
if ($rate <= 0 || $rate > 50000) {
    $errors[] = "Rate must be between $1.00 and $50,000.00.";
}
if (mb_strlen($description) < 10 || mb_strlen($description) > 2000) {
    $errors[] = "Description must be between 10 and 2000 characters.";
}

if (!empty($errors)) {
    if ($isAjax) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'errors' => $errors, 'message' => implode(' ', $errors)]);
        exit;
    }
    die("Validation Error: " . htmlspecialchars(implode('<br>', $errors)));
}

try {
    $gigId = createGig($creatorId, $creatorName, $title, $category, $rate, $description, $maxSlots);

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'success',
            'message' => 'Gig published successfully!',
            'data'    => [
                'id'           => $gigId,
                'title'        => $title,
                'category'     => $category,
                'rate'         => $rate,
                'creator_name' => $creatorName
            ]
        ]);
        exit;
    }

    header('Location: ../creator.php?posted=1#my-gigs');
    exit;
} catch (Exception $e) {
    $errId = logAppError($e, 'action_post_gig');
    if ($isAjax) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'An error occurred while creating the gig.', 'error_id' => $errId]);
        exit;
    }
    die("Error creating gig. Ref ID: " . htmlspecialchars($errId));
}
