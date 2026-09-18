<?php
/**
 * SkillSwap - Action Handler: Post a Gig
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Strict PDO Prepared Statements, Validation of Fixed Categories
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

$creatorId = (int)($_POST['creator_id'] ?? 1);
$creatorName = trim($_POST['creator_name'] ?? 'Elena Rostova');
$title = trim($_POST['title'] ?? '');
$category = trim($_POST['category'] ?? '');
$rate = (float)($_POST['rate'] ?? 0.0);
$description = trim($_POST['description'] ?? '');
$maxSlots = (int)($_POST['max_slots'] ?? 3);

// Validation
$errors = [];
if (empty($title)) $errors[] = "Title is required.";
if (!in_array($category, ALLOWED_CATEGORIES, true)) {
    $errors[] = "Invalid category. Must be one of: " . implode(', ', ALLOWED_CATEGORIES);
}
if ($rate <= 0) $errors[] = "Rate must be greater than zero.";
if (empty($description)) $errors[] = "Description is required.";

if (!empty($errors)) {
    if ($isAjax) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'errors' => $errors]);
        exit;
    }
    die("Validation Error: " . implode('<br>', $errors));
}

try {
    $gigId = createGig($creatorId, $creatorName, $title, $category, $rate, $description, $maxSlots);

    if ($isAjax) {
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
    if ($isAjax) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }
    die("Error creating gig: " . $e->getMessage());
}
