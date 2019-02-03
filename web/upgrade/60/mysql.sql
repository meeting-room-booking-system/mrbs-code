#
# Change remaining tables to use utf8mb4 charset instead of the now amibiguous utf8 charset.
# (utf8 currently defaults to utf8mb3 but in a future release will default to utf8mb4).
# Reduce the size of keyed fields to avoid bumping into the maximum size for a key.

ALTER TABLE %DB_TBL_PREFIX%zoneinfo
  CONVERT TO CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci,
  MODIFY timezone varchar(127) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' NOT NULL,
  MODIFY vtimezone text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE %DB_TBL_PREFIX%sessions
  CONVERT TO CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci,
  MODIFY id varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
