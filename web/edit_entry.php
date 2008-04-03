<?php
# $Id$

require_once('grab_globals.inc.php');
include "config.inc.php";
include "functions.inc";
include "$dbsys.inc";
include "mrbs_auth.inc";

global $twentyfourhour_format;

#If we dont know the right date then make it up
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}
if(empty($area))
	$area = get_default_area();
if(!isset($edit_type))
	$edit_type = "";

if(!getAuthorised(1))
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
	$sql = "select name, create_by, description, start_time, end_time,
	        type, room_id, entry_type, repeat_id from $tbl_entry where id=$id";
	
	$res = sql_query($sql);
	if (! $res) fatal_error(1, sql_error());
	if (sql_count($res) != 1) fatal_error(1, get_vocab("entryid") . $id . get_vocab("not_found"));
	
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
	$duration    = $row[4] - $row[3] - cross_dst($row[3], $row[4]);
	$type        = $row[5];
	$room_id     = $row[6];
	$entry_type  = $row[7];
	$rep_id      = $row[8];
	
	if($entry_type >= 1)
	{
		$sql = "SELECT rep_type, start_time, end_date, rep_opt, rep_num_weeks
		        FROM $tbl_repeat WHERE id=$rep_id";
		
		$res = sql_query($sql);
		if (! $res) fatal_error(1, sql_error());
		if (sql_count($res) != 1) fatal_error(1, get_vocab("repeat_id") . $rep_id . get_vocab("not_found"));
		
		$row = sql_row($res, 0);
		sql_free($res);
		
		$rep_type = $row[0];

		if($edit_type == "series")
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
				case 6:
					$rep_day[0] = $row[3][0] != "0";
					$rep_day[1] = $row[3][1] != "0";
					$rep_day[2] = $row[3][2] != "0";
					$rep_day[3] = $row[3][3] != "0";
					$rep_day[4] = $row[3][4] != "0";
					$rep_day[5] = $row[3][5] != "0";
					$rep_day[6] = $row[3][6] != "0";

					if ($rep_type == 6)
					{
						$rep_num_weeks = $row[4];
					}
					
					break;
				
				default:
					$rep_day = array(0, 0, 0, 0, 0, 0, 0);
			}
		}
		else
		{
			$rep_type     = $row[0];
			$rep_end_date = utf8_strftime('%A %d %B %Y',$row[2]);
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
    // Avoid notices for $hour and $minute if periods is enabled
    (isset($hour)) ? $start_hour = $hour : '';
	(isset($minute)) ? $start_min = $minute : '';
	$duration    = ($enable_periods ? 60 : 60 * 60);
	$type        = "I";
	$room_id     = $room;
    unset($id);

	$rep_id        = 0;
	$rep_type      = 0;
	$rep_end_day   = $day;
	$rep_end_month = $month;
	$rep_end_year  = $year;
	$rep_day       = array(0, 0, 0, 0, 0, 0, 0);
}

# These next 4 if statements handle the situation where
# this page has been accessed directly and no arguments have
# been passed to it.
# If we have not been provided with a room_id
if( empty( $room_id ) )
{
	$sql = "select id from $tbl_room limit 1";
	$res = sql_query($sql);
	$row = sql_row($res, 0);
	$room_id = $row[0];

}

# If we have not been provided with starting time
if( empty( $start_hour ) && $morningstarts < 10 )
	$start_hour = "0$morningstarts";

if( empty( $start_hour ) )
	$start_hour = "$morningstarts";

if( empty( $start_min ) )
	$start_min = "00";

// Remove "Undefined variable" notice
if (!isset($rep_num_weeks))
{
    $rep_num_weeks = "";
}

$enable_periods ? toPeriodString($start_min, $duration, $dur_units) : toTimeString($duration, $dur_units);

#now that we know all the data to fill the form with we start drawing it

if(!getWritable($create_by, getUserName()))
{
	showAccessDenied($day, $month, $year, $area);
	exit;
}

print_header($day, $month, $year, $area);

?>

<script type="text/javascript">
<!-- Hide this from non-Javascript aware UAs

