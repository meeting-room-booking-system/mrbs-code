-- $Id$
-- Run this script to upgrade postgres or mysql mrbs database

-- Add an extra column to the mrbs_entry and mrbs_repeat table 
-- for private bookings handling

ALTER TABLE %DB_TBL_PREFIX%repeat 
 ADD private BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE %DB_TBL_PREFIX%entry 
 ADD private BOOLEAN NOT NULL DEFAULT FALSE;
