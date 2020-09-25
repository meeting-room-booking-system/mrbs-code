-- Add an invalid_types column

ALTER TABLE %DB_TBL_PREFIX%room 
  ADD COLUMN invalid_types varchar(255) DEFAULT NULL;