// do a little form verifying
function validate_and_submit ()
{
  // null strings and spaces only strings not allowed
  if(/(^$)|(^\s+$)/.test(document.forms["main"].name.value))
  {
    alert ( "<?php echo get_vocab("you_have_not_entered") . '\n' . get_vocab("brief_description") ?>");
    return false;
  }
  <?php if( ! $enable_periods ) { ?>

  h = parseInt(document.forms["main"].hour.value);
  m = parseInt(document.forms["main"].minute.value);

  if(h > 23 || m > 59)
  {
    alert ("<?php echo get_vocab("you_have_not_entered") . '\n' . get_vocab("valid_time_of_day") ?>");
    return false;
  }
  <?php } ?>

  // check form element exist before trying to access it
  if( document.forms["main"].id )
    i1 = parseInt(document.forms["main"].id.value);
  else
    i1 = 0;

  i2 = parseInt(document.forms["main"].rep_id.value);
  if ( document.forms["main"].rep_num_weeks)
  {
  	n = parseInt(document.forms["main"].rep_num_weeks.value);
  }
  if ((!i1 || (i1 && i2)) && (document.forms["main"].rep_type.value != 0) && document.forms["main"].rep_type[6].checked && (!n || n < 2))
  {
    alert("<?php echo get_vocab("you_have_not_entered") . '\n' . get_vocab("useful_n-weekly_value") ?>");
    return false;
  }
  
  if ((document.forms["main"].rep_type.value != 0) &&
      (document.forms["main"].rep_type[2].checked ||
      document.forms["main"].rep_type[6].checked))
  {
    ok = false;
    for (j=0; j < 7; j++)
    {
      if (document.forms["main"]["rep_day["+j+"]"].checked)
      {
        ok = true;
        break;
      }
    }
    
    if (ok == false)
    {
      alert("<?php echo get_vocab("you_have_not_entered") . '\n' . get_vocab("rep_rep_day") ?>");
      return false;
    }
  }

  // check that a room(s) has been selected
  // this is needed as edit_entry_handler does not check that a room(s)
  // has been chosen
  if( document.forms["main"].elements['rooms[]'].selectedIndex == -1 )
  {
    alert("<?php echo get_vocab("you_have_not_selected") . '\n' . get_vocab("valid_room") ?>");
    return false;
  }

  // Form submit can take some times, especially if mails are enabled and
  // there are more than one recipient. To avoid users doing weird things
  // like clicking more than one time on submit button, we hide it as soon
  // it is clicked.
  document.forms["main"].save_button.disabled="true";

  // would be nice to also check date to not allow Feb 31, etc...
  document.forms["main"].submit();

  return true;
}
function OnAllDayClick(allday) // Executed when the user clicks on the all_day checkbox.
{
  form = document.forms["main"];
  if (allday.checked) // If checking the box...
  {
    <?php if( ! $enable_periods ) { ?>
      form.hour.value = "00";
      form.minute.value = "00";
    <?php } ?>
    if (form.dur_units.value!="days") // Don't change it if the user already did.
    {
      form.duration.value = "1";
      form.dur_units.value = "days";
    }
  }
}
// End of Javascript -->
</script>

<h2>
<?php

if (isset($id) && !isset($copy))
{
  if ($edit_type == "series")
  {
    $token = "editseries";
  }
  else
  {
    $token = "editentry";
  }
}
else
{
  if (isset($copy))
  {
    if ($edit_type == "series")
    {
      $token = "copyseries";
    }
    else
    {
      $token = "copyentry";
    }
  }
  else
  {
    $token = "addentry";
  }
}
echo get_vocab($token);
?>
</h2>


<FORM NAME="main" ACTION="edit_entry_handler.php" METHOD="GET">

<table BORDER=0>

<TR><TD CLASS=CR><B><?php echo get_vocab("namebooker")?></B></TD>
  <TD CLASS=CL><INPUT NAME="name" SIZE=40 VALUE="<?php echo htmlspecialchars($name) ?>"></TD></TR>

<TR><TD CLASS=TR><B><?php echo get_vocab("fulldescription")?></B></TD>
  <TD CLASS=TL><TEXTAREA NAME="description" ROWS=8 COLS=40><?php echo
htmlspecialchars ( $description ); ?></TEXTAREA></TD></TR>

<TR><TD CLASS=CR><B><?php echo get_vocab("date")?>:</B></TD>
 <TD CLASS=CL>
  <?php genDateSelector("", $start_day, $start_month, $start_year) ?>
 </TD>
</TR>

