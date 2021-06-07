ALTER TABLE %DB_TBL_PREFIX%area
ADD COLUMN private_enabled       tinyint,
ADD COLUMN private_default       tinyint,
ADD COLUMN private_mandatory     tinyint,
ADD COLUMN private_override      varchar(32);
