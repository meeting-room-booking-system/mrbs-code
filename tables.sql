# MySQL dump 7.1
#
# Host: localhost    Database: mrbs
#--------------------------------------------------------
# Server version	3.22.32

#
# Table structure for table 'mrbs_area'
#
CREATE TABLE mrbs_area (
  id int(11) DEFAULT '0' NOT NULL auto_increment,
  area_name varchar(30),
  PRIMARY KEY (id)
);

#
# Dumping data for table 'mrbs_area'
#

INSERT INTO mrbs_area VALUES (1,'Building 1');
INSERT INTO mrbs_area VALUES (2,'Building 2');
INSERT INTO mrbs_area VALUES (3,'Northampton');
INSERT INTO mrbs_area VALUES (4,'Tokyo');

#
# Table structure for table 'mrbs_entry'
#
CREATE TABLE mrbs_entry (
  id int(11) DEFAULT '0' NOT NULL auto_increment,
  room_id int(11) DEFAULT '1' NOT NULL,
  create_by varchar(25) DEFAULT '' NOT NULL,
  start_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  end_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
  timestamp timestamp(14),
  type char(1) DEFAULT 'E' NOT NULL,
  name varchar(80) DEFAULT '' NOT NULL,
  description text,
  PRIMARY KEY (id),
  KEY idxDate (start_time)
);

#
# Dumping data for table 'mrbs_entry'
#


#
# Table structure for table 'mrbs_room'
#
CREATE TABLE mrbs_room (
  id int(11) DEFAULT '0' NOT NULL auto_increment,
  room_name varchar(25) DEFAULT '' NOT NULL,
  area_id int(11) DEFAULT '0' NOT NULL,
  description varchar(60),
  capacity int(11) DEFAULT '0' NOT NULL,
  PRIMARY KEY (id)
);

#
# Dumping data for table 'mrbs_room'
#

INSERT INTO mrbs_room VALUES (1,'Room1',2,'Building 2 Room 1',6);
INSERT INTO mrbs_room VALUES (2,'Room2',2,'Building 2 Room 2',10);
INSERT INTO mrbs_room VALUES (3,'Room3',2,'Building 2 Room 3',6);
INSERT INTO mrbs_room VALUES (4,'Room4',2,'Building 2 Room 4',6);
INSERT INTO mrbs_room VALUES (5,'Conf1',1,'Conference Room 1',25);
INSERT INTO mrbs_room VALUES (6,'Conf2',1,'Conference Room 2',30);
INSERT INTO mrbs_room VALUES (7,'Board_Room',1,'Board Room',40);
INSERT INTO mrbs_room VALUES (8,'NorthA',3,'Northampton Unit A',8);
INSERT INTO mrbs_room VALUES (9,'NorthB',3,'Northampton Unit B',5);
INSERT INTO mrbs_room VALUES (10,'Tokyo1',4,'Tokyo Room 1',0);
INSERT INTO mrbs_room VALUES (11,'Tokyo2',4,'Tokyo Room 2',0);
INSERT INTO mrbs_room VALUES (12,'Tokyo3',4,'Tokyo Room 3',0);
INSERT INTO mrbs_room VALUES (13,'Tokyo4',4,'Tokyo Room 4',0);
INSERT INTO mrbs_room VALUES (14,'Room5',2,'Building 2 Room 5',20);
INSERT INTO mrbs_room VALUES (15,'Room6',2,'Building 2 Room 6',20);
INSERT INTO mrbs_room VALUES (17,'NorthConf',3,'Northampton Conference',15);
INSERT INTO mrbs_room VALUES (18,'Tokyo5',4,'Tokyo Room 5',0);

