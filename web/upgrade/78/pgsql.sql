-- Add columns for registration opens and closes

ALTER TABLE %DB_TBL_PREFIX%entry
  ADD COLUMN registration_opens int DEFAULT NULL,
  ADD COLUMN registration_closes int DEFAULT NULL;

comment on column %DB_TBL_PREFIX%entry.registration_opens is 'Seconds before the start time';
comment on column %DB_TBL_PREFIX%entry.registration_closes is 'Seconds before the start time';
