-- $Id$
--
-- Sample data - make some areas and rooms.
-- This is meant to go into a new empty database only! Deleting all rows from
-- all tables is not sufficient. You have to reset auto-increment fields too.

-- Generate some default areas
INSERT INTO mrbs_area ( id, area_name ) VALUES ( 1, 'Building 1');
INSERT INTO mrbs_area ( id, area_name ) VALUES ( 2, 'Building 2');
INSERT INTO mrbs_area_id_seq ( sequence ) VALUES ( 2);

-- Generate some default rooms
INSERT INTO mrbs_room ( id, area_id, room_name, description, capacity ) VALUES ( 1, 1, 'Room 1', '', 8);
INSERT INTO mrbs_room ( id, area_id, room_name, description, capacity ) VALUES ( 2, 1, 'Room 2', '', 8);
INSERT INTO mrbs_room ( id, area_id, room_name, description, capacity ) VALUES ( 3, 1, 'Room 3', '', 8);
INSERT INTO mrbs_room ( id, area_id, room_name, description, capacity ) VALUES ( 4, 1, 'Room 4', '', 8);
INSERT INTO mrbs_room ( id, area_id, room_name, description, capacity ) VALUES ( 5, 2, 'Room 1', '', 8);
INSERT INTO mrbs_room ( id, area_id, room_name, description, capacity ) VALUES ( 6, 2, 'Room 2', '', 8);
INSERT INTO mrbs_room ( id, area_id, room_name, description, capacity ) VALUES ( 7, 2, 'Room 3', '', 8);
INSERT INTO mrbs_room ( id, area_id, room_name, description, capacity ) VALUES ( 8, 2, 'Room 4', '', 8);
INSERT INTO mrbs_room ( id, area_id, room_name, description, capacity ) VALUES ( 9, 2, 'Room 5', '', 8);
INSERT INTO mrbs_room ( id, area_id, room_name, description, capacity ) VALUES ( 10, 2, 'Room 6', '', 8);
INSERT INTO mrbs_room_id_seq ( sequence ) VALUES ( 10);