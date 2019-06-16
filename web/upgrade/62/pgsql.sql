-- Replace rep_num_weeks and rep_num_months by interval

-- No need to save the current timestamp because timestamps do not auto-update
-- in PostgreSQL.  However we do need to disable the triggger which does the
-- update and then re-enable it at the end.

ALTER TABLE %DB_TBL_PREFIX%repeat
  DISABLE TRIGGER USER;

ALTER TABLE %DB_TBL_PREFIX%repeat
  ADD COLUMN rep_interval smallint DEFAULT 1 NOT NULL;
  
UPDATE %DB_TBL_PREFIX%repeat
  SET rep_interval=rep_num_weeks WHERE rep_type=2 AND rep_num_weeks IS NOT NULL;
  
UPDATE %DB_TBL_PREFIX%repeat
  SET rep_interval=rep_num_months WHERE rep_type=3 AND rep_num_months IS NOT NULL;
  
ALTER TABLE %DB_TBL_PREFIX%repeat
  DROP COLUMN rep_num_weeks,
  DROP COLUMN rep_num_months;

ALTER TABLE %DB_TBL_PREFIX%repeat
  ENABLE TRIGGER USER;
  