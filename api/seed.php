<?php
/**
 * SkillSwap - REST API: Database Seed & Reset Endpoint
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = getDB();
    $schemaFile = __DIR__ . '/../schema.sql';
    if (!file_exists($schemaFile)) {
        throw new RuntimeException("schema.sql not found");
    }

    $sql = file_get_contents($schemaFile);
    $pdo->exec($sql);

    echo json_encode([
        'status'  => 'success',
        'message' => 'SkillSwap database re-seeded successfully with initial creators, clients, and gigs.',
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Failed to seed database: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
