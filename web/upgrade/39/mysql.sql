# $Id$

# Convert all tables to InnoDB.   Although all new installations use InnoDB, some
# older ones may still have tables using the MyISAM engine.

ALTER TABLE %DB_TBL_PREFIX%area ENGINE=InnoDB;
ALTER TABLE %DB_TBL_PREFIX%entry ENGINE=InnoDB;
ALTER TABLE %DB_TBL_PREFIX%repeat ENGINE=InnoDB;
ALTER TABLE %DB_TBL_PREFIX%room ENGINE=InnoDB;
ALTER TABLE %DB_TBL_PREFIX%users ENGINE=InnoDB;
ALTER TABLE %DB_TBL_PREFIX%variables ENGINE=InnoDB;
ALTER TABLE %DB_TBL_PREFIX%zoneinfo ENGINE=InnoDB;
