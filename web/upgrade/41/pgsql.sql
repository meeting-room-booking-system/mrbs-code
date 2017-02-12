-- Make mrbs_entry.repeat_id a foreign key
-- Previously repeat_id was set to zero if there was no repeat.
-- However this breaks our foreign key constraint so we have to modify
-- the repeat_id column before we can create the foreign key.

ALTER TABLE %DB_TBL_PREFIX%entry
  ALTER COLUMN repeat_id DROP DEFAULT,
  ALTER COLUMN repeat_id DROP NOT NULL,
  ALTER COLUMN repeat_id SET DEFAULT NULL;
  
UPDATE %DB_TBL_PREFIX%entry
  SET repeat_id=NULL WHERE repeat_id=0;
  
-- Tidy up the database getting rid of any zombie rows which would prevent
-- the foreign key being created.   Note that these rows will not be visible to users 
-- and admins through MRBS.   They have most likely been created when a row has been
-- deleted from a table using a database admin tool, rather than through MRBS.  Of course,
-- foreign keys will stop this happening in the future.

DELETE FROM %DB_TBL_PREFIX%entry
  WHERE repeat_id IS NOT NULL
  AND repeat_id NOT IN (SELECT id FROM %DB_TBL_PREFIX%repeat);

-- Also, while we're at it, get rid of any redundant entries in the repeat table, ie rows
-- that are not referenced by a row in the entry table.
DELETE FROM %DB_TBL_PREFIX%repeat
  WHERE id NOT IN (SELECT repeat_id FROM %DB_TBL_PREFIX%entry WHERE repeat_id IS NOT NULL);
  

ALTER TABLE %DB_TBL_PREFIX%entry
  ADD FOREIGN KEY (repeat_id) 
    REFERENCES %DB_TBL_PREFIX%repeat(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE;
    