# Make the session data column accept 4 byte characters (eg emojis in usernames)

ALTER TABLE %DB_TBL_PREFIX%sessions
  MODIFY data text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL;
