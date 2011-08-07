# $Id$

CREATE TABLE %DB_TBL_PREFIX%zoneinfo
(
  id                 int NOT NULL auto_increment,
  timezone           varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '' NOT NULL,
  outlook_compatible tinyint unsigned NOT NULL DEFAULT 0,
  vtimezone          text CHARACTER SET utf8 COLLATE utf8_general_ci,
  last_updated       int NOT NULL DEFAULT 0,
      
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
