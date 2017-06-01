-- Correct type of earlier timestamp fields
ALTER TABLE %DB_TBL_PREFIX%entry
  ALTER COLUMN timestamp TYPE timestamptz;
ALTER TABLE %DB_TBL_PREFIX%repeat
  ALTER COLUMN timestamp TYPE timestamptz;

-- Add a timestamp field for the users table

ALTER TABLE %DB_TBL_PREFIX%users
  ADD COLUMN timestamp timestamptz DEFAULT current_timestamp;
