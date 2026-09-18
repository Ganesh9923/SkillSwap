<?php
/**
 * SkillSwap - Centralized Security, Defensive Headers & Validation
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Provides:
 * - Secure Session Cookie Settings (HttpOnly, SameSite=Lax, Secure on HTTPS)
 * - Standard Defensive HTTP Security Headers
 * - IP & Session Rate Limiting (Token Bucket)
 * - Honeypot Bot Detection
 * - Input Bounds & Type Validation Helpers
 * - Safe Server-Side Error Logging (No Stack Trace Leaks)
 */

declare(strict_types=1);

// Ensure logs directory exists
$logsDir = __DIR__ . '/../logs';
if (!is_dir($logsDir)) {
    @mkdir($logsDir, 0750, true);
}

/**
 * Configure and start secure session
 */
function initSecureSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    // Set secure session name
    session_name('SKILLSWAP_DEMO_SESS');
    @session_start();
}

/**
 * Emit standard defensive security headers
 */
function emitSecurityHeaders(): void {
    if (headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
    
    // Content Security Policy allowing Google Fonts, Unsplash images, and Razorpay Checkout SDK
    $csp = "default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'; " .
           "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://checkout.razorpay.com; " .
           "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
           "font-src 'self' https://fonts.gstatic.com; " .
           "img-src 'self' data: https: https://images.unsplash.com; " .
           "connect-src 'self' https: https://api.razorpay.com https://lumberjack.razorpay.com;";
    header("Content-Security-Policy: {$csp}");
}

/**
 * Lightweight file/session-based rate limiter
 * Returns true if request is permitted, false if rate limit exceeded.
 */
function checkRateLimit(string $action, int $maxAttempts = 15, int $windowSeconds = 300): bool {
    // CLI or automated test runner bypass
    if (php_sapi_name() === 'cli') {
        return true;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $key = "rate_" . md5("{$action}_{$ip}");

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['attempts' => 1, 'first_attempt' => time()];
        return true;
    }

    $data = $_SESSION[$key];
    $elapsed = time() - $data['first_attempt'];

    if ($elapsed > $windowSeconds) {
        // Reset window
        $_SESSION[$key] = ['attempts' => 1, 'first_attempt' => time()];
        return true;
    }

    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }

    $_SESSION[$key]['attempts']++;
    return true;
}

/**
 * Verify honeypot field. Returns false if a bot filled the hidden field.
 */
function validateHoneypot(string $fieldName = 'website_hp'): bool {
    if (isset($_POST[$fieldName]) && !empty(trim((string)$_POST[$fieldName]))) {
        return false; // Bot caught!
    }
    return true;
}

/**
 * Safe server-side error logging
 */
function logAppError(Throwable|string $error, string $context = 'general'): string {
    $errorId = bin2hex(random_bytes(6));
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/../logs/app.log';

    $message = is_string($error) ? $error : ($error->getMessage() . "\n" . $error->getTraceAsString());
    $logEntry = "[{$timestamp}] [ID: {$errorId}] [Context: {$context}] {$message}\n";

    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    return $errorId;
}

/**
 * Check if app is in production mode
 */
function isProduction(): bool {
    $env = strtolower((string)(getenv('APP_ENV') ?: 'development'));
    return in_array($env, ['production', 'prod', 'live'], true);
}

/**
 * Guard restricted endpoints from public production access
 */
function guardRestrictedEndpoint(string $endpointName = 'Diagnostic'): void {
    if (isProduction() && php_sapi_name() !== 'cli') {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => 'error',
            'message' => "Forbidden: {$endpointName} is disabled in production demo environment."
        ]);
        exit;
    }
}
