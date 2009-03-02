<?php
// $Id$

require_once "grab_globals.inc.php";
require_once "config.inc.php";
require_once "functions.inc";
require_once "dbsys.inc";
require_once "mrbs_auth.inc";


function date_time_string($t)
{
  global $twentyfourhour_format;
  if ($twentyfourhour_format)
  {
    $timeformat = "%H:%M:%S";
  }
  else
  {
    $timeformat = "%I:%M:%S%p";
  }
  return utf8_strftime("%A %d %B %Y ".$timeformat, $t);
}

function hours_minutes_seconds_format()
{
  global $twentyfourhour_format;

  if ($twentyfourhour_format)
  {
    $timeformat = "%H:%M:%S";
  }
  else
  {
    $timeformat = "%I:%M:%S%p";
  }
  return $timeformat;
}

// Convert a start time and end time to a plain language description.
// This is similar but different from the way it is done in view_entry.
function describe_span($starts, $ends)
{
  global $twentyfourhour_format;
  $start_date = utf8_strftime('%A %d %B %Y', $starts);
  $start_time = utf8_strftime(hours_minutes_seconds_format(), $starts);
  $duration = $ends - $starts;
  if ($start_time == "00:00:00" && $duration == 60*60*24)
  {
    return $start_date . " - " . get_vocab("all_day");
  }
  toTimeString($duration, $dur_units);
  return $start_date . " " . $start_time . " - " . $duration . " " . $dur_units;
}

// Convert a start period and end period to a plain language description.
// This is similar but different from the way it is done in view_entry.
function describe_period_span($starts, $ends)
{
  list( $start_period, $start_date) =  period_date_string($starts);
  list( , $end_date) =  period_date_string($ends, -1);
  $duration = $ends - $starts;
  toPeriodString($start_period, $duration, $dur_units);
  return $start_date . " - " . $duration . " " . $dur_units;
}

// this is based on describe_span but it displays the start and end
// date/time of an entry
function start_to_end($starts, $ends)
{
  global $twentyfourhour_format;
  $start_date = utf8_strftime('%A %d %B %Y', $starts);
  $start_time = utf8_strftime(hours_minutes_seconds_format(), $starts);

  $end_date = utf8_strftime('%A %d %B %Y', $ends);
  $end_time = utf8_strftime(hours_minutes_seconds_format(), $ends);
  return $start_date . " " . $start_time . " - " . $end_date . " " . $end_time;
}


// this is based on describe_period_span but it displays the start and end
// date/period of an entry
function start_to_end_period($starts, $ends)
{
  list( , $start_date) =  period_date_string($starts);
  list( , $end_date) =  period_date_string($ends, -1);
  return $start_date . " - " . $end_date;
}

