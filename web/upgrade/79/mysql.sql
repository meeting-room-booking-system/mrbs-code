
CREATE TABLE %DB_TBL_PREFIX%group
(
  id          int NOT NULL auto_increment,
  auth_type   varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'db',
  name        varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

  PRIMARY KEY (id),
  UNIQUE KEY uq_group_name_auth_type (name, auth_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE %DB_TBL_PREFIX%user_group
(
  user_id     int NOT NULL,
  group_id    int NOT NULL,

  UNIQUE KEY uq_user_group (user_id, group_id),
  FOREIGN KEY (user_id)
    REFERENCES %DB_TBL_PREFIX%user(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  FOREIGN KEY (group_id)
    REFERENCES %DB_TBL_PREFIX%group(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
