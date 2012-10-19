# $Id$

# Get rid of n-weekly repeats and make all weekly and n-weekly repeats weekly

# We save the current value of the timestamp before updating and restore it 
# afterwards because we do not want the timestamp to be changed by this operation

ALTER TABLE %DB_TBL_PREFIX%repeat
ADD COLUMN saved_ts DATETIME;
UPDATE %DB_TBL_PREFIX%repeat SET saved_ts=timestamp;

# Now the meat.  First set the repeat frequency to 1 for all existing weekly
# bookings.  Then turn all n-weekly bookings into weekly bookings
UPDATE %DB_TBL_PREFIX%repeat SET rep_num_weeks=1 WHERE rep_type=2;
UPDATE %DB_TBL_PREFIX%repeat SET rep_type=2 WHERE rep_type=6;

UPDATE %DB_TBL_PREFIX%repeat SET timestamp=saved_ts;
ALTER TABLE %DB_TBL_PREFIX%repeat
DROP COLUMN saved_ts;
