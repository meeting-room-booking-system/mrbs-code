-- Add columns for registration opens and closes
-- Rename the enable_registrant_limit column for consistency

ALTER TABLE %DB_TBL_PREFIX%entry
  RENAME COLUMN enable_registrant_limit TO registrant_limit_enabled;

ALTER TABLE %DB_TBL_PREFIX%entry
  ADD COLUMN registration_opens int DEFAULT 1209600 NOT NULL, -- 2 weeks
  ADD COLUMN registration_opens_enabled smallint DEFAULT 0 NOT NULL,
  ADD COLUMN registration_closes int DEFAULT 0 NOT NULL,
  ADD COLUMN registration_closes_enabled smallint DEFAULT 0 NOT NULL;

comment on column %DB_TBL_PREFIX%entry.registration_opens is 'Seconds before the start time';
comment on column %DB_TBL_PREFIX%entry.registration_closes is 'Seconds before the start time';
