<?php

// $Id$

/////////////////////////////////////////
// Set timezone, if one has been provided

global $timezone;

if (isset($timezone))
{
  if (function_exists("date_default_timezone_set"))
  {
    date_default_timezone_set($timezone);
  }
  else
  {
    putenv("TZ=$timezone");
  }
}


$done_header = FALSE;


// Print the page header
function print_header($day, $month, $year, $area, $room)
{
  global $mrbs_ecompany, $mrbs_company_logo, $mrbs_company_url, $mrbs_company_more_info,
    $search_str, $locale_warning, $unicode_encoding, $vocab, $done_header;

  if ($done_header)
  {
    return;
  }

  // If we dont know the right date then make it up 
  if (!$day)
  {
    $day   = date("d");
  }
  if (!$month)
  {
    $month = date("m");
  }
  if (!$year)
  {
    $year  = date("Y");
  }
  if (empty($search_str))
  {
    $search_str = "";
  }
  
  if ($unicode_encoding)
  {
    header("Content-Type: text/html; charset=utf-8");
  }
  else
  {
    // We use $vocab directly instead of get_vocab() because we have
    // no requirement to convert the vocab text, we just output
    // the charset
    header("Content-Type: text/html; charset=".$vocab["charset"]);
  }

  header("Pragma: no-cache");                          // HTTP 1.0
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
  <head>
<?php
  require_once "style.inc";
?>
    <title><?php echo get_vocab("mrbs") ?></title>
    <script type="text/javascript">

//<![CDATA[

/*   Script inspired by "True Date Selector"
     Created by: Lee Hinder, lee.hinder@ntlworld.com 
     
     Tested with Windows IE 6.0
     Tested with Linux Opera 7.21, Mozilla 1.3, Konqueror 3.1.0
     
*/

function daysInFebruary (year)
{
  // February has 28 days unless the year is divisible by four,
  // and if it is the turn of the century then the century year
  // must also be divisible by 400 when it has 29 days
  return (((year % 4 == 0) && ( (!(year % 100 == 0)) || (year % 400 == 0))) ? 29 : 28 );
}

//function for returning how many days there are in a month including leap years
function DaysInMonth(WhichMonth, WhichYear)
{
  var DaysInMonth = 31;
  if (WhichMonth == "4" || WhichMonth == "6" || WhichMonth == "9" || WhichMonth == "11")
  {
    DaysInMonth = 30;
  }
  if (WhichMonth == "2")
  {
    DaysInMonth = daysInFebruary( WhichYear );
  }
  return DaysInMonth;
}

//function to change the available days in a months
function ChangeOptionDays(formObj, prefix)
{
  var DaysObject = eval("formObj." + prefix + "day");
  var MonthObject = eval("formObj." + prefix + "month");
  var YearObject = eval("formObj." + prefix + "year");

  if (typeof DaysObject.selectedIndex == 'number' && DaysObject.options)
  { // The DOM2 standard way
    // alert("The DOM2 standard way");
    var DaySelIdx = DaysObject.selectedIndex;
    var Month = parseInt(MonthObject.options[MonthObject.selectedIndex].value);
    var Year = parseInt(YearObject.options[YearObject.selectedIndex].value);
  }
  else if (DaysObject.selectedIndex && DaysObject[DaysObject.selectedIndex])
  { // The legacy MRBS way
    // alert("The legacy MRBS way");
    var DaySelIdx = DaysObject.selectedIndex;
    var Month = parseInt(MonthObject[MonthObject.selectedIndex].value);
    var Year = parseInt(YearObject[YearObject.selectedIndex].value);
  }
  else if (DaysObject.value)
  { // Opera 6 stores the selectedIndex in property 'value'.
    // alert("The Opera 6 way");
    var DaySelIdx = parseInt(DaysObject.value);
    var Month = parseInt(MonthObject.options[MonthObject.value].value);
    var Year = parseInt(YearObject.options[YearObject.value].value);
  }

  // alert("Day="+(DaySelIdx+1)+" Month="+Month+" Year="+Year);

  var DaysForThisSelection = DaysInMonth(Month, Year);
  var CurrentDaysInSelection = DaysObject.length;
  if (CurrentDaysInSelection > DaysForThisSelection)
  {
    for (i=0; i<(CurrentDaysInSelection-DaysForThisSelection); i++)
    {
      DaysObject.options[DaysObject.options.length - 1] = null
    }
  }
  if (DaysForThisSelection > CurrentDaysInSelection)
  {
    for (i=0; i<DaysForThisSelection; i++)
    {
      DaysObject.options[i] = new Option(eval(i + 1));
    }
  }
  if (DaysObject.selectedIndex < 0)
  {
    DaysObject.selectedIndex = 0;
  }
  if (DaySelIdx >= DaysForThisSelection)
  {
    DaysObject.selectedIndex = DaysForThisSelection-1;
  }
  else
  {
    DaysObject.selectedIndex = DaySelIdx;
  }
}

//]]>
    </script>
  </head>
  <?php
  // Put the filename in as a class to aid styling.   
  // (Use a class rather than id to avoid specificity problems)
  echo "<body class=\"" . basename($PHP_SELF,".php" ) . "\">\n";
  ?>
    <div class="screenonly">

<?php // show a warning if this is using a low version of php
  if (substr(phpversion(), 0, 1) == 3)
  {
    echo "<h1>" . get_vocab("not_php3") . "</h1>\n";
  }
  if (!empty($locale_warning))
  {
    echo "[Warning: ".$locale_warning."]";
  }
?>

  <table id="banner">
    <tr>
      <td id="company">
          <?php
          echo "<div>\n";
          if (!empty($mrbs_company_url))
          {
            echo "<a href=\"$mrbs_company_url\">\n";
          }
          if (empty($mrbs_company_logo))
          {
            echo "<span>$mrbs_company</span>\n";
          }
          else
          {
            $logo_size = getimagesize($mrbs_company_logo);
            echo "<img src=\"$mrbs_company_logo\" " . $logo_size[3] . " alt=\"logo\">\n";
          }
          if (!empty($mrbs_company_url))
          {
            echo "</a>\n";
          }
          echo "</div>\n";
          if (!empty($mrbs_company_more_info))
          {
            echo "<div>$mrbs_company_more_info</div>\n";
          }
          ?>
          <div>
            <a href="index.php"><?php echo get_vocab("mrbs") ?></a>
          </div>
      </td>
      <td>
        <form action="day.php" method="get" id="Form1">
          <div>
            <?php
            genDateSelector("", $day, $month, $year); // Note: The 1st arg must match the last arg in the call to ChangeOptionDays below.
            if (!empty($area))
            {
              echo "<input type=\"hidden\" name=\"area\" value=\"$area\">\n";
            }
            if (!empty($room))
            {
              echo "<input type=\"hidden\" name=\"room\" value=\"$room\">\n";
            }
  
            ?>
            <script type="text/javascript">
              //<![CDATA[
              // fix number of days for the $month/$year that you start with
              ChangeOptionDays(document.getElementById('Form1'), ''); // Note: The 2nd arg must match the first in the call to genDateSelector above.
              //]]>
            </script>
            <input type="submit" value="<?php echo get_vocab("goto") ?>">
          </div>
        </form>
      </td>
      <?php
      $query_str = "day=$day&amp;month=$month&amp;year=$year";
      if (!empty($area))
      {
        $query_str .= "&amp;area=$area";
      }
      if (!empty($room))
      {
        $query_str .= "&amp;room=$room";
      }
      
      echo "<td>\n";
      echo "<a href=\"help.php?$query_str\">" . get_vocab("help") . "</a>\n";
      echo "</td>\n";
      
      echo "<td>\n";
      echo "<a href=\"admin.php?$query_str\">" . get_vocab("admin") . "</a>\n";
      echo "</td>\n";
      
      echo "<td>\n";
      echo "<a href=\"report.php?$query_str\">" . get_vocab("report") . "</a>\n";
      echo "</td>\n";
      ?>
      
      <td>
        <form id="header_search" method="get" action="search.php">
          <div>
            <a href="search.php?advanced=1"><?php echo get_vocab("search") ?>:</a>
            <input type="text"   name="search_str" value="<?php echo $search_str ?>">
            <input type="hidden" name="day"        value="<?php echo $day        ?>">
            <input type="hidden" name="month"      value="<?php echo $month      ?>">
            <input type="hidden" name="year"       value="<?php echo $year       ?>">
            <?php
            if (!empty($area))
            {
              echo "<input type=\"hidden\" name=\"area\" value=\"$area\">\n";
            }
            if (!empty($room))
            {
              echo "<input type=\"hidden\" name=\"room\" value=\"$room\">\n";
            }
            ?>
          </div>
        </form>
      </td>
      <?php
      // For session protocols that define their own logon box...
      if (function_exists('PrintLogonBox'))
      {
        echo "<td>\n";
        echo "<div id=\"logon_box\">\n";
        PrintLogonBox(); 
        echo "</div>\n";
        echo "</td>\n";
      }
      ?>
    </tr>
  </table>
</div>
<?php

  $done_header = TRUE;

} // end of print_header()


