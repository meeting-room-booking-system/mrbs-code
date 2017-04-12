-- Add a field for the period names

ALTER TABLE %DB_TBL_PREFIX%area
  ADD COLUMN `periods` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL AFTER `enable_periods`;
 