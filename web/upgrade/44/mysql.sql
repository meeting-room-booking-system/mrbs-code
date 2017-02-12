-- Add some UNIQUE constraints

ALTER TABLE %DB_TBL_PREFIX%room
  ADD UNIQUE INDEX uq_room_name (area_id, room_name);
  
ALTER TABLE %DB_TBL_PREFIX%area
  ADD UNIQUE INDEX uq_area_name (area_name);

ALTER TABLE %DB_TBL_PREFIX%users
  ADD UNIQUE INDEX uq_name (name);
  
ALTER TABLE %DB_TBL_PREFIX%variables
  ADD UNIQUE INDEX uq_variable_name (variable_name);
  
ALTER TABLE %DB_TBL_PREFIX%zoneinfo
  ADD UNIQUE INDEX uq_timezone (timezone, outlook_compatible);
