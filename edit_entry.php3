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
if ($id) {
	#We split the start date into all its constituant parts with mySQL
	$sql = "select name, description, date_format(start_time, '%e'), date_format(start_time, '%c'),
	        date_format(start_time, '%Y'), date_format(start_time, '%H'), date_format(start_time, '%i'),
			  (end_time - start_time)/60/60, type, room_id
			  from mrbs_entry where id=$id";
#echo $sql;
	$res = mysql_query($sql);
	echo mysql_error();
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
	$room_id     = $row[9];
} else {
	# It is a new booking. The data comes from whichever button the user clicked
	$name        = "";
	$description = "";
	$start_day   = $day;
	$start_month = $month;
	$start_year  = $year;
	$start_hour  = $hour;
	$start_min   = $minute;
	$duration    = 1;
	$type        = "I";
	$room_id     = $room;
}

#now that we know all the data to fill the form with we start drawing it

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
<BODY>

<h2><? if ($id) echo $lang[editentry]; else echo $lang[addentry]; ?></H2>

<FORM ACTION="edit_entry_handler.php3" METHOD="GET">

<? if ( $id ) echo "<INPUT TYPE=\"hidden\" NAME=\"id\" VALUE=\"$id\">\n"; ?>

<TABLE BORDER=0>

<TR><TD><B><?echo $lang[namebooker]?></B></TD>
  <TD><INPUT NAME="name" SIZE=25 VALUE="<?php echo htmlentities ( $name ); ?>"></TD></TR>

<TR><TD VALIGN="top"><B><?echo $lang[fulldescription]?></B></TD>
  <TD><TEXTAREA NAME="description" ROWS=5 COLS=40 WRAP="virtual"><?php echo htmlentities ( $description ); ?></TEXTAREA></TD></TR>

<TR><TD><B><?echo $lang[date]?></B></TD>
  <TD><SELECT NAME="day">
<?php
  if ( $start_day == 0 )
    $start_day = date ( "d" );
  for ( $i = 1; $i <= 31; $i++ ) echo "<OPTION " . ( $i == $start_day ? " SELECTED" : "" ) . ">$i";
?>
  </SELECT>
  <SELECT NAME="month">
<?php
  if ( $start_month == 0 )
    $start_month = date ( "m" );
  if ( $start_year == 0 )
    $start_year = date ( "Y" );
  for ( $i = 1; $i <= 12; $i++ ) {
    $m = strftime ( "%b", mktime ( 0, 0, 0, $i, 1, $start_year ) );
    print "<OPTION VALUE=\"$i\"" . ( $i == $start_month ? " SELECTED" : "" ) . ">$m";
  }
?>
  </SELECT>
  <SELECT NAME="year">
<?php
  for ( $i = -1; $i < 5; $i++ ) {
    $y = $start_year + $i;
    print "<OPTION VALUE=\"$y\"" . ( $y == $start_year ? " SELECTED" : "" ) . ">$y";
  }
?>
  </SELECT>
</TD></TR>

<TR><TD><B><?echo $lang[time]?></B></TD>
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
  <TD><INPUT NAME="hour" SIZE=2 VALUE="<?php echo $start_hour;?>" MAXLENGTH=2>:<INPUT NAME="minute" SIZE=2 VALUE="<?php echo $start_min;?>" MAXLENGTH=2>
<?php
if ( $TIME_FORMAT == "12" ) {
  echo "<INPUT TYPE=radio NAME=ampm VALUE=\"am\" $amsel>am\n";
  echo "<INPUT TYPE=radio NAME=ampm VALUE=\"pm\" $pmsel>pm\n";
}
?>
</TD></TR>

<TR><TD><B><?echo $lang[duration]?></B></TD>
  <TD><INPUT NAME="duration" SIZE=3 VALUE="<?php echo $duration;?>"> <?echo $lang[hours]?></TD></TR>

<TR><TD><B><?echo $lang[type]?></B></TD>
  <TD><SELECT NAME="type">
    <OPTION VALUE="I"<?php if ( $type == "I" || ! strlen ( $type ) ) echo " SELECTED";?>><?echo $lang[internal]?>
    <OPTION VALUE="E"<?php if ( $type == "E" ) echo " SELECTED";?>><?echo $lang[external]?>
  </SELECT></TD></TR>

<?
print "<input type=hidden name=returl value=\"$HTTP_REFERER\">\n";
print "<input type=hidden name=room_id value = \"$room_id\">";
?>

</TABLE>

<SCRIPT LANGUAGE="JavaScript">
  document.writeln ( '<INPUT TYPE="button" VALUE="<?echo $lang[save]?>" ONCLICK="validate_and_submit()">' );
</SCRIPT>
<NOSCRIPT>
<INPUT TYPE="submit" VALUE="Save">
</NOSCRIPT>
</FORM>
<!--
<?php if ( $id > 0 ) { ?>
<A HREF="del_entry.php3?id=<?php echo $id;?>" onClick="return confirm('Are you sure\nyou want to\ndelete this entry?');">Delete entry</A><BR>
-->
<?php } ?>

<?php include "trailer.inc" ?>
</BODY>
</HTML>
