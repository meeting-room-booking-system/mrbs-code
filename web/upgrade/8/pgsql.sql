-- $Id$

ALTER TABLE %DB_TBL_PREFIX%room
ADD COLUMN sort_key varchar(25) DEFAULT '' NOT NULL;

create index idxSortKey on mrbs_room(sort_key);

