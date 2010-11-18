-- $Id$

-- Add a column to record whether the area/room is enabled or disabled for use

ALTER TABLE %DB_TBL_PREFIX%area 
ADD COLUMN disabled smallint DEFAULT 0 NOT NULL;

ALTER TABLE %DB_TBL_PREFIX%room 
ADD COLUMN disabled smallint DEFAULT 0 NOT NULL;
