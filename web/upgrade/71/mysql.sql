
CREATE TABLE %DB_TBL_PREFIX%roles
(
  id     int NOT NULL auto_increment,
  name   varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  
  PRIMARY KEY (id),
  UNIQUE KEY uq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
