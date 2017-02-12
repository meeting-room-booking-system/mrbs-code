ALTER TABLE %DB_TBL_PREFIX%room
ADD COLUMN sort_key varchar(25) DEFAULT '' NOT NULL AFTER room_name,
ADD KEY idxSortKey (sort_key);
