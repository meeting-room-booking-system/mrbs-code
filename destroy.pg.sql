-- $Id$
--
-- MRBS table destruction script for PostgreSQL 7.0 or higher
-- This exists because I can never remember the sequence name magic.
--

DROP TABLE mrbs_area;
DROP SEQUENCE mrbs_area_id_seq;
DROP TABLE mrbs_room;
DROP SEQUENCE mrbs_room_id_seq;
DROP TABLE mrbs_entry;
DROP SEQUENCE mrbs_entry_id_seq;
DROP TABLE mrbs_repeat;
DROP SEQUENCE mrbs_repeat_id_seq;
