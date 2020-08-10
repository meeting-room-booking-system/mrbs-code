-- Add more columns to the participants table

ALTER TABLE %DB_TBL_PREFIX%participants
  ADD COLUMN create_by varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  ADD COLUMN id INT NOT NULL  AUTO_INCREMENT PRIMARY KEY FIRST;

UPDATE %DB_TBL_PREFIX%participants
  SET create_by=username;
