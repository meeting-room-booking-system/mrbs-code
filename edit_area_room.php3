<?php
include "config.inc";
include "functions.inc";
include "connect.inc";
include "mrbs_auth.inc";

#If we dont know the right date then make it up 
if(!isset($day) or !isset($month) or !isset($year))
{
        $day   = date("d");
        $month = date("m");
        $year  = date("Y");
}

load_user_preferences();

if(!getAuthorised(getUserName(), getUserPassword(), 2))
{
	showAccessDenied($day, $month, $year, $area);
	exit();
}

// Done changing area or room information?
if ($change_done)
{
  if ($room) {  // Get the area the room is in
    $sql = "SELECT * FROM mrbs_room WHERE id=$room";
    $res = mysql_query($sql);
    $row = mysql_fetch_array($res);
    $area = $row["area_id"];
  }
  Header("Location: admin.php3?day=$day&month=$month&year=$year&area=$area");
  exit();
}

print_header($day, $month, $year, isset($area) ? $area : 1);

?>

<h2>Edit Area or Room Description</h2>

<table border=1>

<?php 
if($room) {
  if ($change_room) {
    $sql = "UPDATE mrbs_room SET room_name='$room_name', description='$description', capacity='$capacity' WHERE id=$room";
    $result = mysql_query($sql);
  }

  $res = mysql_query("SELECT * FROM mrbs_room WHERE id=$room");
  $row = mysql_fetch_array($res);
?>
<h3 ALIGN=CENTER>Edit Room</h3>
<form action="<?php echo $PHP_SELF?>" method="post">
<input type=hidden name="room" value="<?php echo $row["id"]?>">
<CENTER>
<TABLE>
<TR><TD>Name:       </TD><TD><input type=text name=room_name value="<?php echo $row["room_name"]; ?>"></TD></TR>
<TR><TD>Description:</TD><TD><input type=text name=description value="<?php echo $row["description"]; ?>"></TD></TR>
<TR><TD>Capacity:   </TD><TD><input type=text name=capacity value="<?php echo $row["capacity"]; ?>"></TD></TR>
</TABLE>
<input type=submit name="change_room" value="Change">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=submit name="change_done" value="Back to Admin">
</FORM>
<?php } ?>

<?php
if($area) {
  if ($change_area) {
    $sql = "UPDATE mrbs_area SET area_name='$area_name' WHERE id=$area";
    $result = mysql_query($sql);
  }

  $res = mysql_query("SELECT * FROM mrbs_area WHERE id=$area");
  $row = mysql_fetch_array($res);
?>
<h3 ALIGN=CENTER>Edit Area</h3>
<form action="<?php echo $PHP_SELF?>" method="post">
<input type=hidden name="area" value="<?php echo $row["id"]?>">
<CENTER>
<TABLE>
<TR><TD>Name:       </TD><TD><input type=text name=area_name value="<?php echo $row["area_name"]; ?>"></TD></TR>
</TABLE>
<input type=submit name="change_area" value="Change">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type=submit name="change_done" value="Back to Admin">
</form>
<?php } ?>
</TABLE>
</CENTER>
<?php include "trailer.inc" ?>

</html>