// Print the standard footer, currently very simple.  Pass $and_exit as
// TRUE to exit afterwards
function print_footer($and_exit)
{
?>
</body>
</html>
<?php
  if ($and_exit)
  {
    exit(0);
  }
}


function toTimeString(&$dur, &$units)
{
  if ($dur >= 60)
  {
    $dur /= 60;

    if ($dur >= 60)
    {
      $dur /= 60;

      if(($dur >= 24) && ($dur % 24 == 0))
      {
        $dur /= 24;

        if(($dur >= 7) && ($dur % 7 == 0))
        {
          $dur /= 7;

          if (($dur >= 52) && ($dur % 52 == 0))
          {
            $dur  /= 52;
            $units = get_vocab("years");
          }
          else
          {
            $units = get_vocab("weeks");
          }
        }
        else
        {
          $units = get_vocab("days");
        }
      }
      else
      {
        $units = get_vocab("hours");
      }
    }
    else
    {
      $units = get_vocab("minutes");
    }
  }
  else
  {
    $units = get_vocab("seconds");
  }
}


function toPeriodString($start_period, &$dur, &$units)
{
  global $enable_periods;
  global $periods;

  $max_periods = count($periods);

  $dur /= 60;

  if ( $dur >= $max_periods || $start_period == 0 )
  {
    if( $start_period == 0 && $dur == $max_periods )
    {
      $units = get_vocab("days");
      $dur = 1;
      return;
    }

    $dur /= 60;
    if(($dur >= 24) && is_int($dur))
    {
      $dur /= 24;
      $units = get_vocab("days");
      return;
    }
    else
    {
      $dur *= 60;
      $dur = ($dur % $max_periods) + floor( $dur/(24*60) ) * $max_periods;
      $units = get_vocab("periods");
      return;
    }
  }
  else
  {
    $units = get_vocab("periods");
  }
}



