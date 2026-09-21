-- Adds the IC/Passport Number field and WhatsApp updates opt-in to the booking form.
-- Safe to run once against a database created before this feature existed.

ALTER TABLE `customer`
  ADD COLUMN ic_passport VARCHAR(30) NULL
    COMMENT 'IC or passport number, collected on the booking form for guest verification'
    AFTER location,
  ADD COLUMN whatsapp_optin TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
    COMMENT 'guest opted in to receive booking updates via WhatsApp'
    AFTER ic_passport;
