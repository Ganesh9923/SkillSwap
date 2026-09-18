<?php
/**
 * SkillSwap - Hostinger SMTP Mailer & Email Verification Service
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Direct socket-based SMTP client over SSL (Port 465) with HTML Glacial Templates.
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/credentials.php';

/**
 * Send an email via Hostinger SMTP SSL socket connection
 */
function sendSmtpEmail(string $toEmail, string $toName, string $subject, string $htmlBody): array {
    $config = getSmtpConfig();
    $host = $config['host'];
    $port = $config['port'];
    $user = $config['user'];
    $pass = $config['pass'];
    $fromEmail = $config['from_email'];
    $fromName = $config['from_name'];

    $timeout = 15;
    $socket = @fsockopen("ssl://{$host}", $port, $errno, $errstr, $timeout);

    if (!$socket) {
        return [
            'success' => false,
            'message' => "Could not connect to SMTP server: {$errstr} ({$errno})"
        ];
    }

    $readResponse = function() use ($socket) {
        $response = "";
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === " ") break;
        }
        return $response;
    };

    $sendCommand = function(string $cmd, int $expectedCode = 250) use ($socket, $readResponse) {
        fputs($socket, $cmd . "\r\n");
        $res = $readResponse();
        $code = (int)substr($res, 0, 3);
        if ($expectedCode > 0 && $code !== $expectedCode) {
            throw new RuntimeException("SMTP Command '{$cmd}' failed. Response: {$res}");
        }
        return $res;
    };

    try {
        $greeting = $readResponse(); // 220
        if ((int)substr($greeting, 0, 3) !== 220) {
            throw new RuntimeException("Invalid SMTP server greeting: {$greeting}");
        }

        $sendCommand("EHLO " . gethostname());
        $sendCommand("AUTH LOGIN", 334);
        $sendCommand(base64_encode($user), 334);
        $sendCommand(base64_encode($pass), 235);
        $sendCommand("MAIL FROM: <{$fromEmail}>", 250);
        $sendCommand("RCPT TO: <{$toEmail}>", 250);
        $sendCommand("DATA", 354);

        $headers = [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "From: {$fromName} <{$fromEmail}>",
            "To: {$toName} <{$toEmail}>",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "Date: " . date('r'),
            "X-Mailer: SkillSwap Glacial Mailer v2.0"
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.";
        $sendCommand($message, 250);
        $sendCommand("QUIT", 221);
        fclose($socket);

        return [
            'success' => true,
            'message' => 'Email dispatched successfully via Hostinger SMTP'
        ];
    } catch (Exception $e) {
        if (is_resource($socket)) fclose($socket);
        return [
            'success' => false,
            'message' => 'SMTP Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Generate luxury glacial HTML email wrapper
 */
function getGlacialEmailTemplate(string $title, string $badge, string $contentHtml): string {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$title}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #050813; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f8fafc;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #050813; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #0a0f24; border: 1px solid rgba(56, 189, 248, 0.25); border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.6);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 30px; background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(99, 102, 241, 0.15)); border-bottom: 1px solid rgba(255, 255, 255, 0.08); text-align: center;">
                            <div style="display: inline-block; padding: 4px 12px; background: rgba(56, 189, 248, 0.15); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 9999px; color: #38bdf8; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">
                                {$badge}
                            </div>
                            <h1 style="margin: 0; font-size: 26px; font-weight: 800; color: #ffffff; letter-spacing: -0.02em;">
                                Skill<span style="color: #00f2fe;">Swap</span>
                            </h1>
                        </td>
                    </tr>
                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 35px 30px; line-height: 1.6; font-size: 15px; color: #cbd5e1;">
                            {$contentHtml}
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 30px; background-color: #060a1a; border-top: 1px solid rgba(255, 255, 255, 0.05); text-align: center; font-size: 12px; color: #64748b;">
                            &copy; 2026 SkillSwap Platform &bull; Track 2: Real-World AI Products<br>
                            Hackathon ID: <strong style="color: #38bdf8;">AZIS-SNTAGG</strong> &bull; Hostinger Secure SMTP
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
 * Send Email Verification OTP
 */
function sendVerificationOtpEmail(string $email, string $name, string $otp): array {
    $content = <<<HTML
        <h2 style="color: #ffffff; font-size: 20px; margin-top: 0;">Verify Your Email Address</h2>
        <p>Hello <strong>{$name}</strong>,</p>
        <p>Use the 6-digit verification code below to confirm your identity on the SkillSwap creator platform:</p>
        
        <div style="margin: 25px 0; text-align: center;">
            <div style="display: inline-block; padding: 14px 28px; background: #0f1738; border: 2px solid #00f2fe; border-radius: 12px; font-size: 32px; font-weight: 800; letter-spacing: 0.25em; color: #00f2fe; box-shadow: 0 0 20px rgba(0, 242, 254, 0.3);">
                {$otp}
            </div>
        </div>
        
        <p style="font-size: 13px; color: #94a3b8;">This code is valid for 10 minutes. If you did not request this verification, please disregard this message.</p>
HTML;

    $html = getGlacialEmailTemplate("Verify Your Email", "Security Verification", $content);
    return sendSmtpEmail($email, $name, "Your SkillSwap Verification Code: {$otp}", $html);
}

/**
 * Send Payment Receipt & Escrow Confirmation Email
 */
function sendPaymentConfirmationEmail(string $clientEmail, string $clientName, array $booking, array $payment): array {
    $amountFormatted = '$' . number_format((float)$payment['amount'], 2);
    $txnId = htmlspecialchars($payment['transaction_id']);
    $gigTitle = htmlspecialchars($booking['gig_title']);
    $creatorName = htmlspecialchars($booking['creator_name']);
    $gateway = htmlspecialchars($payment['gateway']);

    $content = <<<HTML
        <h2 style="color: #ffffff; font-size: 20px; margin-top: 0;">Payment Receipt & Escrow Guarantee</h2>
        <p>Hello <strong>{$clientName}</strong>,</p>
        <p>Your payment of <strong style="color: #00f2fe;">{$amountFormatted}</strong> has been successfully processed and locked in the <strong>SkillSwap Escrow Vault</strong>.</p>
        
        <table width="100%" cellpadding="10" cellspacing="0" style="margin: 20px 0; background: #0f1738; border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); font-size: 14px;">
            <tr>
                <td style="color: #94a3b8;">Transaction ID:</td>
                <td align="right" style="color: #38bdf8; font-family: monospace;">{$txnId}</td>
            </tr>
            <tr>
                <td style="color: #94a3b8;">Gig Service:</td>
                <td align="right" style="color: #fff; font-weight: 600;">{$gigTitle}</td>
            </tr>
            <tr>
                <td style="color: #94a3b8;">Creator:</td>
                <td align="right" style="color: #fff;">{$creatorName}</td>
            </tr>
            <tr>
                <td style="color: #94a3b8;">Payment Gateway:</td>
                <td align="right" style="color: #34d399;">{$gateway}</td>
            </tr>
            <tr>
                <td style="color: #94a3b8;">Escrow Status:</td>
                <td align="right" style="color: #00f2fe; font-weight: 700;">🔒 Funds Held in Escrow</td>
            </tr>
        </table>
        
        <p style="font-size: 13px; color: #94a3b8;">
            🛡️ <strong>DP1 Escrow Guarantee:</strong> Funds will only be released to the creator once you confirm project completion. If the creator declines the request, you receive an immediate full refund.
        </p>
HTML;

    $html = getGlacialEmailTemplate("Payment Receipt", "Escrow Secured", $content);
    return sendSmtpEmail($clientEmail, $clientName, "Payment Confirmed: {$amountFormatted} for {$gigTitle}", $html);
}
