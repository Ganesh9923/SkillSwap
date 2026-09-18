<?php
/**
 * SkillSwap - Glacial Payment Gateway Checkout Portal
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Supports Escrow Vault, 1-Click Sandbox Test Checkout, and Multi-Gateway processing.
 */

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/payment.php';

$pageTitle = "Secure Checkout & Escrow Vault";
$activePersona = getActivePersona();

$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$gigId = isset($_GET['gig_id']) ? (int)$_GET['gig_id'] : 0;

$booking = null;
$gig = null;

if ($bookingId > 0) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = :id");
    $stmt->execute([':id' => $bookingId]);
    $booking = $stmt->fetch();
}

if ($booking) {
    $gig = getGigById((int)$booking['gig_id']);
    $amount = (float)$booking['rate'];
    $itemTitle = $booking['gig_title'];
    $creatorName = $booking['creator_name'];
    $clientName = $booking['client_name'];
} elseif ($gigId > 0) {
    $gig = getGigById($gigId);
    if ($gig) {
        $amount = (float)$gig['rate'];
        $itemTitle = $gig['title'];
        $creatorName = $gig['creator_name'];
        $clientName = $activePersona['name'];
    }
}

if (!$booking && !$gig) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 2.5rem; max-width: 980px;">
    <!-- Breadcrumb & Banner -->
    <div style="margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            <div class="hero-pill" style="margin-bottom: 0.5rem; font-size: 0.8rem;">
                🔒 SkillSwap Escrow Protection Guaranteed
            </div>
            <h1 style="font-size: 2rem; color: #fff;">Secure Checkout Portal</h1>
            <p class="text-muted" style="font-size: 0.95rem;">
                Funds are held in secure Escrow until you review and confirm the creator's deliverables.
            </p>
        </div>

        <a href="my_bookings.php" class="btn btn-secondary">
            <span>&larr; Back to My Bookings</span>
        </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 2rem; margin-bottom: 4rem;">
        <!-- Left: Payment Form & Gateway Selection -->
        <div class="glass-panel reveal stagger-1" style="padding: 2rem;">
            <h3 style="color: #fff; font-size: 1.25rem; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>💳</span> Select Payment Method
            </h3>

            <!-- Payment Method Tabs -->
            <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; background: var(--bg-surface); padding: 0.35rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); flex-wrap: wrap;">
                <button type="button" class="btn btn-sm btn-primary" id="tab-razorpay" style="flex: 1; min-width: 140px;" onclick="switchPaymentTab('razorpay')">
                    ⚡ Razorpay Live
                </button>
                <button type="button" class="btn btn-sm btn-secondary" id="tab-card" style="flex: 1; min-width: 140px;" onclick="switchPaymentTab('card')">
                    💳 Sandbox Card
                </button>
                <button type="button" class="btn btn-sm btn-secondary" id="tab-escrow" style="flex: 1; min-width: 140px;" onclick="switchPaymentTab('escrow')">
                    🛡️ Demo Payment (testing vault)
                </button>
            </div>

            <!-- RAZORPAY LIVE SECTION -->
            <div id="section-razorpay" style="padding: 1rem 0;">
                <div style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(99, 102, 241, 0.15)); border: 1px solid var(--ice-cyan); padding: 1.5rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; text-align: center;">
                    <div style="font-size: 0.82rem; color: var(--ice-300); font-weight: 700; text-transform: uppercase; margin-bottom: 0.5rem;">
                        ⚡ Official Razorpay Live Gateway Active
                    </div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #fff; margin-bottom: 0.25rem;">
                        Total: ₹<?= number_format($amount * 85.0, 2) ?> INR <span style="font-size: 0.9rem; color: var(--text-muted); font-weight: 400;">($<?= number_format($amount, 2) ?> USD)</span>
                    </div>
                    <p style="font-size: 0.85rem; color: #cbd5e1; margin-bottom: 1.25rem;">
                        Pay securely with UPI (GPay, PhonePe, Paytm), Netbanking, Credit/Debit Cards, or Wallets.
                    </p>
                    <button type="button" id="btn-razorpay-pay" class="btn btn-primary btn-lg" style="width: 100%; box-shadow: 0 0 25px rgba(0, 242, 254, 0.4);" onclick="openRazorpayLiveModal()">
                        <span>⚡ Launch Razorpay Secure Checkout</span>
                    </button>
                </div>
            </div>

            <!-- SANDBOX CARD SECTION -->
            <div id="section-card" style="display: none;">
                <!-- Grader Quick-Fill Helper -->
                <div style="background: rgba(56, 189, 248, 0.08); border: 1px solid var(--border-ice); padding: 0.85rem 1.25rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 0.82rem; color: var(--ice-200);">
                        ⚡ <strong>Grader Mode:</strong> Instant sandbox payment.
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" onclick="autoFillSandboxCard()" style="font-size: 0.75rem; padding: 0.25rem 0.6rem;">
                        1-Click Test Card
                    </button>
                </div>

                <form id="checkout-form" method="POST" action="actions/process_payment.php">
                    <!-- Bot Protection Honeypot -->
                    <input type="text" name="website_hp" value="" style="display:none !important;" tabindex="-1" autocomplete="off">
                    <input type="hidden" name="booking_id" value="<?= $booking ? $booking['id'] : '' ?>">
                    <input type="hidden" name="gig_id" value="<?= $gig['id'] ?>">
                    <input type="hidden" id="payment-method-input" name="payment_method" value="card">

                    <div class="form-group">
                        <label class="form-label" for="card-name">Cardholder Name</label>
                        <input type="text" id="card-name" name="card_name" class="form-control" 
                               value="<?= h($clientName) ?>" placeholder="e.g. Sarah Jenkins">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="card-number">Card Number</label>
                        <input type="text" id="card-number" name="card_number" class="form-control" 
                               placeholder="4242 &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; 4242" maxlength="19">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label" for="card-expiry">Expiry Date</label>
                            <input type="text" id="card-expiry" name="card_expiry" class="form-control" 
                                   placeholder="MM/YY" maxlength="5">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="card-cvc">Security Code (CVC)</label>
                            <input type="password" id="card-cvc" name="card_cvc" class="form-control" 
                                   placeholder="•••" maxlength="4">
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem;">
                        <button type="submit" id="btn-pay-now" class="btn btn-primary btn-lg" style="width: 100%;">
                            <span>🔒 Pay <?= formatRate($amount) ?> into Escrow</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- DEMO PAYMENT (TESTING VAULT) FORM -->
            <div id="section-escrow" style="display: none; padding: 1rem 0;">
                <form id="escrow-form" method="POST" action="actions/process_payment.php">
                    <!-- Bot Protection Honeypot -->
                    <input type="text" name="website_hp" value="" style="display:none !important;" tabindex="-1" autocomplete="off">
                    <input type="hidden" name="booking_id" value="<?= $booking ? $booking['id'] : '' ?>">
                    <input type="hidden" name="gig_id" value="<?= $gig['id'] ?>">
                    <input type="hidden" name="payment_method" value="escrow">

                    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); padding: 1.25rem; border-radius: var(--radius-md); margin-bottom: 1rem;">
                        <div style="font-weight: 700; color: #34d399; margin-bottom: 0.25rem;">🛡️ Demo Payment (Testing Vault)</div>
                        <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5;">
                            Simulate an instant demo payment held safely in the testing vault without charging real money. Test funds are automatically refunded if the creator declines in DP1, and released upon project approval.
                        </p>
                    </div>

                    <div style="margin-top: 1.75rem;">
                        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                            <span>🔒 Confirm Demo Payment (<?= formatRate($amount) ?>)</span>
                        </button>
                    </div>
                </form>
            </div>

            <div style="margin-top: 1.5rem; text-align: center; font-size: 0.8rem; color: var(--text-muted); display: flex; align-items: center; justify-content: center; gap: 1rem;">
                <span>🔒 256-bit SSL Encrypted</span>
                <span>&bull;</span>
                <span>🛡️ Money-Back Guarantee</span>
                <span>&bull;</span>
                <span>⚡ Zero-Auth Fast Checkout</span>
            </div>
        </div>

        <!-- Right: Order Summary & Trust Guarantee -->
        <div class="reveal stagger-2">
            <div class="glass-panel" style="padding: 2rem; margin-bottom: 1.5rem;">
                <h3 style="color: #fff; font-size: 1.25rem; margin-bottom: 1.25rem;">Order Summary</h3>
                
                <div style="border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.25rem; margin-bottom: 1.25rem;">
                    <div style="font-size: 0.8rem; color: var(--ice-300); font-weight: 700; text-transform: uppercase;">
                        <?= h($gig['category'] ?? 'Gig Service') ?>
                    </div>
                    <div style="font-size: 1.15rem; font-weight: 700; color: #fff; margin-top: 0.25rem;">
                        <?= h($itemTitle) ?>
                    </div>
                    <div style="font-size: 0.88rem; color: var(--text-muted); margin-top: 0.4rem;">
                        Creator: <strong style="color: var(--ice-200);"><?= h($creatorName) ?></strong>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.95rem; margin-bottom: 1.25rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <span class="text-muted">Creator Rate</span>
                        <span style="color: #fff; font-weight: 600;"><?= formatRate($amount) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span class="text-muted">Escrow Protection Fee</span>
                        <span style="color: #34d399; font-weight: 600;">$0.00 (Free)</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span class="text-muted">Platform Processing</span>
                        <span style="color: #34d399; font-weight: 600;">$0.00</span>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-subtle); padding-top: 1.25rem;">
                    <span style="font-size: 1.1rem; font-weight: 700; color: #fff;">Total Due Today:</span>
                    <span style="font-family: var(--font-heading); font-size: 1.75rem; font-weight: 800; color: var(--ice-cyan);">
                        <?= formatRate($amount) ?>
                    </span>
                </div>
            </div>

            <!-- Decision Point 1 Guarantee Box -->
            <div class="glass-panel" style="padding: 1.5rem; background: rgba(14, 22, 48, 0.45);">
                <div style="font-weight: 700; color: var(--ice-200); font-size: 0.95rem; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.4rem;">
                    <span>⚡</span> DP1 Automatic Refund Policy
                </div>
                <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.6;">
                    If the creator declines your booking request for any reason, your Escrow funds are instantly released and refunded back to your account without any cancellation penalties.
                </p>
            </div>
        </div>
    </div>
