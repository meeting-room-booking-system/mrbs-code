#
# MySQL MRBS table update script
#
# Note: This sql script is intended to upgrade a previous MRBS Mysql database
# to the new MySql schema created by the MDB::Manager. It creates tables and
# fields as was created by this manager.
# However, since:
# 1- the new schema is an XML schema,
# 2- this schema is parsed and translated in DMBS specific fields types
# It may occur that future MDB / Mysql releases creates different tables.
#
# ==> It may be better to create a php script using MDB::Manager to run the
# upgrade, to be sure that the proper tables and fields types be always
# created.
#
# $Id$

#----------------------mrbs_area-------

DROP TABLE IF EXISTS mrbs_area_tmp;

CREATE TABLE mrbs_area_tmp
(
  id        int         DEFAULT '0' NOT NULL auto_increment,
  area_name varchar(30),

  PRIMARY KEY (id)
);

INSERT INTO mrbs_area_tmp
SELECT *
FROM mrbs_area;

DROP TABLE mrbs_area;

CREATE TABLE mrbs_area
(
  id         int(11)     default '0' NOT NULL ,
  area_name char(30)     default NULL,

  UNIQUE KEY id (id)
);

INSERT INTO mrbs_area
SELECT *
FROM mrbs_area_tmp;

DROP TABLE mrbs_area_tmp;

CREATE TABLE mrbs_area_id_seq
(
  sequence int(11) NOT NULL auto_increment,

  PRIMARY KEY  (sequence)
);

INSERT INTO mrbs_area_id_seq
SELECT MAX(id)
FROM mrbs_area;

#----------------------mrbs_room-------

DROP TABLE IF EXISTS mrbs_room_tmp;

CREATE TABLE mrbs_room_tmp
(
  id          int             DEFAULT '0' NOT NULL auto_increment,
  area_id     int             DEFAULT '0' NOT NULL,
  room_name   varchar(25)     DEFAULT '' NOT NULL,
  description varchar(60),
  capacity    int             DEFAULT '0' NOT NULL,

  PRIMARY KEY (id)
);

INSERT INTO mrbs_room_tmp
SELECT *
FROM mrbs_room;

DROP TABLE mrbs_room;

CREATE TABLE mrbs_room
(
  id            int(11)     NOT NULL default '0',
  area_id       int(11)     NOT NULL default '0',
  room_name     char(25)    NOT NULL default '',
  description   char(60)    default NULL,
  capacity      int(11)     NOT NULL default '0',

  UNIQUE KEY id (id)
);

INSERT INTO mrbs_room
SELECT *
FROM mrbs_room_tmp;

DROP TABLE mrbs_room_tmp;

CREATE TABLE mrbs_room_id_seq
(
  sequence int(11) NOT NULL auto_increment,

  PRIMARY KEY  (sequence)
);

INSERT INTO mrbs_room_id_seq
SELECT MAX(id)
FROM mrbs_room;

#----------------------mrbs_repeat-------

DROP TABLE IF EXISTS mrbs_repeat_tmp;

CREATE TABLE mrbs_repeat_tmp
(
  id          int DEFAULT '0' NOT NULL auto_increment,
  start_time  int DEFAULT '0' NOT NULL,
  end_time    int DEFAULT '0' NOT NULL,
  rep_type    int DEFAULT '0' NOT NULL,
  end_date    int DEFAULT '0' NOT NULL,
  rep_opt     varchar(32) DEFAULT '' NOT NULL,
  room_id     int DEFAULT '1' NOT NULL,
  timestamp   timestamp,
  create_by   varchar(25) DEFAULT '' NOT NULL,
  name        varchar(80) DEFAULT '' NOT NULL,
  type        char DEFAULT 'E' NOT NULL,
  description text,
  rep_num_weeks tinyint DEFAULT '' NULL,

  PRIMARY KEY (id)
);

INSERT INTO mrbs_repeat_tmp
SELECT *
FROM mrbs_repeat;

DROP TABLE mrbs_repeat;

CREATE TABLE mrbs_repeat
(
  id            int(11)     NOT NULL default '0',
  start_time    int(11)     NOT NULL default '0',
  end_time      int(11)     NOT NULL default '0',
  rep_type      int(11)     NOT NULL default '0',
  end_date      int(11)     NOT NULL default '0',
  rep_opt       varchar(32) NOT NULL default '',
  room_id       int(11)     NOT NULL default '1',
  timestamp     datetime    default NULL,
  create_by     varchar(25) NOT NULL default '',
  name          varchar(80) NOT NULL default '',
  type          char(1)     NOT NULL default 'E',
  description   text,
  rep_num_weeks int(11)     default '0',

  UNIQUE KEY id (id)
);

INSERT INTO mrbs_repeat
SELECT *
FROM mrbs_repeat_tmp;

DROP TABLE mrbs_repeat_tmp;

CREATE TABLE mrbs_repeat_id_seq
(
  sequence int(11) NOT NULL auto_increment,

  PRIMARY KEY  (sequence)
);

INSERT INTO mrbs_repeat_id_seq
SELECT MAX(id)
FROM mrbs_repeat;

#----------------------mrbs_entry-------

DROP TABLE IF EXISTS mrbs_entry_tmp;

CREATE TABLE mrbs_entry_tmp
(
  id          int DEFAULT '0' NOT NULL auto_increment,
  start_time  int DEFAULT '0' NOT NULL,
  end_time    int DEFAULT '0' NOT NULL,
  entry_type  int DEFAULT '0' NOT NULL,
  repeat_id   int DEFAULT '0' NOT NULL,
  room_id     int DEFAULT '1' NOT NULL,
  timestamp   timestamp,
  create_by   varchar(25) DEFAULT '' NOT NULL,
  name        varchar(80) DEFAULT '' NOT NULL,
  type        char DEFAULT 'E' NOT NULL,
  description text,

  PRIMARY KEY (id),
  KEY idxStartTime (start_time),
  KEY idxEndTime   (end_time)
);

INSERT INTO mrbs_entry_tmp
SELECT *
FROM mrbs_entry;

DROP TABLE mrbs_entry;

CREATE TABLE mrbs_entry (
  id            int(11)     NOT NULL default '0',
  start_time    int(11)     NOT NULL default '0',
  end_time      int(11)     NOT NULL default '0',
  entry_type    int(11)     NOT NULL default '0',
  repeat_id     int(11)     NOT NULL default '0',
  room_id       int(11)     NOT NULL default '1',
  timestamp     datetime    default NULL,
  create_by     varchar(25) NOT NULL default '',
  name          varchar(80) NOT NULL default '',
  type          char(1)     NOT NULL default 'E',
  description   text,

  UNIQUE KEY id (id),
  KEY idxStartTime (start_time),
  KEY idxEndTime (end_time)
);

INSERT INTO mrbs_entry
SELECT *
FROM mrbs_entry_tmp;

DROP TABLE mrbs_entry_tmp;

CREATE TABLE mrbs_entry_id_seq
(
  sequence int(11) NOT NULL auto_increment,

  PRIMARY KEY  (sequence)
);

INSERT INTO mrbs_entry_id_seq
SELECT MAX(id)
FROM mrbs_entry;