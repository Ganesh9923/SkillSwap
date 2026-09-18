<?php
/**
 * SkillSwap - Client "My Bookings" Tracker & DP1 Rejection Routing
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Features:
 * - Feature 5: My Bookings (Client sees bookings with current status: Pending / Accepted / Declined)
 * - DP1: Transparent rejection reasons, 1-click alternative discovery, re-booking workflow
 */

require_once __DIR__ . '/includes/functions.php';

$pageTitle = "My Bookings";
$activePersona = getActivePersona();

$isCreator = ($activePersona['type'] === 'creator');
$clientName = ($activePersona['type'] === 'client') ? $activePersona['name'] : ($_GET['client_name'] ?? DEMO_CLIENTS[1]['name']);
$statusFilter = $_GET['status'] ?? null;

$bookings = getClientBookings($clientName, $statusFilter);
$allBookings = getClientBookings($clientName, null);

$pendingCount = 0;
$acceptedCount = 0;
$declinedCount = 0;

foreach ($allBookings as $b) {
    if ($b['status'] === 'Pending') $pendingCount++;
    if ($b['status'] === 'Accepted') $acceptedCount++;
    if ($b['status'] === 'Declined') $declinedCount++;
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 2.5rem;">
    <?php if ($isCreator): ?>
        <!-- Creator Notice Banner: Enforcing Role Guidance -->
        <div class="glass-panel reveal" style="padding: 1.75rem 2rem; margin-bottom: 2.5rem; border: 1px solid rgba(56, 189, 248, 0.4); background: linear-gradient(135deg, rgba(56, 189, 248, 0.08), rgba(15, 23, 42, 0.7));">
            <div style="display: flex; align-items: flex-start; gap: 1.25rem; flex-wrap: wrap;">
                <div style="font-size: 2.2rem; line-height: 1;">👑</div>
                <div style="flex: 1; min-width: 280px;">
                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.4rem; flex-wrap: wrap;">
                        <span class="category-tag" style="background: rgba(56, 189, 248, 0.2); color: var(--ice-cyan); border-color: rgba(56, 189, 248, 0.4);">
                            Creator Mode Active
                        </span>
                        <h2 style="font-size: 1.35rem; color: #fff; margin: 0;">Viewing as Creator: <?= h($activePersona['name']) ?></h2>
                    </div>
                    <p class="text-muted" style="margin-bottom: 1.25rem; font-size: 0.92rem; line-height: 1.6;">
                        This page tracks bookings sent by <strong>Clients</strong>. To review, accept, or decline incoming bookings on your gigs, visit your <strong><a href="creator.php" style="color: var(--ice-cyan); text-decoration: underline;">Creator Hub Dashboard</a></strong>. To view inquiries under a client identity, switch below:
                    </p>
                    <div style="margin-bottom: 1.25rem;">
                        <div style="font-size: 0.85rem; color: var(--ice-200); margin-bottom: 0.6rem; font-weight: 600;">
                            ✨ Switch to a Client persona to track client bookings:
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                            <?php foreach (DEMO_CLIENTS as $dcl): ?>
                                <a href="my_bookings.php?as_client=<?= urlencode($dcl['name']) ?>" class="btn btn-sm btn-secondary" style="border: 1px solid var(--border-ice);">
                                    <span>🏢 <?= h($dcl['name']) ?> (<?= h($dcl['company']) ?>)</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <a href="creator.php" class="btn btn-primary btn-sm"><span>Go to Creator Dashboard</span></a>
                        <a href="index.php" class="btn btn-secondary btn-sm"><span>Marketplace</span></a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Client Hub Banner -->
    <div class="glass-panel reveal" style="padding: 2rem; margin-bottom: 2.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
        <div>
            <div class="hero-pill" style="margin-bottom: 0.5rem; font-size: 0.8rem;">Feature 5: Client Bookings Tracker</div>
            <h1 style="font-size: 1.8rem; color: #fff;">
                Bookings for <span class="text-gradient-cyan"><?= h($clientName) ?></span>
            </h1>
            <p class="text-muted" style="margin-top: 0.25rem;">
                Track the real-time status of all your submitted creator inquiries and contracts.
            </p>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <a href="index.php" class="btn btn-primary">
                <span>Browse More Gigs</span>
            </a>
        </div>
    </div>

    <!-- Booking Confirmation Toast if just booked -->
    <?php if (!empty($_GET['booked'])): ?>
        <div class="reveal" style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(0, 242, 254, 0.2)); border: 1px solid var(--ice-cyan); padding: 1.25rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <span style="font-size: 1.5rem;">✨</span>
                <div>
                    <strong style="color: #fff;">Booking Request Submitted Successfully!</strong>
                    <div style="color: var(--ice-200); font-size: 0.88rem;">Your request is now in <strong>Pending</strong> status waiting for creator acceptance.</div>
                </div>
            </div>
            <span class="status-badge pending">
                <span class="status-dot"></span>
                Pending
            </span>
        </div>
    <?php endif; ?>

    <!-- Status Filter Tabs -->
    <div class="reveal stagger-1" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;">
        <div style="font-size: 1.1rem; font-weight: 600; color: #fff;">
            Showing <?= count($bookings) ?> <?= !empty($statusFilter) ? h($statusFilter) : 'Total' ?> Inquiries
        </div>

        <div style="display: flex; gap: 0.5rem; background: var(--bg-surface); padding: 0.35rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
            <a href="my_bookings.php?client_name=<?= urlencode($clientName) ?>" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?>">
                All (<?= count($allBookings) ?>)
            </a>
            <a href="my_bookings.php?client_name=<?= urlencode($clientName) ?>&status=Pending" class="btn btn-sm <?= $statusFilter === 'Pending' ? 'btn-primary' : 'btn-secondary' ?>">
                Pending (<?= $pendingCount ?>)
            </a>
            <a href="my_bookings.php?client_name=<?= urlencode($clientName) ?>&status=Accepted" class="btn btn-sm <?= $statusFilter === 'Accepted' ? 'btn-primary' : 'btn-secondary' ?>">
                Accepted (<?= $acceptedCount ?>)
            </a>
            <a href="my_bookings.php?client_name=<?= urlencode($clientName) ?>&status=Declined" class="btn btn-sm <?= $statusFilter === 'Declined' ? 'btn-primary' : 'btn-secondary' ?>">
                Declined (<?= $declinedCount ?>)
            </a>
        </div>
    </div>

    <!-- Bookings List -->
    <section class="reveal stagger-2" style="margin-bottom: 4rem;">
        <?php if (empty($bookings)): ?>
            <div class="glass-panel" style="padding: 3.5rem 2rem; text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">❄️</div>
                <h3 style="color: #fff; font-size: 1.35rem; margin-bottom: 0.5rem;">No bookings found</h3>
                <p class="text-muted" style="max-width: 480px; margin: 0 auto 1.5rem;">
                    <?= !empty($statusFilter) ? "No bookings with status '{$statusFilter}'." : "You haven't booked any creator gigs yet. Explore the marketplace to find top talent." ?>
                </p>
                <a href="index.php" class="btn btn-primary">Explore Available Gigs</a>
            </div>
        <?php else: ?>
            <div>
                <?php foreach ($bookings as $b): ?>
                    <?php 
                        $meta = getCategoryMeta($b['category']);
                        $isDeclined = ($b['status'] === 'Declined');
                        $isAccepted = ($b['status'] === 'Accepted');
                        $isPending  = ($b['status'] === 'Pending');
                    ?>
                    <div class="booking-item-card" id="client-booking-<?= $b['id'] ?>">
                        <div class="booking-item-header">
                            <div>
                                <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.35rem;">
                                    <span class="category-tag" style="color: <?= $meta['color'] ?>; background: <?= $meta['glow'] ?>; border: 1px solid <?= $meta['color'] ?>40;">
                                        <span><?= $meta['icon'] ?></span>
                                        <span><?= h($b['category']) ?></span>
                                    </span>
                                    <span class="text-muted" style="font-size: 0.82rem;">Booking #<?= $b['id'] ?> &bull; Placed <?= date('M d, Y', strtotime($b['created_at'])) ?></span>
                                </div>
                                <h3 style="font-size: 1.25rem; color: #fff;"><?= h($b['gig_title']) ?></h3>
                            </div>

                            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                                <div style="font-family: var(--font-heading); font-weight: 700; font-size: 1.35rem; color: var(--ice-200);">
                                    <?= formatRate($b['rate']) ?>
                                </div>
                                <span class="status-badge <?= strtolower($b['status']) ?>">
                                    <span class="status-dot"></span>
                                    <?= h($b['status']) ?>
                                </span>
                                <?php 
                                    require_once __DIR__ . '/includes/payment.php';
                                    echo getPaymentStatusBadge($b['payment_status'] ?? 'Unpaid');
                                ?>
                            </div>
                        </div>

                        <!-- Creator & Scope Details -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.25rem; background: rgba(15, 23, 42, 0.45); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); margin-bottom: 1.25rem;">
                            <div style="display: flex; align-items: center; gap: 0.85rem;">
                                <img src="<?= h($b['creator_avatar']) ?>" alt="<?= h($b['creator_name']) ?>" 
                                     style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-ice);">
                                <div>
                                    <div style="font-weight: 600; color: #fff;"><?= h($b['creator_name']) ?></div>
                                    <div style="font-size: 0.82rem; color: var(--text-muted);">
                                        Creator &bull; ★ <?= number_format((float)$b['creator_rating'], 2) ?>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 0.25rem;">Target Delivery Date:</div>
                                <div style="font-weight: 600; color: var(--ice-200);">
                                    <?= !empty($b['booked_date']) ? date('F d, Y', strtotime($b['booked_date'])) : 'Flexible Schedule' ?>
                                </div>
                            </div>

                            <div style="grid-column: 1 / -1;">
                                <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 0.25rem;">Submitted Project Scope:</div>
                                <div style="font-size: 0.9rem; color: var(--text-primary); font-style: italic;">
                                    "<?= h($b['message']) ?>"
                                </div>
                            </div>
                        </div>

                        <!-- Payment & Escrow Action Bar -->
                        <?php if (($b['payment_status'] ?? 'Unpaid') === 'Unpaid' && $b['status'] !== 'Declined'): ?>
                            <div style="margin-bottom: 1rem; background: rgba(14, 165, 233, 0.08); border: 1px solid var(--border-ice); padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                                <div style="font-size: 0.88rem; color: var(--ice-200);">
                                    🛡️ <strong>Escrow Unfunded:</strong> Secure your project spot by locking funds into SkillSwap Escrow.
                                </div>
                                <a href="checkout.php?booking_id=<?= $b['id'] ?>" class="btn btn-sm btn-primary">
                                    <span>💳 Pay <?= formatRate($b['rate']) ?> into Escrow</span>
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Status Explanation & DP1 Rejection Resolution -->
                        <?php if ($isPending): ?>
                            <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.2); padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); font-size: 0.88rem; color: #fde68a;">
                                ⏳ <strong>Waiting for Creator Review:</strong> The creator has been notified and will accept or respond with feedback shortly.
                            </div>
                        <?php elseif ($isAccepted): ?>
                            <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25); padding: 0.85rem 1.25rem; border-radius: var(--radius-sm); font-size: 0.88rem; color: #6ee7b7; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                                <div>
                                    ✨ <strong>Booking Confirmed & Active!</strong> The creator has accepted your scope and timeline. Work is underway.
                                </div>
                                <span style="font-weight: 700; color: var(--ice-cyan);">Active Contract #<?= $b['id'] ?></span>
                            </div>
                        <?php elseif ($isDeclined): ?>
                            <!-- DP1 Transparent Rejection & Alternative Pathways -->
                            <div style="background: rgba(244, 63, 94, 0.08); border: 1px solid rgba(244, 63, 94, 0.25); padding: 1.25rem; border-radius: var(--radius-sm);">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem; margin-bottom: 1rem;">
                                    <span style="font-size: 1.4rem;">💡</span>
                                    <div>
                                        <div style="font-weight: 700; color: #fda4af; margin-bottom: 0.2rem;">
                                            DP1 Transparent Feedback: Creator Declined Request
                                        </div>
                                        <div style="font-size: 0.9rem; color: var(--text-primary);">
                                            <strong>Reason:</strong> "<?= h($b['decline_reason'] ?: 'Schedule fully booked for requested timeframe.') ?>"
                                        </div>
                                    </div>
                                </div>

                                <!-- DP1 1-Click Action Alternatives -->
                                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: center; padding-top: 0.75rem; border-top: 1px solid rgba(244, 63, 94, 0.2);">
                                    <span style="font-size: 0.85rem; color: var(--text-secondary);">Next Steps:</span>
                                    
                                    <a href="index.php?category=<?= urlencode($b['category']) ?>#marketplace-grid" class="btn btn-sm btn-primary">
                                        <span>🔍 Find Alternative <?= h($b['category']) ?> Creators</span>
                                    </a>

                                    <button type="button" 
                                            class="btn btn-sm btn-secondary book-gig-btn"
                                            data-gig-id="<?= $b['gig_id'] ?>"
                                            data-gig-title="<?= h($b['gig_title']) ?>"
                                            data-creator-name="<?= h($b['creator_name']) ?>"
                                            data-gig-rate="<?= formatRate($b['rate']) ?>">
                                        <span>🔄 Re-Book with Updated Scope / Date</span>
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
