<?php
/**
 * SkillSwap - Credentials & Integrations Configuration
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Safely loads environment variables from .env or system environment.
 * Default values point to safe local sandbox / mock endpoints.
 */

declare(strict_types=1);

require_once __DIR__ . '/env.php';

function getSmtpConfig(): array {
    return [
        'host'       => getenv('SMTP_HOST') ?: 'smtp.hostinger.com',
        'port'       => (int)(getenv('SMTP_PORT') ?: 465),
        'secure'     => getenv('SMTP_SECURE') ?: 'ssl',
        'user'       => getenv('SMTP_USER') ?: 'support@dalavix.com',
        'pass'       => getenv('SMTP_PASS') ?: '',
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'support@dalavix.com',
        'from_name'  => getenv('SMTP_FROM_NAME') ?: 'SkillSwap Platform'
    ];
}

function getRazorpayConfig(): array {
    return [
        'key_id'         => getenv('RAZORPAY_KEY_ID') ?: 'rzp_test_sandbox_dummy',
        'key_secret'     => getenv('RAZORPAY_KEY_SECRET') ?: 'sandbox_secret_dummy',
        'webhook_secret' => getenv('RAZORPAY_WEBHOOK_SECRET') ?: '',
        'currency'       => getenv('RAZORPAY_CURRENCY') ?: 'INR',
        'usd_to_inr'     => (float)(getenv('USD_TO_INR') ?: 85.0)
    ];
}
