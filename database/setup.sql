-- ============================================================
--  Legacy Safaris Ltd – Full Database Schema
--  Run once to create all tables and seed default data
-- ============================================================

CREATE DATABASE IF NOT EXISTS legacy_safaris CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE legacy_safaris;

-- ─── Site Settings ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS site_settings (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key  VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ─── Admin Users ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin_users (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(120) NOT NULL,
  email        VARCHAR(180) NOT NULL UNIQUE,
  password     VARCHAR(255) NOT NULL,  -- bcrypt hash
  role         ENUM('superadmin','editor') DEFAULT 'editor',
  last_login   DATETIME,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_password_resets (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT UNSIGNED NOT NULL,
  token_hash  CHAR(64) NOT NULL,
  expires_at  DATETIME NOT NULL,
  used_at     DATETIME NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_admin_reset_admin (admin_id),
  INDEX idx_admin_reset_token (token_hash),
  FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
);

-- ─── Tours ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS tours (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug          VARCHAR(160) NOT NULL UNIQUE,
  name          VARCHAR(200) NOT NULL,
  short_desc    TEXT,
  full_desc     LONGTEXT,
  price         DECIMAL(10,2) DEFAULT 0,
  currency      VARCHAR(5) DEFAULT 'USD',
  duration_days TINYINT UNSIGNED DEFAULT 1,
  category      ENUM('budget','premium','family','adventure','honeymoon') DEFAULT 'budget',
  image         VARCHAR(300),
  accommodation TEXT,
  max_group     TINYINT UNSIGNED DEFAULT 12,
  is_featured   TINYINT(1) DEFAULT 0,
  is_active     TINYINT(1) DEFAULT 1,
  sort_order    SMALLINT DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS tour_highlights (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tour_id  INT UNSIGNED NOT NULL,
  highlight VARCHAR(300) NOT NULL,
  sort_order TINYINT DEFAULT 0,
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tour_itinerary (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tour_id  INT UNSIGNED NOT NULL,
  day_num  TINYINT UNSIGNED NOT NULL,
  title    VARCHAR(200),
  description TEXT,
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tour_includes (
  id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tour_id INT UNSIGNED NOT NULL,
  item    VARCHAR(300) NOT NULL,
  type    ENUM('include','exclude') DEFAULT 'include',
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
);

-- ─── Destinations ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS destinations (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(160) NOT NULL UNIQUE,
  name        VARCHAR(200) NOT NULL,
  country     VARCHAR(100),
  short_desc  TEXT,
  full_desc   LONGTEXT,
  image       VARCHAR(300),
  is_featured TINYINT(1) DEFAULT 0,
  is_active   TINYINT(1) DEFAULT 1,
  sort_order  SMALLINT DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── Blog Posts ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS blog_posts (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(200) NOT NULL UNIQUE,
  title       VARCHAR(250) NOT NULL,
  excerpt     TEXT,
  body        LONGTEXT,
  image       VARCHAR(300),
  author      VARCHAR(120) DEFAULT 'Legacy Safaris',
  category    VARCHAR(100),
  tags        VARCHAR(300),
  is_published TINYINT(1) DEFAULT 0,
  published_at DATETIME,
  views       INT UNSIGNED DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ─── Gallery ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS gallery_images (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  filename    VARCHAR(300) NOT NULL,
  alt_text    VARCHAR(300),
  caption     TEXT,
  category    VARCHAR(100) DEFAULT 'general',
  is_active   TINYINT(1) DEFAULT 1,
  sort_order  SMALLINT DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── Team Members ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS team_members (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150) NOT NULL,
  role        VARCHAR(150),
  bio         TEXT,
  image       VARCHAR(300),
  email       VARCHAR(180),
  sort_order  SMALLINT DEFAULT 0,
  is_active   TINYINT(1) DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── Testimonials ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS testimonials (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150) NOT NULL,
  location    VARCHAR(150),
  quote       TEXT NOT NULL,
  rating      TINYINT UNSIGNED DEFAULT 5,
  is_active   TINYINT(1) DEFAULT 1,
  sort_order  SMALLINT DEFAULT 0,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── Bookings ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS bookings (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tour_id      INT UNSIGNED,
  tour_name    VARCHAR(200),
  name         VARCHAR(150) NOT NULL,
  email        VARCHAR(180) NOT NULL,
  phone        VARCHAR(30),
  travel_date  DATE,
  group_size   TINYINT UNSIGNED DEFAULT 1,
  message      TEXT,
  status       ENUM('new','contacted','confirmed','cancelled') DEFAULT 'new',
  admin_notes  TEXT,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE SET NULL
);

-- ─── Contact Messages ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contacts (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(150) NOT NULL,
  email      VARCHAR(180) NOT NULL,
  phone      VARCHAR(30),
  subject    VARCHAR(250),
  message    TEXT NOT NULL,
  is_read    TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── Newsletter Subscribers ──────────────────────────────────
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(180) NOT NULL UNIQUE,
  name          VARCHAR(150),
  is_active     TINYINT(1) DEFAULT 1,
  token         VARCHAR(64),          -- for unsubscribe link
  subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── Partners / Logos ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS partners (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(150),
  logo       VARCHAR(300),
  url        VARCHAR(300),
  sort_order SMALLINT DEFAULT 0,
  is_active  TINYINT(1) DEFAULT 1
);

-- ─── Default Settings Seed ───────────────────────────────────
INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
('site_name',           'Legacy Safaris Ltd'),
('site_tagline',        'Authentic · Ethical · Unforgettable'),
('site_email',          'reservations@legacysafaris.com'),
('contact_email',       'hello@legacysafaris.com'),
('phone',               '+254 712 345 678'),
('phone_secondary',     ''),
('address',             'Nairobi, Kenya / Arusha, Tanzania'),
('office_hours',        'Mon-Sat: 9am – 6pm (EAT)'),
('facebook_url',        '#'),
('instagram_url',       '#'),
('twitter_url',         '#'),
('whatsapp_number',     '+254712345678'),
('smtp_host',           'smtp.gmail.com'),
('smtp_port',           '587'),
('smtp_username',       ''),
('smtp_password',       ''),
('smtp_from_name',      'Legacy Safaris Ltd'),
('smtp_from_email',     'reservations@legacysafaris.com'),
('smtp_encryption',     'tls'),
('hero_title',          'Unleash the untamed beauty of Africa'),
('hero_subtitle',       'Experience legendary wildlife, raw landscapes, and authentic safari adventures.'),
('hero_image',          ''),
('about_short',         'Legacy Safaris Ltd is a premier East African safari operator with over 15 years of experience crafting unforgettable journeys into the wild.'),
('meta_keywords',       'safari, kenya, tanzania, africa, wildlife, tours, gorilla trekking'),
('meta_description',    'Book authentic African safari adventures with Legacy Safaris Ltd. Kenya, Tanzania, Uganda and beyond.'),
('google_analytics_id', ''),
('maintenance_mode',    '0'),
('currency',            'USD'),
('currency_symbol',     '$');

-- ─── Default Admin User (password: Admin@1234) ───────────────
-- IMPORTANT: Change this password immediately after first login
-- Hash generated with: password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost'=>12])
INSERT IGNORE INTO admin_users (name, email, password, role)
VALUES ('Super Admin', 'admin@legacysafaris.com',
        '$2y$12$SBiSL/IsTV8zBrI1ZHh/gODQk35Rqcl5QlrzYtoZ.mTnA2FVKkzmm',
        'superadmin');

-- ─── Seed Demo Tours ─────────────────────────────────────────
INSERT IGNORE INTO tours (slug, name, short_desc, price, duration_days, category, image, is_featured, is_active)
VALUES
('great-migration-safari', 'Great Migration Safari',
 'Witness the greatest wildlife spectacle on earth – wildebeest migration in Maasai Mara & Serengeti.',
 2450.00, 7, 'premium', 'images/image10.jpg', 1, 1),
('big-five-explorer', 'Big Five Explorer',
 'Classic Kenya safari – Amboseli elephants, Lake Nakuru rhinos, and big cats.',
 1890.00, 5, 'budget', 'images/image2.jpg', 1, 1),
('gorilla-golden-monkey', 'Gorilla & Golden Monkey',
 "Uganda's Bwindi Impenetrable Forest – once-in-a-lifetime gorilla trekking.",
 3250.00, 4, 'premium', 'images/image3.jpg', 1, 1);

-- ─── Seed Demo Testimonials ──────────────────────────────────
INSERT IGNORE INTO testimonials (name, location, quote, rating, is_active) VALUES
('Sarah & Mark',  'UK',  'The migration crossing was surreal. Our guide Joseph knew exactly where to position us. This was a once-in-a-lifetime trip!', 5, 1),
('Dr. James O.',  'USA', 'Legacy Safaris went above and beyond to arrange a gorilla trek for my 70th birthday. Truly unforgettable.', 5, 1),
('The Henriksen Family', 'Norway', 'The attention to detail, the eco-lodges, the food – everything was perfect. We\'ll be back for the wildebeest calving.', 5, 1);
