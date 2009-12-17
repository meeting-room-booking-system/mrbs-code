-- $Id$
--
-- Add columns to record the minimum and maximum times in advance
-- that ordinary users may make bookings

ALTER TABLE %DB_TBL_PREFIX%area
ADD COLUMN min_book_ahead_enabled  smallint,
ADD COLUMN min_book_ahead_secs     int,
ADD COLUMN max_book_ahead_enabled  smallint,
ADD COLUMN max_book_ahead_secs     int;
