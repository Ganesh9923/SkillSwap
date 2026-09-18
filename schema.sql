-- ====================================================================
-- SkillSwap Production & Demonstration Database Schema
-- Hackathon ID: AZIS-SNTAGG | Track 2: Real-World AI Products
-- Team: Om's team (Om Dipak Kanase - Leader, Ganesh Arun Dalave - Team Member 1)
-- Institution: Lovely Professional University (LPU)
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `skillswap_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `skillswap_db`;

-- ====================================================================
-- 1. CREATORS TABLE (Demo Personas & DP3 Ranking Multipliers)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `creators` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `tagline` VARCHAR(150) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `rating` DECIMAL(3, 2) DEFAULT 4.90,
    `completed_gigs` INT DEFAULT 12,
    `response_rate` INT DEFAULT 98,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_creator_rating` (`rating`),
    INDEX `idx_creator_response` (`response_rate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 2. CLIENTS TABLE (Demo Personas & Multi-Client Session Support)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `clients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `company` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_client_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 3. GIGS TABLE (Feature 1, Feature 2, DP2 Concurrency, DP3 Ranking)
-- Categories: Design, Writing, Video Editing, Music, Coding, Tutoring, Other
-- ====================================================================
CREATE TABLE IF NOT EXISTS `gigs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `creator_id` INT NOT NULL,
    `creator_name` VARCHAR(100) NOT NULL,
    `title` VARCHAR(200) NOT NULL,
    `category` ENUM('Design', 'Writing', 'Video Editing', 'Music', 'Coding', 'Tutoring', 'Other') NOT NULL,
    `rate` DECIMAL(10, 2) NOT NULL,
    `description` TEXT NOT NULL,
    `max_concurrent_slots` INT DEFAULT 3,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_gig_category` (`category`),
    INDEX `idx_gig_creator` (`creator_id`),
    INDEX `idx_gig_rate` (`rate`),
    INDEX `idx_gig_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 4. BOOKINGS TABLE (Feature 3, Feature 4, Feature 5, DP1 & DP2)
-- Status Lifecycle: Pending -> Accepted | Declined (with Reason)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `gig_id` INT NOT NULL,
    `gig_title` VARCHAR(200) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `rate` DECIMAL(10, 2) NOT NULL,
    `creator_id` INT NOT NULL,
    `creator_name` VARCHAR(100) NOT NULL,
    `client_id` INT DEFAULT NULL,
    `client_name` VARCHAR(100) NOT NULL,
    `message` TEXT DEFAULT NULL,
    `status` ENUM('Pending', 'Accepted', 'Declined') NOT NULL DEFAULT 'Pending',
    `payment_status` ENUM('Unpaid', 'Held_In_Escrow', 'Released_To_Creator', 'Refunded') NOT NULL DEFAULT 'Unpaid',
    `decline_reason` VARCHAR(255) DEFAULT NULL,
    `booked_date` DATE DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_creator_status` (`creator_id`, `status`),
    INDEX `idx_client_status` (`client_name`, `status`),
    INDEX `idx_gig_status` (`gig_id`, `status`),
    INDEX `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- 5. PAYMENTS TABLE (Escrow Vault & Multi-Gateway Simulation)
-- ====================================================================
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `gig_id` INT NOT NULL,
    `client_name` VARCHAR(100) NOT NULL,
    `creator_id` INT NOT NULL,
    `creator_name` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'USD',
    `gateway` VARCHAR(50) DEFAULT 'SkillSwap Demo Sandbox',
    `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
    `payment_method` VARCHAR(50) DEFAULT 'Credit Card (Escrow)',
    `status` ENUM('Held_In_Escrow', 'Released_To_Creator', 'Refunded', 'Failed') NOT NULL DEFAULT 'Held_In_Escrow',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_booking_payment` (`booking_id`),
    INDEX `idx_tx_id` (`transaction_id`),
    INDEX `idx_payment_state` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SEED DATA: 1. Creators
-- ====================================================================
INSERT INTO `creators` (`id`, `name`, `email`, `avatar`, `tagline`, `bio`, `rating`, `completed_gigs`, `response_rate`) VALUES
(1, 'Elena Rostova', 'elena@skillswap.ai', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80', 'Principal Product & Glacial UI Designer', 'Specialized in luxury dark-mode interfaces, interactive design systems, and WebGL aesthetics.', 4.95, 34, 99),
(2, 'Marcus Vance', 'marcus@skillswap.ai', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80', 'Full-Stack Web Architect & AI Engineer', 'Building robust APIs, high-concurrency microservices, and modern web applications with speed and precision.', 4.92, 48, 97),
(3, 'Aria Chen', 'aria@skillswap.ai', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=150&auto=format&fit=crop&q=80', 'Ambient Electronic Music & SFX Producer', 'Crafting custom sonic identities, cinematic trailers, atmospheric game soundtracks, and podcast mixing.', 4.88, 29, 95),
(4, 'Devin Thorne', 'devin@skillswap.ai', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=150&auto=format&fit=crop&q=80', 'Senior Video Editor & Motion Graphics Lead', 'Transforming raw footage into high-retention YouTube edits, commercial reels, and 3D kinetic typography.', 4.90, 41, 96),
(5, 'Sophia Al-Mansoor', 'sophia@skillswap.ai', 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=150&auto=format&fit=crop&q=80', 'Technical Copywriter & AI Prompt Strategist', 'Translating complex engineering systems into crisp whitepapers, launch copy, and thought leadership articles.', 4.97, 52, 100)
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `email` = VALUES(`email`),
    `tagline` = VALUES(`tagline`),
    `bio` = VALUES(`bio`),
    `rating` = VALUES(`rating`),
    `completed_gigs` = VALUES(`completed_gigs`),
    `response_rate` = VALUES(`response_rate`);

-- ====================================================================
-- SEED DATA: 2. Clients
-- ====================================================================
INSERT INTO `clients` (`id`, `name`, `email`, `avatar`, `company`) VALUES
(1, 'Sarah Jenkins', 'sarah.j@venturelab.io', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&auto=format&fit=crop&q=80', 'VentureLab IO'),
(2, 'Liam O''Connor', 'liam@aurorastudios.com', 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?w=150&auto=format&fit=crop&q=80', 'Aurora Studios'),
(3, 'Priya Patel', 'priya@hyperflow.tech', 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=150&auto=format&fit=crop&q=80', 'HyperFlow Tech')
ON DUPLICATE KEY UPDATE 
    `name` = VALUES(`name`),
    `email` = VALUES(`email`),
    `company` = VALUES(`company`);

-- ====================================================================
-- SEED DATA: 3. Gigs (Required 7 Categories)
-- ====================================================================
INSERT INTO `gigs` (`id`, `creator_id`, `creator_name`, `title`, `category`, `rate`, `description`, `max_concurrent_slots`) VALUES
(1, 1, 'Elena Rostova', 'Next-Gen Glacial UI/UX Design & Design System', 'Design', 180.00, 'I will design a stunning, high-converting web or mobile interface with dark-mode elegance, glassmorphic card elements, custom icons, and interactive Figma components ready for engineering handoff.', 3),
(2, 2, 'Marcus Vance', 'Full-Stack PHP, REST API & MySQL Backend Architecture', 'Coding', 220.00, 'Production-grade backend engineering with strict PDO prepared statements, sub-50ms query optimizations, secure authentication-free or JWT architectures, and clean RESTful API integration.', 3),
(3, 4, 'Devin Thorne', 'Cinematic 4K Video Editing & Dynamic Motion Titles', 'Video Editing', 150.00, 'Professional post-production for SaaS demos, YouTube highlights, and brand reels. Includes color grading, sound design, sound effects layering, and crisp kinetic caption typography.', 3),
(4, 3, 'Aria Chen', 'Custom Synthesizer Soundtracks & Atmospheric Audio Logos', 'Music', 120.00, 'Original electronic music production, ambient background tracks for video games and podcasts, and custom spatial sound branding crafted on analog modular synths.', 2),
(5, 5, 'Sophia Al-Mansoor', 'High-Impact AI & Technical Whitepaper Writing', 'Writing', 140.00, 'Deep-dive technical copywriting, product documentation, developer pitch decks, and crisp launch announcements that distill complex architecture into compelling narratives.', 4),
(6, 2, 'Marcus Vance', '1-on-1 Full-Stack Architecture & Debugging Mentorship', 'Tutoring', 95.00, 'Personalized 60-minute intensive coaching session solving real-world code bottlenecks, database indexing challenges, and system design patterns.', 2),
(7, 1, 'Elena Rostova', '3D Brand Identity & Kinetic Logo Animation', 'Other', 210.00, 'Bespoke futuristic brand identity kit including vector logo, animated 3D SVG/Lottie badges, typography pairings, and complete brand guidelines document.', 2)
ON DUPLICATE KEY UPDATE 
    `creator_name` = VALUES(`creator_name`),
    `title` = VALUES(`title`),
    `category` = VALUES(`category`),
    `rate` = VALUES(`rate`),
    `description` = VALUES(`description`),
    `max_concurrent_slots` = VALUES(`max_concurrent_slots`);

-- ====================================================================
-- SEED DATA: 4. Bookings (Demonstrating Accepted, Pending, Declined)
-- ====================================================================
INSERT INTO `bookings` (`id`, `gig_id`, `gig_title`, `category`, `rate`, `creator_id`, `creator_name`, `client_id`, `client_name`, `message`, `status`, `payment_status`, `decline_reason`, `booked_date`) VALUES
(1, 1, 'Next-Gen Glacial UI/UX Design & Design System', 'Design', 180.00, 1, 'Elena Rostova', 1, 'Sarah Jenkins', 'We are redesigning our AI analytics dashboard and need your signature dark-mode aesthetic for 4 core screens.', 'Accepted', 'Held_In_Escrow', NULL, '2026-09-22'),
(2, 2, 'Full-Stack PHP, REST API & MySQL Backend Architecture', 'Coding', 220.00, 2, 'Marcus Vance', 1, 'Sarah Jenkins', 'Need an audit and optimization of our high-volume MySQL queries and REST endpoints.', 'Pending', 'Unpaid', NULL, '2026-09-25'),
(3, 3, 'Cinematic 4K Video Editing & Dynamic Motion Titles', 'Video Editing', 150.00, 4, 'Devin Thorne', 2, 'Liam O''Connor', 'Looking for an energetic 90-second teaser trailer for our upcoming mobile game launch.', 'Pending', 'Unpaid', NULL, '2026-09-28'),
(4, 4, 'Custom Synthesizer Soundtracks & Atmospheric Audio Logos', 'Music', 120.00, 3, 'Aria Chen', 3, 'Priya Patel', 'Need a 30-second futuristic audio logo and loopable background track for our live keynote.', 'Declined', 'Refunded', 'Current studio capacity booked through end of the month. Recommend checking alternative music creators.', '2026-09-20')
ON DUPLICATE KEY UPDATE 
    `status` = VALUES(`status`),
    `payment_status` = VALUES(`payment_status`),
    `decline_reason` = VALUES(`decline_reason`);

-- ====================================================================
-- SEED DATA: 5. Payments (Escrow Vault Demonstration)
-- ====================================================================
INSERT INTO `payments` (`id`, `booking_id`, `gig_id`, `client_name`, `creator_id`, `creator_name`, `amount`, `currency`, `gateway`, `transaction_id`, `payment_method`, `status`) VALUES
(1, 1, 1, 'Sarah Jenkins', 1, 'Elena Rostova', 180.00, 'USD', 'Demo Payment (testing vault)', 'TXN_SS_DEMO_ESCROW_01', 'Credit Card (Escrow Locked)', 'Held_In_Escrow'),
(2, 4, 4, 'Priya Patel', 3, 'Aria Chen', 120.00, 'USD', 'Demo Payment (testing vault)', 'TXN_SS_DEMO_REFUND_02', 'Credit Card (Auto-Refunded)', 'Refunded')
ON DUPLICATE KEY UPDATE 
    `status` = VALUES(`status`),
    `amount` = VALUES(`amount`);
