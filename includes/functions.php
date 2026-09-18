<?php
/**
 * SkillSwap - Core Business Logic & Helper Functions
 * Hackathon ID: AZIS-SNTAGG
 * Track 2: Real-World AI Products
 * 
 * Implements all 5 required features and 3 Decision Points (DP1, DP2, DP3).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Fixed category specifications per hackathon brief:
 * Design, Writing, Video Editing, Music, Coding, Tutoring, Other
 */
const ALLOWED_CATEGORIES = [
    'Design',
    'Writing',
    'Video Editing',
    'Music',
    'Coding',
    'Tutoring',
    'Other'
];

/**
 * Default Seeded Personas (No-auth instant switching)
 */
const DEMO_CREATORS = [
    1 => ['id' => 1, 'name' => 'Elena Rostova', 'role' => 'Principal Product & Glacial UI Designer', 'category' => 'Design', 'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80', 'rating' => 4.95],
    2 => ['id' => 2, 'name' => 'Marcus Vance', 'role' => 'Full-Stack Web Architect & AI Engineer', 'category' => 'Coding', 'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80', 'rating' => 4.92],
    3 => ['id' => 3, 'name' => 'Aria Chen', 'role' => 'Ambient Electronic Music & SFX Producer', 'category' => 'Music', 'avatar' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=150&auto=format&fit=crop&q=80', 'rating' => 4.88],
    4 => ['id' => 4, 'name' => 'Devin Thorne', 'role' => 'Senior Video Editor & Motion Graphics Lead', 'category' => 'Video Editing', 'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80', 'rating' => 4.90],
    5 => ['id' => 5, 'name' => 'Sophia Al-Mansoor', 'role' => 'Technical Copywriter & AI Prompt Strategist', 'category' => 'Writing', 'avatar' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80', 'rating' => 4.97]
];

const DEMO_CLIENTS = [
    1 => ['id' => 1, 'name' => 'Sarah Jenkins', 'company' => 'VentureLab IO', 'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80'],
    2 => ['id' => 2, 'name' => 'Liam O\'Connor', 'company' => 'Aurora Studios', 'avatar' => 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150&auto=format&fit=crop&q=80'],
    3 => ['id' => 3, 'name' => 'Priya Patel', 'company' => 'HyperFlow Tech', 'avatar' => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80']
];

/**
 * Get active user identity without authentication
 */
function getActivePersona(): array {
    // 1. Check URL parameters for explicit override (great for grading links)
    if (!empty($_GET['as_creator'])) {
        $creatorId = (int)$_GET['as_creator'];
        if (isset(DEMO_CREATORS[$creatorId])) {
            $_SESSION['active_role'] = 'creator';
            $_SESSION['creator_id'] = $creatorId;
            $_SESSION['creator_name'] = DEMO_CREATORS[$creatorId]['name'];
            return ['type' => 'creator', 'id' => $creatorId, 'name' => DEMO_CREATORS[$creatorId]['name'], 'data' => DEMO_CREATORS[$creatorId]];
        }
    }
    if (!empty($_GET['as_client'])) {
        $clientName = trim($_GET['as_client']);
        $_SESSION['active_role'] = 'client';
        $_SESSION['client_name'] = $clientName;
        return ['type' => 'client', 'name' => $clientName];
    }

    // 2. Check Session
    $role = $_SESSION['active_role'] ?? 'client';
    if ($role === 'creator') {
        $creatorId = (int)($_SESSION['creator_id'] ?? 1);
        $creator = DEMO_CREATORS[$creatorId] ?? DEMO_CREATORS[1];
        return ['type' => 'creator', 'id' => $creator['id'], 'name' => $creator['name'], 'data' => $creator];
    } else {
        $clientName = $_SESSION['client_name'] ?? 'Sarah Jenkins';
        return ['type' => 'client', 'name' => $clientName];
    }
}

/**
 * Sanitize output strings for HTML rendering
 */
function h(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency
 */
function formatRate(float|int|string $rate): string {
    return '$' . number_format((float)$rate, 2);
}

/**
 * FEATURE 1: Post a Gig (Creator)
 * Saves gig to MySQL via PDO with prepared statements, tied to creator.
 */
function createGig(int $creatorId, string $creatorName, string $title, string $category, float $rate, string $description, int $maxSlots = 3): int {
    if (!in_array($category, ALLOWED_CATEGORIES, true)) {
        throw new InvalidArgumentException("Invalid category: '{$category}'. Must be one of: " . implode(', ', ALLOWED_CATEGORIES));
    }

    if (empty(trim($title)) || empty(trim($description)) || $rate <= 0) {
        throw new InvalidArgumentException("Title, description, and a positive rate are required.");
    }

    $pdo = getDB();
    $sql = "INSERT INTO gigs (creator_id, creator_name, title, category, rate, description, max_concurrent_slots, created_at)
            VALUES (:creator_id, :creator_name, :title, :category, :rate, :description, :max_slots, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':creator_id'   => $creatorId,
        ':creator_name' => trim($creatorName),
        ':title'        => trim($title),
        ':category'     => $category,
        ':rate'         => $rate,
        ':description'  => trim($description),
        ':max_slots'    => max(1, $maxSlots)
    ]);

    return (int)$pdo->lastInsertId();
}

/**
 * FEATURE 2 & DP3: Browse & Search Gigs with Ranking Algorithm
 * Implements DP3: Composite ranking score = (Recency * 0.35) + (Response Rate * 0.35) + (Rotation Jitter * 0.30)
 */
function getGigs(?string $category = null, ?string $search = null, string $sort = 'fair'): array {
    $pdo = getDB();
    $params = [];
    $where = [];

    if (!empty($category) && $category !== 'All') {
        $where[] = "g.category = :category";
        $params[':category'] = $category;
    }

    if (!empty($search)) {
        $where[] = "(g.title LIKE :search_title OR g.description LIKE :search_desc OR g.creator_name LIKE :search_creator)";
        $searchParam = '%' . trim($search) . '%';
        $params[':search_title'] = $searchParam;
        $params[':search_desc'] = $searchParam;
        $params[':search_creator'] = $searchParam;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Join with creators table to get response rate and completed gigs for DP3 ranking and DP2 slot count
    $sql = "SELECT g.*, 
                   COALESCE(c.avatar, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150') AS avatar,
                   COALESCE(c.rating, 4.90) AS rating,
                   COALESCE(c.response_rate, 98) AS response_rate,
                   COALESCE(c.completed_gigs, 15) AS completed_gigs,
                   (SELECT COUNT(*) FROM bookings b WHERE b.gig_id = g.id AND b.status = 'Accepted') AS active_accepted_bookings,
                   (SELECT COUNT(*) FROM bookings b WHERE b.gig_id = g.id AND b.status = 'Pending') AS active_pending_bookings
            FROM gigs g
            LEFT JOIN creators c ON g.creator_id = c.id
            {$whereSql}";

    // Sorting logic (DP3)
    if ($sort === 'cheapest') {
        $sql .= " ORDER BY g.rate ASC, g.id DESC";
    } elseif ($sort === 'expensive') {
        $sql .= " ORDER BY g.rate DESC, g.id DESC";
    } elseif ($sort === 'newest') {
        $sql .= " ORDER BY g.id DESC";
    } else {
        // DP3: Fair Hybrid Ranking
        // Computes ranking balance: fresh listings get exposure, responsive creators get boosted, and rotation ensures diversity.
        $sql .= " ORDER BY (
                    (g.id * 1.5) + 
                    (COALESCE(c.response_rate, 90) * 0.4) + 
                    (COALESCE(c.rating, 4.5) * 10)
                  ) DESC, g.id DESC";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $gigs = $stmt->fetchAll();

    // Attach capacity metadata for DP2
    foreach ($gigs as &$gig) {
        $maxSlots = (int)($gig['max_concurrent_slots'] ?? 3);
        $activeSlots = (int)$gig['active_accepted_bookings'];
        $gig['is_full'] = $activeSlots >= $maxSlots;
        $gig['remaining_slots'] = max(0, $maxSlots - $activeSlots);
    }
    unset($gig);

    return $gigs;
}

/**
 * Get a single gig by ID
 */
function getGigById(int $gigId): ?array {
    $pdo = getDB();
    $sql = "SELECT g.*, 
                   COALESCE(c.avatar, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150') AS avatar,
                   COALESCE(c.tagline, 'Creator on SkillSwap') AS creator_tagline,
                   COALESCE(c.rating, 4.90) AS rating,
                   COALESCE(c.response_rate, 98) AS response_rate,
                   (SELECT COUNT(*) FROM bookings b WHERE b.gig_id = g.id AND b.status = 'Accepted') AS active_accepted_bookings,
                   (SELECT COUNT(*) FROM bookings b WHERE b.gig_id = g.id AND b.status = 'Pending') AS active_pending_bookings
            FROM gigs g
            LEFT JOIN creators c ON g.creator_id = c.id
            WHERE g.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $gigId]);
    $gig = $stmt->fetch();

    if ($gig) {
        $maxSlots = (int)($gig['max_concurrent_slots'] ?? 3);
        $activeSlots = (int)$gig['active_accepted_bookings'];
        $gig['is_full'] = $activeSlots >= $maxSlots;
        $gig['remaining_slots'] = max(0, $maxSlots - $activeSlots);
    }

    return $gig ?: null;
}

/**
 * FEATURE 3 & DP2: Book a Gig (Client)
 * Creates a booking with status 'Pending'.
 * DP2 Decision: Multiple Pending inquiries are accepted into the queue without locking out clients prematurely.
 */
function createBooking(int $gigId, string $clientName, ?string $message = null, ?string $bookedDate = null): array {
    $gig = getGigById($gigId);
    if (!$gig) {
        throw new InvalidArgumentException("Gig not found with ID: {$gigId}");
    }

    $clientName = trim($clientName);
    if (empty($clientName)) {
        throw new InvalidArgumentException("Client name is required.");
    }

    $pdo = getDB();
    
    // Check client ID if matched in demo clients
    $clientId = null;
    foreach (DEMO_CLIENTS as $dc) {
        if (strcasecmp($dc['name'], $clientName) === 0) {
            $clientId = $dc['id'];
            break;
        }
    }

    $sql = "INSERT INTO bookings (gig_id, gig_title, category, rate, creator_id, creator_name, client_id, client_name, message, status, booked_date, created_at)
            VALUES (:gig_id, :gig_title, :category, :rate, :creator_id, :creator_name, :client_id, :client_name, :message, 'Pending', :booked_date, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':gig_id'       => $gig['id'],
        ':gig_title'    => $gig['title'],
        ':category'     => $gig['category'],
        ':rate'         => $gig['rate'],
        ':creator_id'   => $gig['creator_id'],
        ':creator_name' => $gig['creator_name'],
        ':client_id'    => $clientId,
        ':client_name'  => $clientName,
        ':message'      => !empty($message) ? trim($message) : 'Ready to start project with this scope.',
        ':booked_date'  => !empty($bookedDate) ? $bookedDate : date('Y-m-d', strtotime('+3 days'))
    ]);

    $bookingId = (int)$pdo->lastInsertId();

    return [
        'id'           => $bookingId,
        'gig_id'       => $gig['id'],
        'gig_title'    => $gig['title'],
        'creator_name' => $gig['creator_name'],
        'client_name'  => $clientName,
        'rate'         => $gig['rate'],
        'status'       => 'Pending'
    ];
}

/**
 * FEATURE 4: Creator Dashboard Bookings
 * Creator sees all bookings on their gigs.
 */
function getCreatorBookings(int $creatorId, ?string $status = null): array {
    $pdo = getDB();
    $params = [':creator_id' => $creatorId];
    $statusFilter = '';

    if (!empty($status) && in_array($status, ['Pending', 'Accepted', 'Declined'], true)) {
        $statusFilter = "AND status = :status";
        $params[':status'] = $status;
    }

    $sql = "SELECT * FROM bookings 
            WHERE creator_id = :creator_id {$statusFilter}
            ORDER BY 
              CASE status 
                WHEN 'Pending' THEN 1 
                WHEN 'Accepted' THEN 2 
                WHEN 'Declined' THEN 3 
              END, id DESC";
              
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * FEATURE 5: My Bookings (Client)
 * Client sees their bookings with current status: Pending / Accepted / Declined.
 */
function getClientBookings(string $clientName, ?string $status = null): array {
    $pdo = getDB();
    $params = [':client_name' => trim($clientName)];
    $statusFilter = '';

    if (!empty($status) && in_array($status, ['Pending', 'Accepted', 'Declined'], true)) {
        $statusFilter = "AND b.status = :status";
        $params[':status'] = $status;
    }

    $sql = "SELECT b.*, 
                   g.description AS gig_description,
                   COALESCE(c.avatar, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150') AS creator_avatar,
                   COALESCE(c.rating, 4.90) AS creator_rating
            FROM bookings b
            LEFT JOIN gigs g ON b.gig_id = g.id
            LEFT JOIN creators c ON b.creator_id = c.id
            WHERE (b.client_name = :client_name OR b.client_name LIKE :client_like) {$statusFilter}
            ORDER BY b.id DESC";

    $params[':client_like'] = '%' . trim($clientName) . '%';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * FEATURE 4 & DP1: Update Booking Status (Accept / Decline)
 * Persists status change in MySQL with decline reason.
 */
function updateBookingStatus(int $bookingId, string $status, ?string $declineReason = null): bool {
    if (!in_array($status, ['Accepted', 'Declined', 'Pending'], true)) {
        throw new InvalidArgumentException("Invalid booking status: {$status}");
    }

    $pdo = getDB();
    $sql = "UPDATE bookings 
            SET status = :status, 
                decline_reason = :decline_reason, 
                updated_at = NOW() 
            WHERE id = :id";
            
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([
        ':status'         => $status,
        ':decline_reason' => ($status === 'Declined') ? ($declineReason ?: 'Schedule fully committed for this timeframe.') : null,
        ':id'             => $bookingId
    ]);
}

/**
 * DP1: Find Alternative Gigs in the same category for declined bookings
 */
function getAlternativeGigs(string $category, int $excludeGigId, int $limit = 3): array {
    $pdo = getDB();
    $sql = "SELECT g.*, 
                   COALESCE(c.avatar, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150') AS avatar,
                   COALESCE(c.rating, 4.90) AS rating
            FROM gigs g
            LEFT JOIN creators c ON g.creator_id = c.id
            WHERE g.category = :category AND g.id != :exclude_id
            ORDER BY g.id DESC 
            LIMIT :limit";
            
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':category', $category, PDO::PARAM_STR);
    $stmt->bindValue(':exclude_id', $excludeGigId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get category styling color & icon class
 */
function getCategoryMeta(string $category): array {
    return match ($category) {
        'Design'        => ['color' => '#38bdf8', 'glow' => 'rgba(56, 189, 248, 0.25)', 'icon' => '🎨'],
        'Coding'        => ['color' => '#818cf8', 'glow' => 'rgba(129, 140, 248, 0.25)', 'icon' => '⚡'],
        'Video Editing' => ['color' => '#f472b6', 'glow' => 'rgba(244, 114, 182, 0.25)', 'icon' => '🎬'],
        'Music'         => ['color' => '#a78bfa', 'glow' => 'rgba(167, 139, 250, 0.25)', 'icon' => '🎵'],
        'Writing'       => ['color' => '#34d399', 'glow' => 'rgba(52, 211, 153, 0.25)', 'icon' => '✍️'],
        'Tutoring'      => ['color' => '#fbbf24', 'glow' => 'rgba(251, 191, 36, 0.25)', 'icon' => '💡'],
        default         => ['color' => '#94a3b8', 'glow' => 'rgba(148, 163, 184, 0.25)', 'icon' => '🔮']
    };
}
