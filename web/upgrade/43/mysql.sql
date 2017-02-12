-- Make max_duration a per-area setting

ALTER TABLE %DB_TBL_PREFIX%area
  ADD COLUMN max_duration_enabled tinyint(1) DEFAULT 0 NOT NULL,
  ADD COLUMN max_duration_secs int DEFAULT 0 NOT NULL,
  ADD COLUMN max_duration_periods int DEFAULT 0 NOT NULL;
