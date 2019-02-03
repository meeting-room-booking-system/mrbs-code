-- Bring PostgreSQL into line with MySQL.

ALTER TABLE %DB_TBL_PREFIX%zoneinfo
  ALTER COLUMN timezone TYPE varchar(127);
 
ALTER TABLE %DB_TBL_PREFIX%sessions 
  ALTER COLUMN id TYPE varchar(191);