function genDateSelector($prefix, $day, $month, $year)
{
  if($day   == 0)
  {
    $day = date("d");
  }
  if($month == 0)
  {
    $month = date("m");
  }
  if ($year  == 0)
  {
    $year = date("Y");
  }
  
  echo "
                  <select name=\"${prefix}day\">";
   
  for ($i = 1; $i <= 31; $i++)
  {
    echo "
                    <option" . ($i == $day ? " selected=\"selected\"" : "") . ">$i</option>";
  }

  echo "
                  </select>

                  <select name=\"${prefix}month\" onchange=\"ChangeOptionDays(this.form,'$prefix')\">";

  for ($i = 1; $i <= 12; $i++)
  {
    $m = utf8_strftime("%b", mktime(0, 0, 0, $i, 1, $year));
      
    print "
                    <option value=\"$i\"" . ($i == $month ? " selected=\"selected\"" : "") . ">$m</option>";
  }

  echo "
                  </select>
             <select name=\"${prefix}year\" onchange=\"ChangeOptionDays(this.form,'$prefix')\">";

  $min = min($year, date("Y")) - 5;
  $max = max($year, date("Y")) + 5;

  for ($i = $min; $i <= $max; $i++)
  {
    print "
                    <option value=\"$i\"" . ($i == $year ? " selected=\"selected\"" : "") . ">$i</option>";
  }

  echo "
                  </select>";
}

// Error handler - this is used to display serious errors such as database
// errors without sending incomplete HTML pages. This is only used for
// errors which "should never happen", not those caused by bad inputs.
// If $need_header!=0 output the top of the page too, else assume the
// caller did that. Alway outputs the bottom of the page and exits.
function fatal_error($need_header, $message)
{
  if ($need_header)
  {
    print_header(0, 0, 0, 0, "");
  }
  echo $message;
  require_once "trailer.inc";
  exit;
}

// Remove backslash-escape quoting if PHP is configured to do it with
// magic_quotes_gpc. Use this whenever you need the actual value of a GET/POST
// form parameter (which might have special characters) regardless of PHP's
// magic_quotes_gpc setting.
function unslashes($s)
{
  if (get_magic_quotes_gpc())
  {
    return stripslashes($s);
  }
  else
  {
    return $s;
  }
}

// Return a default area; used if no area is already known. This returns the
// lowest area ID in the database (no guaranty there is an area 1).
// This could be changed to implement something like per-user defaults.
function get_default_area()
{
  global $tbl_area;
  $area = sql_query1("SELECT id FROM $tbl_area ORDER BY area_name LIMIT 1");
  return ((!isset($area) || ($area < 0)) ? 0 : $area);
}

// Return a default room given a valid area; used if no room is already known.
// This returns the first room in alphbetic order in the database.
// This could be changed to implement something like per-user defaults.
function get_default_room($area)
{
  global $tbl_room;
  $room = sql_query1("SELECT id FROM $tbl_room WHERE area_id=$area ORDER BY room_name LIMIT 1");
  return ($room < 0 ? 0 : $room);
}

