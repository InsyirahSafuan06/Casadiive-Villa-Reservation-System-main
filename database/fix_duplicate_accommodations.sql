-- STEP 1: run this first to SEE the duplicates and confirm what will be removed.
SELECT accommodation_name, COUNT(*) AS total, GROUP_CONCAT(accommodation_id ORDER BY accommodation_id) AS ids
FROM accommodation
GROUP BY accommodation_name
HAVING COUNT(*) > 1;

-- STEP 2: after checking STEP 1 looks right, run this to delete the duplicate copies,
-- keeping only the OLDEST (smallest accommodation_id) row for each name.
-- If a booking already references one of the newer duplicate rows, this DELETE will fail
-- with a foreign key error instead of silently breaking that booking — if that happens,
-- stop and let Claude know before doing anything else.
DELETE a FROM accommodation a
JOIN (
  SELECT accommodation_name, MIN(accommodation_id) AS keep_id
  FROM accommodation
  GROUP BY accommodation_name
) keep ON keep.accommodation_name = a.accommodation_name
WHERE a.accommodation_id <> keep.keep_id;

-- STEP 3: confirm it's back to exactly 4 Villa + 4 Campsite (8 rows total).
SELECT accommodation_type, COUNT(*) FROM accommodation GROUP BY accommodation_type;
