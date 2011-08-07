#
# MySQL MRBS table creation script
#
# $Id$
#
# Notes:
# (1) If you have decided to change the prefix of your tables from 'mrbs_'
#     to something else using $db_tbl_prefix then you must edit each
#     'CREATE TABLE' and 'INSERT INTO' line below to replace 'mrbs_' with
#     your new table prefix.
#
# (2) If you change the varchar lengths here, then you should check
#     to see whether a corresponding length has been defined in the config file
#     in the array $maxlength.
#
# (3) If you add new fields then you should also change the global variable
#     $standard_fields.   Note that if you are just adding custom fields for
#     a single site then this is not necessary.

CREATE TABLE mrbs_area
(
  id                        int NOT NULL auto_increment,
  disabled                  tinyint(1) DEFAULT 0 NOT NULL,
  area_name                 varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci,
  area_admin_email          text CHARACTER SET utf8 COLLATE utf8_general_ci,
  resolution                int,
  default_duration          int,
  default_duration_all_day  tinyint(1) DEFAULT 0 NOT NULL,
  morningstarts             int,
  morningstarts_minutes     int,
  eveningends               int,
  eveningends_minutes       int,
  private_enabled           tinyint(1),
  private_default           tinyint(1),
  private_mandatory         tinyint(1),
  private_override          varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci,
  min_book_ahead_enabled    tinyint(1),
  min_book_ahead_secs       int,
  max_book_ahead_enabled    tinyint(1),
  max_book_ahead_secs       int,
  custom_html               text CHARACTER SET utf8 COLLATE utf8_general_ci,
  approval_enabled          tinyint(1),
  reminders_enabled         tinyint(1),
  enable_periods            tinyint(1),
  confirmation_enabled      tinyint(1),
  confirmed_default         tinyint(1),

  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE mrbs_room
(
  id               int NOT NULL auto_increment,
  disabled         tinyint(1) DEFAULT 0 NOT NULL,
  area_id          int DEFAULT '0' NOT NULL,
  room_name        varchar(25) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL,
  sort_key         varchar(25) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL,
  description      varchar(60) CHARACTER SET utf8 COLLATE utf8_general_ci,
  capacity         int DEFAULT '0' NOT NULL,
  room_admin_email text CHARACTER SET utf8 COLLATE utf8_general_ci,
  custom_html      text CHARACTER SET utf8 COLLATE utf8_general_ci,

  PRIMARY KEY (id),
  KEY idxSortKey (sort_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE mrbs_entry
(
  id             int NOT NULL auto_increment,
  start_time     int DEFAULT '0' NOT NULL,
  end_time       int DEFAULT '0' NOT NULL,
  entry_type     int DEFAULT '0' NOT NULL,
  repeat_id      int DEFAULT '0' NOT NULL,
  room_id        int DEFAULT '1' NOT NULL,
  timestamp      timestamp,
  create_by      varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL,
  name           varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL,
  type           char DEFAULT 'E' NOT NULL,
  description    text CHARACTER SET utf8 COLLATE utf8_general_ci,
  status         tinyint unsigned NOT NULL DEFAULT 0,
  reminded       int,
  info_time      int,
  info_user      varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci,
  info_text      text CHARACTER SET utf8 COLLATE utf8_general_ci,
  ical_uid       varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL,
  ical_sequence  smallint DEFAULT 0 NOT NULL,
  ical_recur_id  varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL,

  PRIMARY KEY (id),
  KEY idxStartTime (start_time),
  KEY idxEndTime   (end_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE mrbs_repeat
(
  id             int NOT NULL auto_increment,
  start_time     int DEFAULT '0' NOT NULL,
  end_time       int DEFAULT '0' NOT NULL,
  rep_type       int DEFAULT '0' NOT NULL,
  end_date       int DEFAULT '0' NOT NULL,
  rep_opt        varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL,
  room_id        int DEFAULT '1' NOT NULL,
  timestamp      timestamp,
  create_by      varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL,
  name           varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL,
  type           char DEFAULT 'E' NOT NULL,
  description    text CHARACTER SET utf8 COLLATE utf8_general_ci,
  rep_num_weeks  smallint NULL, 
  status         tinyint unsigned NOT NULL DEFAULT 0,
  reminded       int,
  info_time      int,
  info_user      varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci,
  info_text      text CHARACTER SET utf8 COLLATE utf8_general_ci,
  ical_uid       varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL,
  ical_sequence  smallint DEFAULT 0 NOT NULL,
  
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE mrbs_variables
(
  id               int NOT NULL auto_increment,
  variable_name    varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci,
  variable_content text CHARACTER SET utf8 COLLATE utf8_general_ci,
      
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE mrbs_zoneinfo
(
  id                 int NOT NULL auto_increment,
  timezone           varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL,
  outlook_compatible tinyint unsigned NOT NULL DEFAULT 0,
  vtimezone          text CHARACTER SET utf8 COLLATE utf8_general_ci,
  last_updated       int NOT NULL DEFAULT 0,
      
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE mrbs_users
(
  /* The first four fields are required. Don't remove. */
  id        int NOT NULL auto_increment,
  level     smallint DEFAULT '0' NOT NULL,  /* play safe and give no rights */
  name      varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci,
  password  varchar(40) CHARACTER SET utf8 COLLATE utf8_general_ci,
  email     varchar(75) CHARACTER SET utf8 COLLATE utf8_general_ci,

  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO mrbs_variables (variable_name, variable_content)
  VALUES ( 'db_version', '28');
INSERT INTO mrbs_variables (variable_name, variable_content)
  VALUES ( 'local_db_version', '1');
