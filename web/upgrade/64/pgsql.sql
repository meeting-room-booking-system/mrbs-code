-- Add a display_name column and make the display_name the same as the
-- username (for the moment - MRBS admins can insert the proper names later)

ALTER TABLE %DB_TBL_PREFIX%users
  ADD COLUMN display_name varchar(255);
  
UPDATE %DB_TBL_PREFIX%users
  SET display_name=name;
