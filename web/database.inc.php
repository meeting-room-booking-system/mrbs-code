<?php
// $Id$

/* database.inc.php - Database connection file.
   Include this file after defining the following variables:
     $dbsys = The databse type you want to use
     $db_host = The hostname of the database server
     $db_port = Port used by the database (if not to default)
     $db_login = The username to use when connecting to the database
     $db_password = The database account password
     $db_database = The database name.
     $db_protocol = How to connect to the database (tcp, unix socket)
   Including this file connects you to the database, or exits on error.

   Establish a database connection.
   On connection error, the message will be output without a proper HTML
   header. There is no way I can see around this; if track_errors isn't on
   there seems to be no way to supress the automatic error message output and
   still be able to access the error text.
 */

require_once("MDB.php");

$mdb=&MDB::connect(array
(
    "phptype"  => $dbsys,
    "username" => $db_login,
    "password" => $db_password,
    "hostspec" => $db_host,
    "protocol" => $db_protocol,
    "port"     => $db_port,
    "database" => $db_database
)
, array
(
    'persistent' => !$db_nopersist,
    'optimize'   => 'portability',
    'HOME'       => $oci8_home 
)    
);

if (MDB::isError($mdb))
{
    if ($debug_flag)
    {
        echo "Error: " . $mdb->getMessage() . "<BR>";
        die ("Error: " . $mdb->getUserInfo() . "<BR>");
    }
    else
    {
        die ("<BR><p><BR>" . $vocab['failed_connect_db'] . "<BR>");
    }
}

// Release a mutual-exclusion lock on the named table. See sql_mutex_lock.
// All locks are released by closing the transaction.
// In most DBMS, a locked table remains locked until you either commit your
// transaction or roll it back, either entirely or to a savepoint before you
// locked the table; there is no other way.

function sql_mutex_unlock($name)
{
    global $sql_mutex_unlock_name, $mdb, $dbsys;
    if ("mysql" == $dbsys)
    {
        $mdb->queryOne("SELECT RELEASE_LOCK(" . $mdb->getTextValue($name) . ")");
    }
    else
    {
        $mdb->commit();
    }
    $sql_mutex_unlock_name = "";
}

// Shutdown function to clean up a forgotten lock. For internal use only.
function sql_mutex_cleanup()
{
    global $sql_mutex_shutdown_registered, $sql_mutex_unlock_name, $mdb, $dbsys;
    if (!empty($sql_mutex_unlock_name))
    {
        if ("mysql" == $dbsys)
        {
            sql_mutex_unlock($sql_mutex_unlock_name);
        }
        else
        {
            $mdb->rollback();
        }
        $sql_mutex_unlock_name = "";
    }
}
?>