// Get the local day name based on language. Note 2000-01-02 is a Sunday.
function day_name($daynumber)
{
  return utf8_strftime("%A", mktime(0,0,0,1,2+$daynumber,2000));
}

function hour_min_format()
{
  global $twentyfourhour_format;
  if ($twentyfourhour_format)
  {
    return "%H:%M";
  }
  else
  {
    return "%I:%M%p";
  }
}

function period_date_string($t, $mod_time=0)
{
  global $periods;

  $time = getdate($t);
  $p_num = $time["minutes"] + $mod_time;
  if( $p_num < 0 )
  {
    $p_num = 0;
  }
  if( $p_num >= count($periods) - 1 )
  {
    $p_num = count($periods ) - 1;
  }
  // I have made the separater a ',' as a '-' leads to an ambiguious
  // display in report.php when showing end times.
  return array($p_num, $periods[$p_num] . utf8_strftime(", %A %d %B %Y",$t));
}

function period_time_string($t, $mod_time=0)
{
  global $periods;

  $time = getdate($t);
  $p_num = $time["minutes"] + $mod_time;
  if ( $p_num < 0 )
  {
    $p_num = 0;
  }
  if ( $p_num >= count($periods) - 1 )
  {
    $p_num = count($periods ) - 1;
  }
  return $periods[$p_num];
}

function time_date_string($t)
{
  global $twentyfourhour_format;

  if ($twentyfourhour_format)
  {
    return utf8_strftime("%H:%M:%S - %A %d %B %Y",$t);
  }
  else
  {
    return utf8_strftime("%I:%M:%S%p - %A %d %B %Y",$t);
  }
}

// Output a start table cell tag <td> with color class.
// $colclass is an entry type (A-J), zebra stripes if
// empty or row_highlight if highlighted.
// $slots is the number of time slots high that the cell should be
function tdcell($colclass, $slots)
{
  echo "<td class=\"$colclass\"";
  if ($slots > 1)
  // No need to output more HTML than necessary
  {
    echo " rowspan=\"$slots\"";
  }
  echo ">\n";
}

// Display the entry-type color key. This has up to 2 rows, up to 5 columns.
function show_colour_key()
{
  global $typel;
  // set the table width.   Default is 5, but try and avoid rows of unequal length
  switch (count($typel))
  {
    case '6':
      $table_width = 3;
      break;
    case '8':
    case '12':
      $table_width = 4;
      break;
    default:
      $table_width = 5;
  }
  echo "<table id=\"colour_key\"><tr>\n";
  $nct = 0;
  for ($ct = "A"; $ct <= "Z"; $ct++)
  {
    if (!empty($typel[$ct]))
    {
      if (++$nct > $table_width)
      {
        $nct = 1;
        echo "</tr><tr>";
      }
      tdcell($ct, 1);
      echo "<div class=\"celldiv1\" " .  // put the description inside a div which will give clipping in case of long names
      "title=\"$typel[$ct]\">\n";        // but put the name in the title so you can still read it all if you hover over it
      echo "$typel[$ct]</div></td>\n";
    }
  }
  // If there is more than one row and the bottom row isn't complete then 
  // pad it out with a single merged cell
  if ((count($typel) > $table_width) && ($nct < $table_width))
  {
    echo "<td colspan=\"" . ($table_width - $nct) . "\"" .
        " id=\"row_padding\">&nbsp;</td>\n";
  }
  echo "</tr></table>\n";
}

// Round time down to the nearest resolution
function round_t_down($t, $resolution, $am7)
{
  return (int)$t - (int)abs(((int)$t-(int)$am7)
                            % $resolution);
}

// Round time up to the nearest resolution
function round_t_up($t, $resolution, $am7)
{
  if (($t-$am7) % $resolution != 0)
  {
    return $t + $resolution - abs(((int)$t-(int)
                                   $am7) % $resolution);
  }
  else
  {
    return $t;
  }
}

// generates some html that can be used to select which area should be
// displayed.
function make_area_select_html( $link, $current, $year, $month, $day )
{
  global $tbl_area;
  $out_html = "
<form id=\"areaChangeForm\" method=\"get\" action=\"$link\">
  <div>
    <select name=\"area\" onchange=\"document.forms['areaChangeForm'].submit()\">";
  
    $sql = "select id, area_name from $tbl_area order by area_name";
    $res = sql_query($sql);
    if ($res)
    {
      for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
      {
        $selected = ($row['id'] == $current) ? "selected=\"selected\"" : "";
        $out_html .= "
      <option $selected value=\"". $row['id']. "\">" . htmlspecialchars($row['area_name']) . "</option>";
      }
    }
    $out_html .= "
    </select>
  
    <input type=\"hidden\" name=\"day\"   value=\"$day\">
    <input type=\"hidden\" name=\"month\" value=\"$month\">
    <input type=\"hidden\" name=\"year\"  value=\"$year\">
    <input type=\"submit\" value=\"".get_vocab("change")."\">
    </div>
</form>\n";

  return $out_html;
} // end make_area_select_html

