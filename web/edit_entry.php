<?php
include "config.inc";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";

#If we dont know the right date then make it up
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}
if(empty($area))
	$area = get_default_area();

if(!getAuthorised(getUserName(), getUserPassword(), 1))
{
	showAccessDenied($day, $month, $year, $area);
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
# and if it's a modification we need to get all the old data from the db.
# If we had $id passed in then it's a modification.
if (isset($id))
{
	$sql = "select name, create_by, description, start_time, end_time - start_time,
	        type, room_id, entry_type, repeat_id from mrbs_entry where id=$id";
	
	$res = sql_query($sql);
	if (! $res) fatal_error(1, sql_error());
	if (sql_count($res) != 1) fatal_error(1, "Entry ID $id not found");
	
	$row = sql_row($res, 0);
	sql_free($res);
# Note: Removed stripslashes() calls from name and description. Previous
# versions of MRBS mistakenly had the backslash-escapes in the actual database
# records because of an extra addslashes going on. Fix your database and
# leave this code alone, please.
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
		$sql = "SELECT rep_type, start_time, end_date, rep_opt
		        FROM mrbs_repeat WHERE id=$rep_id";
		
		$res = sql_query($sql);
		if (! $res) fatal_error(1, sql_error());
		if (sql_count($res) != 1) fatal_error(1, "Repeat ID $rep_id not found");
		
		$row = sql_row($res, 0);
		sql_free($res);
		
		$rep_type = $row[0];
		
		if(isset($edit_type) && ($edit_type == "series"))
		{
			$start_day   = (int)strftime('%d', $row[1]);
			$start_month = (int)strftime('%m', $row[1]);
			$start_year  = (int)strftime('%Y', $row[1]);
			
			$rep_end_day   = (int)strftime('%d', $row[2]);
			$rep_end_month = (int)strftime('%m', $row[2]);
			$rep_end_year  = (int)strftime('%Y', $row[2]);
			
			switch($rep_type)
			{
				case 2:
					$rep_day[0] = $row[3][0] != "0";
					$rep_day[1] = $row[3][1] != "0";
					$rep_day[2] = $row[3][2] != "0";
					$rep_day[3] = $row[3][3] != "0";
					$rep_day[4] = $row[3][4] != "0";
					$rep_day[5] = $row[3][5] != "0";
					$rep_day[6] = $row[3][6] != "0";
					
					break;
				
				default:
					$rep_day = array(0, 0, 0, 0, 0, 0, 0);
			}
		}
		else
		{
			$rep_type     = $row[0];
			$rep_end_date = strftime('%A %d %B %Y',$row[2]);
			$rep_opt      = $row[3];
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

if(!getWritable($create_by, getUserName()))
{
	showAccessDenied($day, $month, $year, $area);
	exit;
}

print_header($day, $month, $year, $area);

?>

<SCRIPT LANGUAGE="JavaScript">
// do a little form verifying
function validate_and_submit ()
{
  if(document.forms["main"].name.value == "")
  {
    alert ( "You have not entered a\nBrief Description." );
    return false;
  }
  
  h = parseInt(document.forms["main"].hour.value);
  m = parseInt(document.forms["main"].minute.value);
  
  if(h > 23 || m > 59)
  {
    alert("You have not entered a\nvalid time of day.");
    return false;
  }
  
  // would be nice to also check date to not allow Feb 31, etc...
  document.forms["main"].submit();
  
  return true;
}
</SCRIPT>

<h2><? echo isset($id) ? $lang["editentry"] : $lang["addentry"]; ?></H2>

<FORM NAME="main" ACTION="edit_entry_handler.php" METHOD="GET">

<TABLE BORDER=0>

<TR><TD CLASS=CR><B><? echo $lang["namebooker"]?></B></TD>
  <TD CLASS=CL><INPUT NAME="name" SIZE=40 VALUE="<? echo htmlentities($name) ?>"></TD></TR>

<TR><TD CLASS=TR><B><?echo $lang["fulldescription"]?></B></TD>
  <TD CLASS=TL><TEXTAREA NAME="description" ROWS=8 COLS=40 WRAP="virtual"><? echo htmlentities ( $description ); ?></TEXTAREA></TD></TR>

<TR><TD CLASS=CR><B><? echo $lang["date"]?></B></TD>
 <TD CLASS=CL>
  <? genDateSelector("", $start_day, $start_month, $start_year) ?>
 </TD>
</TR>

<TR><TD CLASS=CR><B><?echo $lang["time"]?></B></TD>
<TD CLASS=CL><INPUT NAME="hour" SIZE=2 VALUE="<? echo $start_hour;?>" MAXLENGTH=2>:<INPUT NAME="minute" SIZE=2 VALUE="<? echo $start_min;?>" MAXLENGTH=2>
</TD></TR>

<TR><TD CLASS=CR><B><? echo $lang["duration"];?></B></TD>
  <TD CLASS=CL><INPUT NAME="duration" SIZE=7 VALUE="<? echo $duration;?>">
    <SELECT NAME="dur_units">
<?
$units = array("minutes", "hours", "days", "weeks");
while (list(,$unit) = each($units))
{
	echo "<OPTION VALUE=$unit";
	if ($dur_units == $lang[$unit]) echo " SELECTED";
	echo ">$lang[$unit]";
}
?>
    </SELECT>
    <INPUT NAME="all_day" TYPE="checkbox" VALUE="yes"> <? echo $lang["all_day"]; ?>
</TD></TR>

<TR><TD CLASS=CR><B><?echo $lang["type"]?></B></TD>
  <TD CLASS=CL><SELECT NAME="type">
<?
for ($c = "A"; $c <= "J"; $c++)
{
	if (!empty($typel[$c]))
		echo "<OPTION VALUE=$c" . ($type == $c ? " SELECTED" : "") . ">$typel[$c]\n";
}
?></SELECT></TD></TR>

<? if(isset($edit_type) && $edit_type == "series") { ?>

<TR>
 <TD CLASS=CR><B><?echo $lang["rep_type"]?></B></TD>
 <TD CLASS=CL>
<?

for($i = 0; isset($lang["rep_type_$i"]); $i++)
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
 <TD CLASS=CR><B><?echo $lang["rep_end_date"]?></B></TD>
 <TD CLASS=CL><? genDateSelector("rep_end_", $rep_end_day, $rep_end_month, $rep_end_year) ?></TD>
</TR>

<TR>
 <TD CLASS=CR><B><? echo $lang["rep_rep_day"]?></B> <? echo $lang["rep_for_weekly"]?></TD>
 <TD CLASS=CL>
<?php
# Display day name checkboxes according to language and preferred weekday start.
for ($i = 0; $i < 7; $i++)
{
	$wday = ($i + $weekstarts) % 7;
	echo "<INPUT NAME=\"rep_day[$wday]\" TYPE=CHECKBOX";
	if ($rep_day[$wday]) echo " CHECKED";
	echo ">" . day_name($wday) . "\n";
}
?>
 </TD>
</TR>

<?
}
else
{
	$key = "rep_type_" . (isset($rep_type) ? $rep_type : "0");
	
	echo "<tr><td class=CR><b>$lang[rep_type]</b></td><td class=CL>$lang[$key]</td></tr>\n";
	
	if(isset($rep_type) && ($rep_type != 0))
	{
		$opt = "";
		if ($rep_type == 2)
		{
			# Display day names according to language and preferred weekday start.
			for ($i = 0; $i < 7; $i++)
			{
				$wday = ($i + $weekstarts) % 7;
				if ($rep_opt[$wday]) $opt .= day_name($wday) . " ";
			}
		}
		if($opt)
			echo "<tr><td class=CR><b>$lang[rep_rep_day]</b></td><td class=CL>$opt</td></tr>\n";
		
		echo "<tr><td class=CR><b>$lang[rep_end_date]</b></td><td class=CL>$rep_end_date</td></tr>\n";
	}
}
?>

<TR>
 <TD colspan=2 align=center>
  <SCRIPT LANGUAGE="JavaScript">
   document.writeln ( '<INPUT TYPE="button" VALUE="<?echo $lang["save"]?>" ONCLICK="validate_and_submit()">' );
  </SCRIPT>
  <NOSCRIPT>
   <INPUT TYPE="submit" VALUE="<? echo $lang["save"]?>">
  </NOSCRIPT>
 </TD></TR>
</TABLE>

<INPUT TYPE=HIDDEN NAME="returl"    VALUE="<? echo $HTTP_REFERER?>">
<INPUT TYPE=HIDDEN NAME="room_id"   VALUE="<? echo $room_id?>">
<INPUT TYPE=HIDDEN NAME="create_by" VALUE="<? echo $create_by?>">
<INPUT TYPE=HIDDEN NAME="rep_id"    VALUE="<? echo $rep_id?>">
<INPUT TYPE=HIDDEN NAME="edit_type" VALUE="<? echo $edit_type?>">
<? if(isset($id)) echo "<INPUT TYPE=HIDDEN NAME=\"id\"        VALUE=\"$id\">\n"; ?>

</FORM>

<? include "trailer.inc" ?>
