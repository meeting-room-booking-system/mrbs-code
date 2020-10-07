-- The PostgreSQL upgrades for versions 71-75 have been consolidated into a single upgrade in version 76

-- Rename the users table and add an auth_type column (decided to have just a single user table)
-- Also change the default on an int column from '0' to 0 (probably not necessary, but just tidying up)

ALTER TABLE %DB_TBL_PREFIX%users_db
  RENAME TO %DB_TBL_PREFIX%user;
 
ALTER SEQUENCE %DB_TBL_PREFIX%users_db_id_seq RENAME TO %DB_TBL_PREFIX%user_id_seq;

ALTER TABLE %DB_TBL_PREFIX%user
  ALTER COLUMN level SET DEFAULT 0,
  ADD COLUMN auth_type varchar(30) NOT NULL DEFAULT 'db',
  DROP CONSTRAINT "%DB_TBL_PREFIX%uq_name",
  ADD CONSTRAINT "%DB_TBL_PREFIX%uq_name_auth_type" UNIQUE (name, auth_type);

-- Create the role table
CREATE TABLE %DB_TBL_PREFIX%role
(
  id     serial primary key,
  name   varchar(191) NOT NULL,

  CONSTRAINT "%DB_TBL_PREFIX%uq_name" UNIQUE (name)
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

  CONSTRAINT "%DB_TBL_PREFIX%uq_role_room" UNIQUE (role_id, room_id)
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

  CONSTRAINT "%DB_TBL_PREFIX%uq_role_area" UNIQUE (role_id, area_id)
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

  CONSTRAINT "%DB_TBL_PREFIX%uq_user_role" UNIQUE (user_id, role_id)
);

-- Rename tables for consistency with other tables
ALTER TABLE %DB_TBL_PREFIX%variables
  RENAME TO %DB_TBL_PREFIX%variable;
  
ALTER SEQUENCE %DB_TBL_PREFIX%variables_id_seq RENAME TO %DB_TBL_PREFIX%variable_id_seq;

ALTER TABLE %DB_TBL_PREFIX%sessions
  RENAME TO %DB_TBL_PREFIX%session;

ALTER TABLE %DB_TBL_PREFIX%participants
  RENAME TO %DB_TBL_PREFIX%participant;
    
ALTER SEQUENCE %DB_TBL_PREFIX%participants_id_seq RENAME TO %DB_TBL_PREFIX%participant_id_seq;
