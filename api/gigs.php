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
    $category = $_GET['category'] ?? null;
    $search = $_GET['q'] ?? $_GET['search'] ?? null;
    $sort = $_GET['sort'] ?? 'fair';

    $gigs = getGigs($category, $search, $sort);
    echo json_encode([
        'status' => 'success',
        'count'  => count($gigs),
        'data'   => $gigs
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $creatorId = (int)($input['creator_id'] ?? 1);
    $creatorName = trim($input['creator_name'] ?? 'Elena Rostova');
    $title = trim($input['title'] ?? '');
    $category = trim($input['category'] ?? '');
    $rate = (float)($input['rate'] ?? 0.0);
    $description = trim($input['description'] ?? '');
    $maxSlots = (int)($input['max_slots'] ?? 3);

    if (empty($title) || empty($category) || $rate <= 0 || empty($description)) {
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Missing required fields: title, category, rate, description'
        ]);
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
        http_response_code(400);
        echo json_encode([
            'status'  => 'error',
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
