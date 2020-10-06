
CREATE TABLE %DB_TBL_PREFIX%group
(
  id          int NOT NULL auto_increment,
  auth_type   varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'db',
  name        varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_group_name_auth_type (name, auth_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
