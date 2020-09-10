-- Rename tables for consistency with other tables
RENAME TABLE %DB_TBL_PREFIX%variables TO %DB_TBL_PREFIX%variable;
RENAME TABLE %DB_TBL_PREFIX%sessions TO %DB_TBL_PREFIX%session;
RENAME TABLE %DB_TBL_PREFIX%users_roles TO %DB_TBL_PREFIX%user_role;
RENAME TABLE %DB_TBL_PREFIX%roles_areas TO %DB_TBL_PREFIX%role_area;
RENAME TABLE %DB_TBL_PREFIX%roles_rooms TO %DB_TBL_PREFIX%role_room;
