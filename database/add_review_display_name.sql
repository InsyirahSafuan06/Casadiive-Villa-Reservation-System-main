-- Adds the "post as a chosen name or anonymously" feature to existing installs.
-- Safe to run once against a database that was created before this column existed.
-- (Fresh installs get this column already, straight from database.sql.)

ALTER TABLE `review`
  ADD COLUMN display_name VARCHAR(100) NULL
    COMMENT 'name the guest chose to show publicly; NULL/empty means the review is shown as Anonymous — never falls back to the booking''s real name'
    AFTER comment;
