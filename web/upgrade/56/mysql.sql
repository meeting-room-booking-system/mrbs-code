
CREATE TABLE %DB_TBL_PREFIX%sessions
(
  id      varchar(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  access  int unsigned DEFAULT NULL,
  data    text CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  
  PRIMARY KEY (id),
  KEY idxAccess (access)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
