-- $Id$

CREATE TABLE %DB_TBL_PREFIX%variables
(
  id               serial primary key,
  variable_name    varchar(80),
  variable_content text
);
INSERT INTO %DB_TBL_PREFIX%variables (variable_name, variable_content)
  VALUES ('db_version', '1');
