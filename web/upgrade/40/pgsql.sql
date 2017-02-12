-- Add some foreign keys

-- First of all tidy up the database getting rid of any zombie rows which would prevent
-- the foreign keys being created.   Note that these rows will not be visible to users 
-- and admins through MRBS.   They have most likely been created when a row has been
-- deleted from a table using a database admin tool, rather than through MRBS.  Of course,
-- foreign keys will stop this happening in the future.

DELETE FROM %DB_TBL_PREFIX%room WHERE area_id NOT IN (SELECT id FROM %DB_TBL_PREFIX%area);
DELETE FROM %DB_TBL_PREFIX%repeat WHERE room_id NOT IN (SELECT id FROM %DB_TBL_PREFIX%room);
DELETE FROM %DB_TBL_PREFIX%entry WHERE room_id NOT IN (SELECT id FROM %DB_TBL_PREFIX%room);


ALTER TABLE %DB_TBL_PREFIX%room
  ADD FOREIGN KEY (area_id) 
    REFERENCES %DB_TBL_PREFIX%area(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT;

ALTER TABLE %DB_TBL_PREFIX%repeat
  ADD FOREIGN KEY (room_id) 
    REFERENCES %DB_TBL_PREFIX%room(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT;
    
ALTER TABLE %DB_TBL_PREFIX%entry
  ADD FOREIGN KEY (room_id) 
    REFERENCES %DB_TBL_PREFIX%room(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT;
