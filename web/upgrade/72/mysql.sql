-- Create the roles_areas table

CREATE TABLE %DB_TBL_PREFIX%roles_areas
(
  role_id     int NOT NULL,
  area_id     int NOT NULL,
  permission  char CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  state       char CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

  UNIQUE KEY uq_role_area (role_id, area_id),
  FOREIGN KEY (role_id)
    REFERENCES %DB_TBL_PREFIX%roles(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  FOREIGN KEY (area_id)
    REFERENCES %DB_TBL_PREFIX%area(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
