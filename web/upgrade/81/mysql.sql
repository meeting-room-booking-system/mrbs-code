-- Add columns for registration opens and closes

ALTER TABLE %DB_TBL_PREFIX%entry
  ADD COLUMN registration_opens int DEFAULT 1209600 NOT NULL COMMENT 'Seconds before the start time', -- 2 weeks
  ADD COLUMN registration_opens_enabled tinyint(1) DEFAULT 0 NOT NULL,
  ADD COLUMN registration_closes int DEFAULT 0 NOT NULL COMMENT 'Seconds before the start time',
  ADD COLUMN registration_closes_enabled tinyint(1) DEFAULT 0 NOT NULL;
