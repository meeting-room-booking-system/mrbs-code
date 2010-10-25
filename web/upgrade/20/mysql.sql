# $Id$

# Change the status fields in the entry and repeat tables
# to be unsigned rather than signed (they are used as bit fields)
# Note:  PostgreSQL does not have unsigned, but as the status
# fields there are smallints (there are no tinyints in PostgreSQL)
# it does not matter.

ALTER TABLE %DB_TBL_PREFIX%entry 
CHANGE status status tinyint unsigned NOT NULL DEFAULT 0;

ALTER TABLE %DB_TBL_PREFIX%repeat
CHANGE status status tinyint unsigned NOT NULL DEFAULT 0;


# Add two new settings to the area table.  (Note:  we could combine
# all the various 'booleans' into a integer field, in the same
# way as status is handled in the entry and repeat tables, but
# as there are relatively few rows in the area table, this does
# not seem worth doing).

ALTER TABLE %DB_TBL_PREFIX%area 
ADD COLUMN confirmation_enabled    tinyint(1),
ADD COLUMN confirmed_default       tinyint(1);
