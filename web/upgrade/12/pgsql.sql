-- $Id$
--
-- Adds a 'custom_html' field to the area and room tables.   Designed for
-- allowing, for example, an embedded Google Maps link to be displayed, but
-- could also contain any custom HTML.

ALTER TABLE %DB_TBL_PREFIX%area 
ADD COLUMN custom_html            text;

ALTER TABLE %DB_TBL_PREFIX%room 
ADD COLUMN custom_html            text;
