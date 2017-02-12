-- Make ical_recur_id nullable, so that we can give it a null value
-- when there is no recurrence

ALTER TABLE %DB_TBL_PREFIX%entry
  ALTER COLUMN ical_recur_id DROP DEFAULT,
  ALTER COLUMN ical_recur_id DROP NOT NULL,
  ALTER COLUMN ical_recur_id SET DEFAULT NULL;
  