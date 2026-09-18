<?php
/**
 * SkillSwap - Razorpay Live Gateway Client
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Creates Razorpay Orders via REST API and verifies HMAC-SHA256 signatures.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/credentials.php';

/**
 * Create a live Razorpay Order via REST API
 */
function createRazorpayOrder(float $amountInUSD, string $receiptId, array $notes = []): array {
    $config = getRazorpayConfig();
    $keyId = $config['key_id'];
    $keySecret = $config['key_secret'];
    $rate = $config['usd_to_inr'];

    // Convert USD to INR Paise (1 INR = 100 paise)
    $amountInINR = round($amountInUSD * $rate, 2);
    $amountInPaise = (int)round($amountInINR * 100);

    $payload = [
        'amount'   => max(100, $amountInPaise), // minimum 1 INR
        'currency' => 'INR',
        'receipt'  => $receiptId,
        'notes'    => $notes
    ];

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_USERPWD        => "{$keyId}:{$keySecret}",
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 15
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return [
            'success' => false,
            'message' => 'cURL Error: ' . $curlErr
        ];
    }

    $result = json_decode((string)$response, true);

    if ($httpCode >= 200 && $httpCode < 300 && isset($result['id'])) {
        return [
            'success'       => true,
            'order_id'      => $result['id'],
            'amount_paise'  => $result['amount'],
            'amount_inr'    => $amountInINR,
            'amount_usd'    => $amountInUSD,
            'currency'      => $result['currency'],
            'key_id'        => $keyId
        ];
    }

    return [
        'success' => false,
        'message' => $result['error']['description'] ?? 'Razorpay API returned HTTP ' . $httpCode
    ];
}

/**
 * Verify Razorpay payment signature
 */
function verifyRazorpaySignature(string $orderId, string $paymentId, string $signature): bool {
    $config = getRazorpayConfig();
    $keySecret = $config['key_secret'];

    $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $keySecret);
    return hash_equals($expectedSignature, $signature);
}
