CREATE TABLE IF NOT EXISTS accommodation_rate_period (
  rate_period_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  accommodation_id INT UNSIGNED NOT NULL,
  label VARCHAR(120) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  UNIQUE KEY uq_accommodation_rate_dates (accommodation_id, start_date, end_date),
  INDEX idx_rate_period_dates (start_date, end_date),
  CONSTRAINT fk_rate_period_accommodation FOREIGN KEY (accommodation_id)
    REFERENCES accommodation(accommodation_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

ALTER TABLE accommodation
  ADD COLUMN IF NOT EXISTS price_seasonal DECIMAL(10,2) NULL AFTER price_holiday;

UPDATE accommodation SET price = 339, price_weekend = 359, price_holiday = 369, price_seasonal = 299, capacity = 5, pax_label = '4-5 PAX',
  features = CONCAT('1 Queen Bed', CHAR(10), '1 Bunk Bed', CHAR(10), 'Air-conditioned', CHAR(10), 'Pool', CHAR(10), 'Parking', CHAR(10), 'Clothes drying rack', CHAR(10), '2 Toilets', CHAR(10), 'Outdoor shower', CHAR(10), 'Outdoor sink'),
  description = 'One queen bed and one bunk bed. Comfortable for 4-5 guests.'
WHERE accommodation_name = 'Casa 1' AND accommodation_type = 'Villa';
UPDATE accommodation SET price = 239, price_weekend = 259, price_holiday = 269, price_seasonal = 199, capacity = 3, pax_label = '2-3 PAX',
  features = CONCAT('1 Queen Bed', CHAR(10), 'Air-conditioned', CHAR(10), 'Pool', CHAR(10), 'Parking', CHAR(10), 'Clothes drying rack', CHAR(10), '2 Toilets', CHAR(10), 'Outdoor shower', CHAR(10), 'Outdoor sink'),
  description = 'One queen bed. Comfortable for 2-3 guests.'
WHERE accommodation_name IN ('Casa 2', 'Casa 3') AND accommodation_type = 'Villa';
UPDATE accommodation SET price = 369, price_weekend = 389, price_holiday = 399, price_seasonal = 329, capacity = 5, pax_label = '4-5 PAX',
  features = CONCAT('2 Sofa Beds', CHAR(10), 'Kitchen', CHAR(10), 'Living Room', CHAR(10), 'Pool', CHAR(10), 'Parking', CHAR(10), 'Clothes drying rack', CHAR(10), '2 Toilets', CHAR(10), 'Outdoor shower', CHAR(10), 'Outdoor sink'),
  description = 'Two sofa beds, a kitchen and a living room. Comfortable for 4-5 guests.'
WHERE accommodation_name = 'Casa 4' AND accommodation_type = 'Villa';

INSERT INTO accommodation_rate_period (accommodation_id, label, start_date, end_date, price)
SELECT accommodation_id, 'Super Peak - Chinese New Year 2026', '2026-02-15', '2026-02-20',
       CASE accommodation_name WHEN 'Casa 1' THEN 369 WHEN 'Casa 2' THEN 269 WHEN 'Casa 3' THEN 269 WHEN 'Casa 4' THEN 399 END
FROM accommodation WHERE accommodation_type = 'Villa' AND accommodation_name IN ('Casa 1', 'Casa 2', 'Casa 3', 'Casa 4')
ON DUPLICATE KEY UPDATE label = VALUES(label), price = VALUES(price);
INSERT INTO accommodation_rate_period (accommodation_id, label, start_date, end_date, price)
SELECT accommodation_id, 'Super Peak - Chinese New Year 2027', '2027-02-06', '2027-02-07',
       CASE accommodation_name WHEN 'Casa 1' THEN 369 WHEN 'Casa 2' THEN 269 WHEN 'Casa 3' THEN 269 WHEN 'Casa 4' THEN 399 END
FROM accommodation WHERE accommodation_type = 'Villa' AND accommodation_name IN ('Casa 1', 'Casa 2', 'Casa 3', 'Casa 4')
ON DUPLICATE KEY UPDATE label = VALUES(label), price = VALUES(price);
INSERT INTO accommodation_rate_period (accommodation_id, label, start_date, end_date, price)
SELECT accommodation_id, 'Super Peak - Hari Raya and School Break 2027', '2027-03-05', '2027-03-13',
       CASE accommodation_name WHEN 'Casa 1' THEN 369 WHEN 'Casa 2' THEN 269 WHEN 'Casa 3' THEN 269 WHEN 'Casa 4' THEN 399 END
FROM accommodation WHERE accommodation_type = 'Villa' AND accommodation_name IN ('Casa 1', 'Casa 2', 'Casa 3', 'Casa 4')
ON DUPLICATE KEY UPDATE label = VALUES(label), price = VALUES(price);

UPDATE accommodation SET price = 50.00 WHERE accommodation_name = 'Campsite Package 1' AND accommodation_type = 'Campsite';
UPDATE accommodation SET price = 80.00 WHERE accommodation_name = 'Campsite Package 2' AND accommodation_type = 'Campsite';
UPDATE accommodation SET price = 110.00 WHERE accommodation_name = 'Campsite Package 3' AND accommodation_type = 'Campsite';
UPDATE accommodation SET price = 130.00 WHERE accommodation_name = 'Campsite Package 4' AND accommodation_type = 'Campsite';
