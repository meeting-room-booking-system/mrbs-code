-- $Id$
--
-- Sample data - make some areas and rooms. (This used to be in tables.sql)
-- This is meant to go into an empty database only!

--
-- If you have decided to change the prefix of your tables from 'mrbs_'
-- to something else then you must edit each 'INSERT INTO' line below.
--

-- Generate some default areas
INSERT INTO mrbs_area ( area_name ) VALUES ('Building 1');
INSERT INTO mrbs_area ( area_name ) VALUES ('Building 2');

-- Generate some default rooms
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 1, 'Room 1', '', 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 1, 'Room 2', '', 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 1, 'Room 3', '', 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 1, 'Room 4', '', 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 2, 'Room 1', '', 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 2, 'Room 2', '', 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 2, 'Room 3', '', 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 2, 'Room 4', '', 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 2, 'Room 5', '', 8);
INSERT INTO mrbs_room ( area_id, room_name, description, capacity ) VALUES ( 2, 'Room 6', '', 8);