</main>

<!-- Official Razorpay Checkout JS SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<script>
function switchPaymentTab(method) {
    ['razorpay', 'card', 'escrow'].forEach(m => {
        const tab = document.getElementById(`tab-${m}`);
        const sec = document.getElementById(`section-${m}`);
        if (tab && sec) {
            if (m === method) {
                tab.className = 'btn btn-sm btn-primary';
                sec.style.display = 'block';
            } else {
                tab.className = 'btn btn-sm btn-secondary';
                sec.style.display = 'none';
            }
        }
    });
}

async function openRazorpayLiveModal() {
    const btn = document.getElementById('btn-razorpay-pay');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span>Initializing Razorpay Live...</span>';

    try {
        // Step 1: Create Order on backend
        const orderRes = await fetch('actions/razorpay_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                booking_id: <?= (int)($booking['id'] ?? 0) ?>,
                gig_id: <?= (int)($gig['id'] ?? 0) ?>
            })
        });

        const orderData = await orderRes.json();

        if (!orderData.success) {
            showToast(orderData.message || 'Error creating Razorpay order', 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
            return;
        }

        // Step 2: Open Razorpay Checkout Popup
        const options = {
            "key": orderData.key_id,
            "amount": orderData.amount_paise,
            "currency": "INR",
            "name": "SkillSwap Platform",
            "description": "<?= addslashes(h($itemTitle)) ?>",
            "order_id": orderData.order_id,
            "prefill": {
                "name": "<?= addslashes(h($clientName)) ?>",
                "email": "support@dalavix.com"
            },
            "theme": {
                "color": "#0ea5e9"
            },
            "handler": async function (response) {
                btn.innerHTML = '<span>Verifying Payment...</span>';
                showToast('⚡ Payment received! Verifying signature...', 'info');

                // Step 3: Verify signature on backend
                const verifyRes = await fetch('actions/razorpay_verify.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature,
                        booking_id: <?= (int)($booking['id'] ?? 0) ?>,
                        gig_id: <?= (int)($gig['id'] ?? 0) ?>,
                        client_email: "support@dalavix.com"
                    })
                });

                const verifyData = await verifyRes.json();

                if (verifyData.success) {
                    showToast('✨ Payment verified & Email receipt sent via Hostinger SMTP!', 'success');
                    setTimeout(() => {
                        window.location.href = `my_bookings.php?client_name=${encodeURIComponent('<?= addslashes($clientName) ?>')}&paid=1`;
                    }, 1200);
                } else {
                    showToast(verifyData.message || 'Signature verification failed', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            },
            "modal": {
                "ondismiss": function() {
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            }
        };

        const rzp = new Razorpay(options);
        rzp.open();
    } catch (err) {
        console.error(err);
        showToast('Network error initializing Razorpay', 'error');
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

function autoFillSandboxCard() {
    document.getElementById('card-number').value = '4242 4242 4242 4242';
    document.getElementById('card-expiry').value = '12/28';
    document.getElementById('card-cvc').value = '888';
    showToast('✨ Test Card credentials auto-filled', 'success');
}

// Format card number with auto spaces
document.getElementById('card-number')?.addEventListener('input', (e) => {
    let val = e.target.value.replace(/\D/g, '');
    val = val.substring(0, 16);
    let formatted = val.match(/.{1,4}/g)?.join(' ') || val;
    e.target.value = formatted;
});

// Format expiry MM/YY
document.getElementById('card-expiry')?.addEventListener('input', (e) => {
    let val = e.target.value.replace(/\D/g, '');
    if (val.length >= 2) {
        val = val.substring(0, 2) + '/' + val.substring(2, 4);
    }
    e.target.value = val;
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
