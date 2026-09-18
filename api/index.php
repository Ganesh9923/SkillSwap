<?php
/**
 * Vercel Serverless Entrypoint & Gateway Router
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World Web Product
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$uri = rtrim($uri, '/');
if ($uri === '') {
    $uri = '/';
}

$rootPath = dirname(__DIR__);

// Handle direct file mappings
$targetFile = null;

if ($uri === '/' || $uri === '/index.php') {
    $targetFile = $rootPath . '/index.php';
} elseif ($uri === '/creator' || $uri === '/creator.php') {
    $targetFile = $rootPath . '/creator.php';
} elseif ($uri === '/my_bookings' || $uri === '/my_bookings.php') {
    $targetFile = $rootPath . '/my_bookings.php';
} elseif ($uri === '/client' || $uri === '/client.php') {
    $targetFile = $rootPath . '/client.php';
} elseif ($uri === '/verify_email' || $uri === '/verify_email.php') {
    $targetFile = $rootPath . '/verify_email.php';
} elseif ($uri === '/checkout' || $uri === '/checkout.php') {
    $targetFile = $rootPath . '/checkout.php';
} elseif ($uri === '/setup' || $uri === '/setup.php') {
    $targetFile = $rootPath . '/setup.php';
} elseif (str_starts_with($uri, '/actions/')) {
    $actionFile = $rootPath . $uri;
    if (!str_ends_with($actionFile, '.php')) {
        $actionFile .= '.php';
    }
    if (file_exists($actionFile)) {
        $targetFile = $actionFile;
    }
} elseif (file_exists($rootPath . $uri . '.php')) {
    $targetFile = $rootPath . $uri . '.php';
} elseif (file_exists($rootPath . $uri) && is_file($rootPath . $uri) && str_ends_with($uri, '.php')) {
    $targetFile = $rootPath . $uri;
}

if ($targetFile && file_exists($targetFile)) {
    // Preserve working directory context
    chdir(dirname($targetFile));
    require $targetFile;
    exit;
}

// Fallback to marketplace index
chdir($rootPath);
require $rootPath . '/index.php';
