-- $Id$

-- Add hash format column to users table

ALTER TABLE %DB_TBL_PREFIX%users
  MODIFY COLUMN `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci,
  ADD COLUMN `hash_format` varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci AFTER `password`;

UPDATE %DB_TBL_PREFIX%users SET hash_format='md5' WHERE hash_format IS NULL;
