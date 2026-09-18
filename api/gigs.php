<?php
/**
 * SkillSwap - REST API: Gigs Endpoint
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Supports programmatic testing & automated grading scripts.
 * GET  /api/gigs.php?category=Design&q=search&sort=fair
 * POST /api/gigs.php (JSON payload: creator_id, creator_name, title, category, rate, description, max_slots)
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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $category = isset($_GET['category']) ? trim((string)$_GET['category']) : null;
    $search = isset($_GET['q']) ? trim((string)$_GET['q']) : (isset($_GET['search']) ? trim((string)$_GET['search']) : null);
    $sort = isset($_GET['sort']) ? trim((string)$_GET['sort']) : 'fair';

    $gigs = getGigs($category, $search, $sort);
    echo json_encode([
        'status' => 'success',
        'count'  => count($gigs),
        'data'   => $gigs
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method === 'POST') {
    if (!checkRateLimit('api_gigs_post', 30, 300)) {
        http_response_code(429);
        echo json_encode(['status' => 'error', 'message' => 'API rate limit exceeded']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $creatorId = (int)($input['creator_id'] ?? 1);
    if (!isset(DEMO_CREATORS[$creatorId])) {
        $creatorId = 1;
    }
    $creatorName = DEMO_CREATORS[$creatorId]['name'];

    $title = trim((string)($input['title'] ?? ''));
    $category = trim((string)($input['category'] ?? ''));
    $rate = (float)($input['rate'] ?? 0.0);
    $description = trim((string)($input['description'] ?? ''));
    $maxSlots = (int)($input['max_slots'] ?? 3);

    // Validation
    if (mb_strlen($title) < 3 || mb_strlen($title) > 150) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Title must be between 3 and 150 characters.']);
        exit;
    }
    if (!in_array($category, ALLOWED_CATEGORIES, true)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid category. Must be one of: ' . implode(', ', ALLOWED_CATEGORIES)]);
        exit;
    }
    if ($rate <= 0 || $rate > 50000) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Rate must be between $1.00 and $50,000.00.']);
        exit;
    }
    if (mb_strlen($description) < 10 || mb_strlen($description) > 2000) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Description must be between 10 and 2000 characters.']);
        exit;
    }

    try {
        $gigId = createGig($creatorId, $creatorName, $title, $category, $rate, $description, $maxSlots);
        $newGig = getGigById($gigId);

        http_response_code(201);
        echo json_encode([
            'status'  => 'success',
            'message' => 'Gig created successfully',
            'data'    => $newGig
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    } catch (Exception $e) {
        $errId = logAppError($e, 'api_post_gig');
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $e->getMessage(),
            'error_id' => $errId
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
