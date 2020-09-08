-- Rename the users table and add an auth_type column (decided to have just a single user table)
-- Also change the default on a couple of int columns from '0' to 0 (probably not necessary, but just tidying up)

RENAME TABLE %DB_TBL_PREFIX%users_db TO %DB_TBL_PREFIX%user;

ALTER TABLE %DB_TBL_PREFIX%user
  ALTER COLUMN level SET DEFAULT 0,
  ALTER COLUMN last_login SET DEFAULT 0,
  ADD COLUMN auth_type varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'db',
  DROP INDEX uq_name, 
  ADD UNIQUE KEY uq_name_auth_type (name, auth_type);
