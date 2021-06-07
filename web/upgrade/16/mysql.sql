#
# Add a column for enable_periods

ALTER TABLE %DB_TBL_PREFIX%area
ADD COLUMN enable_periods    tinyint;
