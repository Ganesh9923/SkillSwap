<?php
/**
 * SkillSwap - Creator Hub & Dashboard
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Features:
 * - Feature 1: Post a Gig (Title, Category dropdown, Rate, Description -> MySQL via PDO)
 * - Feature 4: Creator Dashboard (View incoming bookings, Accept/Decline pending bookings with persistence)
 * - DP1: Decline reason logging & feedback
 * - DP2: Concurrency slot tracking
 */

require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Creator Dashboard & Gig Studio";
$activePersona = getActivePersona();

// Ensure creator persona context
$currentCreatorId = ($activePersona['type'] === 'creator') ? (int)$activePersona['id'] : 1;
$creatorData = DEMO_CREATORS[$currentCreatorId] ?? DEMO_CREATORS[1];

$statusFilter = $_GET['status'] ?? null;
$creatorBookings = getCreatorBookings($currentCreatorId, $statusFilter);

// Calculate metrics
$allBookings = getCreatorBookings($currentCreatorId, null);
$pendingCount = 0;
$acceptedCount = 0;
$declinedCount = 0;
$totalRevenue = 0.0;

foreach ($allBookings as $b) {
    if ($b['status'] === 'Pending') $pendingCount++;
    if ($b['status'] === 'Accepted') {
        $acceptedCount++;
        $totalRevenue += (float)$b['rate'];
    }
    if ($b['status'] === 'Declined') $declinedCount++;
}

