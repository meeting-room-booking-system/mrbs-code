ALTER TABLE %DB_TBL_PREFIX%area
ADD COLUMN resolution            int,
ADD COLUMN default_duration      int,
ADD COLUMN morningstarts         int,
ADD COLUMN morningstarts_minutes int,
ADD COLUMN eveningends           int,
ADD COLUMN eveningends_minutes   int;

