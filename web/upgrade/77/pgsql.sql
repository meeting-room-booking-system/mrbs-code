-- Add an invalid_types column

ALTER TABLE %DB_TBL_PREFIX%room 
  ADD COLUMN invalid_types varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
