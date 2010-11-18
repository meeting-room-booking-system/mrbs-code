-- $Id$
--
-- MRBS table creation script - for PostgreSQL 7.3 and above
--
-- Notes:
-- (1) MySQL inserts the current date/time into any timestamp field which is not
--     specified on insert. To get the same effect, use PostgreSQL default
--     value current_timestamp.
-- 
-- (2) This script is EXPERIMENTAL. PostGreSQL folks have changed some features 
--     with 7.3 version which breaks many application, including mrbs :
--     - An empty string ('') is no longer allowed as the input into an
--       integer field. Formerly, it was silently interpreted as 0. If you want 
--       a field to be 0 then explicitly use 0, if you want it to be undefined 
--       then use NULL.
--     - "INSERT" statements with column lists must specify all values;
--       e.g., INSERT INTO tab (col1, col2) VALUES ('val1') is now invalid
--     This tables creation script now works with 7.3, but the second issue above
--     is already there, so currently mrbs does NOT work with pgsql 7.3 and above
--    (thierry_bo 2003-12-03)
--
-- (3) If you have decided to change the prefix of your tables from 'mrbs_'
--     to something else using $db_tbl_prefix then you must edit each
--     'CREATE TABLE', 'create index' and 'INSERT INTO' line below to replace
--     'mrbs_' with your new table prefix.
--
-- (4) If you change the varchar lengths here, then you should check
--     to see whether a corresponding length has been defined in the config file
--     in the array $maxlength.
--
-- (5) If you add new (standard) fields then you should also change the global variable
--     $standard_fields.    Note that if you are just adding custom fields for
--     a single site then this is not necessary.


CREATE TABLE mrbs_area
(
  id                     serial primary key,
  disabled               smallint DEFAULT 0 NOT NULL,
  area_name              varchar(30),
  area_admin_email       text,
  resolution             int,
  default_duration       int,
  morningstarts          int,
  morningstarts_minutes  int,
  eveningends            int,
  eveningends_minutes    int,
  private_enabled        smallint,
  private_default        smallint,
  private_mandatory      smallint,
  private_override       varchar(32),
  min_book_ahead_enabled smallint,
  min_book_ahead_secs    int,
  max_book_ahead_enabled smallint,
  max_book_ahead_secs    int,
  custom_html            text,
  approval_enabled       smallint,
  reminders_enabled      smallint,
  enable_periods         smallint,
  confirmation_enabled   smallint,
  confirmed_default      smallint
);

CREATE TABLE mrbs_room
(
  id                serial primary key,
  disabled          smallint DEFAULT 0 NOT NULL,
  area_id           int DEFAULT 0 NOT NULL,
  room_name         varchar(25) NOT NULL,
  sort_key          varchar(25) NOT NULL,
  description       varchar(60),
  capacity          int DEFAULT 0 NOT NULL,
  room_admin_email  text,
  custom_html       text
);
create index mrbs_idxSortKey on mrbs_room(sort_key);

CREATE TABLE mrbs_entry
(
  id          serial primary key,
  start_time  int DEFAULT 0 NOT NULL,
  end_time    int DEFAULT 0 NOT NULL,
  entry_type  int DEFAULT 0 NOT NULL,
  repeat_id   int DEFAULT 0 NOT NULL,
  room_id     int DEFAULT 1 NOT NULL,
  timestamp   timestamp DEFAULT current_timestamp,
  create_by   varchar(80) NOT NULL,
  name        varchar(80) NOT NULL,
  type        char DEFAULT 'E' NOT NULL,
  description text,
  status      smallint DEFAULT 0 NOT NULL,
  reminded    int,
  info_time   int,
  info_user   varchar(80),
  info_text   text
);
create index mrbs_idxStartTime on mrbs_entry(start_time);
create index mrbs_idxEndTime on mrbs_entry(end_time);

CREATE TABLE mrbs_repeat
(
  id          serial primary key,
  start_time  int DEFAULT 0 NOT NULL,
  end_time    int DEFAULT 0 NOT NULL,
  rep_type    int DEFAULT 0 NOT NULL,
  end_date    int DEFAULT 0 NOT NULL,
  rep_opt     varchar(32) NOT NULL,
  room_id     int DEFAULT 1 NOT NULL,
  timestamp   timestamp DEFAULT current_timestamp,
  create_by   varchar(80) NOT NULL,
  name        varchar(80) NOT NULL,
  type        char DEFAULT 'E' NOT NULL,
  description text,
  rep_num_weeks smallint DEFAULT 0 NULL,
  status      smallint DEFAULT 0 NOT NULL,
  reminded    int,
  info_time   int,
  info_user   varchar(80),
  info_text   text
);

CREATE TABLE mrbs_variables
(
  id               serial primary key,
  variable_name    varchar(80),
  variable_content text
);

CREATE TABLE mrbs_users
(
  /* The first four fields are required. Don't remove. */
  id        serial primary key,
  level     smallint DEFAULT '0' NOT NULL,  /* play safe and give no rights */
  name      varchar(30),
  password  varchar(40),
  email     varchar(75)
);

INSERT INTO mrbs_variables (variable_name, variable_content)
  VALUES ('db_version', '22');
INSERT INTO mrbs_variables (variable_name, variable_content)
  VALUES ('local_db_version', '1');
