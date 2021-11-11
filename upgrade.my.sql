
drop table mrbs_entry_tmp;

CREATE TABLE mrbs_entry_tmp (
  id int(11) NOT NULL auto_increment,
  room_id int(11) DEFAULT '1' NOT NULL,
  create_by varchar(25) DEFAULT '' NOT NULL,
  start_time int(11) NOT NULL,
  end_time int(11) NOT NULL,
  timestamp timestamp(14),
  type char(1) DEFAULT 'E' NOT NULL,
  name varchar(80) DEFAULT '' NOT NULL,
  description text,
  PRIMARY KEY (id)
);

insert into mrbs_entry_tmp (room_id, create_by, start_time,
            end_time, timestamp, type, name, description)

select room_id, create_by, unix_timestamp(start_time),
       unix_timestamp(end_time), timestamp, type,
       name, description
from mrbs_entry;

drop table mrbs_entry;

CREATE TABLE mrbs_entry (
  id int(11) NOT NULL auto_increment,
  room_id int(11) DEFAULT '1' NOT NULL,
  create_by varchar(25) DEFAULT '' NOT NULL,
  start_time int(11) NOT NULL,
  end_time int(11) NOT NULL,
  timestamp timestamp(14),
  type char(1) DEFAULT 'E' NOT NULL,
  name varchar(80) DEFAULT '' NOT NULL,
  description text,
  PRIMARY KEY (id),
  key idxStartTime (start_time),
  key idxEndTime (end_time)
);

insert into mrbs_entry select * from mrbs_entry_tmp;

drop table mrbs_entry_tmp;
