-- Rename and expand the password column in the users table

ALTER TABLE %DB_TBL_PREFIX%users
  CHANGE COLUMN `password` `password_hash` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci;
