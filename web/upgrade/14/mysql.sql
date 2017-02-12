# Add columns so that details about the last request for More Info on
# provisional bookings can be recorded.

ALTER TABLE %DB_TBL_PREFIX%entry 
ADD COLUMN info_time    int,
ADD COLUMN info_user    varchar(80),
ADD COLUMN info_text    text;

ALTER TABLE %DB_TBL_PREFIX%repeat 
ADD COLUMN info_time    int,
ADD COLUMN info_user    varchar(80),
ADD COLUMN info_text    text;
