-- $Id$

-- Get rid of n-weekly repeats and make all weekly and n-weekly repeats weekly

-- No need to save the current timestamp because timestamps do not auto-update
-- in PostgreSQL 

-- First set the repeat frequency to 1 for all existing weekly
-- bookings.  Then turn all n-weekly bookings into weekly bookings

UPDATE %DB_TBL_PREFIX%repeat SET rep_num_weeks=1 WHERE rep_type=2;
UPDATE %DB_TBL_PREFIX%repeat SET rep_type=2 WHERE rep_type=6;
