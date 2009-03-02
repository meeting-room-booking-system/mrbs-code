<?php
// $Id$

require_once "grab_globals.inc.php";
require_once "config.inc.php";
require_once "functions.inc";
require_once "dbsys.inc";
require_once "mrbs_auth.inc";

// Get form variables
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$room = get_form_var('room', 'int');
$id = get_form_var('id', 'int');
$series = get_form_var('series', 'int');

// If we dont know the right date then make it up
if (!isset($day) or !isset($month) or !isset($year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}
if (empty($area))
{
  $area = get_default_area();
}

print_header($day, $month, $year, $area, isset($room) ? $room : "");

if (empty($series))
{
  $series = 0;
}
else
{
  $series = 1;
}

if ($series)
{
  $sql = "
   SELECT $tbl_repeat.name,
          $tbl_repeat.description,
          $tbl_repeat.create_by,
          $tbl_room.room_name,
          $tbl_area.area_name,
          $tbl_repeat.type,
          $tbl_repeat.room_id,
          " . sql_syntax_timestamp_to_unix("$tbl_repeat.timestamp") . " AS last_updated,
          ($tbl_repeat.end_time - $tbl_repeat.start_time) AS duration,
          $tbl_repeat.start_time,
          $tbl_repeat.end_time,
          $tbl_repeat.rep_type,
          $tbl_repeat.end_date,
          $tbl_repeat.rep_opt,
          $tbl_repeat.rep_num_weeks

   FROM  $tbl_repeat, $tbl_room, $tbl_area
   WHERE $tbl_repeat.room_id = $tbl_room.id
      AND $tbl_room.area_id = $tbl_area.id
      AND $tbl_repeat.id=$id
   ";
}
else
{
  $sql = "
   SELECT $tbl_entry.name,
          $tbl_entry.description,
          $tbl_entry.create_by,
          $tbl_room.room_name,
          $tbl_area.area_name,
          $tbl_entry.type,
          $tbl_entry.room_id,
          " . sql_syntax_timestamp_to_unix("$tbl_entry.timestamp") . " AS last_updated,
          ($tbl_entry.end_time - $tbl_entry.start_time) AS duration,
          $tbl_entry.start_time,
          $tbl_entry.end_time,
          $tbl_entry.repeat_id

   FROM  $tbl_entry, $tbl_room, $tbl_area
   WHERE $tbl_entry.room_id = $tbl_room.id
      AND $tbl_room.area_id = $tbl_area.id
      AND $tbl_entry.id=$id
   ";
}

$res = sql_query($sql);
if (! $res)
{
  fatal_error(0, sql_error());
}

if (sql_count($res) < 1)
{
  fatal_error(0,
              ($series ? get_vocab("invalid_series_id") : get_vocab("invalid_entry_id"))
    );
}

$row = sql_row_keyed($res, 0);
sql_free($res);

$name         = htmlspecialchars($row['name']);
$description  = htmlspecialchars($row['description']);
$create_by    = htmlspecialchars($row['create_by']);
$room_name    = htmlspecialchars($row['room_name']);
$area_name    = htmlspecialchars($row['area_name']);
$type         = $row['type'];
$room_id      = $row['room_id'];
$updated      = time_date_string($row['last_updated']);
// need to make DST correct in opposite direction to entry creation
// so that user see what he expects to see
$duration     = $row['duration'] - cross_dst($row['start_time'],
                                             $row['end_time']);

if ($enable_periods)
{
  list($start_period, $start_date) =  period_date_string($row['start_time']);
}
else
{
  $start_date = time_date_string($row['start_time']);
}

if ($enable_periods)
{
  list( , $end_date) =  period_date_string($row['end_time'], -1);
}
else
{
  $end_date = time_date_string($row['end_time']);
}


$rep_type = 0;

if ($series == 1)
{
  $rep_type     = $row['rep_type'];
  $rep_end_date = utf8_strftime('%A %d %B %Y',$row['end_date']);
  $rep_opt      = $row['rep_opt'];
  $rep_num_weeks = $row['rep_num_weeks'];
  // I also need to set $id to the value of a single entry as it is a
  // single entry from a series that is used by del_entry.php and
  // edit_entry.php
  // So I will look for the first entry in the series where the entry is
  // as per the original series settings
  $sql = "SELECT id
          FROM $tbl_entry
          WHERE repeat_id=\"$id\" AND entry_type=\"1\"
          ORDER BY start_time
          LIMIT 1";
  $res = sql_query($sql);
  if (! $res)
  {
    fatal_error(0, sql_error());
  }
  if (sql_count($res) < 1)
  {
    // if all entries in series have been modified then
    // as a fallback position just select the first entry
    // in the series
    // hopefully this code will never be reached as
    // this page will display the start time of the series
    // but edit_entry.php will display the start time of the entry
    sql_free($res);
    $sql = "SELECT id
            FROM $tbl_entry
            WHERE repeat_id=\"$id\"
            ORDER BY start_time
            LIMIT 1";
    $res = sql_query($sql);
    if (! $res)
    {
      fatal_error(0, sql_error());
    }
  }
  $row = sql_row_keyed($res, 0);
  $id = $row['id'];
  sql_free($res);
}
else
{
  $repeat_id = $row['repeat_id'];

  if ($repeat_id != 0)
  {
    $res = sql_query("SELECT rep_type, end_date, rep_opt, rep_num_weeks
                      FROM $tbl_repeat WHERE id=$repeat_id");
    if (! $res)
    {
      fatal_error(0, sql_error());
    }

    if (sql_count($res) == 1)
    {
      $row = sql_row_keyed($res, 0);

      $rep_type     = $row['rep_type'];
      $rep_end_date = utf8_strftime('%A %d %B %Y',$row['end_date']);
      $rep_opt      = $row['rep_opt'];
      $rep_num_weeks = $row['rep_num_weeks'];
    }
    sql_free($res);
  }
}


$enable_periods ? toPeriodString($start_period, $duration, $dur_units) : toTimeString($duration, $dur_units);

$repeat_key = "rep_type_" . $rep_type;

// Now that we know all the data we start drawing it

?>

<h3><?php echo $name ?></h3>
 <table id="entry">
   <tr>
    <td><?php echo get_vocab("description") ?>:</td>
    <td><?php echo nl2br($description) ?></td>
   </tr>
   <tr>
    <td><?php echo get_vocab("room") ?>:</td>
    <td><?php    echo  nl2br($area_name . " - " . $room_name) ?></td>
   </tr>
   <tr>
    <td><?php echo get_vocab("start_date") ?>:</td>
    <td><?php    echo $start_date ?></td>
   </tr>
   <tr>
    <td><?php echo get_vocab("duration") ?>:</td>
    <td><?php    echo $duration . " " . $dur_units ?></td>
   </tr>
   <tr>
    <td><?php echo get_vocab("end_date") ?>:</td>
    <td><?php    echo $end_date ?></td>
   </tr>
   <tr>
    <td><?php echo get_vocab("type") ?>:</td>
    <td><?php    echo empty($typel[$type]) ? "?$type?" : $typel[$type] ?></td>
   </tr>
   <tr>
    <td><?php echo get_vocab("createdby") ?>:</td>
    <td><?php    echo $create_by ?></td>
   </tr>
   <tr>
    <td><?php echo get_vocab("lastupdate") ?>:</td>
    <td><?php    echo $updated ?></td>
   </tr>
   <tr>
    <td><?php echo get_vocab("rep_type") ?>:</td>
    <td><?php    echo get_vocab($repeat_key) ?></td>
   </tr>
<?php

if($rep_type != 0)
{
  $opt = "";
  if (($rep_type == 2) || ($rep_type == 6))
  {
    // Display day names according to language and preferred weekday start.
    for ($i = 0; $i < 7; $i++)
    {
      $daynum = ($i + $weekstarts) % 7;
      if ($rep_opt[$daynum])
      {
        $opt .= day_name($daynum) . " ";
      }
    }
  }
  if ($rep_type == 6)
  {
    echo "<tr><td>".get_vocab("rep_num_weeks")." ".get_vocab("rep_for_nweekly").":</td><td>$rep_num_weeks</td></tr>\n";
  }

  if ($opt)
  {
    echo "<tr><td>".get_vocab("rep_rep_day").":</td><td>$opt</td></tr>\n";
  }

  echo "<tr><td>".get_vocab("rep_end_date").":</td><td>$rep_end_date</td></tr>\n";
}

?>
</table>

<?php
// Need to tell all the links where to go back to after an edit or delete
if (isset($HTTP_REFERER))
{
  $returl = $HTTP_REFERER;
}
// If we haven't got a referer (eg we've come here from an email) then construct
// a sensible place to go to afterwards
else
{
  switch ($default_view)
  {
    case "month":
      $returl = "month.php";
      break;
    case "week":
      $returl = "week.php";
      break;
    default:
      $returl = "day.php";
  }
  $returl .= "?year=$year&month=$month&day=$day&area=$area";
}
$returl = urlencode($returl);
?>

<div id="view_entry_nav">
  <div>
    <?php
    if (! $series)
    {
      echo "<a href=\"edit_entry.php?id=$id&amp;returl=$returl\">". get_vocab("editentry") ."</a>";
    }
    
    if ($repeat_id)
    {
      echo " - ";
    }
    
    if ($repeat_id || $series )
    {
      echo "<a href=\"edit_entry.php?id=$id&amp;edit_type=series&amp;day=$day&amp;month=$month&amp;year=$year&amp;returl=$returl\">".get_vocab("editseries")."</a>";
    }
    
     ?>
  </div>
  <div>
    <?php
    
    // Copy and Copy series
    if ( ! $series )
    {
      echo "<a href=\"edit_entry.php?id=$id&amp;copy=1&amp;returl=$returl\">". get_vocab("copyentry") ."</a>";
    }
       
    if ($repeat_id)
    {
      echo " - ";
    }
       
    if ($repeat_id || $series ) 
    {
      echo "<a href=\"edit_entry.php?id=$id&amp;edit_type=series&amp;day=$day&amp;month=$month&amp;year=$year&amp;copy=1&amp;returl=$returl\">".get_vocab("copyseries")."</a>";
    }
    
    ?>
  </div>
  <div>
    <?php
    if ( ! $series )
    {
      echo "<a href=\"del_entry.php?id=$id&amp;series=0&amp;returl=$returl\" onclick=\"return confirm('".get_vocab("confirmdel")."');\">".get_vocab("deleteentry")."</a>";
    }
    
    if ($repeat_id)
    {
      echo " - ";
    }
    
    if ($repeat_id || $series )
    {
      echo "<a href=\"del_entry.php?id=$id&amp;series=1&amp;day=$day&amp;month=$month&amp;year=$year&amp;returl=$returl\" onClick=\"return confirm('".get_vocab("confirmdel")."');\">".get_vocab("deleteseries")."</a>";
    }
    
    ?>
  </div>
  <div>
    <?php
    if (isset($HTTP_REFERER)) //remove the link if displayed from an email
    {
    ?>
    <a href="<?php echo htmlspecialchars($HTTP_REFERER) ?>"><?php echo get_vocab("returnprev") ?></a>
    <?php
    }
    ?>
  </div>
</div>

<?php
require_once "trailer.inc";
?>
