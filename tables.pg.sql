-- $Id$
--
-- MRBS table creation script - for PostgreSQL
--
-- Notes:
-- MySQL inserts the current date/time into any timestamp field which is not
-- specified on insert. To get the same effect, use PostgreSQL default
-- value current_timestamp.

CREATE TABLE mrbs_area
(
  id        serial primary key,
  area_name varchar(30)
);

CREATE TABLE mrbs_room
(
  id          serial primary key,
  area_id     int DEFAULT 0 NOT NULL,
  room_name   varchar(25) DEFAULT '' NOT NULL,
  description varchar(60),
  capacity    int DEFAULT 0 NOT NULL
);

CREATE TABLE mrbs_entry
(
  id          serial primary key,
  start_time  int DEFAULT 0 NOT NULL,
  end_time    int DEFAULT 0 NOT NULL,
  entry_type  int DEFAULT 0 NOT NULL,
  repeat_id   int DEFAULT 0 NOT NULL,
  room_id     int DEFAULT 1 NOT NULL,
  timestamp   timestamp DEFAULT current_timestamp,
  create_by   varchar(25) DEFAULT '' NOT NULL,
  name        varchar(80) DEFAULT '' NOT NULL,
  type        char DEFAULT 'E' NOT NULL,
  description text
);
create index idxStartTime on mrbs_entry(start_time);
create index idxEndTime on mrbs_entry(end_time);

CREATE TABLE mrbs_repeat
(
  id          serial primary key,
  start_time  int DEFAULT 0 NOT NULL,
  end_time    int DEFAULT 0 NOT NULL,
  rep_type    int DEFAULT 0 NOT NULL,
  end_date    int DEFAULT 0 NOT NULL,
  rep_opt     varchar(32) DEFAULT '' NOT NULL,
  room_id     int DEFAULT 1 NOT NULL,
  timestamp   timestamp DEFAULT current_timestamp,
  create_by   varchar(25) DEFAULT '' NOT NULL,
  name        varchar(80) DEFAULT '' NOT NULL,
  type        char DEFAULT 'E' NOT NULL,
  description text
);