function make_room_select_html( $link, $area, $current, $year, $month, $day )
{
  global $tbl_room;
  $out_html = "
<form id=\"roomChangeForm\" method=\"get\" action=\"$link\">
  <div>
    <select name=\"room\" onchange=\"document.forms['roomChangeForm'].submit()\">";
  
    $sql = "select id, room_name from $tbl_room where area_id=$area order by room_name";
    $res = sql_query($sql);
    if ($res)
    {
      for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
      {
        $selected = ($row['id'] == $current) ? "selected=\"selected\"" : "";
        $out_html .= "
      <option $selected value=\"". $row['id']. "\">" . htmlspecialchars($row['room_name']) . "</option>";
      }
    }
    $out_html .= "
    </select>
    <input type=\"hidden\" name=\"day\"   value=\"$day\">
    <input type=\"hidden\" name=\"month\" value=\"$month\">
    <input type=\"hidden\" name=\"year\"  value=\"$year\">
    <input type=\"hidden\" name=\"area\"  value=\"$area\">
    <input type=\"submit\" value=\"".get_vocab("change")."\">
  </div>
</form>\n";

  return $out_html;
} // end make_area_select_html


// This will return the appropriate value for isdst for mktime().
// The order of the arguments was chosen to match those of mktime.
// hour is added so that this function can when necessary only be
// run if the time is between midnight and 3am (all DST changes
// occur in this period.
function is_dst ( $month, $day, $year, $hour="-1" )
{
  if ( $hour != -1  && $hour > 3)
  {
    return( -1 );
  }
   
  // entering DST
  if( !date( "I", mktime(12, 0, 0, $month, $day-1, $year)) && 
      date( "I", mktime(12, 0, 0, $month, $day, $year)))
  {
    return( 0 ); 
  }

  // leaving DST
  else if( date( "I", mktime(12, 0, 0, $month, $day-1, $year)) && 
           !date( "I", mktime(12, 0, 0, $month, $day, $year)))
  {
    return( 1 );
  }
  else
  {
    return( -1 );
  }
}

// if crossing dst determine if you need to make a modification
// of 3600 seconds (1 hour) in either direction
function cross_dst ( $start, $end )
{
  // entering DST
  if ( !date( "I", $start) &&  date( "I", $end))
  {
    $modification = -3600;
  }

  // leaving DST
  else if(  date( "I", $start) && !date( "I", $end))
  {
    $modification = 3600;
  }
  else
  {
    $modification = 0;
  }

  return $modification;
}

// checks whether a given day of the week is supposed to be hidden in the display
function is_hidden_day ($dow)
{
  global $hidden_days;
  return (isset($hidden_days) && in_array($dow, $hidden_days));
}

