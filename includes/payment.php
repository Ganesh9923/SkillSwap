<?php
/**
 * SkillSwap - Payment Gateway & Escrow Subsystem
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Supports:
 * - Simulated Glacial Sandbox Gateway & Escrow Vault
 * - Escrow Vault (Funds locked securely on booking, released on completion)
 * - Automatic Refunds on DP1 Decline Actions
 * - Database transactions for financial integrity
 */

declare(strict_types=1);

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Supported payment methods
 */
const PAYMENT_METHODS = [
    'card'     => 'Simulated Card (Escrow Vault)',
    'upi'      => 'Instant UPI / Fast QR (Escrow Vault)',
    'razorpay' => 'Razorpay Gateway (Escrow Vault)',
    'escrow'   => 'SkillSwap Escrow Balance'
];

/**
 * Process a payment for a booking and lock funds in Escrow
 */
function processBookingPayment(int $bookingId, string $method = 'card', string $gateway = 'SkillSwap Glacial Sandbox'): array {
    if ($bookingId <= 0) {
        throw new InvalidArgumentException("Invalid booking ID");
    }

    $pdo = getDB();
    
    // Fetch booking
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
    $stmt->execute([':id' => $bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        throw new InvalidArgumentException("Booking #{$bookingId} not found");
    }

    if (in_array($booking['payment_status'], ['Held_In_Escrow', 'Released_To_Creator'], true)) {
        throw new RuntimeException("Booking #{$bookingId} is already funded ({$booking['payment_status']})");
    }

    $amount = (float)$booking['rate'];
    $methodName = PAYMENT_METHODS[$method] ?? 'Simulated Card (Escrow Vault)';
    $txnId = 'TXN_SS_' . strtoupper(bin2hex(random_bytes(6)));

    $pdo->beginTransaction();

    try {
        $paySql = "INSERT INTO payments (booking_id, gig_id, client_name, creator_id, creator_name, amount, currency, gateway, transaction_id, payment_method, status, created_at)
                   VALUES (:booking_id, :gig_id, :client_name, :creator_id, :creator_name, :amount, 'USD', :gateway, :txn_id, :method, 'Held_In_Escrow', NOW())";
        
        $payStmt = $pdo->prepare($paySql);
        $payStmt->execute([
            ':booking_id'   => $booking['id'],
            ':gig_id'       => $booking['gig_id'],
            ':client_name'  => $booking['client_name'],
            ':creator_id'   => $booking['creator_id'],
            ':creator_name' => $booking['creator_name'],
            ':amount'       => $amount,
            ':gateway'      => substr($gateway, 0, 50),
            ':txn_id'       => $txnId,
            ':method'       => substr($methodName, 0, 50)
        ]);

        $paymentId = (int)$pdo->lastInsertId();

        // Update bookings payment status
        $updateStmt = $pdo->prepare("UPDATE bookings SET payment_status = 'Held_In_Escrow', updated_at = NOW() WHERE id = :id");
        $updateStmt->execute([':id' => $booking['id']]);

        $pdo->commit();

        return [
            'payment_id'     => $paymentId,
            'booking_id'     => $booking['id'],
            'amount'         => $amount,
            'transaction_id' => $txnId,
            'status'         => 'Held_In_Escrow',
            'gateway'        => $gateway,
            'message'        => 'Funds secured in SkillSwap Escrow Vault. Creator will receive payout upon completion.'
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        logAppError($e, 'process_payment');
        throw $e;
    }
}

/**
 * Release Escrow payment to creator when booking is marked complete
 */
function releaseEscrowPayment(int $bookingId): bool {
    if ($bookingId <= 0) return false;

    $pdo = getDB();
    $pdo->beginTransaction();

    try {
        $stmt1 = $pdo->prepare("UPDATE payments SET status = 'Released_To_Creator', updated_at = NOW() WHERE booking_id = :bid AND status = 'Held_In_Escrow'");
        $stmt1->execute([':bid' => $bookingId]);

        $stmt2 = $pdo->prepare("UPDATE bookings SET payment_status = 'Released_To_Creator', updated_at = NOW() WHERE id = :bid");
        $stmt2->execute([':bid' => $bookingId]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        logAppError($e, 'release_escrow');
        return false;
    }
}

/**
 * Refund payment when a booking is declined by creator (DP1)
 */
function refundBookingPayment(int $bookingId, ?string $reason = null): bool {
    if ($bookingId <= 0) return false;

    $pdo = getDB();
    $pdo->beginTransaction();

    try {
        $stmt1 = $pdo->prepare("UPDATE payments SET status = 'Refunded', updated_at = NOW() WHERE booking_id = :bid AND status = 'Held_In_Escrow'");
        $stmt1->execute([':bid' => $bookingId]);

        $stmt2 = $pdo->prepare("UPDATE bookings SET payment_status = 'Refunded', updated_at = NOW() WHERE id = :bid");
        $stmt2->execute([':bid' => $bookingId]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        logAppError($e, 'refund_escrow');
        return false;
    }
}

/**
 * Get payment details for a specific booking
 */
function getPaymentByBookingId(int $bookingId): ?array {
    if ($bookingId <= 0) return null;

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, booking_id, gig_id, client_name, creator_id, creator_name, amount, currency, gateway, transaction_id, payment_method, status, created_at FROM payments WHERE booking_id = :bid ORDER BY id DESC LIMIT 1");
    $stmt->execute([':bid' => $bookingId]);
    $payment = $stmt->fetch();
    return $payment ?: null;
}

/**
 * Format payment status badge
 */
function getPaymentStatusBadge(string $status): string {
    return match ($status) {
        'Held_In_Escrow' => '<span class="status-badge pending" title="Funds safely held in Escrow"><span class="status-dot"></span> 🔒 Escrow Secured</span>',
        'Released_To_Creator' => '<span class="status-badge accepted" title="Payment released to creator"><span class="status-dot"></span> ✓ Paid to Creator</span>',
        'Refunded' => '<span class="status-badge declined" title="Refunded due to declined booking"><span class="status-dot"></span> ↩ Refunded</span>',
        default => '<span class="status-badge" style="background: rgba(255,255,255,0.06); color: var(--text-muted); border: 1px solid var(--border-subtle);"><span class="status-dot"></span> Unpaid</span>'
    };
}
