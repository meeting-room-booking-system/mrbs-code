-- Create the group_role table

CREATE TABLE %DB_TBL_PREFIX%group_role
(
  group_id    int NOT NULL
                REFERENCES %DB_TBL_PREFIX%group(id)
                ON UPDATE CASCADE
                ON DELETE CASCADE,
  role_id     int NOT NULL
                REFERENCES %DB_TBL_PREFIX%role(id)
                ON UPDATE CASCADE
                ON DELETE CASCADE,

  CONSTRAINT %DB_TBL_PREFIX_SHORT%uq_group_role UNIQUE (group_id, role_id)
);