function map_add_booking ($row, &$column, $am7, $pm7, $format)
{
  // Enters the contents of the booking found in $row into $column, which is
  // a column of the map of the bookings being prepared ready for display.
  //
  // $column    the column of the map that is being prepared (see below)
  // $row       a booking from the database
  // $am7       the start of the first slot of the booking day (Unix timestamp)
  // $pm7       the start of the last slot of the booking day (Unix timestamp)
  // $format    time format used for indexing the $map array
  
  // $row is expected to have the following field names, when present:
  //       room_id
  //       start_time
  //       end_time
  //       name
  //       entry_id
  //       type
  //       entry_description
  
  // $column is a column of the map of the screen that will be displayed
  // It looks like:
  //     $column[Time][n][id]
  //                     [color]
  //                     [data]
  //                     [long_descr]
  //                     [start_time]
  //                     [slots]
  
  // slots records the duration of the booking in number of time slots.
	// Used to calculate how high to make the block used for clipping
	// overflow descriptions.

  // Fill in the map for this meeting. Start at the meeting start time,
  // or the day start time, whichever is later. End one slot before the
  // meeting end time (since the next slot is for meetings which start then),
  // or at the last slot in the day, whichever is earlier.
  // Time is of the format HHMM without leading zeros.
  //
  // [n] exists because it's possible that there may be multiple bookings
  // in the same time slot.   Normally this won't be the case.   However it
  // can arise legitimately if you increase the resolution, or shift the 
  // displayed day.   For example if you previously had a resolution of 1800 
  // seconds you might have a booking (A) for 1000-1130 and another (B) for 1130-1230.
  // If you then increase the resolution to 3600 seconds, these two bookings 
  // will both occupy the 1100-1200 time slot.   [n] starts at 0.   For
  // the example above the map for the room would look like this
  //
  //       0  1
  // 1000  A
  // 1100  A  B
  // 1200  B
  //
  // Note: int casts on database rows for max may be needed for PHP3.
  // Adjust the starting and ending times so that bookings which don't
  // start or end at a recognized time still appear.
  
  global $resolution;
  
  $start_t = max(round_t_down($row['start_time'], $resolution, $am7), $am7);
  $end_t = min(round_t_up($row['end_time'], $resolution, $am7) - $resolution, $pm7);
  // calculate the times used for indexing
  $time_start_t = date($format,$start_t);
  $time_end_t = date($format,$end_t);
  

  for ($t = $start_t; $t <= $end_t; $t += $resolution)
  { 
    $time_t = date($format,$t);
    
    // find the first free index (in case there are multiple bookings in a timeslot)
    $n = 0;
    while (!empty($column[$time_t][$n]["id"]))
    {
      $n++;
    }
    
    // fill in the id, type and start time
    $column[$time_t][$n]["id"]    = $row['entry_id'];
    $column[$time_t][$n]["color"] = $row['type'];
    $column[$time_t][$n]["start_time"] = utf8_strftime(hour_min_format(), $row['start_time']);
    $column[$time_t][$n]["slots"] = null;  // to avoid undefined index NOTICE errors
    // if it's a multiple booking also fill in the name and description
    if ($n > 0)
    {
      $column[$time_t][$n]["data"]  = $row['name'];
      $column[$time_t][$n]["long_descr"]  = $row['entry_description'];
    }
    // otherwise just leave them blank (we'll fill in the first whole slot later)
    else
    {
      $column[$time_t][$n]["data"]  = "";
      $column[$time_t][$n]["long_descr"]  = ""; 
    }
  } // end for
  
  
  // Show the name of the booker, the description and the number of complete
  // slots in the first complete slot that the booking happens in, or at the 
  // start of the day if it started before today.

  // Find the number of time slots that the booking occupies, and the index
  // of the first slot that this booking has entirely to itself
  $n_slots = intval(($end_t - $start_t)/$resolution) + 1;
  $first_slot = $start_t;
  
  // If the last time slot is already occupied, we have a multiple
  // booking in the last slot, so decrement the number of slots that
  // we will display for this booking
  if (isset($column[$time_end_t][1]["id"]))
  {
    $n_slots--;
    // If we're only the second booking to land on this time slot
    // then we'll have to adjust the information held for the first booking
    // (unless it's just one slot long in the first place, when it 
    // doesn't matter as it will now be part of a multiple booking).
    // If we are the third booking or more, then it will have already
    // been adjusted.
    if (!isset($column[$time_end_t][2]["id"]))
    {
      if ($column[$time_end_t][0]["slots"] > 1)
      {
        // Move the name and description into the new first slot and decrement the number of slots
        $column[date($format, $end_t + $resolution)][0]["data"] = $column[$time_end_t][0]["data"];
        $column[date($format, $end_t + $resolution)][0]["long_descr"] = $column[$time_end_t][0]["long_descr"];
        $column[date($format, $end_t + $resolution)][0]["slots"] = $column[$time_end_t][0]["slots"] - 1;
      }
    }
  }
  
  // and if the first time slot is already occupied, decrement
  // again, adjust the first slot for this booking
  if (isset($column[$time_start_t][1]["id"]))
  {
    $n_slots--;
    $first_slot += $resolution;
    // If we're only the second booking to land on this time slot
    // then we'll have to adjust the information held for the first booking
    if (!isset($column[$time_start_t][2]["id"]))
    {
      // Find the first slot ($s) of the first booking, by going back through the slots
      // until you get to a slot that has more than one booking, or else just
      // one booking but a different one, or else no booking at all.    Then 
      // you know you've gone back one slot too far.
      $first_booking_id = $column[$time_start_t][0]["id"];
      for ($s = $start_t -$resolution; $s >= $am7; $s -= $resolution)
      {
        if (isset($column[date($format,$s)][1]["id"]) || 
            !isset($column[date($format,$s)][0]["id"]) ||
            ($column[date($format,$s)][0]["id"] != $first_booking_id))   
        {
          $s += $resolution;  // Gone back one slot too far, so advance one.
          break;
        }
      }
      // and decrement the slot count for the first booking
      $column[date($format,$s)][0]["slots"]--;
      // and put the name and description in the multiply booked slot
      $column[$time_start_t][0]["data"] = $column[date($format,$s)][0]["data"];
      $column[$time_start_t][0]["long_descr"] = $column[date($format,$s)][0]["long_descr"];

    }
  }
 
  // now we've got all the information we can enter it in the first complete
  // slot for the booking (provided it's not a multiple booking slot)
  if (!isset($column[date($format,$first_slot)][1]["id"]))
  {
    $column[date($format,$first_slot)][0]["data"] = $row['name'];
    $column[date($format,$first_slot)][0]["long_descr"] = $row['entry_description'];
    $column[date($format,$first_slot)][0]["slots"] = $n_slots;
  }

} // end function map_add_booking()



