-- This is a consolidated upgrade.  It could be simplified!

-- Rename the users table to make way for a global users table
RENAME TABLE %DB_TBL_PREFIX%users TO %DB_TBL_PREFIX%users_db;

CREATE TABLE %DB_TBL_PREFIX%roles
(
    id     int NOT NULL auto_increment,
    name   varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,

    PRIMARY KEY (id),
    UNIQUE KEY uq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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

-- Rename the users table and add an auth_type column (decided to have just a single user table)
-- Also change the default on a couple of int columns from '0' to 0 (probably not necessary, but just tidying up)
RENAME TABLE %DB_TBL_PREFIX%users_db TO %DB_TBL_PREFIX%user;

ALTER TABLE %DB_TBL_PREFIX%user
    ALTER COLUMN level SET DEFAULT 0,
    ALTER COLUMN last_login SET DEFAULT 0,
    ADD COLUMN auth_type varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'db',
    DROP INDEX uq_name,
    ADD UNIQUE KEY uq_name_auth_type (name, auth_type);

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

-- Rename tables for consistency with other tables
RENAME TABLE %DB_TBL_PREFIX%variables TO %DB_TBL_PREFIX%variable;
RENAME TABLE %DB_TBL_PREFIX%sessions TO %DB_TBL_PREFIX%session;
RENAME TABLE %DB_TBL_PREFIX%users_roles TO %DB_TBL_PREFIX%user_role;
RENAME TABLE %DB_TBL_PREFIX%roles_areas TO %DB_TBL_PREFIX%role_area;
RENAME TABLE %DB_TBL_PREFIX%roles_rooms TO %DB_TBL_PREFIX%role_room;

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
