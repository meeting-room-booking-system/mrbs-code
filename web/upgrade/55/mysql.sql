#
# Change all tables to use utf8mb4 charset instead of the flawed utf8 charset.
# The 'zoneinfo' table still uses utf8 so the unique index on the 255 character 'zoneinfo'
# column doesn't go over the 767 byte limit on MySQL 'COMPACT' format tables.

ALTER TABLE %DB_TBL_PREFIX%area CONVERT TO CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE %DB_TBL_PREFIX%room CONVERT TO CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE %DB_TBL_PREFIX%repeat CONVERT TO CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE %DB_TBL_PREFIX%entry CONVERT TO CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE %DB_TBL_PREFIX%variables CONVERT TO CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE %DB_TBL_PREFIX%zoneinfo CONVERT TO CHARSET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE %DB_TBL_PREFIX%users CONVERT TO CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;
