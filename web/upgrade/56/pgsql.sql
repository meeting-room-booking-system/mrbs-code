
CREATE TABLE %DB_TBL_PREFIX%sessions
(
  id      varchar(32) NOT NULL primary key,
  access  int DEFAULT NULL,
  data    text DEFAULT NULL
);
create index "%DB_TBL_PREFIX%idxAccess" on %DB_TBL_PREFIX%sessions(access);
