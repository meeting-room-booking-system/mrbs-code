-- Add columns for registration opens and closes

ALTER TABLE %DB_TBL_PREFIX%entry 
  ADD COLUMN registration_opens int DEFAULT NULL COMMENT 'Seconds before the start time',
  ADD COLUMN registration_closes int DEFAULT NULL COMMENT 'Seconds before the start time';
