<?php
include "config.inc";
include "functions.inc";
include "connect.inc";


# This page will either add or modify a booking

# We need to know:
#  Name of booker
#  Description of meeting
#  Date (option select box for day, month, year)
#  Time
#  Duration
#  Internal/External


# Firstly we need to know if this is a new booking or modifying an old one
# and if its a modification we need to get all the old data from the db.
# If we had $id passed in then its a modification
echo "yayaya";
if ($id) {
	#We split the start date into all its constituant parts with mySQL
	$sql = "select name, description, date_format(start_time, '%e'), date_format(start_time, '%c'),
	        date_format(start_time, '%Y'), date_format(start_time, '%H'), date_format(start_time, '%i'),
			  (unix_timestamp(end_time) - unix_timestamp(start_time)/60/60, type
			  from mrbs_entry where id=$id";
echo $sql;
	$res = mysql_query($sql);
	$row = mysql_fetch_row($res);
	$name        = $row[0];
	$description = $row[1];
	$start_day   = $row[2];
	$start_month = $row[3];
	$start_year  = $row[4];
	$start_hour  = $row[5];
	$start_min   = $row[6];
	$duration    = $row[7];
	$type        = $row[8];
}

#now that we know all the data to fill the form with we start drawing it

echo "$row[0]<br>\n";
echo "$row[1]<br>\n";
echo "$row[2]<br>\n";
echo "$row[3]<br>\n";
echo "$row[4]<br>\n";
echo "$row[5]<br>\n";
echo "$row[6]<br>\n";
echo "$row[7]<br>\n";
echo "$row[8]<br>\n";



$participants[$room] = 1;

if ( $id > 0 ) {
  $sql = "SELECT create_by, date, time, timestamp, duration, priority, type, access, status, name, description FROM cal_entry WHERE id = " . $id;
  $res = mysql_query ( $sql );
  if ( $res ) {
    $row = mysql_fetch_array ( $res );
    $list = split ( "-", $row[1] );
    $year = $list[0];
    $month = $list[1];
    $day = $list[2];
    $time = $row[2];
    if ( strlen ( $time ) > 0 ) {
      $list = split ( ":", $time );
      $hour = $list[0];
      $minute = $list[1];
    }
    $duration = $row[4] / 60;
    $priority = $row[5];
    $type = $row[6];
    $access = $row[7];
    $status = $row[8];
    $name = $row[9];
    $description = $row[10];
  }
  $sql = "SELECT login FROM cal_entry_user WHERE id = $id";
  $res = mysql_query ( $sql );
  if ( $res ) {
    while ( $row = mysql_fetch_array ( $res ) ) {
      $participants[$row[0]] = 1;
    }
  }
}
if ( $year )
  $thisyear = $year;
if ( $month )
  $thismonth = $month;
?>
<HTML>
<HEAD>
<TITLE>WebCalendar</TITLE>
<?include "style.inc"?>

<SCRIPT LANGUAGE="JavaScript">
// do a little form verifying
function validate_and_submit () {
  if ( document.forms[0].name.value == "" ) {
    alert ( "You have not entered a\nBrief Description." );
    return false;
  }
  h = parseInt ( document.forms[0].hour.value );
  m = parseInt ( document.forms[0].minute.value );
  if ( h > 23 || m > 59 ) {
    alert ( "You have not entered a\nvalid time of day." );
    return false;
  }
  // would be nice to also check date to not allow Feb 31, etc...
  document.forms[0].submit ();
  return true;
}
</SCRIPT>
</HEAD>
<BODY BGCOLOR="<?php echo $BGCOLOR; ?>">

<H2><FONT COLOR="<?php echo $H2COLOR;?>"><?php if ( $id ) echo "Edit Entry"; else echo "Add Entry"; ?></FONT></H2>

<FORM ACTION="edit_entry_handler.php3" METHOD="GET">

<?php if ( $id ) echo "<INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$id\">\n"; ?>

<TABLE BORDER=0>

<TR><TD><B>Name of Booker:</B></TD>
  <TD><INPUT NAME="name" SIZE=25 VALUE="<?php echo htmlentities ( $name ); ?>"></TD></TR>

<TR><TD VALIGN="top"><B>Full Description:<br>&nbsp;&nbsp;(Number of people,<br>&nbsp;&nbsp;Internal/External etc)</B></TD>
  <TD><TEXTAREA NAME="description" ROWS=5 COLS=40 WRAP="virtual"><?php echo htmlentities ( $description ); ?></TEXTAREA></TD></TR>

<TR><TD><B>Date:</B></TD>
  <TD><SELECT NAME="day">
<?php
  if ( $day == 0 )
    $day = date ( "d" );
  for ( $i = 1; $i <= 31; $i++ ) echo "<OPTION " . ( $i == $day ? " SELECTED" : "" ) . ">$i";
?>
  </SELECT>
  <SELECT NAME="month">
<?php
  if ( $month == 0 )
    $month = date ( "m" );
  if ( $year == 0 )
    $year = date ( "Y" );
  for ( $i = 1; $i <= 12; $i++ ) {
    $m = strftime ( "%b", mktime ( 0, 0, 0, $i, 1, $year ) );
    print "<OPTION VALUE=\"$i\"" . ( $i == $month ? " SELECTED" : "" ) . ">$m";
  }
?>
  </SELECT>
  <SELECT NAME="year">
<?php
  for ( $i = -1; $i < 5; $i++ ) {
    $y = date ( "Y" ) + $i;
    print "<OPTION VALUE=\"$y\"" . ( $y == $year ? " SELECTED" : "" ) . ">$y";
  }
?>
  </SELECT>
</TD></TR>

<TR><TD><B>Time:</B></TD>
<?php
$h12 = $hour;
$amsel = "CHECKED"; $pmsel = "";
if ( $TIME_FORMAT == "12" ) {
  if ( $h12 < 12 ) {
    $amsel = "CHECKED"; $pmsel = "";
  } else {
    $amsel = ""; $pmsel = "CHECKED";
  }
  $h12 %= 12;
  if ( $h12 == 0 && $hour ) $h12 = 12;
  if ( $h12 == 0 && ! $hour ) $h12 = "";
}
?>
  <TD><INPUT NAME="hour" SIZE=2 VALUE="<?php echo $h12;?>" MAXLENGTH=2>:<INPUT NAME="minute" SIZE=2 VALUE="<?php echo $minute;?>" MAXLENGTH=2>
<?php
if ( $TIME_FORMAT == "12" ) {
  echo "<INPUT TYPE=radio NAME=ampm VALUE=\"am\" $amsel>am\n";
  echo "<INPUT TYPE=radio NAME=ampm VALUE=\"pm\" $pmsel>pm\n";
}
?>
</TD></TR>

<TR><TD><B>Duration:</B></TD>
  <TD><INPUT NAME="duration" SIZE=3 VALUE="<?php echo $duration;?>"> hours</TD></TR>

<!-- <TR><TD><B>Priority:</B></TD>
  <TD><SELECT NAME="priority">
    <OPTION VALUE="1"<?php if ( $priority == 1 ) echo " SELECTED";?>>Low
    <OPTION VALUE="2"<?php if ( $priority == 2 || $priority == 0 ) echo " SELECTED";?>>Medium
    <OPTION VALUE="3"<?php if ( $priority == 3 ) echo " SELECTED";?>>High
  </SELECT></TD></TR>
-->
<TR><TD><B>Type:</B></TD>
  <TD><SELECT NAME="access">
    <OPTION VALUE="I"<?php if ( $access == "I" || ! strlen ( $access ) ) echo " SELECTED";?>>Internal
    <OPTION VALUE="E"<?php if ( $access == "E" ) echo " SELECTED";?>>External
  </SELECT></TD></TR>

<?php
// only ask for participants if we have more than one user in the system
// and we are multi-user
if ( ! strlen ( $single_user_login ) ) {
  $sql = "SELECT login, lastname, firstname FROM cal_user WHERE is_resource ='Y' ORDER BY lastname, firstname, login";
  $res = mysql_query ( $sql );
  if ( $res ) {
    if ( mysql_num_rows ( $res ) > 1 ) {
      $size = 0;
      $users = "";
      print "<TR><TD VALIGN=\"top\"><B>Room:</B></TD>";
      while ( $row = mysql_fetch_array ( $res ) ) {
        $size++;
        $users .= "<OPTION VALUE=\"$row[0]\"";
        if ( $id > 0 ) {
          if ( $participants[$row[0]] )
            $users .= " SELECTED";
        } else {
          if ( ($row[0] == $login || $row[0] == $user) && !$room )
            $users .= " SELECTED";
        }
				if ($row[0] == $room) {
					$users .= " SELECTED";
				}
        $users .= ">";
        if ( strlen ( $row[1] ) == 0 )
          $users .= $row[0];
        else
          $users .= "$row[1], $row[2]";
      }
      if ( $size > 50 )
        $size = 15;
      else if ( $size > 10 )
        $size = 10;
      print "<TD><SELECT NAME=\"participants[]\" SIZE=$size MULTIPLE>$users\n";
      print "</SELECT></TD></TR>\n";
#			print "<td><input type=hidden name=participants value=\"$room\"></td></tr>\n";
		}
  }
}
#print "<td>" . htmlspecialchars("<input name=participants type=hidden value=\"$room\">") . "</td>";
#print "<td><input name=participants type=hidden value=\"$room\"></td>";
print "<input type=hidden name=returl value=\"$HTTP_REFERER\">";
?>

</TABLE>

<SCRIPT LANGUAGE="JavaScript">
  document.writeln ( '<INPUT TYPE="button" VALUE="Save" ONCLICK="validate_and_submit()">' );
  document.writeln ( '<INPUT TYPE="button" VALUE="Help..." ONCLICK="window.open ( \'help_edit_entry.php3\', \'cal_help\', \'dependent,menubar,height=400,width=400,innerHeight=420,outerWidth=420\');">' );
</SCRIPT>
<NOSCRIPT>
<INPUT TYPE="submit" VALUE="Save">
</NOSCRIPT>

<INPUT TYPE="hidden" NAME="participant_list" VALUE="">

</FORM>

<?php if ( $id > 0 ) { ?>
<A HREF="del_entry.php3?id=<?php echo $id;?>" onClick="return confirm('Are you sure\nyou want to\ndelete this entry?');">Delete entry</A><BR>
<?php } ?>

<?php include "trailer.inc" ?>
</BODY>
</HTML>
