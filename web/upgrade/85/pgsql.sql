-- Make Unix timestamp columns 64 bit to enable dates after 2038 to be used

ALTER TABLE %DB_TBL_PREFIX%repeat
  ALTER COLUMN start_time TYPE bigint,
  ALTER COLUMN end_time TYPE bigint,
  ALTER COLUMN end_date TYPE bigint,
  ALTER COLUMN reminded TYPE bigint,
  ALTER COLUMN info_time TYPE bigint;

ALTER TABLE %DB_TBL_PREFIX%entry
  ALTER COLUMN start_time TYPE bigint,
  ALTER COLUMN end_time TYPE bigint,
  ALTER COLUMN reminded TYPE bigint,
  ALTER COLUMN info_time TYPE bigint;

ALTER TABLE %DB_TBL_PREFIX%participant
  ALTER COLUMN registered TYPE bigint;

ALTER TABLE %DB_TBL_PREFIX%zoneinfo
  ALTER COLUMN last_updated TYPE bigint;

ALTER TABLE %DB_TBL_PREFIX%session
  ALTER COLUMN access TYPE bigint;

ALTER TABLE %DB_TBL_PREFIX%user
  ALTER COLUMN last_login TYPE bigint,
  ALTER COLUMN reset_key_expiry TYPE bigint;
