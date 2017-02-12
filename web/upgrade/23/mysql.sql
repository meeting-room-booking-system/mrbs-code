# Add UID and SEQUENCE columns for use with iCalendar

ALTER TABLE %DB_TBL_PREFIX%entry 
ADD COLUMN ical_uid        varchar(255) DEFAULT '' NOT NULL,
ADD COLUMN ical_sequence   smallint DEFAULT 0 NOT NULL;

ALTER TABLE %DB_TBL_PREFIX%repeat
ADD COLUMN ical_uid        varchar(255) DEFAULT '' NOT NULL,
ADD COLUMN ical_sequence   smallint DEFAULT 0 NOT NULL;
