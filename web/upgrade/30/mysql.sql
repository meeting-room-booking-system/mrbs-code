# Add the max number of bookings fields

ALTER TABLE %DB_TBL_PREFIX%area 
ADD COLUMN max_per_day_enabled       tinyint DEFAULT 0 NOT NULL,
ADD COLUMN max_per_day               int DEFAULT 0 NOT NULL,
ADD COLUMN max_per_week_enabled      tinyint DEFAULT 0 NOT NULL,
ADD COLUMN max_per_week              int DEFAULT 0 NOT NULL,
ADD COLUMN max_per_month_enabled     tinyint DEFAULT 0 NOT NULL,
ADD COLUMN max_per_month             int DEFAULT 0 NOT NULL,
ADD COLUMN max_per_year_enabled      tinyint DEFAULT 0 NOT NULL,
ADD COLUMN max_per_year              int DEFAULT 0 NOT NULL,
ADD COLUMN max_per_future_enabled    tinyint DEFAULT 0 NOT NULL,
ADD COLUMN max_per_future            int DEFAULT 0 NOT NULL;