// Report on one entry. See below for columns in $row[].
// $last_area_room remembers the current area/room.
// $last_date remembers the current date.
function reporton(&$row, &$last_area_room, &$last_date, $sortby, $display)
{
  global $typel;
  global $enable_periods;
  // Display Area/Room, but only when it changes:
  $area_room = htmlspecialchars($row['area_name']) . " - " . htmlspecialchars($row['room_name']);
  $date = utf8_strftime("%d-%b-%Y", $row['start_time']);
  // entries to be sorted on area/room
  echo "<div class=\"div_report\">\n";
  if( $sortby == "r" )
  {
    if ($area_room != $last_area_room)
    {
      echo "<h2>". get_vocab("room") . ": " . $area_room . "</h2>\n";
    }
    if ($date != $last_date || $area_room != $last_area_room)
    {
      echo "<h3>". get_vocab("date") . ": " . $date . "</h3>\n";
      $last_date = $date;
    }
    // remember current area/room that is being processed.
    // this is done here as the if statement above needs the old
    // values
    if ($area_room != $last_area_room)
    {
      $last_area_room = $area_room;
    }
  }
  else
    // entries to be sorted on start date
  {
    if ($date != $last_date)
    {
      echo "<h2>". get_vocab("date") . ": " . $date . "</h2>\n";
    }
    if ($area_room != $last_area_room  || $date != $last_date)
    {
      echo "<h3>". get_vocab("room") . ": " . $area_room . "</h3>\n";
      $last_area_room = $area_room;
    }
    // remember current date that is being processed.
    // this is done here as the if statement above needs the old
    // values
    if ($date != $last_date)
    {
      $last_date = $date;
    }
  }
  echo "<div class=\"report_entry_title\">\n";
  
  echo "<div class=\"report_entry_name\">\n";
  // Brief Description (title), linked to view_entry:
  echo "<a href=\"view_entry.php?id=".$row['entry_id']."\">" . htmlspecialchars($row['name']) . "</a>\n";
  echo "</div>\n";
  
  echo "<div class=\"report_entry_when\">\n";
  // what do you want to display duration or end date/time
  if( $display == "d" )
  {
    // Start date/time and duration:
    echo (empty($enable_periods) ? 
          describe_span($row['start_time'], $row['end_time']) : 
          describe_period_span($row['start_time'], $row['end_time'])) . "\n";
  }
  else
  {
    // Start date/time and End date/time:
    echo (empty($enable_periods) ? 
          start_to_end($row['start_time'], $row['end_time']) :
          start_to_end_period($row['start_time'], $row['end_time'])) . "\n";
  }
  echo "</div>\n";
  echo "</div>\n";
  
  echo "<table>\n";
  echo "<colgroup>\n";
  echo "<col class=\"col1\">\n";
  echo "<col class=\"col2\">\n";
  echo "</colgroup>\n";

  // Description:
  echo "<tr><td>" . get_vocab("description") . ":</td><td>" .
    nl2br(htmlspecialchars($row['description'])) . "</td></tr>\n";

  // Entry Type:
  $et = empty($typel[$row['type']]) ? "?".$row['type']."?" : $typel[$row['type']];
  echo "<tr><td>" . get_vocab("type")       . ":</td><td>$et</td></tr>\n";
  // Created by and last update timestamp:
  echo "<tr class=\"createdby\"><td>"  . get_vocab("createdby")  . ":</td><td>" . htmlspecialchars($row['create_by'])    . "</td></tr>\n";
  echo "<tr class=\"lastupdate\"><td>" . get_vocab("lastupdate") . ":</td><td>" . date_time_string($row['last_updated']) . "</td></tr>\n";

  echo "</table>\n";
  echo "</div>\n\n";
}

// Collect summary statistics on one entry. See below for columns in $row[].
// $sumby selects grouping on brief description (d) or created by (c).
// This also builds hash tables of all unique names and rooms. When sorted,
// these will become the column and row headers of the summary table.
function accumulate(&$row, &$count, &$hours, $report_start, $report_end,
                    &$room_hash, &$name_hash)
{
  global $sumby;
  // Use brief description or created by as the name:
  $name = htmlspecialchars($row[($sumby == "d" ? 'name' : 'create_by')]);
  // Area and room separated by break:
  $room = htmlspecialchars($row['area_name']) . "<br>" . htmlspecialchars($row['room_name']);
  // Accumulate the number of bookings for this room and name:
  @$count[$room][$name]++;
  // Accumulate hours used, clipped to report range dates:
  @$hours[$room][$name] += (min((int)$row['end_time'], $report_end)
                            - max((int)$row['start_time'], $report_start)) / 3600.0;
  $room_hash[$room] = 1;
  $name_hash[$name] = 1;
}

