-- $Id$

-- Rename and expand the password column in the users table

ALTER TABLE %DB_TBL_PREFIX%users
  RENAME COLUMN password TO password_hash;
ALTER TABLE %DB_TBL_PREFIX%users
  ALTER COLUMN password_hash TYPE varchar(255);
