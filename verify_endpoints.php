<?php
$urls = [
    "http://127.0.0.1:8088/index.php",
    "http://127.0.0.1:8088/creator.php",
    "http://127.0.0.1:8088/my_bookings.php",
    "http://127.0.0.1:8088/setup.php",
    "http://127.0.0.1:8088/api/gigs.php",
    "http://127.0.0.1:8088/api/bookings.php"
];

echo "=======================================================\n";
echo " HTTP ENDPOINTS VERIFICATION (SkillSwap @ port 8088)\n";
echo "=======================================================\n";

foreach ($urls as $url) {
    $ctx = stream_context_create(["http" => ["timeout" => 5]]);
    $content = @file_get_contents($url, false, $ctx);
    if ($content !== false) {
        $statusLine = $http_response_header[0] ?? "HTTP/1.1 200 OK";
        echo "[OK] {$url} -> {$statusLine} (" . strlen($content) . " bytes)\n";
    } else {
        echo "[FAIL] {$url}\n";
    }
}
echo "=======================================================\n";
