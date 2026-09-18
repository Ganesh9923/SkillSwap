<?php
/**
 * SkillSwap - Zero-Dependency Environment (.env) Loader
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Safely parses .env files into putenv(), $_ENV, and $_SERVER.
 */

declare(strict_types=1);

function loadEnvFile(string $filePath): void {
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) return;

    foreach ($lines as $line) {
        $line = trim($line);
        // Skip comments and empty lines
        if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, ';')) {
            continue;
        }

        // Split by first =
        $pos = strpos($line, '=');
        if ($pos === false) continue;

        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));

        // Strip surrounding quotes
        if (
            (str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))
        ) {
            $val = substr($val, 1, -1);
        }

        // Set in environment if not already set (allows system env vars to override)
        if (getenv($key) === false) {
            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

// Auto-load .env from project root
loadEnvFile(__DIR__ . '/../.env');
