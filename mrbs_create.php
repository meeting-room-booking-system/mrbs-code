<?php
// +---------------------------------------------------------------------------+
// | Meeting Room Booking System.
// +---------------------------------------------------------------------------+
// | MRBS Database Installation script
// |---------------------------------------------------------------------------+
// | How to use this script. Either:
// | - Run the following command on your shell: $ php mrbs_create.php
// | or
// | - Copy mrbs_create.php and mrbs.schema.xml in the same web server 
// |   directory as mrbs and run it.
// | Note: in either case, mrbs_create.php and mrbs.schema.xml must be in the 
// |       same directory.
// +---------------------------------------------------------------------------+
// | @author    thierry_bo.
// | @version   $Revision$.
// +---------------------------------------------------------------------------+
//
// $Id$

/**************************
* Database settings
* You shouldn't have to modify anything outside this section.
**************************/

// Choose database system: see INSTALL for the list of supported databases
// ("mysql"=MySQL,...) and valid strings.
$dbsys = "mysql";

// Hostname of database server (SID for Oracle) :
$db_host = "localhost";

// Port of database server. Leave empty to use default for the database
$db_port = "";

// Database name:
$db_database = "mrbs";

// Database login user name:
$db_login = "mrbs";

// Database login password:
$db_password = "mrbs-password";

// Flag that indicates whether the database should be created or use a
// previously installed database of the same name. Another circumstance on
// which this flag may have to be set to 0 is when the DBMS driver does
// not support database creation or if this operation requires special
// database administrator permissions that may not be available to the
// database user.
// Set $db_create to 0 to NOT create the database.
$db_create = 1;

// Communication protocol tu use. For pgsql, you can use 'unix' instead of
// 'tcp' to use Unix Domain Sockets instead of TCP/IP.
$db_protocol = "tcp";

/**************************
* DBMS specific options
***************************/

//****ORACLE*****

// Home directory path where Oracle is installed if it is running in the local machine.
// Default value: value of the environment variable ORACLE_HOME
$oci8_home = "";


/**************************
* End of database settings
***************************/

include_once("MDB.php");
MDB::loadFile("Manager");

$schema_file = "mrbs.schema.xml";
$variables = array
(
    "database_name"   => $db_database,
    "database_create" => $db_create
);
$dsn = array
(
    "phptype"  => $dbsys,
    "username" => $db_login,
    "password" => $db_password,
    "hostspec" => $db_host,
    "protocol" => $db_protocol,
    "port"	   => $db_port
);
$options = array
(
    "HOME"       => $oci8_home,
    "optimize"   => 'portability'
);
$manager = new MDB_manager;
$manager->connect($dsn, $options);
$success = $manager->updateDatabase($schema_file, $schema_file . ".before", $variables);
if (MDB::isError($success))
{
    echo "Error: " . $success->getMessage() . "<BR>";
    echo "Error: " . $success->getUserInfo() . "<BR>";
}
if (count($manager->warnings)>0)
{
	echo "WARNING:<BR>",implode($manager->getWarnings(),"!\n"),"\n";
}

?>