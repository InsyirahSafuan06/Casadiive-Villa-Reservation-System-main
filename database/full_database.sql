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


-- Seed 10 demo staff/manager accounts.
-- All accounts share the password: Staff@123 (hashed below with password_hash/PASSWORD_DEFAULT).

INSERT INTO user (username, password, fullname, email, phone, role, profile_picture, status) VALUES ('aiman.rashid', '$2y$10$g9im8eSJll8pBtZS7qLkqOJrpRCuuK64hrDWl311DO3iCwRhw6vlS', 'Aiman Rashid', 'aiman.rashid@casadivevilla.com', '0134567811', 'staff', NULL, 'active');
INSERT INTO user (username, password, fullname, email, phone, role, profile_picture, status) VALUES ('farah.nabila', '$2y$10$g9im8eSJll8pBtZS7qLkqOJrpRCuuK64hrDWl311DO3iCwRhw6vlS', 'Farah Nabila', 'farah.nabila@casadivevilla.com', '0134567812', 'staff', NULL, 'active');
INSERT INTO user (username, password, fullname, email, phone, role, profile_picture, status) VALUES ('zulkifli.bakar', '$2y$10$g9im8eSJll8pBtZS7qLkqOJrpRCuuK64hrDWl311DO3iCwRhw6vlS', 'Zulkifli Bakar', 'zulkifli.bakar@casadivevilla.com', '0134567813', 'manager', NULL, 'active');
INSERT INTO user (username, password, fullname, email, phone, role, profile_picture, status) VALUES ('nadia.hassan', '$2y$10$g9im8eSJll8pBtZS7qLkqOJrpRCuuK64hrDWl311DO3iCwRhw6vlS', 'Nadia Hassan', 'nadia.hassan@casadivevilla.com', '0134567814', 'staff', NULL, 'active');
INSERT INTO user (username, password, fullname, email, phone, role, profile_picture, status) VALUES ('amirul.hakim', '$2y$10$g9im8eSJll8pBtZS7qLkqOJrpRCuuK64hrDWl311DO3iCwRhw6vlS', 'Amirul Hakim', 'amirul.hakim@casadivevilla.com', '0134567815', 'staff', NULL, 'inactive');
INSERT INTO user (username, password, fullname, email, phone, role, profile_picture, status) VALUES ('syafiqah.osman', '$2y$10$g9im8eSJll8pBtZS7qLkqOJrpRCuuK64hrDWl311DO3iCwRhw6vlS', 'Syafiqah Osman', 'syafiqah.osman@casadivevilla.com', '0134567816', 'staff', NULL, 'active');
INSERT INTO user (username, password, fullname, email, phone, role, profile_picture, status) VALUES ('izzat.rahman', '$2y$10$g9im8eSJll8pBtZS7qLkqOJrpRCuuK64hrDWl311DO3iCwRhw6vlS', 'Izzat Rahman', 'izzat.rahman@casadivevilla.com', '0134567817', 'manager', NULL, 'active');
INSERT INTO user (username, password, fullname, email, phone, role, profile_picture, status) VALUES ('adam.yusof', '$2y$10$g9im8eSJll8pBtZS7qLkqOJrpRCuuK64hrDWl311DO3iCwRhw6vlS', 'Adam Yusof', 'adam.yusof@casadivevilla.com', '0134567818', 'staff', NULL, 'suspended');
INSERT INTO user (username, password, fullname, email, phone, role, profile_picture, status) VALUES ('balqis.mahmud', '$2y$10$g9im8eSJll8pBtZS7qLkqOJrpRCuuK64hrDWl311DO3iCwRhw6vlS', 'Nur Balqis Mahmud', 'balqis.mahmud@casadivevilla.com', '0134567819', 'staff', NULL, 'active');
INSERT INTO user (username, password, fullname, email, phone, role, profile_picture, status) VALUES ('haziq.firdaus', '$2y$10$g9im8eSJll8pBtZS7qLkqOJrpRCuuK64hrDWl311DO3iCwRhw6vlS', 'Haziq Firdaus', 'haziq.firdaus@casadivevilla.com', '0134567820', 'staff', NULL, 'active');

