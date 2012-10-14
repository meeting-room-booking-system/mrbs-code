# $Id$

# Add a month_relative column so that monthly sameday repeats can be converted to the
# new format.   The conversion itself is done in post.inc

# We save the current value of the timestamp before updating and restore it 
# afterwards because we do not want the timestamp to be changed by this operation.
# The restoration is done in the next upgrade because we need to operate on the table
# first using post.inc
ALTER TABLE %DB_TBL_PREFIX%repeat
ADD COLUMN saved_ts DATETIME;
UPDATE %DB_TBL_PREFIX%repeat SET saved_ts=timestamp;

ALTER TABLE %DB_TBL_PREFIX%repeat
ADD COLUMN month_relative varchar(4) DEFAULT NULL;
