-- $Id$

ALTER TABLE %DB_TBL_PREFIX%area 
ADD COLUMN provisional_enabled    smallint,
ADD COLUMN reminders_enabled      smallint;
