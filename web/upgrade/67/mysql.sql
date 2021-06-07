-- Add columns to allow registration and a participants table

ALTER TABLE %DB_TBL_PREFIX%entry
  ADD COLUMN allow_registration       tinyint DEFAULT 0 NOT NULL,
  ADD COLUMN enable_registrant_limit  tinyint DEFAULT 1 NOT NULL,
  ADD COLUMN registrant_limit         int DEFAULT 0 NOT NULL;


CREATE TABLE %DB_TBL_PREFIX%participants
(
  entry_id    int NOT NULL,
  username    varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  registered  int,

  UNIQUE KEY uq_entryid_username (entry_id, username),
  FOREIGN KEY (entry_id)
    REFERENCES %DB_TBL_PREFIX%entry(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
