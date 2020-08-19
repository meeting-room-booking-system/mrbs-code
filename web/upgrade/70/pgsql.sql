-- Rename the users table to make way for a global users table

ALTER TABLE %DB_TBL_PREFIX%users
  RENAME TO %DB_TBL_PREFIX%users_db;
