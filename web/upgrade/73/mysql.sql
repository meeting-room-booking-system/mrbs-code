-- Create the roles_rooms table

CREATE TABLE %DB_TBL_PREFIX%roles_rooms
(
  role_id     int NOT NULL,
  room_id     int NOT NULL,
  permission  char CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  state       char CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

  UNIQUE KEY uq_role_room (role_id, room_id),
  FOREIGN KEY (role_id)
    REFERENCES %DB_TBL_PREFIX%roles(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  FOREIGN KEY (room_id)
    REFERENCES %DB_TBL_PREFIX%room(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
