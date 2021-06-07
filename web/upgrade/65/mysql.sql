-- Add extra columns to the area table to hold area settings

ALTER TABLE %DB_TBL_PREFIX%area 
 ADD times_along_top tinyint NOT NULL DEFAULT 0,
 ADD default_type char DEFAULT 'E' NOT NULL;
