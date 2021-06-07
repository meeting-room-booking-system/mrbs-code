ALTER TABLE %DB_TBL_PREFIX%area
ADD COLUMN provisional_enabled   tinyint,
ADD COLUMN reminders_enabled     tinyint;
