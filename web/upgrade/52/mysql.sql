-- Add a timestamp field for the users table

ALTER TABLE %DB_TBL_PREFIX%users
  ADD COLUMN `timestamp` timestamp  DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `email`;
 