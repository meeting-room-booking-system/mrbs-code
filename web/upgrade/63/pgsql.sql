-- Add an index to improve performance

create index "%DB_TBL_PREFIX_SHORT%idxRoomStartEnd" on %DB_TBL_PREFIX%entry(room_id, start_time, end_time);
