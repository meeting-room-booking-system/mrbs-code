<?
// $Id$

// Sample data - This script make some areas and rooms.
// This is meant to go into a new empty database, but contrary to previuos SQL
// script, this is not mandatory

require_once("grab_globals.inc.php");
require "config.inc.php";
require_once("database.inc.php");
require "$dbsys.inc";


// Generate some default areas
for ($i = 1; $i < 3; $i++)
{
	$id = $mdb->nextId('mrbs_area_id');
	if (MDB::isError($id))
    {
        fatal_error(1, "<p>" . $id->getMessage() . "<BR>" . $id->getUserInfo());
    }
    $area[$i] = $id;
    $sql  = "INSERT INTO mrbs_area ( id, area_name )
            VALUES (" . $id . ", " . $mdb->getTextValue("Building $i") . ")";
    echo "sql: " . $sql . "<BR>";
    $res = $mdb->query($sql);
    if (MDB::isError($res))
    {
        echo $res->getMessage() . "<BR>";
        die($res->getUserInfo());
    }
}

// Generate some default rooms
for ($i = 1; $i < 11; $i++)
{
	$id = $mdb->nextId('mrbs_room_id');
	if (MDB::isError($id))
    {
        fatal_error(1, "<p>" . $id->getMessage() . "<BR>" . $id->getUserInfo());
    }
    $sql = "INSERT INTO mrbs_room ( id, area_id, room_name, description, capacity )
        VALUES (" . $id . "," . $area[(abs($i/4)>1) ? 2 : 1] . ", "
        . $mdb->getTextValue('Room ' . (abs($i/4)>1 ? ($i - 4)  : $i ))
        . ", " . $mdb->getTextValue('') . ", 8)";
    echo "sql: " . $sql . "<BR>";
    $res = $mdb->query($sql);
    if (MDB::isError($res))
    {
        echo $res->getMessage() . "<BR>";
        die($res->getUserInfo());
    }
}

$mdb->disconnect();
?>