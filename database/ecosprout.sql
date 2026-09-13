-- =============================================================
-- EcoSprout Plant Nursery & Gardening Services Management System
-- Database: ecosprout
-- Created for: ICBT Assignment – Sem 02
-- Version: 1.0
-- =============================================================

CREATE DATABASE IF NOT EXISTS ecosprout
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ecosprout;

-- =============================================================
-- TABLE: users
-- Stores all user accounts: customers, staff, and administrators.
-- Status 'disabled' prevents login even with correct credentials.
-- =============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(120)  NOT NULL,
    email       VARCHAR(180)  NOT NULL UNIQUE,
    phone       VARCHAR(20)   NULL,
    password    VARCHAR(255)  NOT NULL COMMENT 'Bcrypt hash via password_hash()',
    role        ENUM('customer','staff','admin') NOT NULL DEFAULT 'customer',
    status      ENUM('enabled','disabled')       NOT NULL DEFAULT 'enabled',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email  (email),
    INDEX idx_role   (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: plants
-- Plant catalogue with botanical details, pricing and stock.
-- =============================================================
CREATE TABLE IF NOT EXISTS plants (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plant_name         VARCHAR(150) NOT NULL,
    botanical_name     VARCHAR(200) NULL,
    category           ENUM('indoor','outdoor','ornamental','edible') NOT NULL DEFAULT 'indoor',
    description        TEXT         NULL,
    care_instructions  TEXT         NULL,
    price              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock_quantity     INT UNSIGNED  NOT NULL DEFAULT 0,
    image              VARCHAR(255)  NULL COMMENT 'Relative path under assets/images/plants/',
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_plant_name (plant_name),
    INDEX idx_stock (stock_quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: gardening_services
-- Services offered by EcoSprout (landscaping, pruning, etc.)
-- =============================================================
CREATE TABLE IF NOT EXISTS gardening_services (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(150)   NOT NULL,
    description  TEXT           NULL,
    price        DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_service_name (service_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: workshops
-- Educational gardening workshops and events.
-- =============================================================
CREATE TABLE IF NOT EXISTS workshops (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title          VARCHAR(200)  NOT NULL,
    description    TEXT          NULL,
    schedule_date  DATETIME      NOT NULL,
    capacity       INT UNSIGNED  NOT NULL DEFAULT 20,
    fee            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_schedule_date (schedule_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: workshop_registrations
-- Records which customers are registered for which workshops.
-- =============================================================
CREATE TABLE IF NOT EXISTS workshop_registrations (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workshop_id   INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_workshop_user (workshop_id, user_id),
    FOREIGN KEY (workshop_id) REFERENCES workshops(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
    INDEX idx_user_id     (user_id),
    INDEX idx_workshop_id (workshop_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: orders
-- Customer plant purchase orders.
-- payment_status: pending = awaiting mock payment, paid = simulated success
-- order_status: pending → processing → completed | cancelled
-- =============================================================
CREATE TABLE IF NOT EXISTS orders (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id    INT UNSIGNED  NOT NULL,
    order_date     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_amount   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_status ENUM('pending','paid','failed')              NOT NULL DEFAULT 'pending',
    order_status   ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
    notes          TEXT          NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_customer_id    (customer_id),
    INDEX idx_order_status   (order_status),
    INDEX idx_payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: order_items
-- Line items for each order (plant, quantity, price at time of purchase).
-- unit_price is captured at order time to preserve historical pricing.
-- =============================================================
CREATE TABLE IF NOT EXISTS order_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED  NOT NULL,
    plant_id   INT UNSIGNED  NOT NULL,
    quantity   INT UNSIGNED  NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (plant_id) REFERENCES plants(id) ON DELETE RESTRICT,
    INDEX idx_order_id (order_id),
    INDEX idx_plant_id (plant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- TABLE: queries
-- Customer plant-care questions and staff responses.
-- status: open → answered | closed
-- =============================================================
CREATE TABLE IF NOT EXISTS queries (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    subject     VARCHAR(250) NOT NULL,
    message     TEXT         NOT NULL,
    status      ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
    response    TEXT         NULL COMMENT 'Staff/Admin reply',
    responded_by INT UNSIGNED NULL COMMENT 'FK to users.id of the staff member who replied',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer_id (customer_id),
    INDEX idx_status      (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- SEED DATA: Sample User Accounts
--
-- ASSUMPTION: Passwords are hashed using password_hash($plain, PASSWORD_BCRYPT).
-- The hashes below correspond to the following plain-text passwords:
--   admin@ecosprout.lk  → Admin@123
--   staff@ecosprout.lk  → Staff@123
--   customer@ecosprout.lk → Customer@123
--
-- To regenerate hashes: echo password_hash('Admin@123', PASSWORD_BCRYPT);
-- =============================================================
INSERT INTO users (full_name, email, phone, password, role, status) VALUES
(
    'System Administrator',
    'admin@ecosprout.lk',
    '+94712345678',
    '$2y$10$XYaJm3sPX3oXTWy6MMifb.s5jcxhDmpxeQtYxFG0wGukks0ZmeTT.',
    'admin',
    'enabled'
),
(
    'Kamal Perera',
    'staff@ecosprout.lk',
    '+94723456789',
    '$2y$10$zO2cqB.j3Bb/vw4NpqfLo.gKYi.6BFAEkNNDCxls3K4YcqcmdEkqa',
    'staff',
    'enabled'
),
(
    'Nimali Silva',
    'customer@ecosprout.lk',
    '+94734567890',
    '$2y$10$BMF4dfDuibDRGIf/Q7uzhegF6nCY/6VgW4BKmjNGEgXWWWk1.cmmK',
    'customer',
    'enabled'
);

-- =============================================================
-- SEED DATA: Sample Plants
-- =============================================================
INSERT INTO plants (plant_name, botanical_name, category, description, care_instructions, price, stock_quantity) VALUES
-- Indoor Plants
('Peace Lily', 'Spathiphyllum wallisii', 'indoor',
 'A popular indoor plant known for its elegant white blooms and air-purifying qualities. Perfect for low-light indoor spaces.',
 'Water once a week. Keep away from direct sunlight. Mist leaves regularly. Ideal temperature: 18–27°C.',
 1200.00, 25),

('Snake Plant', 'Sansevieria trifasciata', 'indoor',
 'One of the hardiest houseplants available. Its striking upright leaves make a bold statement in any room.',
 'Water every 2–6 weeks depending on season. Tolerates low light. Avoid overwatering.',
 850.00, 40),

('Pothos', 'Epipremnum aureum', 'indoor',
 'A fast-growing trailing vine with heart-shaped leaves. Excellent for hanging baskets and shelves.',
 'Water when soil is dry. Tolerates low to bright indirect light. Prune to encourage bushy growth.',
 550.00, 60),

('Chinese Evergreen', 'Aglaonema commutatum', 'indoor',
 'Beautiful variegated leaves in green and silver. One of the most adaptable houseplants.',
 'Low to medium light. Water moderately. Avoid cold drafts. Wipe leaves with damp cloth monthly.',
 950.00, 30),

-- Outdoor Plants
('Bougainvillea', 'Bougainvillea spectabilis', 'outdoor',
 'A stunning climbing plant with vibrant magenta-pink bracts. Ideal for fences and garden walls in Sri Lanka\'s climate.',
 'Full sun. Water regularly during growing season. Prune after flowering. Drought tolerant once established.',
 650.00, 35),

('Ixora', 'Ixora coccinea', 'outdoor',
 'A dense tropical shrub with clusters of tiny scarlet flowers. Widely grown in Sri Lankan gardens.',
 'Full sun to partial shade. Water regularly. Apply balanced fertilizer monthly. Prune to shape.',
 450.00, 50),

('Heliconia', 'Heliconia psittacorum', 'outdoor',
 'Tropical beauty with dramatic banana-like leaves and exotic lobster-claw flowers. Excellent for tropical gardens.',
 'Partial shade. Moist well-drained soil. Water regularly. Divide clumps every 2–3 years.',
 750.00, 20),

-- Ornamental Plants
('Bird of Paradise', 'Strelitzia reginae', 'ornamental',
 'Iconic orange and blue flowers resembling a tropical bird in flight. A showpiece for any garden or large interior.',
 'Full sun. Water deeply but infrequently. Well-drained soil. Fertilize during spring and summer.',
 2800.00, 12),

('Anthurium', 'Anthurium andraeanum', 'ornamental',
 'Glossy heart-shaped spathes in red, pink or white. Long-lasting blooms make it a popular ornamental.',
 'Bright indirect light. Water when top inch of soil is dry. High humidity preferred. Fertilize monthly.',
 1500.00, 18),

('Croton', 'Codiaeum variegatum', 'ornamental',
 'Spectacularly coloured leaves in red, orange, yellow and green. A dramatic focal point in any setting.',
 'Bright light for best colour. Keep moist but not waterlogged. Avoid cold drafts.',
 680.00, 45),

-- Edible Plants
('Curry Leaf', 'Murraya koenigii', 'edible',
 'An essential herb in Sri Lankan and South Indian cooking. Fresh curry leaves add an unmistakable aroma to dishes.',
 'Full sun. Water regularly. Well-drained soil. Apply organic fertilizer every 2 months.',
 350.00, 80),

('Chilli Pepper', 'Capsicum annuum', 'edible',
 'Sri Lankan green chillies ideal for home gardens. High-yielding variety suited to the local climate.',
 'Full sun. Water consistently. Rich, well-drained soil. Stake tall plants. Harvest when green or red.',
 280.00, 100),

('Drumstick (Moringa)', 'Moringa oleifera', 'edible',
 'A nutritional powerhouse. Every part – leaves, pods, flowers – is edible and packed with nutrients.',
 'Full sun. Drought tolerant. Sandy or loamy soil. Minimal water once established. Fast-growing.',
 400.00, 55),

('Lemongrass', 'Cymbopogon citratus', 'edible',
 'Aromatic grass used in Sri Lankan cooking and herbal teas. Easy to grow and maintain.',
 'Full sun. Water regularly. Divide clumps annually. Apply balanced fertilizer in growing season.',
 250.00, 90);

-- =============================================================
-- SEED DATA: Sample Gardening Services
-- =============================================================
INSERT INTO gardening_services (service_name, description, price) VALUES
('Garden Design & Landscaping',
 'Full-service garden design including site analysis, plant selection, layout planning, and installation. We create beautiful, sustainable gardens tailored to the Sri Lankan climate.',
 15000.00),

('Garden Maintenance Package',
 'Regular monthly garden upkeep including weeding, pruning, fertilizing, and pest management. Ideal for busy homeowners who want a pristine garden year-round.',
 3500.00),

('Lawn Care & Turf Management',
 'Professional lawn mowing, edging, aeration, and top-dressing. We use eco-friendly methods to keep your lawn lush and green.',
 2500.00),

('Tree Pruning & Shaping',
 'Expert pruning to promote healthy growth, improve aesthetics, and remove dead or hazardous branches. All sizes of trees handled safely.',
 4000.00),

('Irrigation System Installation',
 'Design and installation of drip irrigation or sprinkler systems. Water-efficient solutions that save time and reduce your water bill.',
 18000.00),

('Composting & Soil Enrichment',
 'Setup of compost systems and soil amendment programmes. We test your soil and recommend organic treatments for optimal plant health.',
 2000.00);

-- =============================================================
-- SEED DATA: Sample Workshops
-- =============================================================
INSERT INTO workshops (title, description, schedule_date, capacity, fee) VALUES
('Introduction to Home Gardening',
 'A beginner-friendly workshop covering soil preparation, seed planting, watering schedules, and basic plant care. Participants will receive a starter plant kit to take home.',
 DATE_ADD(NOW(), INTERVAL 14 DAY),
 25, 500.00),

('Tropical Plant Care Masterclass',
 'An intermediate workshop focused on caring for Sri Lanka\'s native tropical plants. Topics include pruning techniques, fertilizing schedules, and pest identification.',
 DATE_ADD(NOW(), INTERVAL 28 DAY),
 20, 750.00),

('Kitchen Garden & Edible Plants',
 'Learn to grow your own food! This workshop covers edible herbs, vegetables and fruits suited to small home gardens in Sri Lanka. Includes hands-on planting session.',
 DATE_ADD(NOW(), INTERVAL 21 DAY),
 30, 600.00),

('Composting & Sustainable Gardening',
 'Discover eco-friendly gardening practices. Learn to make compost, reduce garden waste, and create a sustainable green space. Free composting bin for all participants.',
 DATE_ADD(NOW(), INTERVAL 35 DAY),
 20, 400.00);

-- =============================================================
-- SEED DATA: Sample Query
-- =============================================================
INSERT INTO queries (customer_id, subject, message, status) VALUES
(3, 'My Peace Lily leaves are turning yellow',
 'Hello, I bought a Peace Lily last week and the leaves are starting to turn yellow. I water it every 2 days. Could you advise what I should do?',
 'open');
