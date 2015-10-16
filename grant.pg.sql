-- $Id$
--
-- grant.pg.sql - Edit this to grant rights on PostgreSQL MRBS tables.
-- You should not need to use this file if you create the tables (using the
-- tables.pg.sql script) while connected to the database as the same user
-- MRBS will use (the one in config.inc.php), because that account will own the
-- tables and have all rights.  However if you create the tables with another
-- account, such as the superuser account, you need to use this script to
-- grant rights to the user found in your config.inc.php file.
--
-- If you have decided to change the prefix of your tables from 'mrbs_'
-- to something else then you must change each reference to 'mrbs_' in the
-- lines below.
--
-- Copy and edit this file as needed- Change the user name, then run it.

GRANT ALL ON
   mrbs_area,mrbs_area_id_seq,
   mrbs_entry,mrbs_entry_id_seq,
   mrbs_repeat,mrbs_repeat_id_seq,
   mrbs_room,mrbs_room_id_seq,
   mrbs_users,mrbs_users_id_seq,
   mrbs_variables,mrbs_variables_id_seq,
   mrbs_zoneinfo,mrbs_zoneinfo_id_seq
TO mrbs;
