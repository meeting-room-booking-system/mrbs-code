-- $Id$
--
-- Add column to record the last time a reminder was sent.
-- (Unfortunately, timestamps in PostgreSQL are not automatically
-- updated on UPDATE, so updating the reminders count does not have
-- the effect of changing the timestamp, as it does in MySQL).

ALTER TABLE %DB_TBL_PREFIX%entry
ADD COLUMN reminded            int;

ALTER TABLE %DB_TBL_PREFIX%repeat
ADD COLUMN reminded            int;
