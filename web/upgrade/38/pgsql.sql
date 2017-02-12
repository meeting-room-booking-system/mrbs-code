-- Add finer grained policies, to distinguish between creation and deletion of
-- bookings.  Note that deletion of max_book_ahead bookings was always
-- allowed which is why max_delete_ahead_enabled is set to FALSE.

ALTER TABLE %DB_TBL_PREFIX%area
  ADD COLUMN min_create_ahead_enabled smallint,
  ADD COLUMN min_create_ahead_secs int,
  ADD COLUMN max_create_ahead_enabled smallint,
  ADD COLUMN max_create_ahead_secs int,
  ADD COLUMN min_delete_ahead_enabled smallint,
  ADD COLUMN min_delete_ahead_secs int,
  ADD COLUMN max_delete_ahead_enabled smallint,
  ADD COLUMN max_delete_ahead_secs int;

UPDATE %DB_TBL_PREFIX%area SET
  min_create_ahead_enabled = min_book_ahead_enabled,
  min_create_ahead_secs = min_book_ahead_secs,
  max_create_ahead_enabled = max_book_ahead_enabled,
  max_create_ahead_secs = max_book_ahead_secs,
  min_delete_ahead_enabled = min_book_ahead_enabled,
  min_delete_ahead_secs = min_book_ahead_secs,
  max_delete_ahead_enabled = 0,
  max_delete_ahead_secs = max_book_ahead_secs;
  
ALTER TABLE %DB_TBL_PREFIX%area
  DROP min_book_ahead_enabled,
  DROP min_book_ahead_secs,
  DROP max_book_ahead_enabled,
  DROP max_book_ahead_secs;
