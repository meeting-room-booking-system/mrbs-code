-- $Id$
--
-- mrbs/purge.my.sql 2001-01-13 : Purge old MRBS entries, for MySQL
--
-- This SQL script will delete old entries from your MRBS database.
-- By default, entries which ended 30 days or more in the past will be removed,
-- Repeat table records with no corresponding entry records will be removed.
--
-- If old entries get purged from a series, then somebody edits the series,
-- the old entries will be re-created unless they change the start date on
-- the form. Fixing this would require changing the start_time and end_time
-- in the repeat record to match oldest undeleted entry; this is left as an
-- exercise to the reader.
--
-- If you have decided to change the prefix of your tables from 'mrbs_'
-- to something else then you must edit each 'DELETE FROM' line below.
--
-- MySQL Notes:
-- To change the number of days, edit BOTH places below.
--
-- Because MySQL lacks sub-selects, I can't use SQL to remove orphan repeat
-- entries. (See purge.pg.sql for the "right way".) Instead, this removes
-- records from the repeat table based on their repeat end_date, which is
-- close enough to be almost the same thing.

DELETE FROM mrbs_entry
WHERE end_time < unix_timestamp(date_sub(current_timestamp, interval 30 day));

DELETE FROM mrbs_repeat
WHERE end_date < unix_timestamp(date_sub(current_timestamp, interval 30 day));
