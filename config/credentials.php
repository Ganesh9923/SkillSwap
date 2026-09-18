<?php
/**
 * SkillSwap - Credentials & Integrations Configuration
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Configures Hostinger SMTP Mailer and Razorpay Live Payment Gateway.
 */

declare(strict_types=1);

// SMTP Outgoing Mailer Configuration (Hostinger SSL)
const SMTP_CONFIG = [
    'host'       => 'smtp.hostinger.com',
    'port'       => 465,
    'secure'     => 'ssl',
    'user'       => 'support@dalavix.com',
    'pass'       => 'Arun9923@@',
    'from_email' => 'support@dalavix.com',
    'from_name'  => 'SkillSwap Platform'
];

// Razorpay Live Gateway Configuration
const RAZORPAY_CONFIG = [
    'key_id'         => 'rzp_live_TCkPgYdGhmgdsK',
    'key_secret'     => 'HcItpLXzeYrXSMjfvbH6qQti',
    'webhook_secret' => 'whsec_edutrack_live_webhook_secret',
    'currency'       => 'INR',
    'usd_to_inr'     => 85.0 // Approximate conversion for standard display
];

function getSmtpConfig(): array {
    return [
        'host'       => getenv('SMTP_HOST') ?: SMTP_CONFIG['host'],
        'port'       => (int)(getenv('SMTP_PORT') ?: SMTP_CONFIG['port']),
        'secure'     => getenv('SMTP_SECURE') ?: SMTP_CONFIG['secure'],
        'user'       => getenv('SMTP_USER') ?: SMTP_CONFIG['user'],
        'pass'       => getenv('SMTP_PASS') ?: SMTP_CONFIG['pass'],
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: SMTP_CONFIG['from_email'],
        'from_name'  => getenv('SMTP_FROM_NAME') ?: SMTP_CONFIG['from_name']
    ];
}

function getRazorpayConfig(): array {
    return [
        'key_id'         => getenv('RAZORPAY_KEY_ID') ?: RAZORPAY_CONFIG['key_id'],
        'key_secret'     => getenv('RAZORPAY_KEY_SECRET') ?: RAZORPAY_CONFIG['key_secret'],
        'webhook_secret' => getenv('RAZORPAY_WEBHOOK_SECRET') ?: RAZORPAY_CONFIG['webhook_secret'],
        'currency'       => getenv('RAZORPAY_CURRENCY') ?: RAZORPAY_CONFIG['currency'],
        'usd_to_inr'     => (float)(getenv('USD_TO_INR') ?: RAZORPAY_CONFIG['usd_to_inr'])
    ];
}
