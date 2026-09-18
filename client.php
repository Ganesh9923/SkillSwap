<?php
/**
 * SkillSwap - Client Hub (Dedicated Client Role View)
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 */

require_once __DIR__ . '/includes/functions.php';

// Force active role to client if visited directly
if (!isset($_GET['as_client']) && !isset($_GET['as_creator'])) {
    $_SESSION['active_role'] = 'client';
}

$activePersona = getActivePersona();
$clientName = $activePersona['name'];

// Forward to index.php with client view or render my_bookings directly if requested
header('Location: index.php?role=client');
exit;
