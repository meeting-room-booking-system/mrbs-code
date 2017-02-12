
CREATE TABLE %DB_TBL_PREFIX%variables
(
  id               int NOT NULL auto_increment,
  variable_name    varchar(80),
  variable_content text,
      
  PRIMARY KEY (id)
);
INSERT INTO %DB_TBL_PREFIX%variables (variable_name, variable_content)
  VALUES ( 'db_version', '1');
