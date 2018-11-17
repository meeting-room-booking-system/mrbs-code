-- Add "last login" field to the users table

ALTER TABLE %DB_TBL_PREFIX%users
  ADD COLUMN `last_login` int DEFAULT 0 NOT NULL AFTER `timestamp`;
