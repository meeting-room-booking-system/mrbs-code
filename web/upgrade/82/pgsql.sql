-- This is a consolidated upgrade.  It could be simplified!

-- Rename the users table to make way for a global users table
ALTER TABLE %DB_TBL_PREFIX%users
RENAME TO %DB_TBL_PREFIX_SHORT%users_db;

ALTER SEQUENCE "%DB_TBL_PREFIX_SHORT%users_id_seq" RENAME TO "%DB_TBL_PREFIX_SHORT%users_db_id_seq";


-- Rename the users table and add an auth_type column (decided to have just a single user table)
-- Also change the default on an int column from '0' to 0 (probably not necessary, but just tidying up)
ALTER TABLE %DB_TBL_PREFIX%users_db
RENAME TO %DB_TBL_PREFIX_SHORT%user;

ALTER SEQUENCE "%DB_TBL_PREFIX_SHORT%users_db_id_seq" RENAME TO "%DB_TBL_PREFIX_SHORT%user_id_seq";

ALTER TABLE %DB_TBL_PREFIX%user
ALTER COLUMN level SET DEFAULT 0,
    ADD COLUMN auth_type varchar(30) NOT NULL DEFAULT 'db',
DROP CONSTRAINT "%DB_TBL_PREFIX_SHORT%uq_name",
    ADD CONSTRAINT "%DB_TBL_PREFIX_SHORT%uq_name_auth_type" UNIQUE (name, auth_type);

-- Create the role table
CREATE TABLE %DB_TBL_PREFIX%role
(
    id     serial primary key,
    name   varchar(191) NOT NULL,

    CONSTRAINT "%DB_TBL_PREFIX_SHORT%uq_name" UNIQUE (name)
);

-- Fix the existing trigger
CREATE TRIGGER "update_%DB_TBL_PREFIX%user_timestamp" BEFORE UPDATE ON %DB_TBL_PREFIX%user FOR EACH ROW EXECUTE PROCEDURE update_timestamp_column();

-- Create the role_room table
CREATE TABLE %DB_TBL_PREFIX%role_room
(
    role_id     int NOT NULL
        REFERENCES %DB_TBL_PREFIX%role(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    room_id     int NOT NULL
        REFERENCES %DB_TBL_PREFIX%room(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    permission  char NOT NULL,
    state       char NOT NULL,

    CONSTRAINT "%DB_TBL_PREFIX_SHORT%uq_role_room" UNIQUE (role_id, room_id)
);


-- Create the role_area table
CREATE TABLE %DB_TBL_PREFIX%role_area
(
    role_id     int NOT NULL
        REFERENCES %DB_TBL_PREFIX%role(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    area_id     int NOT NULL
        REFERENCES %DB_TBL_PREFIX%area(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    permission  char NOT NULL,
    state       char NOT NULL,

    CONSTRAINT "%DB_TBL_PREFIX_SHORT%uq_role_area" UNIQUE (role_id, area_id)
);


-- Create the user_role table
CREATE TABLE %DB_TBL_PREFIX%user_role
(
    user_id     int NOT NULL
        REFERENCES %DB_TBL_PREFIX%user(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    role_id     int NOT NULL
        REFERENCES %DB_TBL_PREFIX%role(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT "%DB_TBL_PREFIX_SHORT%uq_user_role" UNIQUE (user_id, role_id)
);

-- Rename tables for consistency with other tables
ALTER TABLE %DB_TBL_PREFIX%variables
RENAME TO %DB_TBL_PREFIX_SHORT%variable;

ALTER SEQUENCE "%DB_TBL_PREFIX_SHORT%variables_id_seq" RENAME TO "%DB_TBL_PREFIX_SHORT%variable_id_seq";

ALTER TABLE %DB_TBL_PREFIX%sessions
RENAME TO %DB_TBL_PREFIX_SHORT%session;

ALTER TABLE %DB_TBL_PREFIX%participants
RENAME TO %DB_TBL_PREFIX_SHORT%participant;

ALTER SEQUENCE "%DB_TBL_PREFIX_SHORT%participants_id_seq" RENAME TO "%DB_TBL_PREFIX_SHORT%participant_id_seq";


CREATE TABLE %DB_TBL_PREFIX%group
(
    id          serial primary key,
    auth_type   varchar(30) NOT NULL DEFAULT 'db',
    name        varchar(191) NOT NULL,

    CONSTRAINT "%DB_TBL_PREFIX_SHORT%uq_group_name_auth_type" UNIQUE (name, auth_type)
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

    CONSTRAINT "%DB_TBL_PREFIX_SHORT%uq_user_group" UNIQUE (user_id, group_id)
);

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
