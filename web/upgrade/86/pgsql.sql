-- Various fixes to bring PostgreSQL in line with MySQL

ALTER TABLE %DB_TBL_PREFIX%entry
  ALTER COLUMN create_by SET DEFAULT '',
  ALTER COLUMN modified_by SET DEFAULT '',
  ALTER COLUMN name SET DEFAULT '';

ALTER TABLE %DB_TBL_PREFIX%repeat
  ALTER COLUMN create_by SET DEFAULT '',
  ALTER COLUMN modified_by SET DEFAULT '',
  ALTER COLUMN name SET DEFAULT '';
