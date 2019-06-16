# Replace rep_num_weeks and rep_num_months by interval

# We save the current value of the timestamp before updating and restore it 
# afterwards because we do not want the timestamp to be changed by this operation

ALTER TABLE %DB_TBL_PREFIX%repeat
  ADD COLUMN saved_ts DATETIME;
  
UPDATE %DB_TBL_PREFIX%repeat
  SET saved_ts=timestamp;

ALTER TABLE %DB_TBL_PREFIX%repeat
  ADD COLUMN rep_interval smallint DEFAULT 1 NOT NULL AFTER rep_num_months;
  
UPDATE %DB_TBL_PREFIX%repeat
  SET rep_interval=rep_num_weeks WHERE rep_type=2 AND rep_num_weeks IS NOT NULL;
  
UPDATE %DB_TBL_PREFIX%repeat
  SET rep_interval=rep_num_months WHERE rep_type=3 AND rep_num_months IS NOT NULL;
  
ALTER TABLE %DB_TBL_PREFIX%repeat
  DROP COLUMN rep_num_weeks,
  DROP COLUMN rep_num_months;

UPDATE %DB_TBL_PREFIX%repeat
  SET timestamp=saved_ts;
  
ALTER TABLE %DB_TBL_PREFIX%repeat
  DROP COLUMN saved_ts;