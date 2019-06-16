--
-- Add rep_num_months

ALTER TABLE %DB_TBL_PREFIX%repeat
  ADD COLUMN rep_num_months smallint DEFAULT 1 NOT NULL;
