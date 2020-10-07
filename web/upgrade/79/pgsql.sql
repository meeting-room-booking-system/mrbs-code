
CREATE TABLE %DB_TBL_PREFIX%group
(
  id          serial primary key,
  auth_type   varchar(30) NOT NULL DEFAULT 'db',
  name        varchar(191) NOT NULL,

  CONSTRAINT %DB_TBL_PREFIX%uq_group_name_auth_type UNIQUE (name, auth_type)
);


CREATE TABLE %DB_TBL_PREFIX%user_group
(
  user_id   int NOT NULL
              REFERENCES %DB_TBL_PREFIX%user(id)
              ON UPDATE CASCADE
              ON DELETE CASCADE,
  group_id  int NOT NULL
              REFERENCES %DB_TBL_PREFIX%group(id)
              ON UPDATE CASCADE
              ON DELETE CASCADE,

  CONSTRAINT %DB_TBL_PREFIX%uq_user_group UNIQUE (user_id, group_id)
);
