# $Id$

# Make ical_recur_id nullable, so that we can give it a null value
# when there is no recurrence

ALTER TABLE %DB_TBL_PREFIX%entry
  MODIFY COLUMN ical_recur_id  varchar(16) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL;
    