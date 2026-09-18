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

$isCreator = ($activePersona['type'] === 'creator');
$currentCreatorId = $isCreator ? (int)$activePersona['id'] : 1;
$creatorData = DEMO_CREATORS[$currentCreatorId] ?? DEMO_CREATORS[1];

$statusFilter = $_GET['status'] ?? null;
$creatorBookings = $isCreator ? getCreatorBookings($currentCreatorId, $statusFilter) : [];

// Calculate metrics
$allBookings = $isCreator ? getCreatorBookings($currentCreatorId, null) : [];
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
$myGigs = $isCreator ? $myGigsStmt->fetchAll() : [];

require_once __DIR__ . '/includes/header.php';
?>

<main class="container" style="padding-top: 2rem;">
    <?php if (!$isCreator): ?>
        <!-- Client Guidance Banner: Enforcing Role Boundary -->
        <div class="glass-panel" style="padding: 1.75rem 2rem; margin-bottom: 2.5rem; border: 1px solid #e2cb9c; background: #fffdfa; border-radius: var(--radius-lg); box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
            <div style="display: flex; align-items: flex-start; gap: 1.25rem; flex-wrap: wrap;">
                <div style="font-size: 2.2rem; line-height: 1;">💼</div>
                <div style="flex: 1; min-width: 280px;">
                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.4rem; flex-wrap: wrap;">
                        <span class="category-tag" style="background: #fef3c7; color: #92400e; font-weight: 700; border: 1px solid #fde68a;">
                            Client Mode Active
                        </span>
                        <h2 style="font-size: 1.35rem; color: var(--ink); margin: 0;">Viewing as Client: <?= h($activePersona['name']) ?></h2>
                    </div>
                    <p style="margin-bottom: 1.25rem; font-size: 0.95rem; color: var(--ink-soft); line-height: 1.65;">
                        The <strong>Creator Hub &amp; Gig Studio</strong> is reserved for <strong>Creators</strong> to post service listings and manage incoming client contracts. As a client, your core workflow is browsing gigs in the marketplace and tracking your inquiries in <strong>My Bookings</strong>.
                    </p>
                    <div style="margin-bottom: 1.25rem;">
                        <div style="font-size: 0.88rem; color: var(--ink); margin-bottom: 0.6rem; font-weight: 700;">
                            ✨ Switch to a Creator persona to post gigs or manage incoming inquiries:
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.6rem;">
                            <?php foreach (DEMO_CREATORS as $dc): ?>
                                <a href="creator.php?as_creator=<?= $dc['id'] ?>" class="btn btn-sm btn-secondary" style="border: 1px solid var(--line); font-weight: 600;">
                                    <span>👤 <?= h($dc['name']) ?> (<?= h($dc['category']) ?>)</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                        <a href="index.php" class="btn btn-primary btn-sm"><span>← Explore Marketplace</span></a>
                        <a href="my_bookings.php" class="btn btn-secondary btn-sm"><span>View My Bookings</span></a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Creator Profile Header -->
    <div class="glass-panel" style="padding: 2rem; margin-bottom: 2.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem; border: 1px solid var(--line); background: var(--paper);">
        <div style="display: flex; align-items: center; gap: 1.25rem;">
            <img src="<?= h($creatorData['avatar']) ?>" alt="<?= h($creatorData['name']) ?>" 
                 style="width: 72px; height: 72px; border-radius: 50%; object-fit: cover; border: 2px solid var(--line); box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
            <div>
                <div style="display: flex; align-items: center; gap: 0.6rem;">
                    <h1 style="font-size: 1.75rem; color: var(--ink); margin: 0;"><?= h($creatorData['name']) ?></h1>
                    <span class="category-tag" style="background: var(--accent-soft); color: var(--accent); border: 1px solid var(--border-ice); font-weight: 600;">
                        Verified Creator
                    </span>
                </div>
                <p style="font-size: 0.95rem; color: var(--ink-soft); margin-top: 0.25rem;"><?= h($creatorData['role']) ?></p>
            </div>
        </div>

        <div style="display: flex; gap: 0.75rem;">
            <?php if ($isCreator): ?>
                <a href="#post-gig-section" class="btn btn-primary">
                    <span>+ Create New Gig</span>
                </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary">
                <span>Marketplace View</span>
            </a>
        </div>
    </div>

    <!-- Creator Metrics Row -->
    <section class="dashboard-metrics">
        <div class="metric-card">
            <div class="metric-icon" style="color: var(--accent);">⚡</div>
            <div>
                <div class="metric-val"><?= count($myGigs) ?></div>
                <div class="metric-lbl">Active Gigs</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon" style="color: #b45309; background: #fef3c7;">⏳</div>
            <div>
                <div class="metric-val"><?= $pendingCount ?></div>
                <div class="metric-lbl">Pending Requests</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon" style="color: #15803d; background: #dcfce7;">✨</div>
            <div>
                <div class="metric-val"><?= $acceptedCount ?></div>
                <div class="metric-lbl">Accepted Projects</div>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon" style="color: var(--accent); background: var(--accent-soft);">💎</div>
            <div>
                <div class="metric-val"><?= formatRate($totalRevenue) ?></div>
                <div class="metric-lbl">Booked Volume</div>
            </div>
        </div>
    </section>

    <!-- SECTION 1: Creator Dashboard Bookings (Feature 4 & DP1) -->
    <section style="margin-bottom: 4rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 class="section-title">Client Bookings Dashboard</h2>
                <p style="color: var(--ink-soft); font-size: 0.95rem;">Review, accept, or decline client bookings on your gigs in real time.</p>
            </div>

            <!-- Status Tabs -->
            <div style="display: flex; gap: 0.5rem; background: var(--paper-warm); padding: 0.35rem; border-radius: var(--radius-md); border: 1px solid var(--line);">
                <a href="creator.php<?= $isCreator ? '?as_creator=' . $currentCreatorId : '' ?>" class="btn btn-sm <?= empty($statusFilter) ? 'btn-primary' : 'btn-secondary' ?>">
                    All (<?= count($allBookings) ?>)
                </a>
                <a href="creator.php?<?= $isCreator ? 'as_creator=' . $currentCreatorId . '&' : '' ?>status=Pending" class="btn btn-sm <?= $statusFilter === 'Pending' ? 'btn-primary' : 'btn-secondary' ?>">
                    Pending (<?= $pendingCount ?>)
                </a>
                <a href="creator.php?<?= $isCreator ? 'as_creator=' . $currentCreatorId . '&' : '' ?>status=Accepted" class="btn btn-sm <?= $statusFilter === 'Accepted' ? 'btn-primary' : 'btn-secondary' ?>">
                    Accepted (<?= $acceptedCount ?>)
                </a>
                <a href="creator.php?<?= $isCreator ? 'as_creator=' . $currentCreatorId . '&' : '' ?>status=Declined" class="btn btn-sm <?= $statusFilter === 'Declined' ? 'btn-primary' : 'btn-secondary' ?>">
                    Declined (<?= $declinedCount ?>)
                </a>
            </div>
        </div>

        <?php if (empty($creatorBookings)): ?>
            <div class="glass-panel" style="padding: 3.5rem 2rem; text-align: center; border: 1px solid var(--line);">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">📂</div>
                <h4 style="color: var(--ink); margin-bottom: 0.35rem; font-size: 1.25rem;">No bookings found</h4>
                <p style="color: var(--ink-soft); font-size: 0.92rem;">
                    <?= !empty($statusFilter) ? "No bookings with status '{$statusFilter}'." : "You haven't received any bookings yet. Gigs posted will receive client inquiries here." ?>
                </p>
            </div>
        <?php else: ?>
            <div>
                <?php foreach ($creatorBookings as $b): ?>
                    <div class="booking-item-card" id="booking-card-<?= $b['id'] ?>" style="background: var(--paper); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                        <div class="booking-item-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                            <div>
                                <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">Booking #<?= $b['id'] ?> &bull; <?= date('M d, Y', strtotime($b['created_at'])) ?></span>
                                <h3 style="font-size: 1.35rem; color: var(--ink); margin-top: 0.2rem;"><?= h($b['gig_title']) ?></h3>
                            </div>

                            <div style="display: flex; align-items: center; gap: 1rem;">
                                <div style="font-family: var(--font-heading); font-weight: 700; font-size: 1.4rem; color: var(--accent);">
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

                        <div style="background: var(--paper-warm); padding: 1.1rem 1.35rem; border-radius: var(--radius-md); border: 1px solid var(--line); margin-bottom: 1.25rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem; flex-wrap: wrap; gap: 0.5rem;">
                                <span style="color: var(--ink);">Client: <strong style="color: var(--ink);"><?= h($b['client_name']) ?></strong></span>
                                <span style="color: var(--text-muted);">Target Delivery: <?= !empty($b['booked_date']) ? date('M d, Y', strtotime($b['booked_date'])) : 'Flexible' ?></span>
                            </div>
                            <p style="font-size: 0.93rem; color: var(--ink); line-height: 1.55;">
                                "<?= h($b['message']) ?>"
                            </p>

                            <?php if ($b['status'] === 'Declined' && !empty($b['decline_reason'])): ?>
                                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid #fed7aa; font-size: 0.88rem; color: #9a3412;">
                                    <strong>DP1 Rejection Reason provided:</strong> <?= h($b['decline_reason']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                            <span style="font-size: 0.88rem; color: var(--ink-soft);">
                                Category: <strong style="color: var(--ink);"><?= h($b['category']) ?></strong>
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
                                    <span style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">Status finalized: <?= h($b['status']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- SECTION 2: Post a Gig Form (Feature 1) -->
    <section id="post-gig-section" class="glass-panel" style="padding: 2.5rem; margin-bottom: 4rem; border: 1px solid var(--line); background: var(--paper);">
        <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--line); padding-bottom: 1.25rem;">
            <div class="hero-pill" style="margin-bottom: 0.5rem; font-size: 0.8rem;">Feature 1: Post a Gig</div>
            <h2 class="section-title">List a New Creator Gig</h2>
            <p style="color: var(--ink-soft); font-size: 0.95rem;">Publish your expertise to the marketplace. Gig will be tied to your creator identity and immediately searchable.</p>
        </div>

        <?php if (!empty($_GET['posted'])): ?>
            <div style="background: #edf7f0; border: 1px solid #b9d7c0; padding: 1rem 1.5rem; border-radius: var(--radius-md); margin-bottom: 2rem; color: var(--success); font-weight: 600;">
                ✨ <strong>Success!</strong> Your gig has been posted and is now live on the marketplace.
            </div>
        <?php endif; ?>

        <?php if (!$isCreator): ?>
            <!-- Locked State for Clients -->
            <div style="background: var(--paper-warm); border: 1px dashed var(--line); border-radius: var(--radius-md); padding: 2.5rem 2rem; text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🔒</div>
                <h3 style="color: var(--ink); font-size: 1.35rem; margin-bottom: 0.5rem;">Creator Identity Required to Post Gigs</h3>
                <p style="max-width: 540px; margin: 0 auto 1.5rem; font-size: 0.95rem; color: var(--ink-soft); line-height: 1.6;">
                    You are currently viewing as Client <strong><?= h($activePersona['name']) ?></strong>. Gigs must be authored by a verified Creator. Switch identity to list a new service:
                </p>
                <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 0.75rem;">
                    <a href="creator.php?as_creator=1#post-gig-section" class="btn btn-primary">
                        <span>👤 Switch to Elena Rostova (Design) &amp; Post</span>
                    </a>
                    <a href="creator.php?as_creator=2#post-gig-section" class="btn btn-secondary">
                        <span>👤 Switch to Marcus Vance (Coding)</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <form id="post-gig-form" action="actions/post_gig.php" method="POST">
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
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
