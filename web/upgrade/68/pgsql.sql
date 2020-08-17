-- Add more columns to the participants table

ALTER TABLE %DB_TBL_PREFIX%participants
  ADD COLUMN create_by varchar(255),
  ADD COLUMN id serial primary key;

UPDATE %DB_TBL_PREFIX%participants
  SET create_by=username;
