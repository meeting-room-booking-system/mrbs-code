<?

include "config.inc";
include "functions.inc";
include "connect.inc";
include "mincals.inc";

load_user_preferences();

#If we dont know the right date then make it up 
if(!isset($day) or !isset($month) or !isset($year))
{
	$day   = date("d");
	$month = date("m");
	$year  = date("Y");
}

if(!isset($area))
	$area = 1;

# Make the date valid if day is more then number of days in month
while (!checkdate($month, $day, $year))
	$day--;

# print the page header
print_header($day, $month, $year, $area);

# Define the start of day and end of day (default is 7-7)
$am7=mktime($morningstarts,00,0,$month,$day,$year);
$pm7=mktime($eveningends,0,0,$month,$day,$year);


echo "<table><tr><td width=\"100%\">";


#Find out which rooms a user wants to see
#If we havent had $area passed in then for now default to area "1" - in the
#  future this will be customisable per user

if (!$area) { $area = 1; };

#Show all avaliable areas
echo "<u>$lang[areas]</u><br>";
$sql = "select id, area_name from mrbs_area";
$res = mysql_query($sql);
while($row = mysql_fetch_row($res))
{
	echo "<a href=day.php3?year=$year&month=$month&day=$day&area=$row[0]>";
	if ($row[0] == $area) { echo "<font color=red>";}
	echo "$row[1]</a><br>";
}

#Draw the three month calendars
minicals($year, $month, $day, $area);
echo "</tr></table>";

#y? are year, month and day of yesterday
#t? are year, month and day of tomorrow

$i= mktime(0,0,0,$month,$day-1,$year);
$yy = date("Y",$i);
$ym = date("m",$i);
$yd = date("d",$i);

$i= mktime(0,0,0,$month,$day+1,$year);
$ty = date("Y",$i);
$tm = date("m",$i);
$td = date("d",$i);

#Show colour key
echo "<table border=0><tr><td class=\"I\">$lang[internal]</td><td class=\"E\">$lang[external]</td></tr></table>";

#Show Go to day before and after links
echo "<table width=100%><tr><td><a href=day.php3?year=$yy&month=$ym&day=$yd&area=$area>&lt;&lt; $lang[daybefore]</a></td>
      <td align=center><a href=day.php3?area=$area>$lang[gototoday]</a></td>
      <td align=right><a href=day.php3?year=$ty&month=$tm&day=$td&area=$area>$lang[dayafter] &gt;&gt;</a></td></tr></table>";


#We want to build an array containing all the data we want to show
#and then spit it out. 

#Get all appointments for today in the area that we care about
$sql = "SELECT room_id, start_time, end_time, mrbs_entry.id, type, name, mrbs_entry.description

FROM mrbs_entry LEFT JOIN mrbs_room ON (mrbs_entry.room_id = mrbs_room.id)

WHERE area_id = $area AND
(
 start_time BETWEEN $am7       AND $pm7 OR
 end_time   BETWEEN $am7       AND $pm7 OR
 $am7       BETWEEN start_time AND end_time
)";

$res = mysql_query($sql);
echo mysql_error();

while($row = mysql_fetch_row($res))
{
	# Each row weve got here is an appointment. add the details to the array
	# Loop from the start of the booking to the end, adding to the array
	# 
	# $row[0] = Room ID
	# $row[1] = Start time
	# $row[2] = End time
	# $row[3] = Entry ID
	# $row[4] = Booking type
	# $row[5] = Booked for
	# $row[6] = Description
	
	# $today is a map of the screen that will be displayed
	# It looks like:
	#     $today[Room ID][Time][0] = The rooms ID
	#                          [1] = The rooms type (Internal, External)
	#                          [2] = The text to display
	
	for($t = $row[1]; ($t < $row[2]) || ($t == $row[1]); $t += $resolution)
	{
		$today[$row[0]][$t][0] = $row[3];
		$today[$row[0]][$t][1] = $row[4];
		$today[$row[0]][$t][2] = "";
	}
	
	# show the name of the booker in the first segment that the
	# booking happens in, or at the start of the day if it started
	# before today
	
	if($row[1] > $am7)
		$today[$row[0]][$row[1]][2] = stripslashes($row[5]);
	else
		$today[$row[0]][$am7][2] = stripslashes($row[5]);
}

# We need to know what all the rooms area called, so we can show them all
# pull the data from the db and store it. Convienently we can print the room 
# headings and capacities at the same time

$sql = "SELECT room_name, capacity, id FROM mrbs_room WHERE area_id=$area ORDER BY room_name";
$res = mysql_query($sql);

# It might be that there are no rooms defined for this area.
# If there are none then show an error and dont bother doing anything
# else

if(mysql_num_rows($res) == 0)
{
	echo "<h1>No rooms defined for this area</h1>";
}
else
{
	#This is where we start displaying stuff
	echo "<table cellspacing=0 border=1 width=100%>";
	echo "<tr><th>$lang[time]</th>";
	
	while ($row = mysql_fetch_row($res))
	{
		echo "<th align=top>$row[0] ($row[1])</th>";
		$rooms[] = $row[2];
	}
	
	echo "</tr>\n";
	
	$REQUEST_URI = basename($REQUEST_URI);
	
	# This is the main bit of the display
	# We loop through unixtime and then the rooms we just got
	
	for($t = $am7; $t <= $pm7; $t += $resolution)
	{
		$nw = date("Y-m-d H:i",$t+$resolution);
		echo "<tr>";
		
		# Show the time linked to the URL for highlighting that time
		echo "<td width=1% class=\"red\"><a href=$REQUEST_URI&timetohighlight=$t>" . date("H:i",$t) . "</a></td>";
		
		# Loop through the list of rooms we have for this area
		while(list($key, $room) = each($rooms))
		{
			if(isset($today[$room][$t][0]))
			{
				$id    = $today[$room][$t][0];
				$color = $today[$room][$t][1];
				$descr = htmlspecialchars($today[$room][$t][2]);
			}
			else
				unset($id);
			
			# $c is the colour of the cell that the browser sees. White normally, 
			# red if were hightlighting that line and a nice attractive green if the room is booked.
			# We tell if its booked by $id having something in it
			if(isset($id))
				$c = $color;
			else
			{
				if(isset($timetohighlight) && ($t == $timetohighlight))
					$c = "red";
				else
					$c = "white";
			}
			
			echo "<td class=\"" . $c . "\">";
			
			#If the room isnt booked then allow it to be booked
			if(!isset($id))
			{
				$hour = date("H",$t); $minute  = date("i",$t);
				
				echo "<center><a href=edit_entry.php3?room=$room&hour=$hour&minute=$minute&year=$year&month=$month&day=$day><img src=new.gif border=0></a></center>";
			}
			else
			{
				if($descr != "")
				{
					#if it is booked then show 
					echo "<a href=view_entry.php3?id=$id&day=$day&month=$month&year=$year>$descr&nbsp;</a>";
				}
				else
					echo "&nbsp;";
			}
			
			echo "</td>\n";
		}
		
		echo "</tr>\n";
		
		reset($rooms);
	}
}
echo "</table>";


include "trailer.inc"; 

#phpinfo();

echo "</BODY>";
echo "</HTML>";
?>

