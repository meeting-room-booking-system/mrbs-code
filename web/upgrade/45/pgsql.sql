-- $Id$

-- Add hash format column to users table

ALTER TABLE %DB_TBL_PREFIX%users
  ALTER COLUMN password TYPE varchar(255),
  ADD COLUMN hash_format varchar(16);

UPDATE %DB_TBL_PREFIX%users SET hash_format='md5' WHERE hash_format IS NULL;
