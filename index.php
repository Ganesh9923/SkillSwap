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

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$pageTitle = "Creator Marketplace";
$selectedCategory = $_GET['category'] ?? 'All';
$searchQuery = $_GET['q'] ?? '';
$sortOrder = $_GET['sort'] ?? 'fair';

$gigs = getGigs($selectedCategory, $searchQuery, $sortOrder);
$activePersona = getActivePersona();

require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <!-- Marketplace introduction -->
    <section class="hero-section reveal">
        <div class="hero-pill">
            <span>Creator marketplace</span>
        </div>
        
        <h1 class="display-title">
            Find the right person<br>
            <span class="text-gradient-cyan">for the work.</span>
        </h1>
        
        <p class="hero-subtitle">
            Browse specialised creator services, compare the details, and send a project request in a few clear steps.
        </p>

        <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
            <a href="#marketplace-grid" class="btn btn-primary btn-lg">
                <span>Explore gigs</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M7 17l9.2-9.2M17 17V7H7"/></svg>
            </a>
            <a href="creator.php" class="btn btn-secondary btn-lg">
                <span>List your service</span>
            </a>
        </div>

    </section>

    <!-- Marketplace Filter & Search Engine (Feature 2 & DP3) -->
    <section id="marketplace-grid" class="filter-search-container reveal stagger-2">
        <form method="GET" action="index.php" id="marketplace-filter-form">
            <input type="hidden" name="category" id="marketplace-category-input" value="<?= h($selectedCategory) ?>">
            <div class="search-sort-row">
                <div class="search-box-wrapper" style="position: relative; display: flex; align-items: center; flex: 1;">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="marketplace-search" name="q" class="search-input" 
                           placeholder="Search gigs by title, description, or creator..." 
                           value="<?= h($searchQuery) ?>" autocomplete="off" style="width: 100%;">
                    <button type="button" id="search-clear-btn" style="display: <?= !empty($searchQuery) ? 'inline-flex' : 'none' ?>; position: absolute; right: 12px; background: rgba(255,255,255,0.12); border: none; color: var(--text-muted); cursor: pointer; border-radius: 50%; width: 22px; height: 22px; align-items: center; justify-content: center; font-size: 14px; transition: var(--transition-fast);" title="Clear search">&times;</button>
                </div>

                <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                    <label for="sort-select" style="font-size: 0.85rem; color: var(--text-muted); white-space: nowrap;">Sort by:</label>
                    <select id="sort-select" name="sort" class="sort-select">
                        <option value="fair" <?= $sortOrder === 'fair' ? 'selected' : '' ?>>✨ DP3 Fair Rotation & Merit</option>
                        <option value="newest" <?= $sortOrder === 'newest' ? 'selected' : '' ?>>🕒 Newest Gigs</option>
                        <option value="cheapest" <?= $sortOrder === 'cheapest' ? 'selected' : '' ?>>💎 Rate: Low to High</option>
                        <option value="expensive" <?= $sortOrder === 'expensive' ? 'selected' : '' ?>>👑 Rate: High to Low</option>
                    </select>

                    <button type="submit" class="btn btn-secondary">Filter</button>
                    <?php if (!empty($searchQuery) || (strcasecmp($selectedCategory, 'All') !== 0 && !empty($selectedCategory)) || ($sortOrder !== 'fair' && !empty($sortOrder))): ?>
                        <a href="index.php" class="btn btn-sm btn-danger" id="clear-all-btn" title="Reset Filters">&times; Clear</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Fixed Category Filter Chips -->
            <div class="category-chips" id="category-chips-container">
                <a href="index.php?category=All<?= !empty($searchQuery) ? '&q='.urlencode($searchQuery) : '' ?><?= !empty($sortOrder) && $sortOrder !== 'fair' ? '&sort='.$sortOrder : '' ?>" 
                   class="category-chip <?= (strcasecmp($selectedCategory, 'All') === 0 || empty($selectedCategory)) ? 'active' : '' ?>"
                   data-category="All">
                    🌐 All Categories
                </a>
                <?php foreach (ALLOWED_CATEGORIES as $cat): ?>
                    <?php $meta = getCategoryMeta($cat); ?>
                    <a href="index.php?category=<?= urlencode($cat) ?><?= !empty($searchQuery) ? '&q='.urlencode($searchQuery) : '' ?><?= !empty($sortOrder) && $sortOrder !== 'fair' ? '&sort='.$sortOrder : '' ?>" 
                       class="category-chip <?= strcasecmp($selectedCategory, $cat) === 0 ? 'active' : '' ?>"
                       data-category="<?= h($cat) ?>">
                        <span><?= $meta['icon'] ?></span>
                        <span><?= h($cat) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </form>
    </section>

    <!-- Gig Cards Grid (Feature 2 & Feature 3 Trigger) -->
    <section>
        <div class="gigs-grid" id="marketplace-gigs-grid">
            <div id="no-gigs-found" class="glass-panel" style="<?= empty($gigs) ? 'display: block;' : 'display: none;' ?> padding: 3.5rem 2rem; text-align: center; grid-column: 1 / -1; margin-bottom: 2rem;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🔍</div>
                <h3 style="color: #fff; font-size: 1.35rem; margin-bottom: 0.5rem;">No matching gigs found</h3>
                <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 1.25rem;">Try adjusting your search query, switching categories, or resetting filters.</p>
                <a href="index.php" class="btn btn-primary" id="reset-all-filters-btn">Reset All Filters</a>
            </div>
                <?php foreach ($gigs as $idx => $gig): ?>
                    <?php 
                        $meta = getCategoryMeta($gig['category']); 
                        $staggerClass = 'stagger-' . (($idx % 4) + 1);
                    ?>
                    <article class="gig-card reveal <?= $staggerClass ?>" 
                             id="gig-card-<?= $gig['id'] ?>"
                             data-id="<?= $gig['id'] ?>"
                             data-rate="<?= (float)$gig['rate'] ?>"
                             data-created="<?= strtotime($gig['created_at']) ?>"
                             data-rating="<?= (float)$gig['rating'] ?>"
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
                                <div class="capacity-badge <?= $gig['is_full'] ? 'full' : '' ?>" title="DP2 Capacity: <?= $gig['active_accepted_bookings'] ?> active accepted projects of <?= (int)$gig['max_concurrent_slots'] ?> max">
                                    <?php if ($gig['is_full']): ?>
                                        <span>⚠️ Full (Waitlist)</span>
                                    <?php else: ?>
                                        <span>🟢 <?= $gig['remaining_slots'] ?> slots open</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Booking Action Button (Feature 3 Trigger) -->
                            <div style="display: flex; gap: 0.5rem;">
                                <?php if ($activePersona['type'] === 'creator' && (int)$gig['creator_id'] === (int)$activePersona['id']): ?>
                                    <a href="creator.php" class="btn btn-secondary" style="width: 100%; border: 1px solid var(--ice-cyan); color: var(--ice-cyan);">
                                        <span>👑 Your Gig &bull; Manage on Hub</span>
                                    </a>
                                <?php else: ?>
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
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
