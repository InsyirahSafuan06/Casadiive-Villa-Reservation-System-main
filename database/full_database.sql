CREATE DATABASE IF NOT EXISTS sabrisae_casadivevilla
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE sabrisae_casadivevilla;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `user`;
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
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `customer`;
CREATE TABLE `customer` (
  customer_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name       VARCHAR(100) NOT NULL,
  phone           VARCHAR(20) NOT NULL,
  email           VARCHAR(150),
  plate_num       VARCHAR(20),
  location        VARCHAR(100) NULL,
  ic_passport     VARCHAR(30) NULL,
  whatsapp_optin  TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  INDEX idx_customer_phone (phone)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `accommodation`;
CREATE TABLE `accommodation` (
  accommodation_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  accommodation_name VARCHAR(100) NOT NULL,
  accommodation_type ENUM('Villa','Campsite') NOT NULL,
  price              DECIMAL(10,2) NOT NULL,
  price_weekend      DECIMAL(10,2) NULL,
  price_holiday      DECIMAL(10,2) NULL,
  capacity           INT UNSIGNED NOT NULL,
  pax_label          VARCHAR(30) NULL,
  features           TEXT NULL,
  description        TEXT,
  status             ENUM('available','unavailable','maintenance') NOT NULL DEFAULT 'available',
  image              VARCHAR(255),
  door_code          VARCHAR(20)
) ENGINE=InnoDB;

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
  addon_bbq       TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  addon_mattress  TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
  CONSTRAINT fk_booking_customer FOREIGN KEY (customer_id)
    REFERENCES customer(customer_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

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

DROP TABLE IF EXISTS `review`;
CREATE TABLE `review` (
  review_id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id                 INT UNSIGNED NULL UNIQUE,
  rating                     TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment                    TEXT,
  display_name               VARCHAR(100) NULL,
  image_path                 VARCHAR(255) NULL,
  review_date                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_review_booking FOREIGN KEY (booking_id)
    REFERENCES booking(booking_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

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

DROP TABLE IF EXISTS `gallery`;
CREATE TABLE `gallery` (
  gallery_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  image_path   VARCHAR(255) NOT NULL,
  caption      VARCHAR(150) NULL,
  uploaded_by  INT UNSIGNED NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_gallery_user FOREIGN KEY (uploaded_by)
    REFERENCES user(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO `user` (username, password, fullname, email, phone, role, status) VALUES
('manager', '$2y$10$C8purFJ.QK6c9kBuqaU6Vu7HFBbMJlB0I.W3K/cAIVM.xcxW6AL2m', 'System Manager', 'admin@casadivevilla.com', '0123456789', 'manager', 'active'),
('staff', '$2y$10$1yNiKF71gLVq6G2Ozm9E/.omTBkcQ1wp8mTd7jJovK3Fm0W59bIl6', 'Front Desk Staff', 'staff@casadivevilla.com', '0123456780', 'staff', 'active'),
('staff2', '$2y$10$Ex337AncA2EotqQQ/H0kSOd2te5C3R69nyG0AJezSal8AeYe9lzsy', 'Front Desk Staff 2', 'staff2@casadivevilla.com', '0123456781', 'staff', 'active');

INSERT INTO `accommodation`
  (accommodation_name, accommodation_type, price, price_weekend, price_holiday, capacity, pax_label, features, description, status, image, door_code)
VALUES
('Casa 1', 'Villa', 1.00, 1.00, 1.00, 5, '4-5 PAX',
 '1 Queen Bed\n1 Bunk Bed\nBathroom\nAircond\nSea View\nPool View\nKettle\nIron + Iron Board\nWifi',
 'Spacious and comfortable, perfect for families or groups.', 'available', '../assets/images/casa1-porch.jpg', '1745'),
('Casa 2', 'Villa', 1.00, 1.00, 1.00, 3, '2-3 PAX',
 '1 Queen Bed\nBathroom\nAircond\nSea View\nPool View\nKettle\nIron + Iron Board\nWifi',
 'Perfect for couples or small families looking for relaxing beachfront stay with beautiful sea and pool views.', 'available', '../assets/images/casa2-close.jpg', '2836'),
('Casa 3', 'Villa', 1.00, 1.00, 1.00, 3, '2-3 PAX',
 '1 Queen Bed\nBathroom\nAircond\nSea View\nPool View\nKettle\nIron + Iron Board\nWifi',
 'Perfect for couples or small families looking for relaxing beachfront stay with beautiful sea and pool views.', 'available', '../assets/images/casa3-balcony-view.jpg', '3917'),
('Casa 4', 'Villa', 1.00, 1.00, 1.00, 5, '4-5 PAX',
 '2 Sofa Beds\nLiving Room\nBathroom\nAircond\nSea View\nPool View\nKettle\nIron + Iron Board\nWifi\nSmall Kitchen',
 'Spacious and comfortable, perfect for families or groups.', 'available', '../assets/images/wooden-villa-day.jpg', '4028'),
('Campsite Package 1', 'Campsite', 1.00, NULL, NULL, 2, NULL, NULL, 'Site only, 1 unit only.',                          'available', '../assets/images/campsite-tent-pool.jpg', NULL),
('Campsite Package 2', 'Campsite', 1.00, NULL, NULL, 4, NULL, NULL, 'Site with pool access and a small tent rental.',   'available', '../assets/images/campsite-tents-pool.jpg', NULL),
('Campsite Package 3', 'Campsite', 1.00, NULL, NULL, 2, NULL, NULL, 'Site only, max 2 pax, 1 unit only.',               'available', '../assets/images/campsite-tent-pool.jpg', NULL),
('Campsite Package 4', 'Campsite', 1.00, NULL, NULL, 6, NULL, NULL, 'Site with pool access and a small tent rental, max 6 pax.', 'available', '../assets/images/campsite-tents-pool.jpg', NULL);