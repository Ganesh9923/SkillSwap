<?php
/**
 * SkillSwap - Action Handler: Persona Reset
 * Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

$_SESSION['active_role'] = 'client';
$_SESSION['client_name'] = 'Sarah Jenkins';
$_SESSION['creator_id'] = 1;

header('Location: ../index.php');
exit;
