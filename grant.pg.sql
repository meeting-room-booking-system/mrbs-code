-- $Id$
--
-- grant.pg.sql - Edit this to grant rights on PostgreSQL MRBS tables.
-- You should not need to use this file if you create the tables (using the
-- tables.pg.sql script) while connected to the database as the same user
-- MRBS will use (the one in config.inc), because that account will own the
-- tables and have all rights.  However if you create the tables with another
-- account, such as the superuser account, you need to use this script to
-- grant rights to the user found in your config.inc file.
-- Copy and edit this file as needed- Change the user name, then run it.

grant all on
   mrbs_area,mrbs_area_id_seq,
   mrbs_entry,mrbs_entry_id_seq,
   mrbs_repeat,mrbs_repeat_id_seq,
   mrbs_room,mrbs_room_id_seq
to mrbs;