<?php if(! $enable_periods ) { ?>
<TR><TD CLASS=CR><B><?php echo get_vocab("time")?>:</B></TD>
  <TD CLASS=CL><INPUT NAME="hour" SIZE=2 VALUE="<?php if (!$twentyfourhour_format && ($start_hour > 12)){ echo ($start_hour - 12);} else { echo $start_hour;} ?>" MAXLENGTH=2>:<INPUT NAME="minute" SIZE=2 VALUE="<?php echo $start_min;?>" MAXLENGTH=2>
<?php
if (!$twentyfourhour_format)
{
  $checked = ($start_hour < 12) ? "checked" : "";
  echo "<INPUT NAME=\"ampm\" type=\"radio\" value=\"am\" $checked>".utf8_strftime("%p",mktime(1,0,0,1,1,2000));
  $checked = ($start_hour >= 12) ? "checked" : "";
  echo "<INPUT NAME=\"ampm\" type=\"radio\" value=\"pm\" $checked>".utf8_strftime("%p",mktime(13,0,0,1,1,2000));
}
?>
</TD></TR>
<?php } else { ?>
<TR><TD CLASS=CR><B><?php echo get_vocab("period")?>:</B></TD>
  <TD CLASS=CL>
    <SELECT NAME="period">
<?php
foreach ($periods as $p_num => $p_val)
{
	echo "<OPTION VALUE=$p_num";
	if( ( isset( $period ) && $period == $p_num ) || $p_num == $start_min)
        	echo " SELECTED";
	echo ">$p_val";
}
?>
    </SELECT>

</TD></TR>

<?php } ?>
<TR><TD CLASS=CR><B><?php echo get_vocab("duration");?>:</B></TD>
  <TD CLASS=CL><INPUT NAME="duration" SIZE=7 VALUE="<?php echo $duration;?>">
    <SELECT NAME="dur_units">
<?php
if( $enable_periods )
	$units = array("periods", "days");
else
	$units = array("minutes", "hours", "days", "weeks");

while (list(,$unit) = each($units))
{
	echo "<OPTION VALUE=$unit";
	if ($dur_units == get_vocab($unit)) echo " SELECTED";
	echo ">".get_vocab($unit);
}
?>
    </SELECT>
    <INPUT NAME="all_day" TYPE="checkbox" VALUE="yes" onClick="OnAllDayClick(this)"> <?php echo get_vocab("all_day"); ?>
</TD></TR>


<?php
      # Determine the area id of the room in question first
      $sql = "select area_id from $tbl_room where id=$room_id";
      $res = sql_query($sql);
      $row = sql_row($res, 0);
      $area_id = $row[0];
      # determine if there is more than one area
      $sql = "select id from $tbl_area";
      $res = sql_query($sql);
      $num_areas = sql_count($res);
      # if there is more than one area then give the option
      # to choose areas.
      if( $num_areas > 1 ) {

?>
<tr><td>
<script type="text/javascript">
<!-- Hide the Javascript from non-Javascript UAs

function changeRooms( formObj )
{
    areasObj = eval( "formObj.areas" );

    area = areasObj[areasObj.selectedIndex].value
    roomsObj = eval( "formObj.elements['rooms[]']" )

    // remove all entries
    roomsNum = roomsObj.length;
    for (i=(roomsNum-1); i >= 0; i--)
    {
      roomsObj.options[i] = null
    }
    // add entries based on area selected
    switch (area){
<?php
        # get the area id for case statement
	$sql = "select id, area_name from $tbl_area order by area_name";
        $res = sql_query($sql);
	if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
	{

                print "      case \"".$row[0]."\":\n";
        	# get rooms for this area
		$sql2 = "select id, room_name from $tbl_room where area_id='".$row[0]."' order by room_name";
        	$res2 = sql_query($sql2);
		if ($res2) for ($j = 0; ($row2 = sql_row($res2, $j)); $j++)
		{
                	print "        roomsObj.options[$j] = new Option(\"".str_replace('"','\\"',$row2[1])."\",".$row2[0] .")\n";
                }
		# select the first entry by default to ensure
		# that one room is selected to begin with
		print "        roomsObj.options[0].selected = true\n";
		print "        break\n";
	}
?>
    } //switch
}

// Create area selector, only if we have Javascript

this.document.writeln("<b><?php echo get_vocab("areas") ?>:<\/b><\/td><td class=CL valign=top>");
this.document.writeln("          <select name=\"areas\" onChange=\"changeRooms(this.form)\">");

<?php
# get list of areas
$sql = "select id, area_name from $tbl_area order by area_name";
$res = sql_query($sql);
if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
{
	$selected = "";
	if ($row[0] == $area_id) {
		$selected = "SELECTED";
	}
	print "this.document.writeln(\"            <option $selected value=\\\"".$row[0]."\\\">".$row[1]."\")\n";
}
?>
this.document.writeln("          <\/select>");

// End of Javascipt -->
</script>
</td></tr>
<?php
} # if $num_areas
?>
<tr><td class=CR><b><?php echo get_vocab("rooms") ?>:</b></td>
  <td class=CL valign=top><table><tr><td><select name="rooms[]" multiple="multiple">
  <?php
        # select the rooms in the area determined above
	$sql = "select id, room_name from $tbl_room where area_id=$area_id order by room_name";
   	$res = sql_query($sql);


   	if ($res) for ($i = 0; ($row = sql_row($res, $i)); $i++)
   	{
		$selected = "";
		if ($row[0] == $room_id) {
			$selected = "SELECTED";
		}
		echo "<option $selected value=\"".$row[0]."\">".$row[1];
        // store room names for emails
        $room_names[$i] = $row[1];
   	}
  ?>
  </select></td><td><?php echo get_vocab("ctrl_click") ?></td></tr></table>
    </td></tr>

