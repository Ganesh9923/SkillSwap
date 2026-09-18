<?php
/**
 * SkillSwap - Shared Footer & Global Modals
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 */
?>
    <!-- Global Booking Modal (Feature 3 & DP2) -->
    <div id="booking-modal" class="modal-overlay">
        <div class="modal-card">
            <button class="modal-close-btn" data-modal-close aria-label="Close">&times;</button>
            <div style="margin-bottom: 1.5rem;">
                <span class="hero-pill" style="font-size: 0.75rem; padding: 0.2rem 0.6rem; margin-bottom: 0.5rem;">Instant Gig Booking</span>
                <h3 id="modal-gig-title" style="font-size: 1.35rem; color: #fff; margin-bottom: 0.25rem;">Booking Gig</h3>
                <p class="text-muted" style="font-size: 0.88rem;">
                    Creator: <strong id="modal-creator-name" style="color: var(--ice-300);"></strong> &bull; 
                    Rate: <strong id="modal-gig-rate" style="color: var(--ice-cyan);"></strong>
                </p>
            </div>

            <form id="booking-form" method="POST" action="actions/book_gig.php">
                <!-- Bot Protection Honeypot -->
                <input type="text" name="website_hp" value="" style="display:none !important;" tabindex="-1" autocomplete="off">
                <input type="hidden" id="modal-gig-id" name="gig_id" value="">

                <?php if ($activePersona['type'] === 'creator'): ?>
                    <div style="background: rgba(56, 189, 248, 0.1); border: 1px solid var(--border-ice); padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem; font-size: 0.82rem; color: var(--ice-200);">
                        ℹ️ <strong>Creator Active:</strong> You are viewing as Creator (<?= h($activePersona['name']) ?>). Booking is a Client action—select which client persona to book under:
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="client-name-input">Client Persona For Booking *</label>
                        <select id="client-name-input" name="client_name" class="form-control" required>
                            <?php foreach (DEMO_CLIENTS as $cl): ?>
                                <option value="<?= h($cl['name']) ?>">🏢 <?= h($cl['name']) ?> (<?= h($cl['company']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label class="form-label" for="client-name-input">Your Name / Client Identity *</label>
                        <input type="text" id="client-name-input" name="client_name" class="form-control" 
                               value="<?= h($activePersona['name']) ?>" required placeholder="e.g. Sarah Jenkins">
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="booking-date-input">Target Start / Delivery Date</label>
                    <input type="date" id="booking-date-input" name="booked_date" class="form-control" 
                           value="<?= date('Y-m-d', strtotime('+3 days')) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="booking-msg-input">Project Scope / Custom Note</label>
                    <textarea id="booking-msg-input" name="message" class="form-control" rows="3" 
                              placeholder="Briefly describe what you'd like the creator to deliver..."></textarea>
                </div>

                <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        <span>Confirm & Submit Booking</span>
                    </button>
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Global Decline Booking Modal (DP1 Rejection Reason) -->
    <div id="decline-modal" class="modal-overlay">
        <div class="modal-card">
            <button class="modal-close-btn" data-modal-close aria-label="Close">&times;</button>
            <div style="margin-bottom: 1.25rem;">
                <span class="status-badge declined" style="margin-bottom: 0.5rem;">Decision Point 1: Rejection Feedback</span>
                <h3 style="font-size: 1.35rem; color: #fff;">Decline Booking Request</h3>
                <p class="text-muted" style="font-size: 0.88rem; margin-top: 0.25rem;">
                    Provide constructive feedback to the client. This will be visible on their "My Bookings" page along with alternative creator recommendations.
                </p>
            </div>

            <form id="decline-form">
                <input type="hidden" id="decline-booking-id" value="">

                <div class="form-group">
                    <label class="form-label">Primary Reason</label>
                    <select id="decline-reason-select" class="form-control">
                        <option value="Schedule fully committed for this timeframe">Schedule fully committed for this timeframe</option>
                        <option value="Project scope requires specialized tooling outside current focus">Project scope requires specialized tooling</option>
                        <option value="Requested delivery timeline is too tight">Requested delivery timeline is too tight</option>
                        <option value="Budget/scope mismatch for requested deliverables">Budget/scope mismatch</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Custom Note / Advice (Optional)</label>
                    <textarea id="decline-custom-reason" class="form-control" rows="2" placeholder="Optional custom note to help the client adjust their request..."></textarea>
                </div>

                <div style="margin-top: 1.5rem; display: flex; gap: 0.75rem;">
                    <button type="submit" class="btn btn-danger" style="flex: 1;">
                        <span>Decline & Send Feedback</span>
                    </button>
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-top">
                <div style="max-width: 420px;">
                    <div class="brand-logo" style="margin-bottom: 1rem;">
                        <div class="brand-icon">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <span>Skill<span class="text-gradient-cyan">Swap</span></span>
                    </div>
                    <p style="font-size: 0.88rem; line-height: 1.7; margin-bottom: 1.25rem;">
                        A simple creator marketplace for discovering services, sending project requests, and managing bookings without an account barrier.
                    </p>
                    <div class="footer-badge-box">
                        <span>⚡ Hackathon ID:</span>
                        <strong style="color: var(--ice-cyan);">AZIS-SNTAGG</strong>
                    </div>
                </div>

                <div>
                    <h4 style="font-size: 1rem; margin-bottom: 1rem;">How it works</h4>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.6rem; font-size: 0.88rem;">
                        <li><span style="color: var(--accent); font-weight: 600;">Clear feedback:</span> Declined requests include helpful next steps.</li>
                        <li><span style="color: var(--accent); font-weight: 600;">Capacity-aware:</span> Creators can manage active work sustainably.</li>
                        <li><span style="color: var(--accent); font-weight: 600;">Fair discovery:</span> Quality and responsiveness inform ranking.</li>
                    </ul>
                </div>

                <div>
                    <h4 style="font-size: 1rem; margin-bottom: 1rem;">Explore</h4>
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.6rem; font-size: 0.88rem;">
                        <li><a href="index.php" style="color: var(--text-secondary); text-decoration: none;">Browse gigs</a></li>
                        <li><a href="creator.php" style="color: var(--text-secondary); text-decoration: none;">Creator workspace</a></li>
                        <li><a href="my_bookings.php" style="color: var(--text-secondary); text-decoration: none;">My bookings</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <div>
                    &copy; <?= date('Y') ?> SkillSwap &bull; Team <strong>Om's team (Om Dipak Kanase - Leader, Ganesh Arun Dalave - Member 1), LPU</strong>
                </div>
                <div>
                    Track 2: Real-World AI Products &bull; Strict Zero-Auth Grader Compliance
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/marketplace.js"></script>
</body>
</html>
