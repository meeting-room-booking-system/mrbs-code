-- Rename the roles and participants tables for consistency with other tables
RENAME TABLE %DB_TBL_PREFIX%roles TO %DB_TBL_PREFIX%role;
RENAME TABLE %DB_TBL_PREFIX%participants TO %DB_TBL_PREFIX%participant;

-- Drop and re-create the roles_rooms table (easier than altering the
-- foreign key which involves finding the reference)
DROP TABLE %DB_TBL_PREFIX%roles_rooms;
CREATE TABLE %DB_TBL_PREFIX%roles_rooms
(
  role_id     int NOT NULL,
  room_id     int NOT NULL,
  permission  char CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  state       char CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

  UNIQUE KEY uq_role_room (role_id, room_id),
  FOREIGN KEY (role_id)
  REFERENCES %DB_TBL_PREFIX%role(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE,
  FOREIGN KEY (room_id)
  REFERENCES %DB_TBL_PREFIX%room(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Drop and re-create the roles_areas table (easier than altering the
-- foreign key which involves finding the reference)
DROP TABLE %DB_TBL_PREFIX%roles_areas;
CREATE TABLE %DB_TBL_PREFIX%roles_areas
(
  role_id     int NOT NULL,
  area_id     int NOT NULL,
  permission  char CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  state       char CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

  UNIQUE KEY uq_role_area (role_id, area_id),
  FOREIGN KEY (role_id)
  REFERENCES %DB_TBL_PREFIX%role(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE,
  FOREIGN KEY (area_id)
  REFERENCES %DB_TBL_PREFIX%area(id)
  ON UPDATE CASCADE
  ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create the users_roles table
CREATE TABLE %DB_TBL_PREFIX%users_roles
(
  user_id     int NOT NULL,
  role_id     int NOT NULL,

  UNIQUE KEY uq_user_role (user_id, role_id),
  FOREIGN KEY (user_id)
    REFERENCES %DB_TBL_PREFIX%user(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  FOREIGN KEY (role_id)
    REFERENCES %DB_TBL_PREFIX%role(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
