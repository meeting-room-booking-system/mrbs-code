<?php
# $Id$

require_once "grab_globals.inc.php";
include "config.inc.php";
include "functions.inc";
require_once("database.inc.php");
MDB::loadFile("Date"); 
include "$dbsys.inc";

#If we dont know the right date then make it up
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}
if(empty($area))
	$area = get_default_area();

print_header($day, $month, $year, $area);

$sql = "
SELECT mrbs_entry.name,
       mrbs_entry.description,
       mrbs_entry.create_by,
       mrbs_room.room_name,
       mrbs_area.area_name,
       mrbs_entry.type,
       mrbs_entry.room_id,
       mrbs_entry.repeat_id,
       mrbs_entry.timestamp,
       (mrbs_entry.end_time - mrbs_entry.start_time),
       mrbs_entry.start_time,
       mrbs_entry.end_time

FROM mrbs_entry, mrbs_room, mrbs_area
WHERE mrbs_entry.room_id = mrbs_room.id
  AND mrbs_room.area_id = mrbs_area.id
  AND mrbs_entry.id=$id
";

$types = array('text', 'text', 'text', 'text', 'text', 'text', 'integer',
               'integer', 'timestamp', 'integer', 'integer', 'integer');
$row = $mdb->queryRow($sql, $types);
if (MDB::isError($row))
{
    fatal_error(0, $row->getMessage() . "\n" . $row->getUserInfo() . "\n");
}

if (NULL == $row) 
{
    fatal_error(0, $vocab['invalid_entry_id']);
}

# Note: Removed stripslashes() calls from name and description. Previous
# versions of MRBS mistakenly had the backslash-escapes in the actual database
# records because of an extra addslashes going on. Fix your database and
# leave this code alone, please.
$name         = htmlspecialchars($row[0]);
$description  = htmlspecialchars($row[1]);
$create_by    = htmlspecialchars($row[2]);
$room_name    = htmlspecialchars($row[3]);
$area_name    = htmlspecialchars($row[4]);
$type         = $row[5];
$room_id      = $row[6];
$repeat_id    = $row[7];
$updated      = time_date_string(MDB_Date::mdbstamp2Unix($row[8]));
# need to make DST correct in opposite direction to entry creation
# so that user see what he expects to see
$duration     = $row[9] - cross_dst($row[10], $row[11]);

if( $enable_periods )
	list( $start_period, $start_date) =  period_date_string($row[10]);
else
        $start_date = time_date_string($row[10]);

if( $enable_periods )
	list( , $end_date) =  period_date_string($row[11], -1);
else
        $end_date = time_date_string($row[11]);


$rep_type = 0;

if($repeat_id != 0)
{
    $types = array('integer', 'integer', 'text', 'integer');
    $row = $mdb->queryRow("SELECT rep_type, end_date, rep_opt, rep_num_weeks
                        FROM mrbs_repeat WHERE id=$repeat_id");
    if (MDB::isError($row))
    {
        fatal_error(0, $row->getMessage() . "\n" . $row->getUserInfo() . "\n");
    }
    
    if (NULL <> $row)
    {
        $rep_type     = $row[0];
        $rep_end_date = utf8_strftime('%A %d %B %Y',$row[1]);
        $rep_opt      = $row[2];
        $rep_num_weeks = $row[3];
    }
}

$enable_periods ? toPeriodString($start_period, $duration, $dur_units) : toTimeString($duration, $dur_units);

$repeat_key = "rep_type_" . $rep_type;

# Now that we know all the data we start drawing it

?>

<H3><?php echo $name ?></H3>
 <table border=0>
   <tr>
    <td><b><?php echo get_vocab("description") ?></b></td>
    <td><?php    echo nl2br($description) ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("room") ?></b></td>
    <td><?php    echo  nl2br($area_name . " - " . $room_name) ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("start_date") ?></b></td>
    <td><?php    echo $start_date ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("duration") ?></b></td>
    <td><?php    echo $duration . " " . $dur_units ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("end_date") ?></b></td>
    <td><?php    echo $end_date ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("type") ?></b></td>
    <td><?php    echo empty($typel[$type]) ? "?$type?" : $typel[$type] ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("createdby") ?></b></td>
    <td><?php    echo $create_by ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("lastupdate") ?></b></td>
    <td><?php    echo $updated ?></td>
   </tr>
   <tr>
    <td><b><?php echo get_vocab("rep_type") ?></b></td>
    <td><?php    echo get_vocab($repeat_key) ?></td>
   </tr>
<?php

if($rep_type != 0)
{
	$opt = "";
	if (($rep_type == 2) || ($rep_type == 6))
	{
		# Display day names according to language and preferred weekday start.
		for ($i = 0; $i < 7; $i++)
		{
			$daynum = ($i + $weekstarts) % 7;
			if ($rep_opt[$daynum]) $opt .= day_name($daynum) . " ";
		}
	}
	if ($rep_type == 6)
	{
		echo "<tr><td><b>".get_vocab("rep_num_weeks").get_vocab("rep_for_nweekly")."</b></td><td>$rep_num_weeks</td></tr>\n";
	}
	
	if($opt)
		echo "<tr><td><b>".get_vocab("rep_rep_day")."</b></td><td>$opt</td></tr>\n";
	
	echo "<tr><td><b>".get_vocab("rep_end_date")."</b></td><td>$rep_end_date</td></tr>\n";
}

?>
</table>
<br>
<p>
<a href="edit_entry.php?id=<?php echo $id ?>"><?php echo get_vocab("editentry") ?></a>
<?php

if($repeat_id)
	echo " - <a href=\"edit_entry.php?id=$id&edit_type=series&day=$day&month=$month&year=$year\">".get_vocab("editseries")."</a>";

?>
<BR>
<A HREF="del_entry.php?id=<?php echo $id ?>&series=0" onClick="return confirm('<?php echo get_vocab("confirmdel") ?>');"><?php echo get_vocab("deleteentry") ?></A>
<?php

if($repeat_id)
	echo " - <A HREF=\"del_entry.php?id=$id&series=1&day=$day&month=$month&year=$year\" onClick=\"return confirm('".get_vocab("confirmdel")."');\">".get_vocab("deleteseries")."</A>";

?>
<BR>
<a href="<?php echo $HTTP_REFERER ?>"><?php echo get_vocab("returnprev") ?></a>
<?php include "trailer.inc"; ?>