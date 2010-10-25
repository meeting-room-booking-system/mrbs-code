-- $Id$

-- Rename provisional bookings

ALTER TABLE %DB_TBL_PREFIX%area 
RENAME COLUMN provisional_enabled TO approval_enabled;
