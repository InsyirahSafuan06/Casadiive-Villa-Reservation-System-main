-- ============================================================
-- Casadive Villa Reservation System
-- Database schema (based on src/ERD Casadive Villa Reservation
-- System.drawio.pdf)
-- ============================================================

CREATE DATABASE IF NOT EXISTS casadive_villa_reservation
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE casadive_villa_reservation;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- User (staff / admin accounts)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  user_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username        VARCHAR(50)  NOT NULL UNIQUE,
  password        VARCHAR(255) NOT NULL,
  fullname        VARCHAR(100) NOT NULL,
  email           VARCHAR(150) NOT NULL UNIQUE,
  phone           VARCHAR(20),
  role            ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  profile_picture VARCHAR(255),
  status          ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Customer (guest making the booking)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `customer`;
CREATE TABLE `customer` (
  customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name   VARCHAR(100) NOT NULL,
  phone       VARCHAR(20) NOT NULL,
  email       VARCHAR(150),
  plate_num   VARCHAR(20),
  location    VARCHAR(100) NULL COMMENT 'optional — customer city/country, shown under their name in testimonials',
  INDEX idx_customer_phone (phone)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Accommodation (villa rooms / campsite packages)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `accommodation`;
CREATE TABLE `accommodation` (
  accommodation_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  accommodation_name VARCHAR(100) NOT NULL,
  accommodation_type ENUM('Villa','Campsite') NOT NULL,
  price              DECIMAL(10,2) NOT NULL COMMENT 'weekday / base rate per night, used for all booking calculations',
  price_weekend      DECIMAL(10,2) NULL COMMENT 'display only — not applied to booking totals yet',
  price_holiday      DECIMAL(10,2) NULL COMMENT 'display only — not applied to booking totals yet',
  capacity           INT UNSIGNED NOT NULL,
  pax_label          VARCHAR(30) NULL COMMENT 'display string e.g. "4-5 PAX", falls back to "Max N guests"',
  features           TEXT NULL COMMENT 'one feature per line, e.g. "Wifi\nAircond\nSea View"',
  description        TEXT,
  status             ENUM('available','unavailable','maintenance') NOT NULL DEFAULT 'available',
  image              VARCHAR(255),
  door_code          VARCHAR(20) COMMENT 'lock box / door code shown to the guest in the check-in reminder'
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Booking
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `booking`;
CREATE TABLE `booking` (
  booking_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id    INT UNSIGNED NOT NULL,
  booking_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  check_in       DATE NOT NULL,
  check_out      DATE NOT NULL,
  total_guest    INT UNSIGNED NOT NULL DEFAULT 1,
  deposit_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  total_amount   DECIMAL(10,2) NOT NULL DEFAULT 0,
  booking_status ENUM('pending','confirmed','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'pending',
  special_request TEXT NULL,
  CONSTRAINT fk_booking_customer FOREIGN KEY (customer_id)
    REFERENCES customer(customer_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Booking_item (accommodation lines within a booking)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `booking_item`;
CREATE TABLE `booking_item` (
  booking_item_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id       INT UNSIGNED NOT NULL,
  accommodation_id INT UNSIGNED NOT NULL,
  quantity         INT UNSIGNED NOT NULL DEFAULT 1,
  price            DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_bookingitem_booking FOREIGN KEY (booking_id)
    REFERENCES booking(booking_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_bookingitem_accommodation FOREIGN KEY (accommodation_id)
    REFERENCES accommodation(accommodation_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  UNIQUE KEY uq_booking_accommodation (booking_id, accommodation_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Payment
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `payment`;
CREATE TABLE `payment` (
  payment_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id     INT UNSIGNED NOT NULL,
  deposit_paid   DECIMAL(10,2) NOT NULL DEFAULT 0,
  payment_method ENUM('qr','online_banking','toyyibpay') NULL,
  payment_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  payment_status ENUM('pending','partial','paid','refunded','failed') NOT NULL DEFAULT 'pending',
  receipt        VARCHAR(255),
  CONSTRAINT fk_payment_booking FOREIGN KEY (booking_id)
    REFERENCES booking(booking_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Review (one per booking)
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `review`;
CREATE TABLE `review` (
  review_id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id                 INT UNSIGNED NOT NULL UNIQUE,
  rating                     TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment                    TEXT,
  image_path                 VARCHAR(255) NULL COMMENT 'set only once an uploaded/captured photo passes client-side AI verification',
  image_verification_status  ENUM('not_applicable','verified','rejected') NOT NULL DEFAULT 'not_applicable',
  image_category              VARCHAR(50) NULL COMMENT 'category the client-side model detected, e.g. bedroom/selfie/food/vehicle/uncertain',
  image_confidence           DECIMAL(4,3) NULL COMMENT 'model confidence score (0-1) reported by the client at upload time',
  review_date                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_review_booking FOREIGN KEY (booking_id)
    REFERENCES booking(booking_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Notification_status
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `notification_status`;
CREATE TABLE `notification_status` (
  notification_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id        INT UNSIGNED NOT NULL,
  customer_id       INT UNSIGNED NOT NULL,
  sent_date         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status             ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  notification_type ENUM('booking_confirmation','payment_reminder','pending','check_in','check_out','cancellation','cancelled','review_request','general') NOT NULL,
  channel           ENUM('whatsapp','email') NOT NULL DEFAULT 'whatsapp',
  CONSTRAINT fk_notification_booking FOREIGN KEY (booking_id)
    REFERENCES booking(booking_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_notification_customer FOREIGN KEY (customer_id)
    REFERENCES customer(customer_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Seed data
-- ============================================================

-- Passwords are 'Admin@12345' / 'Staff@12345', stored as bcrypt hashes (password_hash() in PHP).
-- Verify with password_verify($input, $hash) — never store or compare plaintext.
INSERT INTO `user` (username, password, fullname, email, phone, role, status) VALUES
('admin', '$2y$10$rBP.oWw4UJ.BkELLXJy2eOBztVZiuY.DUCtvkifE4cDTAVQi7sGzC', 'System Admin', 'admin@casadivevilla.com', '0123456789', 'admin', 'active'),
('staff', '$2y$10$yfE.LeII4j/qo7r022Ld6elviifDOMu5.oVupuJUNr.dxn8HAvkHi', 'Front Desk Staff', 'staff@casadivevilla.com', '0123456780', 'staff', 'active');

INSERT INTO `accommodation`
  (accommodation_name, accommodation_type, price, price_weekend, price_holiday, capacity, pax_label, features, description, status, door_code)
VALUES
('Casa 1', 'Villa', 1.00, 1.00, 1.00, 5, '4-5 PAX',
 '1 Queen Bed\n1 Bunk Bed\nBathroom\nAircond\nSea View\nPool View\nKettle\nIron + Iron Board\nWifi',
 'Spacious and comfortable, perfect for families or groups.', 'available', '1745'),
('Casa 2', 'Villa', 1.00, 1.00, 1.00, 3, '2-3 PAX',
 '1 Queen Bed\nBathroom\nAircond\nSea View\nPool View\nKettle\nIron + Iron Board\nWifi',
 'Perfect for couples or small families looking for relaxing beachfront stay with beautiful sea and pool views.', 'available', '2836'),
('Casa 3', 'Villa', 1.00, 1.00, 1.00, 3, '2-3 PAX',
 '1 Queen Bed\nBathroom\nAircond\nSea View\nPool View\nKettle\nIron + Iron Board\nWifi',
 'Perfect for couples or small families looking for relaxing beachfront stay with beautiful sea and pool views.', 'available', '3917'),
('Casa 4', 'Villa', 1.00, 1.00, 1.00, 5, '4-5 PAX',
 '2 Sofa Beds\nLiving Room\nBathroom\nAircond\nSea View\nPool View\nKettle\nIron + Iron Board\nWifi\nSmall Kitchen',
 'Spacious and comfortable, perfect for families or groups.', 'available', '4028'),
('Campsite Package 1', 'Campsite', 1.00, NULL, NULL, 2, NULL, NULL, 'Site only, 1 unit only.',                          'available', NULL),
('Campsite Package 2', 'Campsite', 1.00, NULL, NULL, 4, NULL, NULL, 'Site with pool access and a small tent rental.',   'available', NULL),
('Campsite Package 3', 'Campsite', 1.00, NULL, NULL, 2, NULL, NULL, 'Site only, max 2 pax, 1 unit only.',               'available', NULL),
('Campsite Package 4', 'Campsite', 1.00, NULL, NULL, 6, NULL, NULL, 'Site with pool access and a small tent rental, max 6 pax.', 'available', NULL);