function accumulate_periods(&$row, &$count, &$hours, $report_start,
                            $report_end, &$room_hash, &$name_hash)
{
  global $sumby;
  global $periods;
  $max_periods = count($periods);

  // Use brief description or created by as the name:
  $name = htmlspecialchars($row[($sumby == "d" ? 'name' : 'create_by')]);
  // Area and room separated by break:
  $room = htmlspecialchars($row['area_name']) . "<br>" . htmlspecialchars($row['room_name']);
  // Accumulate the number of bookings for this room and name:
  @$count[$room][$name]++;
  // Accumulate hours used, clipped to report range dates:
  $dur = (min((int)$row['end_time'], $report_end) - max((int)$row['start_time'], $report_start))/60;
  @$hours[$room][$name] += ($dur % $max_periods) + floor( $dur/(24*60) ) * $max_periods;
  $room_hash[$room] = 1;
  $name_hash[$name] = 1;
}

// Output a table cell containing a count (integer) and hours (float):
// (actually output two cells, so that we can style the counts and hours)
function cell($count, $hours)
{
  echo "<td class=\"count\">($count)</td><td>" . sprintf("%.2f", $hours) . "</td>\n";
}

// Output the summary table (a "cross-tab report"). $count and $hours are
// 2-dimensional sparse arrays indexed by [area/room][name].
// $room_hash & $name_hash are arrays with indexes naming unique rooms and names.
function do_summary(&$count, &$hours, &$room_hash, &$name_hash)
{
  global $enable_periods;
        
  // Make a sorted array of area/rooms, and of names, to use for column
  // and row indexes. Use the rooms and names hashes built by accumulate().
  // At PHP4 we could use array_keys().
  reset($room_hash);
  while (list($room_key) = each($room_hash))
  {
    $rooms[] = $room_key;
  }
  ksort($rooms);
  reset($name_hash);
  while (list($name_key) = each($name_hash))
  {
    $names[] = $name_key;
  }
  ksort($names);
  $n_rooms = sizeof($rooms);
  $n_names = sizeof($names);

  echo "<div id=\"div_summary\">\n";
  echo "<h1>" . (empty($enable_periods) ? get_vocab("summary_header") : get_vocab("summary_header_per")). "</h1>\n";
  echo "<table>\n";
  
  echo "<thead>\n";
  echo "<tr><th>&nbsp;</th>\n";
  for ($c = 0; $c < $n_rooms; $c++)
  {
    echo "<th colspan=\"2\">$rooms[$c]</th>\n";
    $col_count_total[$c] = 0;
    $col_hours_total[$c] = 0.0;
  }
  echo "<th colspan=\"2\"><br>" . get_vocab("total") . "</th></tr>\n";
  $grand_count_total = 0;
  $grand_hours_total = 0;
  echo "</thead>\n";
  
  echo "<tbody>\n";
  for ($r = 0; $r < $n_names; $r++)
  {
    $row_count_total = 0;
    $row_hours_total = 0.0;
    $name = $names[$r];
    echo "<tr><td>$name</td>\n";
    for ($c = 0; $c < $n_rooms; $c++)
    {
      $room = $rooms[$c];
      if (isset($count[$room][$name]))
      {
        $count_val = $count[$room][$name];
        $hours_val = $hours[$room][$name];
        cell($count_val, $hours_val);
        $row_count_total += $count_val;
        $row_hours_total += $hours_val;
        $col_count_total[$c] += $count_val;
        $col_hours_total[$c] += $hours_val;
      }
      else
      {
        echo "<td class=\"count\">&nbsp;</td><td>&nbsp;</td>\n";
      }
    }
    cell($row_count_total, $row_hours_total);
    echo "</tr>\n";
    $grand_count_total += $row_count_total;
    $grand_hours_total += $row_hours_total;
  }
  echo "<tr><td>". get_vocab("total"). "</td>\n";
  for ($c = 0; $c < $n_rooms; $c++)
  {
    cell($col_count_total[$c], $col_hours_total[$c]);
  }
  cell($grand_count_total, $grand_hours_total);
  echo "</tr></tbody></table>\n";
  echo "</div>\n";
}

