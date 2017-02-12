# Rename provisional bookings

ALTER TABLE %DB_TBL_PREFIX%area 
CHANGE provisional_enabled approval_enabled tinyint(1);
