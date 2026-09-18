<?php
/**
 * SkillSwap - Main Marketplace & Discovery Engine
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 * 
 * Features:
 * - Feature 2: Browse & Search (Category filters, search title+desc, cards with title/cat/rate/creator/desc)
 * - Feature 3: Book a gig modal trigger
 * - DP3: Freshness + response-rate weighted fair ranking
 * - DP2: Concurrency slot indicators
 */

require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Creator Marketplace";
$selectedCategory = $_GET['category'] ?? 'All';
$searchQuery = $_GET['q'] ?? '';
$sortOrder = $_GET['sort'] ?? 'fair';

$gigs = getGigs($selectedCategory, $searchQuery, $sortOrder);
$activePersona = getActivePersona();

require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <!-- Hero Showcase (igloo.inc style) -->
    <section class="hero-section reveal">
        <div class="hero-pill">
            <span>✨ Real-World AI Products Hackathon</span>
            <span style="color: var(--border-subtle);">&bull;</span>
            <span style="color: var(--ice-cyan); font-weight: 700;">Track 2</span>
        </div>
        
        <h1 class="display-title">
            Exceptional Talent.<br>
            <span class="text-gradient-cyan">Glacial Precision.</span>
        </h1>
        
        <p class="hero-subtitle">
            Exchange creator gigs in design, coding, video editing, and generative media. Zero authentication barrier for instant hackathon evaluation.
        </p>

        <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
            <a href="#marketplace-grid" class="btn btn-primary btn-lg">
                <span>Explore Marketplace</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 17l9.2-9.2M17 17V7H7"/></svg>
            </a>
            <a href="creator.php" class="btn btn-secondary btn-lg">
                <span>Post as Creator</span>
            </a>
        </div>

        <div class="hero-stats reveal stagger-1">
            <div class="hero-stat-card">
                <div class="hero-stat-val"><?= count($gigs) ?>+</div>
                <div class="hero-stat-lbl">Active Verified Gigs</div>
            </div>
            <div class="hero-stat-card">
                <div class="hero-stat-val">100%</div>
                <div class="hero-stat-lbl">Zero-Auth Access</div>
            </div>
            <div class="hero-stat-card">
                <div class="hero-stat-val">sub-50ms</div>
                <div class="hero-stat-lbl">PDO Prepared Queries</div>
            </div>
            <div class="hero-stat-card">
                <div class="hero-stat-val">3 DPs</div>
                <div class="hero-stat-lbl">Architectural Decisions</div>
            </div>
        </div>
    </section>

    <!-- Marketplace Filter & Search Engine (Feature 2 & DP3) -->
    <section id="marketplace-grid" class="filter-search-container reveal stagger-2">
        <form method="GET" action="index.php#marketplace-grid">
            <div class="search-sort-row">
                <div class="search-box-wrapper">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="marketplace-search" name="q" class="search-input" 
                           placeholder="Search gigs by title, description, or creator..." 
                           value="<?= h($searchQuery) ?>">
                </div>

                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <label for="sort-select" style="font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;">Sort by:</label>
                    <select id="sort-select" name="sort" class="sort-select">
                        <option value="fair" <?= $sortOrder === 'fair' ? 'selected' : '' ?>>✨ DP3 Fair Rotation & Merit</option>
                        <option value="newest" <?= $sortOrder === 'newest' ? 'selected' : '' ?>>🕒 Newest Gigs</option>
                        <option value="cheapest" <?= $sortOrder === 'cheapest' ? 'selected' : '' ?>>💎 Rate: Low to High</option>
                        <option value="expensive" <?= $sortOrder === 'expensive' ? 'selected' : '' ?>>👑 Rate: High to Low</option>
                    </select>

                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <?php if (!empty($searchQuery) || $selectedCategory !== 'All' || $sortOrder !== 'fair'): ?>
                        <a href="index.php#marketplace-grid" class="btn btn-sm btn-danger" title="Reset Filters">&times; Clear</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Fixed Category Filter Chips -->
            <div class="category-chips">
                <a href="index.php?category=All<?= !empty($searchQuery) ? '&q='.urlencode($searchQuery) : '' ?><?= !empty($sortOrder) ? '&sort='.$sortOrder : '' ?>#marketplace-grid" 
                   class="category-chip <?= $selectedCategory === 'All' ? 'active' : '' ?>">
                    🌐 All Categories
                </a>
                <?php foreach (ALLOWED_CATEGORIES as $cat): ?>
                    <?php $meta = getCategoryMeta($cat); ?>
                    <a href="index.php?category=<?= urlencode($cat) ?><?= !empty($searchQuery) ? '&q='.urlencode($searchQuery) : '' ?><?= !empty($sortOrder) ? '&sort='.$sortOrder : '' ?>#marketplace-grid" 
                       class="category-chip <?= $selectedCategory === $cat ? 'active' : '' ?>">
                        <span><?= $meta['icon'] ?></span>
                        <span><?= h($cat) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </form>
    </section>

    <!-- Gig Cards Grid (Feature 2 & Feature 3 Trigger) -->
    <section>
        <?php if (empty($gigs)): ?>
            <div class="glass-panel" style="padding: 4rem 2rem; text-align: center; margin-bottom: 4rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">❄️</div>
                <h3 style="color: #fff; font-size: 1.5rem; margin-bottom: 0.5rem;">No gigs found</h3>
                <p class="text-muted" style="max-width: 480px; margin: 0 auto 1.5rem;">
                    We couldn't find any gigs matching your current filters. Try changing your search query or reset category filters.
                </p>
                <a href="index.php#marketplace-grid" class="btn btn-primary">Reset Filters</a>
            </div>
        <?php else: ?>
            <div class="gigs-grid">
                <?php foreach ($gigs as $idx => $gig): ?>
                    <?php 
                        $meta = getCategoryMeta($gig['category']); 
                        $staggerClass = 'stagger-' . (($idx % 4) + 1);
                    ?>
                    <article class="gig-card reveal <?= $staggerClass ?>" 
                             id="gig-card-<?= $gig['id'] ?>"
                             data-id="<?= $gig['id'] ?>"
                             data-title="<?= h($gig['title']) ?>"
                             data-category="<?= h($gig['category']) ?>"
                             data-creator="<?= h($gig['creator_name']) ?>"
                             data-desc="<?= h($gig['description']) ?>">
                        
                        <div>
                            <div class="gig-card-header">
                                <span class="category-tag" style="color: <?= $meta['color'] ?>; background: <?= $meta['glow'] ?>; border: 1px solid <?= $meta['color'] ?>40;">
                                    <span><?= $meta['icon'] ?></span>
                                    <span><?= h($gig['category']) ?></span>
                                </span>

                                <div class="gig-rate">
                                    <?= formatRate($gig['rate']) ?>
                                </div>
                            </div>

                            <h3 class="gig-title">
                                <?= h($gig['title']) ?>
                            </h3>

                            <p class="gig-description">
                                <?= h($gig['description']) ?>
                            </p>
                        </div>

                        <div>
                            <!-- Creator Identity & Meta (supporting DP3 ranking cues) -->
                            <div class="creator-info-row">
                                <img src="<?= h($gig['avatar']) ?>" alt="<?= h($gig['creator_name']) ?>" class="creator-avatar" loading="lazy">
                                <div class="creator-details">
                                    <div class="creator-name"><?= h($gig['creator_name']) ?></div>
                                    <div class="creator-meta">
                                        <span class="creator-rating">★ <?= number_format((float)$gig['rating'], 2) ?></span>
                                        <span>&bull;</span>
                                        <span title="DP3 Metric: Response Rate"><?= $gig['response_rate'] ?>% response</span>
                                    </div>
                                </div>

                                <!-- DP2 Concurrency Slot Badge -->
                                <div class="capacity-badge <?= $gig['is_full'] ? 'full' : '' ?>" title="DP2 Capacity: <?= $gig['active_accepted_bookings'] ?> active accepted projects">
                                    <?php if ($gig['is_full']): ?>
                                        <span>⚠️ Full</span>
                                    <?php else: ?>
                                        <span>🟢 <?= $gig['remaining_slots'] ?> slots</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Booking Action Button (Feature 3 Trigger) -->
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" 
                                        class="btn btn-primary book-gig-btn" 
                                        style="width: 100%;"
                                        data-gig-id="<?= $gig['id'] ?>"
                                        data-gig-title="<?= h($gig['title']) ?>"
                                        data-creator-name="<?= h($gig['creator_name']) ?>"
                                        data-gig-rate="<?= formatRate($gig['rate']) ?>">
                                    <span>Book Gig</span>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
