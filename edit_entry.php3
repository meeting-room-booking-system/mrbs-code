<?php
include "config.inc";
include "functions.inc";
include "connect.inc";
include "mrbs_auth.inc";

if(!getAuthorised(getUserName(), getUserPassword()))
{
?>
<HTML>
 <HEAD>
  <META HTTP-EQUIV="REFRESH" CONTENT="5; URL=index.php3">
  <TITLE><?echo $lang[mrbs]?></TITLE>
  <?include "config.inc"?>
  <?include "style.inc"?>
 <BODY>
  <H1><?echo $lang[accessdenied]?></H1>
  <P>
   <?echo $lang[unandpw]?>
  </P>
  <P>
   <a href=<? echo $HTTP_REFERER; ?>><? echo $lang[returnprev]; ?></a>
  </P>
</HTML>
<?
	exit;
}

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
if ($id)
{
	$sql = "select name, create_by, description, start_time, end_time - start_time,
	        type, room_id, entry_type, repeat_id from mrbs_entry where id=$id";
	
	$res = mysql_query($sql);
	echo mysql_error();
	
	$row = mysql_fetch_row($res);
	$name        = $row[0];
	$create_by   = $row[1];
	$description = $row[2];
	$start_day   = strftime('%d', $row[3]);
	$start_month = strftime('%m', $row[3]);
	$start_year  = strftime('%Y', $row[3]);
	$start_hour  = strftime('%H', $row[3]);
	$start_min   = strftime('%M', $row[3]);
	$duration    = $row[4];
	$type        = $row[5];
	$room_id     = $row[6];
	$entry_type  = $row[7];
	$rep_id      = $row[8];
	
	if($entry_type >= 1)
	{
		$sql = "SELECT rep_type, end_date, rep_opt
		        FROM mrbs_repeat WHERE id=$rep_id";
		
		$res = mysql_query($sql);
		echo mysql_error();
		
		$row = mysql_fetch_row($res);
		
		$rep_type = $row[0];
		
		if($edit_type == "series")
		{
			$rep_end_day   = (int)strftime('%d', $row[1]);
			$rep_end_month = (int)strftime('%m', $row[1]);
			$rep_end_year  = (int)strftime('%Y', $row[1]);
			
			switch($rep_type)
			{
				case 2:
					$rep_day[0] = $row[2][0] != "0";
					$rep_day[1] = $row[2][1] != "0";
					$rep_day[2] = $row[2][2] != "0";
					$rep_day[3] = $row[2][3] != "0";
					$rep_day[4] = $row[2][4] != "0";
					$rep_day[5] = $row[2][5] != "0";
					$rep_day[6] = $row[2][6] != "0";
					
					break;
				
				default:
					$rep_day = array(0, 0, 0, 0, 0, 0, 0);
			}
		}
		else
		{
			$rep_type     = $row[0];
			$rep_end_date = strftime('%A %d %B %Y',$row[1]);
			$rep_opt      = $row[2];			
		}
	}
}
else
{
	# It is a new booking. The data comes from whichever button the user clicked
	$edit_type   = "series";
	$name        = "";
	$create_by   = getUserName();
	$description = "";
	$start_day   = $day;
	$start_month = $month;
	$start_year  = $year;
	$start_hour  = $hour;
	$start_min   = $minute;
	$duration    = 60 * 60;
	$type        = "I";
	$room_id     = $room;
	
	$rep_id        = 0;
	$rep_type      = 0;
	$rep_end_day   = $day;
	$rep_end_month = $month;
	$rep_end_year  = $year;
	$rep_day       = array(0, 0, 0, 0, 0, 0, 0);
}

toTimeString($duration, $dur_units);

#now that we know all the data to fill the form with we start drawing it

?>
<HTML>
<HEAD>
<TITLE><?echo $lang[mrbs]?></TITLE>
<?include "style.inc"?>

<? if(!getWritable($create_by, getUserName())) { ?>

<H1><?echo $lang[accessdenied]?></H1>
<P>
  <?echo $lang[norights]?>
</P>
<P>
  <a href=<? echo $HTTP_REFERER; ?>><? echo $lang[returnprev]; ?></a>
</P>
</BODY>
</HTML>
<? exit; } ?>

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

<TABLE BORDER=0>

<TR><TD><B><?echo $lang[namebooker]?></B></TD>
  <TD><INPUT NAME="name" SIZE=40 VALUE="<?php echo $name ?>"></TD></TR>

<TR><TD VALIGN="top"><B><?echo $lang[fulldescription]?></B></TD>
  <TD><TEXTAREA NAME="description" ROWS=8 COLS=40 WRAP="virtual"><?php echo htmlentities ( $description ); ?></TEXTAREA></TD></TR>

<TR><TD><B><?echo $lang[date]?></B></TD>
 <TD>
  <?php genDateSelector("", $start_day, $start_month, $start_year) ?>
 </TD>
</TR>

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
  <TD><INPUT NAME="duration" SIZE=7 VALUE="<?php echo $duration;?>">
    <SELECT NAME="dur_units">
<!--     <OPTION VALUE="seconds" <? echo ($dur_units == "seconds") ? "SELECTED" : ""; ?>><? echo $lang[seconds]; ?> -->
     <OPTION VALUE="minutes" <? echo ($dur_units == "minutes") ? "SELECTED" : ""; ?>><? echo $lang[minutes]; ?>
     <OPTION VALUE="hours"   <? echo ($dur_units == "hours"  ) ? "SELECTED" : ""; ?>><? echo $lang[hours]; ?>
     <OPTION VALUE="days"    <? echo ($dur_units == "days"   ) ? "SELECTED" : ""; ?>><? echo $lang[days]; ?>
     <OPTION VALUE="weeks"   <? echo ($dur_units == "weeks"  ) ? "SELECTED" : ""; ?>><? echo $lang[weeks]; ?>
