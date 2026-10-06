-- =====================================================================
--  Casa Dive Villa - Full Database
--  Perlu MySQL 8.0.16+ / MariaDB 10.2+ (supaya CHECK constraint berfungsi)
--
--  NOTA HOSTING: kalau guna cPanel/shared hosting, biasanya CREATE DATABASE
--  tak dibenarkan. Dalam kes tu, buang 2 baris (CREATE DATABASE & USE) di bawah,
--  pilih database dalam phpMyAdmin dulu, kemudian import fail ni.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS sabrisae_casadivevilla
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sabrisae_casadivevilla;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
--  DROP (susunan terbalik dari dependency)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `gallery`;
DROP TABLE IF EXISTS `notification_status`;
DROP TABLE IF EXISTS `review`;
DROP TABLE IF EXISTS `payment`;
DROP TABLE IF EXISTS `booking_item`;
DROP TABLE IF EXISTS `booking`;
DROP TABLE IF EXISTS `accommodation_rate_period`;
DROP TABLE IF EXISTS `accommodation`;
DROP TABLE IF EXISTS `customer`;
DROP TABLE IF EXISTS `staff_task`;
DROP TABLE IF EXISTS `user`;

-- ---------------------------------------------------------------------
--  USER
-- ---------------------------------------------------------------------
CREATE TABLE `user` (
  user_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username        VARCHAR(50)  NOT NULL UNIQUE,
  password        VARCHAR(255) NOT NULL,
  fullname        VARCHAR(100) NOT NULL,
  email           VARCHAR(150) NOT NULL UNIQUE,
  phone           VARCHAR(20),
  role            ENUM('manager','staff') NOT NULL DEFAULT 'staff',
  profile_picture VARCHAR(255),
  status          ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  STAFF TASK
-- ---------------------------------------------------------------------
CREATE TABLE `staff_task` (
  task_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title        VARCHAR(255) NOT NULL,
  assigned_to  INT UNSIGNED NULL,
  status       ENUM('pending','in_progress','done') NOT NULL DEFAULT 'pending',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_staff_task_assigned_to (assigned_to),
  CONSTRAINT fk_staff_task_assigned_user FOREIGN KEY (assigned_to)
    REFERENCES `user`(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  CUSTOMER
-- ---------------------------------------------------------------------
CREATE TABLE `customer` (
  customer_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name       VARCHAR(100) NOT NULL,
  phone           VARCHAR(20) NOT NULL,
  email           VARCHAR(150),
  plate_num       VARCHAR(20),
  location        VARCHAR(100) NULL,
  ic_passport     VARCHAR(30) NULL,
  whatsapp_optin  TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  INDEX idx_customer_phone (phone),
  INDEX idx_customer_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  ACCOMMODATION
-- ---------------------------------------------------------------------
CREATE TABLE `accommodation` (
  accommodation_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  accommodation_name VARCHAR(100) NOT NULL,
  accommodation_type ENUM('Villa','Campsite') NOT NULL,
  price              DECIMAL(10,2) NOT NULL,
  price_weekend      DECIMAL(10,2) NULL,
  price_holiday      DECIMAL(10,2) NULL,
  price_seasonal     DECIMAL(10,2) NULL,
  capacity           INT UNSIGNED NOT NULL,
  pax_label          VARCHAR(30) NULL,
  features           TEXT NULL,
  description        TEXT,
  status             ENUM('available','unavailable','maintenance') NOT NULL DEFAULT 'available',
  image              VARCHAR(255),
  door_code          VARCHAR(20),
  INDEX idx_accommodation_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  ACCOMMODATION RATE PERIOD (harga khas ikut tarikh: super peak, dll)
-- ---------------------------------------------------------------------
CREATE TABLE `accommodation_rate_period` (
  rate_period_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  accommodation_id INT UNSIGNED NOT NULL,
  label            VARCHAR(120) NOT NULL,
  rate_type        ENUM('weekday','weekend','public_holiday','school_holiday','ramadan','seasonal','super_peak_cny','super_peak_eid','custom') NOT NULL DEFAULT 'custom',
  start_date       DATE NOT NULL,
  end_date         DATE NOT NULL,
  price            DECIMAL(10,2) NOT NULL,
  UNIQUE KEY uq_accommodation_rate_type_dates (accommodation_id, rate_type, start_date, end_date),
  INDEX idx_accommodation_rate_dates (accommodation_id, start_date, end_date),
  CONSTRAINT chk_rate_period_dates CHECK (end_date >= start_date),
  CONSTRAINT fk_rate_period_accommodation FOREIGN KEY (accommodation_id)
    REFERENCES accommodation(accommodation_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  BOOKING
-- ---------------------------------------------------------------------
CREATE TABLE `booking` (
  booking_id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id     INT UNSIGNED NOT NULL,
  booking_date    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  check_in        DATE NOT NULL,
  check_out       DATE NOT NULL,
  total_guest     INT UNSIGNED NOT NULL DEFAULT 1,
  deposit_amount  DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount    DECIMAL(10,2) NOT NULL DEFAULT 0,
  booking_status  ENUM('pending','confirmed','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'pending',
  special_request TEXT NULL,
  addon_bbq       TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  addon_mattress  TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  INDEX idx_booking_dates (check_in, check_out),
  INDEX idx_booking_status (booking_status),
  CONSTRAINT chk_booking_dates CHECK (check_out > check_in),
  CONSTRAINT chk_booking_guest CHECK (total_guest >= 1),
  CONSTRAINT fk_booking_customer FOREIGN KEY (customer_id)
    REFERENCES customer(customer_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  BOOKING ITEM
-- ---------------------------------------------------------------------
CREATE TABLE `booking_item` (
  booking_item_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id       INT UNSIGNED NOT NULL,
  accommodation_id INT UNSIGNED NOT NULL,
  quantity         INT UNSIGNED NOT NULL DEFAULT 1,
  price            DECIMAL(10,2) NOT NULL,
  UNIQUE KEY uq_booking_accommodation (booking_id, accommodation_id),
  CONSTRAINT fk_bookingitem_booking FOREIGN KEY (booking_id)
    REFERENCES booking(booking_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_bookingitem_accommodation FOREIGN KEY (accommodation_id)
    REFERENCES accommodation(accommodation_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  PAYMENT
-- ---------------------------------------------------------------------
CREATE TABLE `payment` (
  payment_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id     INT UNSIGNED NOT NULL,
  deposit_paid   DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_method ENUM('qr') NULL,
  payment_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  payment_status ENUM('pending','partial','paid','refunded','failed') NOT NULL DEFAULT 'pending',
  receipt        VARCHAR(255),
  INDEX idx_payment_status (payment_status),
  CONSTRAINT fk_payment_booking FOREIGN KEY (booking_id)
    REFERENCES booking(booking_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  REVIEW
-- ---------------------------------------------------------------------
CREATE TABLE `review` (
  review_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id    INT UNSIGNED NULL UNIQUE,
  rating        TINYINT UNSIGNED NOT NULL,
  comment       TEXT,
  display_name  VARCHAR(100) NULL,
  image_path    VARCHAR(255) NULL,
  review_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT chk_review_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT fk_review_booking FOREIGN KEY (booking_id)
    REFERENCES booking(booking_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  NOTIFICATION STATUS
-- ---------------------------------------------------------------------
CREATE TABLE `notification_status` (
  notification_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id        INT UNSIGNED NOT NULL,
  customer_id       INT UNSIGNED NOT NULL,
  sent_date         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status            ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  notification_type ENUM('booking_confirmation','payment_reminder','pending','check_in','check_out','cancellation','cancelled','review_request','general') NOT NULL,
  channel           ENUM('whatsapp','email') NOT NULL DEFAULT 'whatsapp',
  CONSTRAINT fk_notification_booking FOREIGN KEY (booking_id)
    REFERENCES booking(booking_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_notification_customer FOREIGN KEY (customer_id)
    REFERENCES customer(customer_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  GALLERY
-- ---------------------------------------------------------------------
CREATE TABLE `gallery` (
  gallery_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  image_path   VARCHAR(255) NOT NULL,
  caption      VARCHAR(150) NULL,
  uploaded_by  INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_gallery_user FOREIGN KEY (uploaded_by)
    REFERENCES `user`(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  SEED DATA
-- =====================================================================

-- Users (password hash kekal sama macam fail asal)
INSERT INTO `user` (username, password, fullname, email, phone, role, status) VALUES
('manager', '$2y$10$C8purFJ.QK6c9kBuqaU6Vu7HFBbMJlB0I.W3K/cAIVM.xcxW6AL2m', 'System Manager',      'admin@casadivevilla.com',  '0123456789', 'manager', 'active'),
('staff',   '$2y$10$1yNiKF71gLVq6G2Ozm9E/.omTBkcQ1wp8mTd7jJovK3Fm0W59bIl6', 'Front Desk Staff',   'staff@casadivevilla.com',  '0123456780', 'staff',   'active'),
('staff2',  '$2y$10$Ex337AncA2EotqQQ/H0kSOd2te5C3R69nyG0AJezSal8AeYe9lzsy', 'Front Desk Staff 2', 'staff2@casadivevilla.com', '0123456781', 'staff',   'active');

-- Accommodation (harga sebenar terus di-insert, tak perlu UPDATE lepas tu)
INSERT INTO `accommodation`
  (accommodation_name, accommodation_type, price, price_weekend, price_holiday, price_seasonal, capacity, pax_label, features, description, status, image, door_code)
VALUES
('Casa 1', 'Villa', 339.00, 359.00, 369.00, 299.00, 5, '4-5 PAX',
 '1 Queen Bed\n1 Bunk Bed\nAir-conditioned\nPool\nParking\nClothes drying rack\n2 Toilets\nOutdoor shower\nOutdoor sink',
 'One queen bed and one bunk bed. Comfortable for 4-5 guests.',
 'available', '../assets/images/casa1-porch.jpg', '1745'),

('Casa 2', 'Villa', 239.00, 259.00, 269.00, 199.00, 3, '2-3 PAX',
 '1 Queen Bed\nAir-conditioned\nPool\nParking\nClothes drying rack\n2 Toilets\nOutdoor shower\nOutdoor sink',
 'One queen bed. Comfortable for 2-3 guests.',
 'available', '../assets/images/casa2-close.jpg', '2836'),

('Casa 3', 'Villa', 239.00, 259.00, 269.00, 199.00, 3, '2-3 PAX',
 '1 Queen Bed\nAir-conditioned\nPool\nParking\nClothes drying rack\n2 Toilets\nOutdoor shower\nOutdoor sink',
 'One queen bed. Comfortable for 2-3 guests.',
 'available', '../assets/images/casa3-balcony-view.jpg', '3917'),

('Casa 4', 'Villa', 369.00, 389.00, 399.00, 329.00, 5, '4-5 PAX',
 '2 Sofa Beds\nKitchen\nLiving Room\nPool\nParking\nClothes drying rack\n2 Toilets\nOutdoor shower\nOutdoor sink',
 'Two sofa beds, a kitchen and a living room. Comfortable for 4-5 guests.',
 'available', '../assets/images/wooden-villa-day.jpg', '4028'),

('Campsite Package 1', 'Campsite',  50.00, NULL, NULL, NULL, 2, NULL, NULL, 'Site only, 1 unit only.',                                   'available', '../assets/images/campsite-tent-pool.jpg',  NULL),
('Campsite Package 2', 'Campsite',  80.00, NULL, NULL, NULL, 4, NULL, NULL, 'Site with pool access and a small tent rental.',            'available', '../assets/images/campsite-tents-pool.jpg', NULL),
('Campsite Package 3', 'Campsite', 110.00, NULL, NULL, NULL, 2, NULL, NULL, 'Site only, max 2 pax, 1 unit only.',                        'available', '../assets/images/campsite-tent-pool.jpg',  NULL),
('Campsite Package 4', 'Campsite', 130.00, NULL, NULL, NULL, 6, NULL, NULL, 'Site with pool access and a small tent rental, max 6 pax.',  'available', '../assets/images/campsite-tents-pool.jpg', NULL);

-- Rate periods: harga super peak untuk semua villa = price_holiday setiap villa
INSERT INTO accommodation_rate_period (accommodation_id, label, rate_type, start_date, end_date, price)
SELECT accommodation_id, 'Super Peak - Chinese New Year 2026', 'super_peak_cny', '2026-02-15', '2026-02-20', price_holiday
FROM accommodation WHERE accommodation_type = 'Villa';

INSERT INTO accommodation_rate_period (accommodation_id, label, rate_type, start_date, end_date, price)
SELECT accommodation_id, 'Super Peak - Chinese New Year 2027', 'super_peak_cny', '2027-02-06', '2027-02-07', price_holiday
FROM accommodation WHERE accommodation_type = 'Villa';

INSERT INTO accommodation_rate_period (accommodation_id, label, rate_type, start_date, end_date, price)
SELECT accommodation_id, 'Super Peak - Eid al-Fitr and School Break 2027', 'super_peak_eid', '2027-03-05', '2027-03-13', price_holiday
FROM accommodation WHERE accommodation_type = 'Villa';