-- Rename the users table to make way for a global users table

ALTER TABLE %DB_TBL_PREFIX%users
  RENAME TO %DB_TBL_PREFIX_SHORT%users_db;

ALTER SEQUENCE "%DB_TBL_PREFIX_SHORT%users_id_seq" RENAME TO "%DB_TBL_PREFIX_SHORT%users_db_id_seq";
