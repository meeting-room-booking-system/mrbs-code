<?php
// $Id$

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
require_once("database.inc.php");
include "$dbsys.inc";
include "mrbs_auth.inc";

//If we dont know the right date then make it up
if (!isset($day) or !isset($month) or !isset($year))
{
    $day   = date("d");
    $month = date("m");
    $year  = date("Y");
}

if (!getAuthorised(getUserName(), getUserPassword(), 2))
{
    showAccessDenied($day, $month, $year, $area);
    exit();
}

// Done changing area or room information?
if (isset($change_done))
{
    if (!empty($room)) // Get the area the room is in
    {
        $area = $mdb->queryOne("SELECT  area_id 
                                FROM    mrbs_room 
                                WHERE   id=$room", 'integer');
    }
    Header("Location: admin.php?day=$day&month=$month&year=$year&area=$area");
    exit();
}

print_header($day, $month, $year, isset($area) ? $area : "");

?>

<h2><?php echo $vocab["editroomarea"] ?></h2>

<table border=1>

<?php
if (!empty($room)) 
{
    if (isset($change_room))
    {
        $room_name  = unslashes($room_name);
        $description = unslashes($description);

        if (empty($capacity)) 
        {
            $capacity = 0;
        }
        $sql = "UPDATE  mrbs_room 
                SET     room_name=" . $mdb->getTextValue($room_name). ", 
                        description=" . $mdb->getTextValue($description). ", 
                        capacity=$capacity 
                WHERE   id=$room";
        if (MDB::isError($error = $mdb->query($sql)))
        {
            fatal_error(0, $vocab['update_room_failed'] . $error->getUserInfo());
        }
    }

    $types = array('integer', 'text', 'text', 'integer');
    $res = $mdb->query("SELECT  id, room_name, description, capacity
                        FROM    mrbs_room 
                        WHERE   id=$room", $types);
    if (MDB::isError($res))
    {
        fatal_error(0, $vocab['error_room'] . $room . $vocab['not_found']);
    }
    $row = $mdb->fetchInto($res, MDB_FETCHMODE_ASSOC);
    $mdb->freeResult($res);
?>
<h3 ALIGN=CENTER><?php echo $vocab["editroom"] ?></h3>
<form action="edit_area_room.php" method="post">
<input type=hidden name="room" value="<?php echo $row["id"]?>">
<CENTER>
<TABLE>
<TR><TD><?php echo $vocab["name"] ?>:       </TD><TD><input type=text name="room_name" value="<?php
echo htmlspecialchars($row["room_name"]); ?>"></TD></TR>
<TR><TD><?php echo $vocab["description"] ?></TD><TD><input type=text name=description value="<?php
echo htmlspecialchars($row["description"]); ?>"></TD></TR>
<TR><TD><?php echo $vocab["capacity"] ?>:   </TD><TD><input type=text name=capacity value="<?php
echo $row["capacity"]; ?>"></TD></TR>
</TABLE>
<input type=submit name="change_room"
value="<?php echo $vocab["change"] ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=submit name="change_done" value="<?php echo $vocab["backadmin"] ?>">
</CENTER>
</form>
<?php } ?>

<?php
if (!empty($area))
{
    if (isset($change_area))
    {
        $area_name = unslashes($area_name);
        $sql = "UPDATE  mrbs_area 
                SET     area_name=" . $mdb->getTextValue($area_name) . " 
                WHERE   id=$area";
        if (MDB::isError($error = $mdb->query($sql)))
        {
            fatal_error(0, $vocab['update_area_failed'] . $error->getUserInfo());
        }
    }

    $types = array('integer', 'text');
    $res = $mdb->query("SELECT  id, area_name 
                        FROM    mrbs_area 
                        WHERE   id=$area", $types);
    if (MDB::isError($res))
    {
        fatal_error(0, $vocab['error_area'] . $area . $vocab['not_found']);
    }
    $row = $mdb->fetchInto($res, MDB_FETCHMODE_ASSOC);
    $mdb->freeResult($res);
?>
<h3 ALIGN=CENTER><?php echo $vocab["editarea"] ?></h3>
<form action="edit_area_room.php" method="post">
<input type=hidden name="area" value="<?php echo $row["id"]?>">
<CENTER>
<TABLE>
<TR><TD><?php echo $vocab["name"] ?>:       </TD><TD><input type=text name="area_name" value="<?php
echo htmlspecialchars($row["area_name"]); ?>"></TD></TR>
</TABLE>
<input type=submit name="change_area"
value="<?php echo $vocab["change"] ?>">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=submit name="change_done" value="<?php echo $vocab["backadmin"] ?>">
</CENTER>
</form>
<?php } ?>
</TABLE>
<?php include "trailer.inc" ?>