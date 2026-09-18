<?php
require_once __DIR__ . '/includes/security.php';
guardRestrictedEndpoint('Endpoints Verifier');

$urls = [
    "http://127.0.0.1:8088/index.php",
    "http://127.0.0.1:8088/creator.php",
    "http://127.0.0.1:8088/my_bookings.php",
    "http://127.0.0.1:8088/setup.php",
    "http://127.0.0.1:8088/api/gigs.php",
    "http://127.0.0.1:8088/api/bookings.php?client_name=Sarah+Jenkins",
    "http://127.0.0.1:8088/api/bookings.php?creator_id=1"
];

echo "=======================================================\n";
echo " HTTP ENDPOINTS VERIFICATION (SkillSwap @ port 8088)\n";
echo "=======================================================\n";

foreach ($urls as $url) {
    $ctx = stream_context_create(["http" => ["timeout" => 5, "ignore_errors" => true]]);
    $content = @file_get_contents($url, false, $ctx);
    $statusLine = $http_response_header[0] ?? "HTTP/1.1 500 Unknown";
    if (strpos($statusLine, '200') !== false || strpos($statusLine, '201') !== false) {
        echo "[OK] {$url} -> {$statusLine} (" . strlen($content) . " bytes)\n";
    } else {
        echo "[FAIL] {$url} -> {$statusLine}\n";
    }
}
echo "=======================================================\n";
