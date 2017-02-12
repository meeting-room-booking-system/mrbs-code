ALTER TABLE %DB_TBL_PREFIX%area
ADD COLUMN provisional_enabled   tinyint(1),
ADD COLUMN reminders_enabled     tinyint(1);