// Get form variables
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$room = get_form_var('room', 'int');
$From_day = get_form_var('From_day', 'int');
$From_month = get_form_var('From_month', 'int');
$From_year = get_form_var('From_year', 'int');
$To_day = get_form_var('To_day', 'int');
$To_month = get_form_var('To_month', 'int');
$To_year = get_form_var('To_year', 'int');
$creatormatch = get_form_var('creatormatch', 'string');
$areamatch = get_form_var('areamatch', 'string');
$roommatch = get_form_var('roommatch', 'string');
$namematch = get_form_var('namematch', 'string');
$descrmatch = get_form_var('descrmatch', 'string');
$summarize = get_form_var('summarize', 'int');
$typematch = get_form_var('typematch', 'array');
$sortby = get_form_var('sortby', 'string');
$display = get_form_var('display', 'string');
$sumby = get_form_var('sumby', 'string');

//If we dont know the right date then make it up
if (!isset($day) or !isset($month) or !isset($year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}
if(empty($area))
   $area = get_default_area();

// print the page header
print_header($day, $month, $year, $area, isset($room) ? $room : "");

if (isset($areamatch))
{
  // Resubmit - reapply parameters as defaults.
  // Make sure these are not escape-quoted:

  // Make default values when the form is reused.
  $areamatch_default = htmlspecialchars($areamatch);
  $roommatch_default = htmlspecialchars($roommatch);
  (isset($typematch)) ? $typematch_default = $typematch :
    $typematch_default = "";
  $namematch_default = htmlspecialchars($namematch);
  $descrmatch_default = htmlspecialchars($descrmatch);
  $creatormatch_default = htmlspecialchars($creatormatch);


}
else
{
  // New report - use defaults.
  $areamatch_default = "";
  $roommatch_default = "";
  $typematch_default = array();
  $namematch_default = "";
  $descrmatch_default = "";
  $creatormatch_default = "";
  $From_day = $day;
  $From_month = $month;
  $From_year = $year;
  $To_time = mktime(0, 0, 0, $month, $day + $default_report_days, $year);
  $To_day   = date("d", $To_time);
  $To_month = date("m", $To_time);
  $To_year  = date("Y", $To_time);
}
// $summarize: 1=report only, 2=summary only, 3=both.
if (empty($summarize))
{
  $summarize = 1;
}
// $sumby: d=by brief description, c=by creator.
if (empty($sumby))
{
  $sumby = "d";
}
// $sortby: r=room, s=start date/time.
if (empty($sortby))
{
  $sortby = "r";
}
// $display: d=duration, e=start date/time and end date/time.
if (empty($display))
{
  $display = "d";
}

// Upper part: The form.
?>
<div class="screenonly">
 
  <form class="form_general" method="get" action="report.php">
    <fieldset>
    <legend><?php echo get_vocab("report_on");?></legend>
      
      <div id="div_report_start">
        <label><?php echo get_vocab("report_start");?>:</label>
        <?php genDateSelector("From_",
                              $From_day,
                              $From_month,
                              $From_year); ?>
        
      </div>
      
      <div id="div_report_end">
        <label><?php echo get_vocab("report_end");?>:</label>
        <?php genDateSelector("To_",
                              $To_day,
                              $To_month,
                              $To_year); ?>
      </div>
      
      <div id="div_areamatch">                  
        <label for="areamatch"><?php echo get_vocab("match_area");?>:</label>
        <input type="text" id="areamatch" name="areamatch" value="<?php echo $areamatch_default; ?>">
      </div>   
      
      <div id="div_roommatch">
        <label for="roommatch"><?php echo get_vocab("match_room");?>:</label>
        <input type="text" id="roommatch" name="roommatch" value="<?php echo $roommatch_default; ?>">
      </div>
      
      <div id="div_typematch">
        <label for="typematch"><?php echo get_vocab("match_type")?>:</label>
        <select id="typematch" name="typematch[]" multiple="multiple" size="5">
          <?php
          foreach ( $typel as $key => $val )
          {
            if (!empty($val) )
            {
              echo "                  <option value=\"$key\"" .
              (is_array($typematch_default) && in_array ( $key, $typematch_default ) ? " selected" : "") .
              ">$val</option>\n";
            }
          }
        ?>
        </select>
        <span><?php echo get_vocab("ctrl_click_type") ?></span>
      </div>
      
      <div id="div_namematch">     
        <label for="namematch"><?php echo get_vocab("match_entry");?>:</label>
        <input type="text" id="namematch" name="namematch" value="<?php echo $namematch_default; ?>">
      </div>   
      
      <div id="div_descrmatch">
        <label for="descrmatch"><?php echo get_vocab("match_descr");?>:</label>
        <input type="text" id="descrmatch" name="descrmatch" value="<?php echo $descrmatch_default; ?>">
      </div>
      
      <div id="div_creatormatch">
        <label for="creatormatch"><?php echo get_vocab("createdby");?>:</label>
        <input type="text" id="creatormatch" name="creatormatch" value="<?php echo $creatormatch_default; ?>">
      </div> 
      
      <div id="div_summarize">
        <label><?php echo get_vocab("include");?>:</label>
        <div class="group">
          <label>
            <input class="radio" type="radio" name="summarize" value="1"
            <?php 
            if ($summarize==1) echo " checked=\"checked\"";
            echo ">" . get_vocab("report_only");
            ?>
          </label>
          <label>
            <input class="radio" type="radio" name="summarize" value="2"
            <?php
            if ($summarize==2) echo " checked=\"checked\"";
            echo ">" . get_vocab("summary_only");
            ?>
          </label>
          <label>
            <input class="radio" type="radio" name="summarize" value="3"
            <?php 
            if ($summarize==3) echo " checked=\"checked\"";
            echo ">" . get_vocab("report_and_summary");?>
          </label>
        </div>
      </div>
      
      <div id="div_sortby"> 
        <label><?php echo get_vocab("sort_rep");?>:</label>
        <div class="group">
          <label>
            <input class="radio" type="radio" name="sortby" value="r"
            <?php 
            if ($sortby=="r") echo " checked=\"checked\"";
            echo ">". get_vocab("room");?>
          </label>
          <label>
            <input class="radio" type="radio" name="sortby" value="s"
            <?php 
            if ($sortby=="s") echo " checked=\"checked\"";
            echo ">". get_vocab("sort_rep_time");?>
          </label>
        </div>
      </div>
      
      <div id="div_display">
        <label><?php echo get_vocab("rep_dsp");?>:</label>
        <div class="group">
          <label>
            <input class="radio" type="radio" name="display" value="d"
            <?php 
            if ($display=="d") echo " checked=\"checked\"";
            echo ">". get_vocab("rep_dsp_dur");?>
          </label>
          <label>
            <input class="radio" type="radio" name="display" value="e"
            <?php 
            if ($display=="e") echo " checked=\"checked\"";
            echo ">". get_vocab("rep_dsp_end");?>
          </label>
        </div>
      </div>
      
      <div id="div_sumby">
        <label><?php echo get_vocab("summarize_by");?>:</label>
        <div class="group">
          <label>
            <input class="radio" type="radio" name="sumby" value="d"
            <?php 
            if ($sumby=="d") echo " checked=\"checked\"";
            echo ">" . get_vocab("sum_by_descrip");
            ?>
          </label>
          <label>
            <input class="radio" type="radio" name="sumby" value="c"
            <?php 
            if ($sumby=="c") echo " checked=\"checked\"";
            echo ">" . get_vocab("sum_by_creator");
            ?>
          </label>
        </div>
      </div>
      
      <div id="report_submit">
        <input class="submit" type="submit" value="<?php echo get_vocab("submitquery") ?>">
      </div>
      
    </fieldset>
  </form>
</div>

<?php

// Lower part: Results, if called with parameters:
if (isset($areamatch))
{
  // Start and end times are also used to clip the times for summary info.
  $report_start = mktime(0, 0, 0, $From_month+0, $From_day+0, $From_year+0);
  $report_end = mktime(0, 0, 0, $To_month+0, $To_day+1, $To_year+0);

  //   SQL result will contain the following columns:
  // Col Index  Description:
  //   1  [0]   Entry ID, not displayed -- used for linking to View script.
  //   2  [1]   Start time as Unix time_t
  //   3  [2]   End time as Unix time_t
  //   4  [3]   Entry name or short description, must be HTML escaped
  //   5  [4]   Entry description, must be HTML escaped
  //   6  [5]   Type, single char mapped to a string
  //   7  [6]   Created by (user name or IP addr), must be HTML escaped
  //   8  [7]   Creation timestamp, converted to Unix time_t by the database
  //   9  [8]   Area name, must be HTML escaped
  //  10  [9]   Room name, must be HTML escaped
  
  $sql = "SELECT e.id AS entry_id, e.start_time, e.end_time, e.name, e.description, "
  . "e.type, e.create_by, "
  .  sql_syntax_timestamp_to_unix("e.timestamp") . " AS last_updated"
  . ", a.area_name, r.room_name"
  . " FROM $tbl_entry e, $tbl_area a, $tbl_room r"
  . " WHERE e.room_id = r.id AND r.area_id = a.id"
  . " AND e.start_time < $report_end AND e.end_time > $report_start";

  if (!empty($areamatch))
  {
    $sql .= " AND" .  sql_syntax_caseless_contains("a.area_name", $areamatch);
  }
  if (!empty($roommatch))
  {
    $sql .= " AND" .  sql_syntax_caseless_contains("r.room_name", $roommatch);
  }
  if (!empty($typematch))
  {
    $sql .= " AND ";
    if ( count( $typematch ) > 1 )
    {
      $or_array = array();
      foreach ( $typematch as $type )
      {
        $or_array[] = "e.type = '$type'";
      }
      $sql .= "(". implode( " OR ", $or_array ) .")";
    }
    else
    {
      $sql .= "e.type = '".$typematch[0]."'";
    }
  }
  if (!empty($namematch))
  {
    $sql .= " AND" .  sql_syntax_caseless_contains("e.name", $namematch);
  }
  if (!empty($descrmatch))
  {
    $sql .= " AND" .  sql_syntax_caseless_contains("e.description", $descrmatch);
  }
  if (!empty($creatormatch))
  {
    $sql .= " AND" .  sql_syntax_caseless_contains("e.create_by", $creatormatch);
  }
   
  if ( $sortby == "r" )
  {
    // Order by Area, Room, Start date/time
    $sql .= " ORDER BY 9,10,2";
  }
  else
  {
    // Order by Start date/time, Area, Room
    $sql .= " ORDER BY 2,9,10";
  }

  // echo "<p>DEBUG: SQL: <tt> $sql </tt></p>\n";

  $res = sql_query($sql);
  if (! $res)
  {
    fatal_error(0, sql_error());
  }
  $nmatch = sql_count($res);
  if ($nmatch == 0)
  {
    echo "<p class=\"report_entries\">" . get_vocab("nothing_found") . "</p>\n";
    sql_free($res);
  }
  else
  {
    $last_area_room = "";
    $last_date = "";
    echo "<p class=\"report_entries\">" . $nmatch . " "
    . ($nmatch == 1 ? get_vocab("entry_found") : get_vocab("entries_found"))
    .  "</p>\n";

    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
    {
      if ($summarize & 1)
      {
        reporton($row, $last_area_room, $last_date, $sortby, $display);
      }

      if ($summarize & 2)
      {
        (empty($enable_periods) ?
         accumulate($row, $count, $hours, $report_start, $report_end,
                    $room_hash, $name_hash) :
         accumulate_periods($row, $count, $hours, $report_start, $report_end,
                            $room_hash, $name_hash)
          );
      }
    }
    if ($summarize & 2)
    {
      do_summary($count, $hours, $room_hash, $name_hash);
    }
  }
}

require_once "trailer.inc";

?>