function draw_cell($cell, $query_strings, $row_class)
{
  // draws a single cell in the main table of the day and week views
  //
  // $cell is a two dimensional array that is part of the map of the whole
  // display and looks like this:
  // 
  // $cell[n][id]
  //         [color]
  //         [data]
  //         [long_descr]
  //         [start_time]
  //         [slots]
  //
  // where n is the number of the booking in the cell.    There can be none, one or many 
  // bookings in a cell.    If there are no bookings then a blank cell is drawn with a link
  // to the edit entry form.     If there is one booking, then the booking is shown in that
  // cell.    If there is more than one booking then all the bookings are shown, but they can 
  // be shown in two different ways: minimised and maximised.   By default they are shown
  // minimised, so that the standard row height is preserved.   By clicking a control
  // the cell can be maximised.   (Multiple bookings can arise in a cell if the resolution
  // of an existing database in increased or the booking day is shifted).
  
  // $query_strings is an array containg the query strings (or partial query strings) to be
  // appended to the link used for the cell.    It is indexed as follows:
  //    ['new_periods']   the string to be used for an empty cell if using periods
  //    ['new_times']     the string to be used for an empty cell if using times
  //    ['booking']       the string to be used for a full cell
  //
  // $row_class specifies whether the row is even or odd so that the zebra stripes can be drawn
  
  global $main_cell_height, $main_table_cell_border_width;
  global $area, $year, $month, $timetohighlight;
  global $javascript_cursor, $enable_periods;
  
  // if the time slot has got multiple bookings, then draw a mini-table
  if(isset($cell[1]["id"]))
  {
    // Find out how many bookings there are (needed to calculate heights)
    $n_bookings = 0;
    while (isset($cell[$n_bookings]["id"]))
    {
      $n_bookings++;
    }
    
    // Give the cell a unique id so that the JavaScript can use it
    $td_id = uniqid('td', true);
    
    // Make the class maximized by default so that if you don't have JavaScript then
    // you can still see all the bookings.    If you have JavaScript it will overwrite
    // the class and make it minimized.
    echo "<td class=\"multiple_booking maximized\" id=\"$td_id\">\n";
    ?>
    <script type="text/javascript">
    //<![CDATA[
      document.getElementById('<?php echo $td_id ?>').className = "multiple_booking minimized <?php echo $row_class ?>";
    //]]>
    </script>
    <?php
    
    // First draw the mini table
    echo "<div class=\"celldiv1 mini\">\n";
    echo "<div class=\"multiple_control\" onClick=\"document.getElementById('$td_id').className = 'multiple_booking maximized $row_class'\">+</div>\n";
    echo "<table>\n";
    echo "<tbody>\n";
    
    $row_height = $main_cell_height - (($n_bookings-1) * $main_table_cell_border_width);   // subtract the borders (first row has no top border)
    $row_height = $row_height/$n_bookings;  // split what's left between the bookings
    $row_height = (int) ceil($row_height);  // round up, so that (a) there's no empty space at the bottom
                                            // and (b) each stripe is at least 1 unit high
    for ($n=0; $n<$n_bookings; $n++)
    {
      $id    = $cell[$n]["id"];
      $color = $cell[$n]["color"];
      $descr = htmlspecialchars($cell[$n]["data"]);
      $long_descr = htmlspecialchars($cell[$n]["long_descr"]);
      echo "<tr>\n";
      echo "<td class=\"$color\"" .
           (($n==0) ? " style=\"border-top-width: 0\"" : "") .   // no border for first row
           ">\n";
      echo "<div style=\"overflow: hidden; " .
           "height: " . $row_height . "px; " .
           "max-height: " . $row_height . "px; " .
           "min-height: " . $row_height . "px\">\n";
      echo "&nbsp;\n";
      echo "</div>\n";
      echo "</td>\n";
      echo "</tr>\n";
    }
    echo "</tbody>\n";
    echo "</table>\n";
    echo "</div>\n";
    
    // Now draw the maxi table
    echo "<div class=\"maxi\">\n";
    $total_height = $n_bookings * $main_cell_height;
    $total_height += ($n_bookings - 1) * $main_table_cell_border_width;  // (first row has no top border)
    echo "<div class=\"multiple_control\" " .
         "onClick=\"document.getElementById('$td_id').className = 'multiple_booking minimized $row_class'\" " .
         "style =\"height: " . $total_height . "px; " .
                  "min-height: " . $total_height . "px; " .
                  "max-height: " . $total_height . "px; " .
         "\">-</div>\n";  
    echo "<table>\n";
    echo "<tbody>\n";
    for ($n=0; $n<$n_bookings; $n++)
    {
      $id    = $cell[$n]["id"];
      $color = $cell[$n]["color"];
      $descr = htmlspecialchars($cell[$n]["start_time"] . " " . $cell[$n]["data"]);
      $long_descr = htmlspecialchars($cell[$n]["long_descr"]);
      echo "<tr>\n";
      echo "<td class=\"$color\"" .
           (($n==0) ? " style=\"border-top-width: 0\"" : "") .   // no border for first row
           ">\n";
      echo "<div class=\"celldiv1\">\n";     // we want clipping of overflow
      echo "  <a href=\"view_entry.php?id=$id&amp;". $query_strings['booking'] . "\" title=\"$long_descr\">$descr</a>\n";
      echo "</div>\n";
     
      echo "</td>\n";
      echo "</tr>\n";
    }
    echo "</tbody>\n";
    echo "</table>\n";
    echo "</div>\n";
    
    echo "</td>\n";
  }  // end of if isset ( ...[1]..)
  
  // otherwise draw a cell, showing either the booking or a blank cell
  else
  {  
    if(isset($cell[0]["id"]))
    {       
      $id    = $cell[0]["id"];
      $color = $cell[0]["color"];
      $descr = htmlspecialchars($cell[0]["data"]);
      $long_descr = htmlspecialchars($cell[0]["long_descr"]);
      $slots = $cell[0]["slots"];
    }
    else  // id not set
    {
      unset($id);
      $slots = 1;
    }

    // $c is the colour of the cell that the browser sees. Zebra stripes normally,
    // row_highlight if we're highlighting that line and the appropriate colour if
    // it is booked (determined by the type).
    // We tell if its booked by $id having something in it
    if (isset($id))
    {
      $c = $color;
    }
    else if (isset($timetohighlight) && ($time_t == $timetohighlight))
    {
      $c = "row_highlight";
    }
    else
    {
      $c = $row_class; // Use the default color class for the row.
    }
    
    // Don't put in a <td> cell if the slot is booked and there's no description.
    // This would mean that it's the second or subsequent slot of a booking and so the
    // <td> for the first slot would have had a rowspan that extended the cell down for
    // the number of slots of the booking.

    if (!(isset($id) && ($descr == ""))) 
    {
      tdcell($c, $slots);

      // If the room isn't booked then allow it to be booked
      if (!isset($id))
      {
        echo "<div class=\"celldiv1\">\n";  // a bookable slot is only one unit high

        if ($javascript_cursor)
        {
          echo "<script type=\"text/javascript\">\n";
          echo "//<![CDATA[\n";
          echo "BeginActiveCell();\n";
          echo "//]]>\n";
          echo "</script>\n";
        }
        
        if( $enable_periods )
        {
          echo "<a class=\"new_booking\" href=\"edit_entry.php?" . $query_strings['new_periods'] . "\">\n";
          echo "<img src=\"new.gif\" alt=\"New\" width=\"10\" height=\"10\">\n";
          echo "</a>\n";
        }
        else
        {
          echo "<a class=\"new_booking\" href=\"edit_entry.php?" . $query_strings['new_times'] . "\">\n";
          echo "<img src=\"new.gif\" alt=\"New\" width=\"10\" height=\"10\">\n";
          echo "</a>\n";
        }
        
        if ($javascript_cursor)
        {
          echo "<script type=\"text/javascript\">\n";
          echo "//<![CDATA[\n";
          echo "EndActiveCell();\n";
          echo "//]]>\n";
          echo "</script>\n";
        }
        echo "</div>\n";
      }
      else                 // if it is booked then show the booking
      {    
        echo "<div class=\"celldiv" . $slots . "\">\n";     // we want clipping of overflow
        echo "  <a href=\"view_entry.php?id=$id&amp;". $query_strings['booking'] . "\" title=\"$long_descr\">$descr</a>\n";
        echo "</div>\n";
      }
      echo "</td>\n";
    }
  }
}  // end function draw_cell

?>
