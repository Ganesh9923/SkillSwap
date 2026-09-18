<?php
/**
 * SkillSwap - Email Verification Portal
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Powered by Hostinger SSL Outgoing SMTP Mailer.
 */

require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Email Verification";
$activePersona = getActivePersona();

$isVerified = !empty($_SESSION['email_verified']);
$verifiedEmail = $_SESSION['verified_email_address'] ?? 'support@dalavix.com';

require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 3rem; max-width: 680px;">
    <div class="glass-panel reveal" style="padding: 2.5rem; text-align: center; margin-bottom: 4rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">📬</div>
        <div class="hero-pill" style="margin-bottom: 0.75rem; font-size: 0.8rem;">Hostinger SMTP Verification</div>
        <h1 style="font-size: 2rem; color: #fff; margin-bottom: 0.5rem;">Email Security Verification</h1>
        <p class="text-muted" style="font-size: 0.95rem; margin-bottom: 2rem;">
            Test and verify instant delivery of cryptographic OTP security codes via our dedicated SSL SMTP mailer.
        </p>

        <?php if ($isVerified): ?>
            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.4); padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem;">
                <div style="font-size: 2rem; margin-bottom: 0.5rem;">✨</div>
                <h3 style="color: #34d399; margin-bottom: 0.25rem;">Email Verified Successfully!</h3>
                <p style="color: #cbd5e1; font-size: 0.9rem;">
                    Address <strong><?= h($verifiedEmail) ?></strong> is verified and authorized for instant booking notifications.
                </p>
                <div style="margin-top: 1.25rem;">
                    <a href="index.php" class="btn btn-primary">Return to Marketplace</a>
                </div>
            </div>
        <?php else: ?>
            <!-- STEP 1: SEND CODE -->
            <div id="step-send-otp" style="text-align: left; background: var(--bg-surface); padding: 1.75rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.15rem; color: #fff; margin-bottom: 1rem;">1. Send Verification Code</h3>
                
                <form id="send-otp-form">
                    <div class="form-group">
                        <label class="form-label" for="verify-email-input">Email Address to Verify</label>
                        <div style="display: flex; gap: 0.75rem;">
                            <input type="email" id="verify-email-input" class="form-control" 
                                   value="support@dalavix.com" required placeholder="Enter your email...">
                            <button type="submit" id="btn-send-code" class="btn btn-primary" style="white-space: nowrap;">
                                <span>Send OTP</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- STEP 2: ENTER CODE (Initially disabled/hidden until sent) -->
            <div id="step-verify-otp" style="text-align: left; background: var(--bg-surface); padding: 1.75rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); display: none;">
                <h3 style="font-size: 1.15rem; color: #fff; margin-bottom: 0.5rem;">2. Enter 6-Digit Security Code</h3>
                <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 1.25rem;">
                    A verification code has been dispatched via Hostinger SMTP. Please enter it below:
                </p>

                <form id="verify-otp-form">
                    <div class="form-group">
                        <input type="text" id="otp-code-input" class="form-control" 
                               placeholder="123456" maxlength="6" 
                               style="font-size: 1.6rem; letter-spacing: 0.3em; text-align: center; font-weight: 700; color: var(--ice-cyan);" required>
                    </div>

                    <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                        <button type="submit" id="btn-submit-code" class="btn btn-success" style="flex: 1;">
                            <span>✓ Confirm & Verify Email</span>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.getElementById('send-otp-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btn-send-code');
    const email = document.getElementById('verify-email-input').value;
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span>Sending...</span>';

    try {
        const response = await fetch('actions/send_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, name: 'SkillSwap User' })
        });
        const result = await response.json();

        if (result.success) {
            showToast('✨ Verification OTP sent via Hostinger SMTP!', 'success');
            document.getElementById('step-verify-otp').style.display = 'block';
            btn.innerHTML = '<span>Resend</span>';
            btn.disabled = false;
        } else {
            showToast(result.message || 'Failed to dispatch email', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (err) {
        showToast('Network error while dispatching email', 'error');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});

document.getElementById('verify-otp-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const code = document.getElementById('otp-code-input').value.trim();
    const btn = document.getElementById('btn-submit-code');

    btn.disabled = true;
    btn.innerHTML = '<span>Verifying...</span>';

    try {
        const response = await fetch('actions/verify_otp.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: code })
        });
        const result = await response.json();

        if (result.success) {
            showToast('✨ Email verified successfully!', 'success');
            setTimeout(() => window.location.reload(), 800);
        } else {
            showToast(result.message || 'Invalid code', 'error');
            btn.disabled = false;
            btn.innerHTML = '<span>✓ Confirm & Verify Email</span>';
        }
    } catch (err) {
        showToast('Error validating verification code', 'error');
        btn.disabled = false;
        btn.innerHTML = '<span>✓ Confirm & Verify Email</span>';
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
