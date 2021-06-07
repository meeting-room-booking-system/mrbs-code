# Add a column to record whether the all day box should be checked by default

ALTER TABLE %DB_TBL_PREFIX%area 
ADD COLUMN default_duration_all_day tinyint DEFAULT 0 NOT NULL AFTER default_duration;
