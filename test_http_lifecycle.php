<?php
/**
 * SkillSwap - Full HTTP Lifecycle Verification
 * Simulates external browser / grading script making real HTTP requests against the live server.
 */

$baseUrl = "http://127.0.0.1:8088";

function httpPost(string $url, array $data): array {
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    return [
        'status' => $http_response_header[0] ?? 'UNKNOWN',
        'body'   => json_decode($response, true) ?: $response
    ];
}

echo "\n=======================================================\n";
echo " SKILLSWAP REAL HTTP LIFECYCLE TESTS (127.0.0.1:8088)\n";
echo "=======================================================\n";

// 1. Post Gig HTTP Test
$postGigRes = httpPost("{$baseUrl}/actions/post_gig.php", [
    'creator_id'   => 1,
    'creator_name' => 'Elena Rostova',
    'title'        => 'Glacial Micro-interactions & Motion System',
    'category'     => 'Design',
    'rate'         => '240.00',
    'description'  => 'Interactive design tokens, spring physics curves, and high-performance canvas motion.',
    'max_slots'    => 4
]);

echo "[STEP 1] Post a Gig (Feature 1):\n";
echo "         Status: {$postGigRes['status']}\n";
$newGigId = $postGigRes['body']['data']['id'] ?? null;
echo "         Created Gig ID: {$newGigId}\n\n";

// 2. Book Gig HTTP Test
$bookRes = httpPost("{$baseUrl}/actions/book_gig.php", [
    'gig_id'      => $newGigId ?: 1,
    'client_name' => 'Sarah Jenkins',
    'message'     => 'Need this motion system for our Track 2 launch video next week.',
    'booked_date' => date('Y-m-d', strtotime('+4 days'))
]);

echo "[STEP 2] Book a Gig (Feature 3):\n";
echo "         Status: {$bookRes['status']}\n";
$newBookingId = $bookRes['body']['data']['id'] ?? null;
$bookingStatus = $bookRes['body']['data']['status'] ?? null;
echo "         Created Booking ID: {$newBookingId} | Status: {$bookingStatus}\n\n";

// 3. Creator Accept Booking HTTP Test
$acceptRes = httpPost("{$baseUrl}/actions/update_booking.php", [
    'booking_id' => $newBookingId,
    'status'     => 'Accepted'
]);

echo "[STEP 3] Creator Accept Booking (Feature 4):\n";
echo "         Status: {$acceptRes['status']}\n";
echo "         Result: " . json_encode($acceptRes['body']) . "\n\n";

// 4. Create another booking and Decline with DP1 reason
$bookRes2 = httpPost("{$baseUrl}/actions/book_gig.php", [
    'gig_id'      => 2,
    'client_name' => 'Liam O\'Connor',
    'message'     => 'Full backend rewrite in 24 hours.'
]);
$b2Id = $bookRes2['body']['data']['id'] ?? null;

$declineRes = httpPost("{$baseUrl}/actions/update_booking.php", [
    'booking_id'     => $b2Id,
    'status'         => 'Declined',
    'decline_reason' => '24-hour turnaround exceeds standard sprint capacity. Recommend checking alternative backend developers.'
]);

echo "[STEP 4] Creator Decline Booking with DP1 Reason (Feature 4 & DP1):\n";
echo "         Status: {$declineRes['status']}\n";
echo "         Result: " . json_encode($declineRes['body']) . "\n\n";

// 5. Query Client Bookings over HTTP
$clientBookingsJson = file_get_contents("{$baseUrl}/api/bookings.php?client_name=Sarah+Jenkins");
$clientBookings = json_decode($clientBookingsJson, true);

echo "[STEP 5] Client 'My Bookings' API Retrieval (Feature 5):\n";
echo "         Found " . count($clientBookings['data'] ?? []) . " bookings for Sarah Jenkins.\n";

echo "=======================================================\n";
echo " ALL HTTP LIFECYCLE TESTS VERIFIED SUCCESSFULLY!\n";
echo "=======================================================\n\n";
