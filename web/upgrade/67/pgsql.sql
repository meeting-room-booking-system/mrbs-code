-- Add columns to allow registration and a participants table

ALTER TABLE %DB_TBL_PREFIX%entry
  ADD COLUMN allow_registration       smallint DEFAULT 0 NOT NULL,
  ADD COLUMN enable_registrant_limit  smallint DEFAULT 1 NOT NULL,
  ADD COLUMN registrant_limit         int DEFAULT 0 NOT NULL;


CREATE TABLE %DB_TBL_PREFIX%participants
(
  entry_id    int NOT NULL
                REFERENCES %DB_TBL_PREFIX%entry(id)
                ON UPDATE CASCADE
                ON DELETE CASCADE,
  username    varchar(255),
  registered  int,

  CONSTRAINT "%DB_TBL_PREFIX_SHORT%uq_entryid_username" UNIQUE (entry_id, username)
);
