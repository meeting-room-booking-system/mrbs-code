<?php

// $Id$

require_once("grab_globals.inc.php");
require "config.inc.php";
require "functions.inc";
require_once("database.inc.php");
require "$dbsys.inc";
require "mrbs_auth.inc";

if (!getAuthorised(getUserName(), getUserPassword(), 2))
{
    showAccessDenied($day, $month, $year, $area);
    exit();
}

// This file is for adding new areas/rooms

// we need to do different things depending on if its a room
// or an area

if ("area" == $type)
{
    $area_name_q = unslashes($name);
    $id = $mdb->nextId('mrbs_area_id');
    if (MDB::isError($id))
    {
        fatal_error(1, "<p>" . $id->getMessage() . "<br>" . $id->getUserInfo());
    }
    $sql = "INSERT INTO mrbs_area (id, area_name) 
            VALUES      ($id, " . $mdb->getTextValue($area_name_q) . ")";
    $res = $mdb->query($sql);
    if (MDB::isError($res))
    {
        fatal_error(1, "<p>" . $res->getMessage() . "<br>" . $res->getUserInfo());
    }
    $area = $mdb->currId('mrbs_area_id');
}

if ("room" == $type)
{
    $room_name_q = unslashes($name);
    $description_q = unslashes($description);
    if (empty($capacity))
    {
        $capacity = 0;
    }
    $id = $mdb->nextId('mrbs_room_id');
    $sql = "INSERT INTO mrbs_room (id, room_name, area_id, description, capacity)
            VALUES      ($id, " . $mdb->getTextValue($room_name_q) . ", $area, "
                        . $mdb->getTextValue($description_q) . ", $capacity)";
    $res = $mdb->query($sql);
    if (MDB::isError($res))
    {
        fatal_error(1, "<p>" . $res->getMessage() . "<br>" . $res->getUserInfo());
    }
}

header("Location: admin.php?area=$area");