// Fetch gigs posted by this creator
$pdo = getDB();
$myGigsStmt = $pdo->prepare("SELECT * FROM gigs WHERE creator_id = :cid ORDER BY id DESC");
$myGigsStmt->execute([':cid' => $currentCreatorId]);
$myGigs = $myGigsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 2rem;">
    <!-- Creator Profile Header -->
    <div class="glass-panel reveal" style="padding: 2rem; margin-bottom: 2.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem;">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
            <img src="<?= h($creatorData['avatar']) ?>" alt="<?= h($creatorData['name']) ?>" 
                 style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 3px solid var(--border-ice); box-shadow: 0 0 25px rgba(56, 189, 248, 0.3);">
            <div>
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <h1 style="font-size: 1.6rem; color: #fff;"><?= h($creatorData['name']) ?></h1>
                    <span class="category-tag" style="background: rgba(56, 189, 248, 0.15); color: var(--ice-300); border: 1px solid var(--border-ice);">
                        Verified Creator
                    </span>
                </div>
                <p class="text-muted" style="font-size: 0.95rem; margin-top: 0.2rem;"><?= h($creatorData['role']) ?></p>
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <a href="#post-gig-section" class="btn btn-primary">
                <span>+ Create New Gig</span>
            </a>
            <a href="index.php" class="btn btn-secondary">
                <span>Marketplace View</span>
            </a>
        </div>
    </div>

    <!-- Creator Metrics Row -->
    <section class="dashboard-metrics reveal stagger-1">
        <div class="metric-card">
            <div class="metric-icon" style="color: var(--ice-cyan);">⚡</div>
            <div>
                <div class="metric-val"><?= count($myGigs) ?></div>
                <div class="metric-lbl">Active Gigs</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon" style="color: #fbbf24;">⏳</div>
            <div>
                <div class="metric-val"><?= $pendingCount ?></div>
                <div class="metric-lbl">Pending Requests</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon" style="color: #34d399;">✨</div>
            <div>
                <div class="metric-val"><?= $acceptedCount ?></div>
                <div class="metric-lbl">Accepted Projects</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon" style="color: #818cf8;">💎</div>
            <div>
                <div class="metric-val"><?= formatRate($totalRevenue) ?></div>
                <div class="metric-lbl">Booked Volume</div>
            </div>
        </div>
    </section>

    <!-- SECTION 1: Creator Dashboard Bookings (Feature 4 & DP1) -->
    <section style="margin-bottom: 4rem;" class="reveal stagger-2">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 class="section-title">Client Bookings Dashboard</h2>
                <p class="text-muted">Review, accept, or decline client bookings on your gigs in real time.</p>
            </div>

            <!-- Status Tabs -->
            <div style="display: flex; gap: 0.5rem; background: var(--bg-surface); padding: 0.35rem; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
                <a href="creator.php" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?>">
                    All (<?= count($allBookings) ?>)
                </a>
                <a href="creator.php?status=Pending" class="btn btn-sm <?= $statusFilter === 'Pending' ? 'btn-primary' : 'btn-secondary' ?>">
                    Pending (<?= $pendingCount ?>)
                </a>
                <a href="creator.php?status=Accepted" class="btn btn-sm <?= $statusFilter === 'Accepted' ? 'btn-primary' : 'btn-secondary' ?>">
                    Accepted (<?= $acceptedCount ?>)
                </a>
                <a href="creator.php?status=Declined" class="btn btn-sm <?= $statusFilter === 'Declined' ? 'btn-primary' : 'btn-secondary' ?>">
                    Declined (<?= $declinedCount ?>)
                </a>
            </div>
        </div>

        <?php if (empty($creatorBookings)): ?>
            <div class="glass-panel" style="padding: 3rem; text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">📂</div>
                <h4 style="color: #fff; margin-bottom: 0.25rem;">No bookings found</h4>
                <p class="text-muted" style="font-size: 0.9rem;">
                    <?= !empty($statusFilter) ? "No bookings with status '{$statusFilter}'." : "You haven't received any bookings yet. Gigs posted will receive client inquiries here." ?>
                </p>
            </div>
        <?php else: ?>
            <div>
                <?php foreach ($creatorBookings as $b): ?>
                    <div class="booking-item-card" id="booking-card-<?= $b['id'] ?>">
                        <div class="booking-item-header">
                            <div>
                                <span class="text-muted" style="font-size: 0.8rem;">Booking #<?= $b['id'] ?> &bull; <?= date('M d, Y', strtotime($b['created_at'])) ?></span>
                                <h3 style="font-size: 1.15rem; color: #fff; margin-top: 0.2rem;"><?= h($b['gig_title']) ?></h3>
                            </div>

                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="font-family: var(--font-heading); font-weight: 700; font-size: 1.25rem; color: var(--ice-200);">
                                    <?= formatRate($b['rate']) ?>
                                </div>
                                <div class="status-badge-container">
                                    <span class="status-badge <?= strtolower($b['status']) ?>">
                                        <span class="status-dot"></span>
                                        <?= h($b['status']) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div style="background: rgba(15, 23, 42, 0.5); padding: 1rem 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); margin-bottom: 1.25rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.88rem;">
                                <span>Client: <strong style="color: var(--ice-300);"><?= h($b['client_name']) ?></strong></span>
                                <span class="text-muted">Target Delivery: <?= !empty($b['booked_date']) ? date('M d, Y', strtotime($b['booked_date'])) : 'Flexible' ?></span>
                            </div>
                            <p style="font-size: 0.9rem; color: var(--text-primary); font-style: italic;">
                                "<?= h($b['message']) ?>"
                            </p>

                            <?php if ($b['status'] === 'Declined' && !empty($b['decline_reason'])): ?>
                                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(244, 63, 94, 0.2); font-size: 0.85rem; color: #fda4af;">
                                    <strong>DP1 Rejection Reason provided:</strong> <?= h($b['decline_reason']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                            <span class="text-muted" style="font-size: 0.85rem;">
                                Category: <strong style="color: #fff;"><?= h($b['category']) ?></strong>
                            </span>

                            <div class="booking-actions">
                                <?php if ($b['status'] === 'Pending'): ?>
                                    <button type="button" class="btn btn-sm btn-success btn-accept-booking" data-booking-id="<?= $b['id'] ?>">
                                        <span>✓ Accept Booking</span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-decline-booking" data-booking-id="<?= $b['id'] ?>">
                                        <span>✕ Decline</span>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.85rem;">Status finalized: <?= h($b['status']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- SECTION 2: Post a Gig Form (Feature 1) -->
    <section id="post-gig-section" class="glass-panel reveal stagger-3" style="padding: 2.5rem; margin-bottom: 4rem;">
        <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--border-subtle); padding-bottom: 1.25rem;">
            <div class="hero-pill" style="margin-bottom: 0.5rem; font-size: 0.8rem;">Feature 1: Post a Gig</div>
            <h2 class="section-title">List a New Creator Gig</h2>
            <p class="text-muted">Publish your expertise to the marketplace. Gig will be tied to your creator identity and immediately searchable.</p>
        </div>

        <?php if (!empty($_GET['posted'])): ?>
            <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); padding: 1rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem; color: #34d399;">
                ✨ <strong>Success!</strong> Your gig has been posted and is now live on the marketplace.
            </div>
        <?php endif; ?>

        <form action="actions/post_gig.php" method="POST">
            <!-- Bot Protection Honeypot -->
            <input type="text" name="website_hp" value="" style="display:none !important;" tabindex="-1" autocomplete="off">
            <input type="hidden" name="creator_id" value="<?= $creatorData['id'] ?>">
            <input type="hidden" name="creator_name" value="<?= h($creatorData['name']) ?>">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label" for="gig-title">Gig Title *</label>
                    <input type="text" id="gig-title" name="title" class="form-control" 
                           placeholder="e.g. Next-Gen Glacial UI/UX & Design System" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="gig-category">Category * (Fixed Brief Specs)</label>
                    <select id="gig-category" name="category" class="form-control" required>
                        <option value="" disabled selected>Select Category...</option>
                        <?php foreach (ALLOWED_CATEGORIES as $cat): ?>
                            <option value="<?= h($cat) ?>"><?= h($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label class="form-label" for="gig-rate">Rate ($ USD) *</label>
                    <input type="number" id="gig-rate" name="rate" class="form-control" 
                           placeholder="e.g. 175.00" step="0.01" min="1" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="gig-max-slots">Max Concurrent Slots (DP2 Capacity)</label>
                    <input type="number" id="gig-max-slots" name="max_slots" class="form-control" 
                           value="3" min="1" max="10" title="Defines capacity before showing waitlist warning in DP2">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="gig-description">Detailed Description *</label>
                <textarea id="gig-description" name="description" class="form-control" rows="4" 
                          placeholder="Describe your deliverables, technical stack, revisions included, and requirements from the client..." required></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 1rem; margin-top: 2rem;">
                <button type="reset" class="btn btn-secondary">Clear Form</button>
                <button type="submit" class="btn btn-primary btn-lg">
                    <span>Publish Gig to Marketplace</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </button>
            </div>
        </form>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
