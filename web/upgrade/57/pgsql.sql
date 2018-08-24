-- Extend the length of the id column.  32 was stoo short for some systems.

ALTER TABLE %DB_TBL_PREFIX%sessions
  ALTER COLUMN id TYPE varchar(255);
