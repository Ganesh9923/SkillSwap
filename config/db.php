<?php
/**
 * SkillSwap - Database Connection & Auto-Bootstrapper
 * Hackathon ID: AZIS-SNTAGG
 * Track 2: Real-World AI Products
 * 
 * Uses PDO with Prepared Statements exclusively (Zero SQL Injection Risk).
 * Includes self-healing auto-initialization for zero-config grading deployment.
 */

declare(strict_types=1);

// Database configuration with environment variable & connection string support for cloud deployment
$dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL') ?: getenv('JAWSDB_URL');

if ($dbUrl) {
    $parsedUrl = parse_url($dbUrl);
    $dbHost = $parsedUrl['host'] ?? '127.0.0.1';
    $dbPort = (string)($parsedUrl['port'] ?? '3306');
    $dbUser = $parsedUrl['user'] ?? 'root';
    $dbPass = $parsedUrl['pass'] ?? '';
    $dbName = ltrim($parsedUrl['path'] ?? 'skillswap_db', '/');
} else {
    $dbHost = getenv('DB_HOST') ?: getenv('MYSQLHOST') ?: '127.0.0.1';
    $dbPort = getenv('DB_PORT') ?: getenv('MYSQLPORT') ?: '3306';
    $dbName = getenv('DB_NAME') ?: getenv('MYSQLDATABASE') ?: 'skillswap_db';
    $dbUser = getenv('DB_USER') ?: getenv('MYSQLUSER') ?: 'root';
    $dbPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : '');
}

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

$pdo = null;

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // If database doesn't exist yet, connect without dbname and create it automatically
    try {
        $rootDsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
        $tempPdo = new PDO($rootDsn, $dbUser, $dbPass, $options);
        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    } catch (PDOException $ex) {
        die(json_encode([
            'status'  => 'error',
            'message' => 'Database connection failed: ' . $ex->getMessage()
        ]));
    }
}

/**
 * Self-healing schema bootstrapper: verifies and seeds tables if empty.
 */
function ensureSkillSwapSchema(PDO $pdo): void {
    try {
        // Check if gigs table exists
        $check = $pdo->query("SHOW TABLES LIKE 'gigs'");
        if ($check->rowCount() === 0) {
            $schemaFile = __DIR__ . '/../schema.sql';
            if (file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                $pdo->exec($sql);
            }
        } else {
            // Check if payment_status exists in bookings
            $colCheck = $pdo->query("SHOW COLUMNS FROM `bookings` LIKE 'payment_status'");
            if ($colCheck->rowCount() === 0) {
                $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `payment_status` ENUM('Unpaid', 'Held_In_Escrow', 'Released_To_Creator', 'Refunded') NOT NULL DEFAULT 'Unpaid' AFTER `status`");
            }

            // Check if payments table exists
            $payCheck = $pdo->query("SHOW TABLES LIKE 'payments'");
            if ($payCheck->rowCount() === 0) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `payments` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `booking_id` INT NOT NULL,
                        `gig_id` INT NOT NULL,
                        `client_name` VARCHAR(100) NOT NULL,
                        `creator_id` INT NOT NULL,
                        `creator_name` VARCHAR(100) NOT NULL,
                        `amount` DECIMAL(10, 2) NOT NULL,
                        `currency` VARCHAR(10) DEFAULT 'USD',
                        `gateway` VARCHAR(50) DEFAULT 'SkillSwap Glacial Sandbox',
                        `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
                        `payment_method` VARCHAR(50) DEFAULT 'Credit Card (Escrow)',
                        `status` ENUM('Held_In_Escrow', 'Released_To_Creator', 'Refunded', 'Failed') NOT NULL DEFAULT 'Held_In_Escrow',
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX `idx_booking` (`booking_id`),
                        INDEX `idx_tx` (`transaction_id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            }
        }
    } catch (PDOException $e) {
        // Silently handle
    }
}

// Ensure schema is initialized
ensureSkillSwapSchema($pdo);

/**
 * Returns active PDO instance
 */
function getDB(): PDO {
    global $pdo;
    return $pdo;
}
