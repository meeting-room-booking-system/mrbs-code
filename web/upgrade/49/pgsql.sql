-- Add a sort_key for the area table and populate it with the area_name.
-- Just in case the area_name column has been extended, truncate the area_name when copying it into the sort_key

ALTER TABLE %DB_TBL_PREFIX%area
  ADD COLUMN sort_key varchar(30) DEFAULT '' NOT NULL;
  
UPDATE %DB_TBL_PREFIX%area
  SET sort_key=SUBSTR(area_name, 1, 30);
