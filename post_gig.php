<?php
/**
 * SkillSwap - Post a Gig (Dedicated Endpoint / Creator View)
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 */

require_once __DIR__ . '/includes/functions.php';

// Force role to creator
$_SESSION['active_role'] = 'creator';

header('Location: creator.php#post-gig-section');
exit;
