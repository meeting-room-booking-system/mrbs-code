#
# MySQL MRBS table creation script
#

CREATE TABLE mrbs_area
(
  id        int DEFAULT '0' NOT NULL auto_increment,
  area_name varchar(30),

  PRIMARY KEY (id)
);

# Generate some default areas
INSERT INTO mrbs_area ( id, area_name ) VALUES ('1', 'Building 1');
INSERT INTO mrbs_area ( id, area_name ) VALUES ('2', 'Building 2');

CREATE TABLE mrbs_room
(
  id          int DEFAULT '0' NOT NULL auto_increment,
  area_id     int DEFAULT '0' NOT NULL,
  
  room_name   varchar(25) DEFAULT '' NOT NULL,
  description varchar(60),
  capacity    int DEFAULT '0' NOT NULL,

  PRIMARY KEY (id)
);

# Generate some default rooms
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 1, "Room 1", "", 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 1, "Room 2", "", 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 1, "Room 3", "", 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 1, "Room 4", "", 8);

INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 2, "Room 1", "", 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 2, "Room 2", "", 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 2, "Room 3", "", 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 2, "Room 4", "", 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 2, "Room 5", "", 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 2, "Room 6", "", 8);

CREATE TABLE mrbs_entry
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

CREATE TABLE mrbs_repeat
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
  
  PRIMARY KEY (id)
);
