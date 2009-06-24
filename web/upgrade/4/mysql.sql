# $Id$

ALTER TABLE %DB_TBL_PREFIX%area 
ADD COLUMN private_enabled       tinyint(1),
ADD COLUMN private_default       tinyint(1),
ADD COLUMN private_mandatory     tinyint(1),
ADD COLUMN private_override      varchar(32);