<TR><TD CLASS=CR><B><?php echo get_vocab("type")?></B></TD>
  <TD CLASS=CL><SELECT NAME="type">
<?php
for ($c = "A"; $c <= "Z"; $c++)
{
	if (!empty($typel[$c]))
		echo "<OPTION VALUE=$c" . ($type == $c ? " SELECTED" : "") . ">$typel[$c]\n";
}
?></SELECT></TD></TR>

<?php if($edit_type == "series") { ?>

<TR>
 <TD CLASS=CR><B><?php echo get_vocab("rep_type")?>:</B></TD>
 <TD CLASS=CL>
<?php

for($i = 0; isset($vocab["rep_type_$i"]); $i++)
{
	echo "<INPUT NAME=\"rep_type\" TYPE=\"RADIO\" VALUE=\"" . $i . "\"";

	if($i == $rep_type)
		echo " CHECKED";

	echo ">" . get_vocab("rep_type_$i") . "\n";
}

?>
 </TD>
</TR>

<TR>
 <TD CLASS=CR><B><?php echo get_vocab("rep_end_date")?>:</B></TD>
 <TD CLASS=CL><?php genDateSelector("rep_end_", $rep_end_day, $rep_end_month, $rep_end_year) ?></TD>
</TR>

<TR>
 <TD CLASS=CR><B><?php echo get_vocab("rep_rep_day")?>:</B> <?php echo get_vocab("rep_for_weekly")?></TD>
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

<?php
}
else
{
	$key = "rep_type_" . (isset($rep_type) ? $rep_type : "0");

        echo "<input type=hidden name=rep_type value=0>\n";
	echo "<tr><td class=\"CR\"><b>".get_vocab("rep_type").":</b></td><td class=\"CL\">".get_vocab($key)."</td></tr>\n";

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
			echo "<tr><td class=\"CR\"><b>".get_vocab("rep_rep_day").":</b></td><td class=\"CL\">$opt</td></tr>\n";

		echo "<tr><td class=\"CR\"><b>".get_vocab("rep_end_date").":</b></td><td class=\"CL\">$rep_end_date</td></tr>\n";
	}
}
/* We display the rep_num_weeks box only if:
   - this is a new entry ($id is not set)
   Xor
   - we are editing an existing repeating entry ($rep_type is set and
     $rep_type != 0 and $edit_type == "series" )
*/
if ( ( !isset( $id ) ) Xor ( isset( $rep_type ) && ( $rep_type != 0 ) && ( "series" == $edit_type ) ) )
{
?>

<TR>
 <TD CLASS=CR><B><?php echo get_vocab("rep_num_weeks")?>:</B> <?php echo get_vocab("rep_for_nweekly")?></TD>
 <TD CLASS=CL><INPUT TYPE=TEXT NAME="rep_num_weeks" VALUE="<?php echo $rep_num_weeks?>">
</TR>
<?php } ?>

<TR>
 <TD colspan=2 align=center>
  <script type="text/javascript">
   document.writeln ( '<INPUT TYPE="button" NAME="save_button" VALUE="<?php echo get_vocab("save")?>" ONCLICK="validate_and_submit()">' );
  </script>
  <noscript>
   <INPUT TYPE="submit" VALUE="<?php echo get_vocab("save")?>">
  </noscript>
 </TD></TR>
</table>

<INPUT TYPE=HIDDEN NAME="returl"    VALUE="<?php echo htmlspecialchars($HTTP_REFERER) ?>">
<!--INPUT TYPE=HIDDEN NAME="room_id"   VALUE="<?php echo $room_id?>"-->
<INPUT TYPE=HIDDEN NAME="create_by" VALUE="<?php echo $create_by?>">
<INPUT TYPE=HIDDEN NAME="rep_id"    VALUE="<?php echo $rep_id?>">
<INPUT TYPE=HIDDEN NAME="edit_type" VALUE="<?php echo $edit_type?>">
  <?php if(isset($id) && !isset($copy)) echo "<INPUT TYPE=HIDDEN NAME=\"id\"        VALUE=\"$id\">\n";
?>

</FORM>

<?php include "trailer.inc" ?>
