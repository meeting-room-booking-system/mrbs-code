ALTER TABLE %DB_TBL_PREFIX%area
ADD COLUMN private_enabled       smallint,
ADD COLUMN private_default       smallint,
ADD COLUMN private_mandatory     smallint,
ADD COLUMN private_override      varchar(32);

