-- Add a field for the period names

ALTER TABLE %DB_TBL_PREFIX%area
  ADD COLUMN periods text DEFAULT NULL;
