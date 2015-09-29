-- $Id$

-- Add some UNIQUE constraints

ALTER TABLE %DB_TBL_PREFIX%room
  ADD CONSTRAINT "%DB_TBL_PREFIX%uq_room_name" UNIQUE (area_id, room_name);
  
ALTER TABLE %DB_TBL_PREFIX%area
  ADD CONSTRAINT "%DB_TBL_PREFIX%uq_area_name" UNIQUE (area_name);

ALTER TABLE %DB_TBL_PREFIX%users
  ADD CONSTRAINT "%DB_TBL_PREFIX%uq_name" UNIQUE (name);
  
ALTER TABLE %DB_TBL_PREFIX%variables
  ADD CONSTRAINT "%DB_TBL_PREFIX%uq_variable_name" UNIQUE (variable_name);
  
ALTER TABLE %DB_TBL_PREFIX%zoneinfo
  ADD CONSTRAINT "%DB_TBL_PREFIX%uq_timezone" UNIQUE (timezone, outlook_compatible);
