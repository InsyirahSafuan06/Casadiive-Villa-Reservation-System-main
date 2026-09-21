-- Adds the automatic long-stay discount (3+ nights = RM50 off) to the booking form.
-- Only the discount_amount column is new — the actual discount is already folded straight
-- into booking.total_amount at the time of booking (see LONG_STAY_DISCOUNT_MIN_NIGHTS /
-- LONG_STAY_DISCOUNT_AMOUNT in includes/helpers.php), same way the add-ons already work.
-- This column just remembers HOW MUCH was discounted, so receipts can show it as its own
-- line item instead of it being invisibly baked into the total.
-- Safe to run once against a database created before this feature existed.

ALTER TABLE `booking`
  ADD COLUMN discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0
    COMMENT 'long-stay discount already subtracted from total_amount at booking time (see LONG_STAY_DISCOUNT_* in includes/helpers.php)'
    AFTER addon_mattress;
