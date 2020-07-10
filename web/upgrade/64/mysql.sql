-- Add a display_name column and make the display_name the same as the
-- username (for the moment - MRBS admins can insert the proper names later)

ALTER TABLE %DB_TBL_PREFIX%users
  ADD COLUMN display_name varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  
UPDATE %DB_TBL_PREFIX%users
  SET display_name=name;
