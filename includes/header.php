<?php
/**
 * SkillSwap - Universal Header & Zero-Auth Persona Switcher
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 */

require_once __DIR__ . '/functions.php';

$activePersona = getActivePersona();
$currentPage = basename($_SERVER['PHP_SELF']);

// Count pending bookings for active creator or client to display dynamic badge
$pendingBadgeCount = 0;
if ($activePersona['type'] === 'creator') {
    $creatorBookings = getCreatorBookings((int)$activePersona['id'], 'Pending');
    $pendingBadgeCount = count($creatorBookings);
} else {
    $clientBookings = getClientBookings($activePersona['name'], 'Pending');
    $pendingBadgeCount = count($clientBookings);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' | SkillSwap' : 'SkillSwap — Creator Gig Marketplace' ?></title>
    <meta name="description" content="Next-generation creator gig marketplace with glacial aesthetics, instant zero-auth switching, and real-time project collaboration.">
    <meta name="hackathon-id" content="AZIS-SNTAGG">
    <meta name="team" content="Duo (Ganesh Arun Dalave, Om Dipak Kanase), LPU">
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
</head>
<body>
    <!-- Ambient Glacial Lighting Orbs -->
    <div class="bg-ambient-orb orb-1"></div>
    <div class="bg-ambient-orb orb-2"></div>

    <!-- Universal Zero-Auth Persona Switcher Bar (Constraint #1) -->
    <div class="persona-bar">
        <div class="container persona-bar-content">
            <div class="persona-label">
                <span class="pulse-dot"></span>
                <span>Viewing as: <strong style="color: #fff;"><?= h($activePersona['name']) ?></strong> (<?= ucfirst($activePersona['type']) ?>)</span>
            </div>
            <div class="persona-switcher-controls">
                <label for="persona-select" style="color: var(--text-muted); font-size: 0.8rem;">Switch Identity:</label>
                <select id="persona-select" class="persona-select">
                    <optgroup label="✨ Creator Personas">
                        <?php foreach (DEMO_CREATORS as $c): ?>
                            <option value="creator_<?= $c['id'] ?>" <?= ($activePersona['type'] === 'creator' && $activePersona['id'] == $c['id']) ? 'selected' : '' ?>>
                                👤 Creator: <?= h($c['name']) ?> (<?= h($c['category']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="💼 Client Personas">
                        <?php foreach (DEMO_CLIENTS as $cl): ?>
                            <option value="client_<?= urlencode($cl['name']) ?>" <?= ($activePersona['type'] === 'client' && $activePersona['name'] === $cl['name']) ? 'selected' : '' ?>>
                                🏢 Client: <?= h($cl['name']) ?> (<?= h($cl['company']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
                
                <?php if ($activePersona['type'] === 'creator'): ?>
                    <a href="creator.php" class="btn btn-sm btn-primary">Creator Hub</a>
                <?php else: ?>
                    <a href="my_bookings.php" class="btn btn-sm btn-secondary">My Bookings <?= $pendingBadgeCount > 0 ? "({$pendingBadgeCount})" : '' ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Navigation Header -->
    <header class="site-header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="brand-logo">
                    <div class="brand-icon">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <span>Skill<span class="text-gradient-cyan">Swap</span></span>
                </a>

                <ul class="nav-links">
                    <li><a href="index.php" class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">Marketplace</a></li>
                    <li><a href="creator.php" class="nav-link <?= in_array($currentPage, ['creator.php', 'post_gig.php']) ? 'active' : '' ?>">Creator Hub</a></li>
                    <li>
                        <a href="my_bookings.php" class="nav-link <?= $currentPage === 'my_bookings.php' ? 'active' : '' ?>">
                            My Bookings
                            <?php if ($activePersona['type'] === 'client' && $pendingBadgeCount > 0): ?>
                                <span class="nav-badge-count"><?= $pendingBadgeCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li><a href="verify_email.php" class="nav-link <?= $currentPage === 'verify_email.php' ? 'active' : '' ?>">Email Verification</a></li>
                    <li><a href="setup.php" class="nav-link <?= $currentPage === 'setup.php' ? 'active' : '' ?>" style="color: var(--text-muted); font-size: 0.85rem;">Diagnostics</a></li>
                </ul>

                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <a href="creator.php#post-gig-section" class="btn btn-sm btn-primary">
                        <span>+ Post a Gig</span>
                    </a>
                </div>
            </nav>
        </div>
    </header>
