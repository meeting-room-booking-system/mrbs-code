<?php

# $Id$

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
require_once("database.inc.php");
include "$dbsys.inc";
include "mrbs_auth.inc";

#If we dont know the right date then make it up 
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}

if (empty($area))
{
    $area = get_default_area();
}

if(!getAuthorised(getUserName(), getUserPassword(), 2))
{
	showAccessDenied($day, $month, $year, $area);
	exit();
}

print_header($day, $month, $year, isset($area) ? $area : "");

// If area is set but area name is not known, get the name.
if (isset($area))
{
    if (empty($area_name))
    {
        $area_name = $mdb->queryOne("SELECT area_name 
                                     FROM   mrbs_area 
                                     WHERE  id=$area", 'text');
        if (MDB::isError($area_name))
        {
            fatal_error(0, $area_name->getMessage() . "<br>" . $area_name->getUserInfo());
        }
    }
    else
    {
        $area_name = unslashes($area_name);
    }
}
?>

<h2><?php echo get_vocab("administration") ?></h2>

<table border=1>
<tr>
<th><center><b><?php echo get_vocab("areas") ?></b></center></th>
<th><center><b><?php echo get_vocab("rooms") ?> <?php if(isset($area_name)) { echo get_vocab("in") . " " .
  htmlspecialchars($area_name); }?></b></center></th>
</tr>

<tr>
<td>
<?php 
# This cell has the areas
$types = array('integer', 'text');
$res = $mdb->query("SELECT  id, area_name 
                    FROM    mrbs_area 
                    ORDER   by area_name", $types);
if (MDB::isError($res))
{
    fatal_error(0, $res->getMessage() . "<br>" . $res->getUserInfo());
}
$row = $mdb->fetchInto($res);
if (!$row)
{
    echo get_vocab("noareas");
}
else
{
    echo "<ul>";
    do
    {
        $area_name_q = urlencode($row[1]);
        echo "<li><a href=\"admin.php?area=$row[0]&area_name=$area_name_q\">"
			. htmlspecialchars($row[1]) . "</a> (<a href=\"edit_area_room.php?area=$row[0]\">" . get_vocab("edit") . "</a>) (<a href=\"del.php?type=area&area=$row[0]\">" .  get_vocab("delete") . "</a>)\n";
	}
    while ($row = $mdb->fetchInto($res));
	echo "</ul>";
}
$mdb->freeResult($res);
?>
</td>
<td>
<?php
# This one has the rooms
if (isset($area))
{
    $types = array('integer', 'text', 'text', 'integer');
    $res = $mdb->query("SELECT  id, room_name, description, capacity
                        FROM    mrbs_room 
                        WHERE   area_id=$area 
                        ORDER   by room_name", $types);
    if (MDB::isError($res))
    {
        fatal_error(0, $res->getMessage() . "<br>" . $res->getUserInfo());
    }
    $row = $mdb->fetchInto($res);
    if (!$row)
    {
        echo get_vocab("norooms");
    }
    else
    {
        echo "<ul>";
        do
        {
            echo "<li>" . htmlspecialchars($row[1]) . "(" . htmlspecialchars($row[2])
			. ", $row[3]) (<a href=\"edit_area_room.php?room=$row[0]\">" . get_vocab("edit") . "</a>) (<a href=\"del.php?type=room&room=$row[0]\">" . get_vocab("delete") . "</a>)\n";
		}
		while ($row = $mdb->fetchInto($res));
        echo "</ul>";
	}
    $mdb->freeResult($res);
}
else
{
    echo get_vocab("noarea");
}

?>

</tr>
<tr>
<td>
<h3 ALIGN=CENTER><?php echo get_vocab("addarea") ?></h3>
<form action=add.php method=post>
<input type=hidden name=type value=area>

<TABLE>
<TR><TD><?php echo get_vocab("name") ?>:       </TD><TD><input type=text name=name></TD></TR>
</TABLE>
<input type=submit value="<?php echo get_vocab("addarea") ?>">
</form>
</td>

<td>
<?php if (0 != $area) { ?>
<h3 ALIGN=CENTER><?php echo get_vocab("addroom") ?></h3>
<form action=add.php method=post>
<input type=hidden name=type value=room>
<input type=hidden name=area value=<?php echo $area; ?>>

<TABLE>
<TR><TD><?php echo get_vocab("name") ?>:       </TD><TD><input type=text name=name></TD></TR>
<TR><TD><?php echo get_vocab("description") ?></TD><TD><input type=text name=description></TD></TR>
<TR><TD><?php echo get_vocab("capacity") ?>:   </TD><TD><input type=text name=capacity></TD></TR>
</TABLE>
<input type=submit value="<?php echo get_vocab("addroom") ?>">
</form>
<?php } else { echo "&nbsp;"; }?>
</td>
</tr>
</table>

<br>
<?php echo get_vocab("browserlang") . " " . $HTTP_ACCEPT_LANGUAGE . " " . get_vocab("postbrowserlang") ; ?>

<?php include "trailer.inc" ?>