-- Seed 55 test customers/bookings/payments for demo data (Power BI dashboard).
-- Safe to run multiple times (always inserts NEW rows via AUTO_INCREMENT).
-- Receipts reused from real uploaded proofs in assets/uploads/payments/ (already exist, no upload needed).

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Iman Ismail Bakar', '0175193924', 'iman.ismail.bakar814@gmail.com', 'QA5703', 'Alor Setar', '824502974942', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-10 07:43:31', '2026-09-12', '2026-09-16', 1, 1.00, 542, 'checked_in', 1, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 6, 1, 572);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 542, 'qr', '2026-09-12 07:43:31', 'paid', 'assets/uploads/payments/payment_3_7aaad95e.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Aliff Yusof Latif', '0129800118', 'aliff.yusof.latif840@gmail.com', 'DP5079', 'Sungai Petani', '879316200354', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-19 08:43:31', '2026-10-02', '2026-10-08', 4, 1.00, 1968, 'cancelled', 1, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 1, 1, 1998);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Nabila Kamal Ibrahim', '0119819462', 'nabila.kamal.ibrahim484@gmail.com', 'UP7451', 'Sungai Petani', '928312330127', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-08-09 14:43:31', '2026-08-17', '2026-08-21', 2, 1.00, 490, 'pending', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 5, 1, 540);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Aliff Abdullah Hamid', '0137702283', NULL, 'ZA8745', 'Kulim', '976399589818', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-08-30 09:43:31', '2026-09-07', '2026-09-09', 1, 1.00, 394, 'pending', 1, 0, 0);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 5, 1, 374);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Amirul Salleh Mahmud', '0173560607', 'amirul.salleh.mahmud535@gmail.com', 'PP1043', 'Kulim', '878626908481', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-08-04 19:43:31', '2026-08-15', '2026-08-22', 2, 1.00, 769, 'pending', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 6, 1, 819);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Mohd Osman Abdullah', '0156015416', 'mohd.osman.abdullah314@gmail.com', 'OU3535', 'Jitra', '903186131116', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-19 00:43:31', '2026-09-30', '2026-10-04', 2, 1.00, 870, 'confirmed', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 2, 1, 920);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 870, 'online_banking', '2026-09-21 00:43:31', 'paid', 'assets/uploads/payments/payment_4_ba596fb3.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Adam Aziz Ibrahim', '0162940035', NULL, 'ZO1715', 'Jitra', '838178092741', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-08-23 05:43:31', '2026-08-25', '2026-08-31', 3, 1.00, 802, 'cancelled', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 5, 1, 852);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Alya Mahmud Abdullah', '0189789997', NULL, 'NO4594', 'Jitra', '954130301728', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-16 07:43:31', '2026-09-26', '2026-09-30', 1, 1.00, 718, 'confirmed', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 8, 1, 768);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 718, 'qr', '2026-09-18 07:43:31', 'paid', 'assets/uploads/payments/payment_5_f5718a78.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Amirul Rashid Osman', '0138506291', 'amirul.rashid.osman509@gmail.com', 'ME5347', 'Langkawi', '902131204489', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-22 19:43:31', '2026-10-05', '2026-10-08', 3, 1.00, 931, 'checked_in', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 1, 1, 981);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 931, 'qr', '2026-09-24 19:43:31', 'paid', 'assets/uploads/payments/payment_7_e592b113.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Nadia Ibrahim Osman', '0132645104', NULL, 'BJ7981', 'Penang', '953299966314', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-18 14:43:31', '2026-07-25', '2026-07-28', 1, 1.00, 482, 'checked_out', 0, 1, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 7, 1, 522);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 482, 'online_banking', '2026-07-19 14:43:31', 'paid', 'assets/uploads/payments/payment_3_7aaad95e.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Haziq Mahmud Rahman', '0175340659', NULL, 'UU3326', 'Jitra', '895181459155', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-06-09 21:43:31', '2026-06-10', '2026-06-17', 1, 1.00, 748, 'cancelled', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 7, 1, 798);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Qistina Mahmud Salleh', '0161483716', 'qistina.mahmud.salleh452@gmail.com', 'HX4978', 'Butterworth', '874585475389', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-21 03:43:31', '2026-10-02', '2026-10-03', 1, 1.00, 139, 'confirmed', 0, 0, 0);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 8, 1, 139);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 139, 'online_banking', '2026-09-21 03:43:31', 'paid', 'assets/uploads/payments/payment_4_ba596fb3.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Firdaus Ismail Salleh', '0177858286', 'firdaus.ismail.salleh428@gmail.com', 'CW4159', 'Langkawi', '941209305538', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-06-18 09:43:31', '2026-07-01', '2026-07-07', 3, 1.00, 1116, 'confirmed', 1, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 6, 1, 1146);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 1116, 'toyyibpay', '2026-06-19 09:43:31', 'paid', 'assets/uploads/payments/payment_5_f5718a78.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Iman Ismail Ismail', '0181255731', 'iman.ismail.ismail757@gmail.com', 'GX4877', 'Butterworth', '820772023376', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-08 17:43:31', '2026-07-10', '2026-07-17', 4, 1.00, 1637, 'checked_out', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 4, 1, 1687);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 1637, 'online_banking', '2026-07-10 17:43:31', 'paid', 'assets/uploads/payments/payment_7_e592b113.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Amir Rashid Ibrahim', '0178906835', 'amir.rashid.ibrahim372@gmail.com', 'IC3509', 'Langkawi', '936117793064', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-24 05:43:31', '2026-08-06', '2026-08-12', 4, 1.00, 614, 'pending', 0, 1, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 7, 1, 654);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Ahmad Ibrahim Latif', '0196623203', 'ahmad.ibrahim.latif226@gmail.com', 'BU9402', 'Sungai Petani', '834396961226', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-17 00:43:31', '2026-09-30', '2026-10-01', 2, 1.00, 158, 'confirmed', 0, 0, 0);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 6, 1, 158);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 158, 'qr', '2026-09-19 00:43:31', 'paid', 'assets/uploads/payments/payment_3_7aaad95e.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Muhammad Aziz Yusof', '0123928448', NULL, 'YE4677', 'Penang', '824998963253', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-06-10 12:43:31', '2026-06-12', '2026-06-18', 4, 1.00, 928, 'checked_out', 1, 1, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 4, 1, 948);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 928, 'online_banking', '2026-06-10 12:43:31', 'paid', 'assets/uploads/payments/payment_4_ba596fb3.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Amir Hassan Zulkifli', '0198591998', 'amir.hassan.zulkifli883@gmail.com', 'UH3200', 'Penang', '858815134957', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-08-01 15:43:31', '2026-08-03', '2026-08-06', 1, 1.00, 835, 'pending', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 3, 1, 885);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Qistina Ali Mahmud', '0132576678', NULL, 'GE8716', 'Jitra', '924156850012', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-06-04 17:43:31', '2026-06-17', '2026-06-20', 1, 1.00, 406, 'cancelled', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 7, 1, 456);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Zulkifli Kamal Salleh', '0118812989', NULL, 'SL6144', 'Alor Setar', '957631539548', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-06-01 10:43:31', '2026-06-08', '2026-06-10', 3, 1.00, 642, 'checked_out', 1, 0, 0);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 3, 1, 622);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 642, 'qr', '2026-06-01 10:43:31', 'paid', 'assets/uploads/payments/payment_5_f5718a78.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Nurul Rashid Hassan', '0118433550', NULL, 'IT4853', 'Butterworth', '872336867427', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-08-14 19:43:31', '2026-08-27', '2026-08-28', 2, 1.00, 340, 'confirmed', 0, 0, 0);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 4, 1, 340);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 340, 'online_banking', '2026-08-14 19:43:31', 'paid', 'assets/uploads/payments/payment_7_e592b113.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Ahmad Latif Osman', '0157162775', 'ahmad.latif.osman422@gmail.com', 'QG9805', 'Alor Setar', '840390028474', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-24 06:43:31', '2026-09-29', '2026-10-04', 2, 1.00, 480, 'pending', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 7, 1, 530);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Amirul Latif Latif', '0170805742', NULL, 'KF4518', 'Jitra', '860420277844', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-04 19:43:31', '2026-07-07', '2026-07-12', 3, 1.00, 410, 'pending', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 6, 1, 460);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Mohd Kamal Aziz', '0120228641', NULL, 'RA3549', 'Penang', '914787761337', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-21 18:43:31', '2026-07-29', '2026-08-02', 3, 1.00, 488, 'pending', 0, 1, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 7, 1, 528);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Qistina Abdullah Osman', '0143577457', 'qistina.abdullah.osman810@gmail.com', 'BX7396', 'Sungai Petani', '875951402351', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-08-18 00:43:31', '2026-08-27', '2026-08-30', 2, 1.00, 571, 'checked_in', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 3, 1, 621);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 571, 'toyyibpay', '2026-08-19 00:43:31', 'paid', 'assets/uploads/payments/payment_3_7aaad95e.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Aina Ibrahim Osman', '0142641885', NULL, 'BE9327', 'Sungai Petani', '908554508219', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-10 15:43:31', '2026-09-15', '2026-09-22', 1, 1.00, 1896, 'cancelled', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 1, 1, 1946);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Ahmad Mahmud Latif', '0181785267', 'ahmad.mahmud.latif732@gmail.com', 'YZ3063', 'Sungai Petani', '844146285427', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-27 01:43:31', '2026-08-03', '2026-08-10', 1, 1.00, 1021, 'confirmed', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 2, 1, 1071);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 1021, 'online_banking', '2026-07-29 01:43:31', 'paid', 'assets/uploads/payments/payment_4_ba596fb3.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Firdaus Ibrahim Kamal', '0184367028', 'firdaus.ibrahim.kamal156@gmail.com', 'WP6382', 'Butterworth', '807713407500', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-10 20:43:31', '2026-09-21', '2026-09-28', 2, 1.00, 761, 'cancelled', 1, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 7, 1, 791);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Aliff Hassan Abdullah', '0162203064', NULL, 'ZX5851', 'Penang', '914087821084', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-26 00:43:31', '2026-08-03', '2026-08-10', 1, 1.00, 1287, 'checked_out', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 8, 1, 1337);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 1287, 'qr', '2026-07-26 00:43:31', 'paid', 'assets/uploads/payments/payment_5_f5718a78.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Adam Zulkifli Mahmud', '0114764325', NULL, 'XO3483', 'Penang', '814302124227', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-06-02 15:43:31', '2026-06-13', '2026-06-20', 1, 1.00, 1321, 'checked_in', 1, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 2, 1, 1351);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 1321, 'toyyibpay', '2026-06-02 15:43:31', 'paid', 'assets/uploads/payments/payment_7_e592b113.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Rayyan Rashid Hamid', '0113498286', 'rayyan.rashid.hamid207@gmail.com', 'CS1074', 'Sungai Petani', '911229188836', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-08 10:43:31', '2026-07-18', '2026-07-23', 4, 1.00, 875, 'confirmed', 1, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 5, 1, 905);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 875, 'qr', '2026-07-10 10:43:31', 'paid', 'assets/uploads/payments/payment_3_7aaad95e.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Hana Kamal Yusof', '0156225616', NULL, 'HK2172', 'Penang', '866849253341', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-08-08 09:43:31', '2026-08-12', '2026-08-14', 2, 1.00, 354, 'confirmed', 0, 0, 0);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 8, 1, 354);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 354, 'qr', '2026-08-09 09:43:31', 'paid', 'assets/uploads/payments/payment_4_ba596fb3.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Batrisyia Rashid Kamal', '0190474493', 'batrisyia.rashid.kamal812@gmail.com', 'TM6639', 'Langkawi', '831008240491', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-06-15 17:43:31', '2026-06-22', '2026-06-24', 2, 1.00, 380, 'confirmed', 0, 0, 0);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 1, 1, 380);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 380, 'qr', '2026-06-16 17:43:31', 'paid', 'assets/uploads/payments/payment_5_f5718a78.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Aiman Mahmud Osman', '0126768044', 'aiman.mahmud.osman318@gmail.com', 'HU6241', 'Alor Setar', '990691834454', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-24 19:43:31', '2026-10-07', '2026-10-12', 3, 1.00, 1130, 'pending', 1, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 2, 1, 1160);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Amir Abdullah Ibrahim', '0111399354', NULL, 'BN6924', 'Kuala Muda', '963191698257', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-06-18 13:43:31', '2026-07-02', '2026-07-04', 2, 1.00, 220, 'pending', 1, 0, 0);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 6, 1, 200);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Izzat Osman Ismail', '0158551920', 'izzat.osman.ismail584@gmail.com', 'TF8629', 'Sungai Petani', '875189029805', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-19 11:43:31', '2026-09-26', '2026-09-30', 3, 1.00, 914, 'checked_out', 1, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 4, 1, 944);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 914, 'toyyibpay', '2026-09-20 11:43:31', 'paid', 'assets/uploads/payments/payment_7_e592b113.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Rayyan Osman Salleh', '0117404793', 'rayyan.osman.salleh951@gmail.com', 'YG7903', 'Butterworth', '967572603884', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-16 00:43:31', '2026-07-30', '2026-08-04', 4, 1.00, 365, 'pending', 0, 1, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 8, 1, 405);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Siti Ismail Ismail', '0135925316', 'siti.ismail.ismail972@gmail.com', 'VD4470', 'Sungai Petani', '878332621983', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-23 02:43:31', '2026-10-06', '2026-10-13', 3, 1.00, 1237, 'checked_out', 1, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 5, 1, 1267);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 1237, 'online_banking', '2026-09-25 02:43:31', 'paid', 'assets/uploads/payments/payment_3_7aaad95e.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Qistina Hassan Rahman', '0163567275', 'qistina.hassan.rahman535@gmail.com', 'AD3768', 'Alor Setar', '804590153830', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-09 12:43:31', '2026-09-19', '2026-09-26', 3, 1.00, 692, 'cancelled', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 7, 1, 742);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Nabila Karim Latif', '0155432253', NULL, 'RE7012', 'Langkawi', '886004002036', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-08-24 22:43:31', '2026-09-05', '2026-09-06', 3, 1.00, 188, 'confirmed', 0, 1, 0);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 5, 1, 178);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 188, 'qr', '2026-08-25 22:43:31', 'paid', 'assets/uploads/payments/payment_4_ba596fb3.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Haziq Aziz Ali', '0135946897', NULL, 'QX7949', 'Kuala Muda', '898659765423', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-04 13:43:31', '2026-07-12', '2026-07-19', 2, 1.00, 2344, 'confirmed', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 4, 1, 2394);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 2344, 'qr', '2026-07-06 13:43:31', 'paid', 'assets/uploads/payments/payment_5_f5718a78.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Amir Osman Ismail', '0159615018', NULL, 'DW9388', 'Penang', '949456791959', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-06-03 10:43:31', '2026-06-08', '2026-06-11', 3, 1.00, 430, 'cancelled', 1, 1, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 4, 1, 450);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Qistina Zulkifli Hamid', '0154199547', 'qistina.zulkifli.hamid814@gmail.com', 'XR8348', 'Kuala Muda', '857265613110', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-20 14:43:31', '2026-07-23', '2026-07-29', 1, 1.00, 1414, 'checked_out', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 1, 1, 1464);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 1414, 'online_banking', '2026-07-22 14:43:31', 'paid', 'assets/uploads/payments/payment_7_e592b113.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Farah Kamal Karim', '0112839378', 'farah.kamal.karim328@gmail.com', 'WP5242', 'Kuala Muda', '931027880885', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-21 16:43:31', '2026-10-01', '2026-10-02', 2, 1.00, 171, 'checked_out', 0, 0, 0);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 7, 1, 171);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 171, 'toyyibpay', '2026-09-22 16:43:31', 'paid', 'assets/uploads/payments/payment_3_7aaad95e.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Adam Abdullah Ismail', '0147535969', NULL, 'MP5575', 'Langkawi', '818816493812', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-14 05:43:31', '2026-07-17', '2026-07-18', 4, 1.00, 174, 'checked_out', 0, 0, 0);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 6, 1, 174);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Muhammad Hassan Aziz', '0127175554', 'muhammad.hassan.aziz24@gmail.com', 'RY4402', 'Alor Setar', '990167121036', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-06-19 06:43:31', '2026-06-30', '2026-07-03', 2, 1.00, 487, 'cancelled', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 3, 1, 537);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Farah Ibrahim Rahman', '0172922247', NULL, 'FJ4687', 'Kuala Muda', '967847937484', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-06-05 11:43:31', '2026-06-09', '2026-06-16', 2, 1.00, 1342, 'pending', 1, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 7, 1, 1372);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Syafiqah Yusof Kamal', '0198699636', NULL, 'QZ2323', 'Kulim', '920518480127', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-06-19 00:43:31', '2026-06-20', '2026-06-23', 4, 1.00, 261, 'checked_out', 1, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 5, 1, 291);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 261, 'online_banking', '2026-06-19 00:43:31', 'paid', 'assets/uploads/payments/payment_4_ba596fb3.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Nadia Aziz Bakar', '0148110653', 'nadia.aziz.bakar286@gmail.com', 'YD7920', 'Butterworth', '988255929471', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-08-04 19:43:31', '2026-08-07', '2026-08-12', 3, 1.00, 1410, 'checked_in', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 3, 1, 1460);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 1410, 'qr', '2026-08-06 19:43:31', 'paid', 'assets/uploads/payments/payment_5_f5718a78.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Qistina Mahmud Zulkifli', '0159635991', 'qistina.mahmud.zulkifli378@gmail.com', 'VU9501', 'Alor Setar', '805590406977', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-20 00:43:31', '2026-07-22', '2026-07-28', 1, 1.00, 634, 'confirmed', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 6, 1, 684);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 634, 'toyyibpay', '2026-07-21 00:43:31', 'paid', 'assets/uploads/payments/payment_7_e592b113.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Qistina Salleh Hassan', '0170888014', NULL, 'KA7333', 'Kuala Muda', '816473451473', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-06-08 05:43:31', '2026-06-14', '2026-06-21', 3, 1.00, 1119, 'checked_out', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 8, 1, 1169);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 1119, 'qr', '2026-06-09 05:43:31', 'paid', 'assets/uploads/payments/payment_3_7aaad95e.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Adam Karim Bakar', '0194890668', 'adam.karim.bakar31@gmail.com', 'CZ5793', 'Butterworth', '908531657095', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-08-05 12:43:31', '2026-08-11', '2026-08-14', 4, 1.00, 499, 'cancelled', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 8, 1, 549);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Danial Salleh Osman', '0162110508', 'danial.salleh.osman722@gmail.com', 'GE6418', 'Butterworth', '819817083851', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-01 01:43:31', '2026-07-15', '2026-07-18', 4, 1.00, 322, 'cancelled', 0, 0, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 6, 1, 372);

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Nabila Hassan Zulkifli', '0115115067', 'nabila.hassan.zulkifli526@gmail.com', 'PA2210', 'Butterworth', '819401130735', 0);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-09-22 08:43:31', '2026-10-01', '2026-10-03', 4, 1.00, 398, 'checked_in', 0, 0, 0);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 7, 1, 398);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 398, 'qr', '2026-09-24 08:43:31', 'paid', 'assets/uploads/payments/payment_4_ba596fb3.png');

