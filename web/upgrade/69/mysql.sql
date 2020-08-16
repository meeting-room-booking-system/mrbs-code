-- Add columns to allow password reset

ALTER TABLE %DB_TBL_PREFIX%users 
  ADD COLUMN reset_key_hash varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  ADD COLUMN reset_key_expiry int DEFAULT 0 NOT NULL;
