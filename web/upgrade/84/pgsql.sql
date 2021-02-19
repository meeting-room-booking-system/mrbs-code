-- Add a new column to the area table

ALTER TABLE %DB_TBL_PREFIX%area
  ADD COLUMN periods_booking_opens time DEFAULT '00:00:00' NOT NULL;
