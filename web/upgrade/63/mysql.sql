-- Add an index to improve performance

ALTER TABLE %DB_TBL_PREFIX%entry
  ADD INDEX idxRoomStartEnd (room_id, start_time, end_time);
  