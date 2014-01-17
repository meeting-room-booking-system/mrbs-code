# $Id$

# Add a modified_by column so that we can see who last edited a booking

ALTER TABLE %DB_TBL_PREFIX%entry
ADD COLUMN modified_by varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL AFTER create_by;

ALTER TABLE %DB_TBL_PREFIX%repeat
ADD COLUMN modified_by varchar(80) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL AFTER create_by;
