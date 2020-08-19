-- Rename the users table to make way for a global users table

RENAME TABLE %DB_TBL_PREFIX%users TO %DB_TBL_PREFIX%users_db
