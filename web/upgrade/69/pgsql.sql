-- Add columns to allow password reset

ALTER TABLE %DB_TBL_PREFIX%users 
  ADD COLUMN reset_key_hash varchar(255),
  ADD COLUMN reset_key_expiry int DEFAULT 0 NOT NULL;
