-- Create the group_role table

CREATE TABLE %DB_TBL_PREFIX%group_role
(
  group_id    int NOT NULL,
  role_id     int NOT NULL,

  UNIQUE KEY uq_group_role (group_id, role_id),
  FOREIGN KEY (group_id)
    REFERENCES %DB_TBL_PREFIX%group(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  FOREIGN KEY (role_id)
    REFERENCES %DB_TBL_PREFIX%role(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;