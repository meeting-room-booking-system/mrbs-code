-- We are no longer using the "neither" state, so delete all rows that use it

DELETE FROM %DB_TBL_PREFIX%role_area WHERE state='n';
DELETE FROM %DB_TBL_PREFIX%role_room WHERE state='n';
