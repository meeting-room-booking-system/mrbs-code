ALTER TABLE %DB_TBL_PREFIX%room
ADD COLUMN sort_key varchar(25) DEFAULT '' NOT NULL;

create index %DB_TBL_PREFIX%idxSortKey on %DB_TBL_PREFIX%room(sort_key);

