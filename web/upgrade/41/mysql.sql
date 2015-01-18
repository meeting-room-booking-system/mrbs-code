# $Id$

# Make mrbs_entry.repeat_id a foreign key
# Previously repeat_id was set to zero if there was no repeat.
# However this breaks our foreign key constraint so we have to modify
# the repeat_id column before we can create the foreign key.

ALTER TABLE %DB_TBL_PREFIX%entry
  MODIFY COLUMN repeat_id int DEFAULT NULL;
  
UPDATE %DB_TBL_PREFIX%entry
  SET repeat_id=NULL WHERE repeat_id=0;

ALTER TABLE %DB_TBL_PREFIX%entry
  ADD FOREIGN KEY (repeat_id) 
    REFERENCES %DB_TBL_PREFIX%repeat(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE;
    