INSERT INTO customer (full_name, phone, email, plate_num, location, ic_passport, whatsapp_optin) VALUES ('Haziq Abdullah Latif', '0114084196', NULL, 'ED2819', 'Kulim', '885037853782', 1);
SET @cid := LAST_INSERT_ID();
INSERT INTO booking (customer_id, booking_date, check_in, check_out, total_guest, deposit_amount, total_amount, booking_status, addon_bbq, addon_mattress, discount_amount) VALUES (@cid, '2026-07-07 05:43:31', '2026-07-21', '2026-07-24', 1, 1.00, 452, 'checked_in', 0, 1, 50);
SET @bid := LAST_INSERT_ID();
INSERT INTO booking_item (booking_id, accommodation_id, quantity, price) VALUES (@bid, 5, 1, 492);
INSERT INTO payment (booking_id, deposit_paid, payment_method, payment_date, payment_status, receipt) VALUES (@bid, 452, 'toyyibpay', '2026-07-08 05:43:31', 'paid', 'assets/uploads/payments/payment_5_f5718a78.png');

-- Seed ~20 demo reviews (unverified, booking_id NULL — same as footer widget).
-- Photos reused from assets/images/ (already exist on production, no upload needed).

INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'Great experience overall, comfortable beds and nice pool.', 'Haziq Firdaus', NULL, '2026-08-21 13:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 4, 'Enjoyed our trip, kids loved the pool and campsite area.', 'Rayyan Latif', 'assets/images/campsite-tent-pool.jpg', '2026-09-25 01:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'Amazing stay! The villa was clean and the view was breathtaking.', 'Aiman Rashid', 'assets/images/wooden-villa-porch-view.jpg', '2026-08-03 21:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'One of the best staycations we have had, very peaceful.', 'Nadia Hassan', NULL, '2026-08-17 09:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'Perfect getaway, exactly like the pictures. Loved it!', 'Nur Balqis', 'assets/images/casa1-porch.jpg', '2026-09-21 08:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'Beautiful place, beach was so close and staff were helpful.', NULL, 'assets/images/campsite-tents-pool.jpg', '2026-07-14 07:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 3, 'Okay stay, room was fine but check-in took a while.', NULL, NULL, '2026-07-25 05:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 4, 'Good stay overall, room was clean, aircond a bit noisy.', 'Zulkifli Bakar', NULL, '2026-08-28 07:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 4, 'Comfortable and quiet, good for family bonding time.', 'Farah Nabila', 'assets/images/villa-living-room.jpg', '2026-08-07 01:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 3, 'Okay stay, room was fine but check-in took a while.', 'Nadia Hassan', NULL, '2026-08-10 19:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'One of the best staycations we have had, very peaceful.', 'Amirul Hakim', 'assets/images/bedroom-modern-fan.jpg', '2026-09-24 23:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'Beautiful place, beach was so close and staff were helpful.', NULL, 'assets/images/wooden-villa-porch-view.jpg', '2026-08-24 04:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'One of the best staycations we have had, very peaceful.', 'Zulkifli Bakar', NULL, '2026-09-22 03:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'Great experience overall, comfortable beds and nice pool.', 'Farah Nabila', 'assets/images/casa1-porch.jpg', '2026-08-30 05:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'Perfect getaway, exactly like the pictures. Loved it!', 'Zulkifli Bakar', 'assets/images/casa2-close.jpg', '2026-07-22 02:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'Great experience overall, comfortable beds and nice pool.', 'Adam Yusof', NULL, '2026-08-16 10:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'Beautiful place, beach was so close and staff were helpful.', 'Izzat Rahman', 'assets/images/casa1-porch.jpg', '2026-09-07 08:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 4, 'Enjoyed our trip, kids loved the pool and campsite area.', 'Syafiqah Osman', 'assets/images/casa3-close.jpg', '2026-08-31 06:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'Beautiful place, beach was so close and staff were helpful.', 'Farah Nabila', 'assets/images/wooden-villa-porch-view.jpg', '2026-09-15 08:30:54');
INSERT INTO review (booking_id, rating, comment, display_name, image_path, review_date) VALUES (NULL, 5, 'One of the best staycations we have had, very peaceful.', NULL, NULL, '2026-08-18 01:30:54');
INSERT INTO gallery (image_path, caption) VALUES
('assets/images/villa-complex-day.jpg', 'Villa exterior view'),
('assets/images/villa-bedroom-bunk.jpg', 'Villa bedroom'),
('assets/images/villa-living-room.jpg', 'Villa living area'),
('assets/images/campsite-tents-pool.jpg', 'Campsite by the beach'),
('assets/images/campsite-tent-pool.jpg', 'Swimming pool'),
('assets/images/beach-lounge-bench.jpg', 'Poolside lounge'),
('assets/images/wooden-villa-sunset.jpg', 'Beachfront sunset'),
('assets/images/campsite-tents-pool.jpg', 'Campsite tents'),
('assets/images/casa3-balcony-view.jpg', 'Villa balcony view'),
('assets/images/wooden-villa-porch-view.jpg', 'Beachside walkway');
