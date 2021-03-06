-- Make Unix timestamp columns 64 bit to enable dates after 2038 to be used

ALTER TABLE %DB_TBL_PREFIX%repeat
  MODIFY COLUMN start_time bigint DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  MODIFY COLUMN end_time bigint DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  MODIFY COLUMN end_date bigint DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  MODIFY COLUMN reminded bigint COMMENT 'Unix timestamp',
  MODIFY COLUMN info_time bigint COMMENT 'Unix timestamp';

ALTER TABLE %DB_TBL_PREFIX%entry
  MODIFY COLUMN start_time bigint DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  MODIFY COLUMN end_time bigint DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  MODIFY COLUMN reminded bigint COMMENT 'Unix timestamp',
  MODIFY COLUMN info_time bigint COMMENT 'Unix timestamp';

ALTER TABLE %DB_TBL_PREFIX%participant
  MODIFY COLUMN registered bigint COMMENT 'Unix timestamp';

ALTER TABLE %DB_TBL_PREFIX%zoneinfo
  MODIFY COLUMN last_updated bigint NOT NULL DEFAULT 0 COMMENT 'Unix timestamp';

ALTER TABLE %DB_TBL_PREFIX%session
  MODIFY COLUMN access bigint unsigned DEFAULT NULL COMMENT 'Unix timestamp';

ALTER TABLE %DB_TBL_PREFIX%user
  MODIFY COLUMN last_login bigint DEFAULT 0 NOT NULL COMMENT 'Unix timestamp',
  MODIFY COLUMN reset_key_expiry bigint DEFAULT 0 NOT NULL COMMENT 'Unix timestamp';