<!--     <OPTION VALUE="years"   <? echo ($dur_units == "years"  ) ? "SELECTED" : ""; ?>><? echo $lang[years]; ?> -->
    </SELECT>
    <INPUT NAME="all_day" TYPE="checkbox" VALUE="yes"> <? echo $lang[all_day]; ?>
</TD></TR>

<TR><TD><B><?echo $lang[type]?></B></TD>
  <TD><SELECT NAME="type">
    <OPTION VALUE="I"<?php if ( $type == "I" || ! strlen ( $type ) ) echo " SELECTED";?>><?echo $lang[internal]?>
    <OPTION VALUE="E"<?php if ( $type == "E" ) echo " SELECTED";?>><?echo $lang[external]?>
  </SELECT></TD></TR>

<?php if($edit_type == "series") { ?>

<TR>
 <TD><B><?echo $lang[rep_type]?></B></TD>
 <TD>
<?

for($i = 0; $lang["rep_type_$i"]; $i++)
{
	echo "<INPUT NAME=\"rep_type\" TYPE=\"RADIO\" VALUE=\"" . $i . "\"";
	
	if($i == $rep_type)
		echo " CHECKED";
	
	echo ">" . $lang["rep_type_$i"] . "\n";
}

?>
 </TD>
</TR>

<TR>
 <TD><B><?echo $lang[rep_end_date]?></B></TD>
 <TD><?php genDateSelector("rep_end_", $rep_end_day, $rep_end_month, $rep_end_year) ?></TD>
</TR>

<TR>
 <TD><B><?echo $lang[rep_rep_day]?></B> <?echo $lang[rep_for_weekly]?></TD>
 <TD>
  <INPUT NAME="rep_day[0]" TYPE="CHECKBOX"<?echo ($rep_day[0] ? "CHECKED" : "")?>>Sunday
  <INPUT NAME="rep_day[1]" TYPE="CHECKBOX"<?echo ($rep_day[1] ? "CHECKED" : "")?>>Monday
  <INPUT NAME="rep_day[2]" TYPE="CHECKBOX"<?echo ($rep_day[2] ? "CHECKED" : "")?>>Tuesday
  <INPUT NAME="rep_day[3]" TYPE="CHECKBOX"<?echo ($rep_day[3] ? "CHECKED" : "")?>>Wednesday
  <INPUT NAME="rep_day[4]" TYPE="CHECKBOX"<?echo ($rep_day[4] ? "CHECKED" : "")?>>Thursday
  <INPUT NAME="rep_day[5]" TYPE="CHECKBOX"<?echo ($rep_day[5] ? "CHECKED" : "")?>>Friday
  <INPUT NAME="rep_day[6]" TYPE="CHECKBOX"<?echo ($rep_day[6] ? "CHECKED" : "")?>>Saturday
 </TD>
</TR>

<?php
}
else
{
	$key = "rep_type_" . ($rep_type ? $rep_type : "0");
	
	echo "<tr><td><b>$lang[rep_type]</b></td><td>$lang[$key]</td></tr>\n";
	
	if($rep_type != 0)
	{
		switch($rep_type)
		{
			case 2:
				$opt .= $rep_opt[0] ? "Sunday " : "";
				$opt  = $rep_opt[1] ? "Monday " : "";
				$opt .= $rep_opt[2] ? "Tuesday " : "";
				$opt .= $rep_opt[3] ? "Wednesday " : "";
				$opt .= $rep_opt[4] ? "Thursday " : "";
				$opt .= $rep_opt[5] ? "Friday " : "";
				$opt .= $rep_opt[6] ? "Saturday " : "";
				break;
			
			default:
				$opt = "";
		}
		
		if($opt)
			echo "<tr><td><b>$lang[rep_rep_day]</b></td><td>$opt</td></tr>\n";
		
		echo "<tr><td><b>$lang[rep_end_date]</b></td><td>$rep_end_date</td></tr>\n";
	}
}
?>

<TR>
 <TD></TD>
 <TD><BR>
  <SCRIPT LANGUAGE="JavaScript">
   document.writeln ( '<INPUT TYPE="button" VALUE="<?echo $lang[save]?>" ONCLICK="validate_and_submit()">' );
  </SCRIPT>
  <NOSCRIPT>
   <INPUT TYPE="submit" VALUE="Save">
  </NOSCRIPT>
 </TD></TR>
</TABLE>

<INPUT TYPE=HIDDEN NAME="returl"    VALUE="<?echo $HTTP_REFERER?>">
<INPUT TYPE=HIDDEN NAME="room_id"   VALUE="<?echo $room_id?>">
<INPUT TYPE=HIDDEN NAME="create_by" VALUE="<?echo $create_by?>">
<INPUT TYPE=HIDDEN NAME="rep_id"    VALUE="<?echo $rep_id?>">
<INPUT TYPE=HIDDEN NAME="edit_type" VALUE="<?echo $edit_type?>">
<? if ( $id ) echo "<INPUT TYPE=HIDDEN NAME=\"id\"        VALUE=\"$id\">\n"; ?>

</FORM>

<?php include "trailer.inc" ?>
</BODY>
</HTML>
