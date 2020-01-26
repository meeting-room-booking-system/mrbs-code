-- Add an index to improve performance

create index "%DB_TBL_PREFIX%idxRoomStartEnd" on %DB_TBL_PREFIX%entry(room_id, start_time, end_time);
