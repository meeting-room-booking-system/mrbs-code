<?php
namespace MRBS;

// mrbs/month.php - Month-at-a-time view

require "defaultincludes.inc";
require_once "mincals.inc";
require_once "functions_table.inc";

// 3-value compare: Returns result of compare as "< " "= " or "> ".
function cmp3($a, $b)
{
  if ($a < $b)
  {
    return "< ";
  }
  if ($a == $b)
  {
    return "= ";
  }
  return "> ";
}


// Describe the start and end time, accounting for "all day"
// and for entries starting before/ending after today.
// There are 9 cases, for start time < = or > midnight this morning,
// and end time < = or > midnight tonight.

function get_booking_summary($start, $end, $day_start, $day_end)
{
  global $enable_periods, $area;
  
  // Use ~ (not -) to separate the start and stop times, because MSIE
  // will incorrectly line break after a -.
  $separator = '~';
  $after_today = "====&gt;";
  $before_today = "&lt;====";
  $midnight = "24:00";  // need to fix this so it works with AM/PM configurations (and for that matter 24h)
  // Localized "all day" text but with non-breaking spaces:
  $all_day = preg_replace("/ /", "&nbsp;", get_vocab("all_day"));
  
  if ($enable_periods)
  {
    $start_str = htmlspecialchars(period_time_string($start, $area));
    $end_str   = htmlspecialchars(period_time_string($end, $area, -1));
  }
  else
  {
    $start_str = htmlspecialchars(utf8_strftime(hour_min_format(), $start));
    $end_str   = htmlspecialchars(utf8_strftime(hour_min_format(), $end));
  }
 
  switch (cmp3($start, $day_start) . cmp3($end, $day_end + 1))
  {
    case "> < ":         // Starts after midnight, ends before midnight
    case "= < ":         // Starts at midnight, ends before midnight
      $result = $start_str;
      // Don't bother showing the end if it's the same as the start period
      if ($end_str !== $start_str)
      {
        $result .= $separator . $end_str;
      }
      break;
    case "> = ":         // Starts after midnight, ends at midnight
      $result = $start_str . $separator . $midnight;
      break;
    case "> > ":         // Starts after midnight, continues tomorrow
      $result = $start_str . $separator . $after_today;
      break;
    case "= = ":         // Starts at midnight, ends at midnight
      $result = $all_day;
      break;
    case "= > ":         // Starts at midnight, continues tomorrow
      $result = $all_day . $after_today;
      break;
    case "< < ":         // Starts before today, ends before midnight
      $result = $before_today . $separator .  $end_str;
      break;
    case "< = ":         // Starts before today, ends at midnight
      $result = $before_today . $all_day;
      break;
    case "< > ":         // Starts before today, continues tomorrow
      $result = $before_today . $all_day . $after_today;
      break;
  }
  
  return $result;
}


function get_table_head()
{
  global $weekstarts;
  
  $html = '';
  
  // Weekday name header row:
  $html .= "<thead>\n";
  $html .= "<tr>\n";
  for ($i = 0; $i< 7; $i++)
  {
    if (is_hidden_day(($i + $weekstarts) % 7))
    {
      // These days are to be hidden in the display (as they are hidden, just give the
      // day of the week in the header row 
      $html .= "<th class=\"hidden_day\">" . day_name(($i + $weekstarts)%7) . "</th>";
    }
    else
    {
      $html .= "<th>" . day_name(($i + $weekstarts)%7) . "</th>";
    }
  }
  $html .= "\n</tr>\n";
  $html .= "</thead>\n";
  
  return $html;
}


function get_blank_day($col)
{
  global $weekstarts;
  
  $td_class = (is_hidden_day(($col + $weekstarts) % 7)) ? 'hidden_day' : 'invalid';
  return "<td class=\"$td_class\"><div class=\"cell_container\">&nbsp;</div></td>\n";
}


function month_table_innerhtml($day, $month, $year, $room, $area)
{
  global $tbl_entry;
  global $weekstarts, $view_week_number, $show_plus_link, $monthly_view_entries_details;
  global $enable_periods, $morningstarts, $morningstarts_minutes;
  global $approval_enabled, $confirmation_enabled;
  global $is_private_field;
  global $user;
  global $debug_flag;
  
  $html = '';
  
  // Month view start time. This ignores morningstarts/eveningends because it
  // doesn't make sense to not show all entries for the day, and it messes
  // things up when entries cross midnight.
  $month_start = mktime(0, 0, 0, $month, 1, $year);
  // What column the month starts in: 0 means $weekstarts weekday.
  $weekday_start = (date("w", $month_start) - $weekstarts + 7) % 7;
  $days_in_month = date("t", $month_start);
  

  // Get all meetings for this month in the room that we care about
  // This data will be retrieved day-by-day fo the whole month
  for ($day_num = 1; $day_num<=$days_in_month; $day_num++)
  {
    $start_first_slot = get_start_first_slot($month, $day_num, $year);
    $end_last_slot = get_end_last_slot($month, $day_num, $year);
    $entries = get_entries_by_room($room, $start_first_slot, $end_last_slot);

    // Build an array of information about each day in the month.
    // The information is stored as:
    //   d[monthday]["id"][] = ID of each entry, for linking.
    //   d[monthday]["data"][] = "start-stop" times or "name" of each entry.

    foreach($entries as $entry)
    {
      if ($debug_flag)
      {
        $html .= "<br>DEBUG: id ".$entry['id'].", starts ".$entry['start_time'].", ends ".$entry['end_time']."\n";
      }

      if ($debug_flag)
      {
        $html .= "<br>DEBUG: Entry ".$entry['id']." day $day_num\n";
      }
      
      // Handle private events
      if (is_private_event($entry['status'] & STATUS_PRIVATE)  &&
          !getWritable($entry['create_by'], $user, $room))
      {
        $entry['status'] |= STATUS_PRIVATE;   // Set the private bit
        if ($is_private_field['entry.name'])
        {
          $entry['name'] = "[".get_vocab('unavailable')."]";
        }
        if (!empty($is_private_field['entry.type']))
        {
          $entry['type'] = 'private_type';
        }
      }
      else
      {
        $entry['status'] &= ~STATUS_PRIVATE;  // Clear the private bit
      }
      
      $d[$day_num]["shortdescrip"][] = htmlspecialchars($entry['name']);
      $d[$day_num]["id"][]           = $entry['id'];
      $d[$day_num]["color"][]        = $entry['type'];
      $d[$day_num]["status"][]       = $entry['status'];
      $d[$day_num]["is_repeat"][]    = isset($entry['repeat_id']);
      $d[$day_num]["data"][]         = get_booking_summary($entry['start_time'],
                                                           $entry['end_time'],
                                                           $start_first_slot,
                                                           $end_last_slot);
    }
  }

  
  if ($debug_flag)
  {
    $html .= "<p>DEBUG: Array of month day data:</p><pre>\n";
    for ($i = 1; $i <= $days_in_month; $i++)
    {
      if (isset($d[$i]["id"]))
      {
        $n = count($d[$i]["id"]);
        $html .= "Day $i has $n entries:\n";
        for ($j = 0; $j < $n; $j++)
        {
          $html .= "  ID: " . $d[$i]["id"][$j] .
            " Data: " . $d[$i]["data"][$j] . "\n";
        }
      }
    }
    $html .= "</pre>\n";
  }
  
  $html .= get_table_head();
  
  // Main body
  $html .= "<tbody>\n";
  $html .= "<tr>\n";

  // Skip days in week before start of month:
  for ($weekcol = 0; $weekcol < $weekday_start; $weekcol++)
  {
    $html .= get_blank_day($weekcol);
  }

  // Draw the days of the month:
  for ($cday = 1; $cday <= $days_in_month; $cday++)
  {
    // if we're at the start of the week (and it's not the first week), start a new row
    if (($weekcol == 0) && ($cday > 1))
    {
      $html .= "</tr><tr>\n";
    }
    
    // output the day cell
    if (is_hidden_day(($weekcol + $weekstarts) % 7))
    {
      // These days are to be hidden in the display (as they are hidden, just give the
      // day of the week in the header row 
      $html .= "<td class=\"hidden_day\">\n";
      $html .= "<div class=\"cell_container\">\n";
      $html .= "<div class=\"cell_header\">\n";
      // first put in the day of the month
      $html .= "<span>$cday</span>\n";
      $html .= "</div>\n";
      $html .= "</div>\n";
      $html .= "</td>\n";
    }
    else
    {   
      $html .= "<td class=\"valid\">\n";
      $html .= "<div class=\"cell_container\">\n";
      
      $html .= "<div class=\"cell_header\">\n";
      // If it's a Monday (the start of the ISO week), show the week number
      if ($view_week_number && (($weekcol + $weekstarts)%7 == 1))
      {
        $html .= "<a class=\"week_number\" href=\"week.php?year=$year&amp;month=$month&amp;day=$cday&amp;area=$area&amp;room=$room\">";
        $html .= date("W", gmmktime(12, 0, 0, $month, $cday, $year));
        $html .= "</a>\n";
      }
      // then put in the day of the month
      $html .= "<a class=\"monthday\" href=\"day.php?year=$year&amp;month=$month&amp;day=$cday&amp;area=$area\">$cday</a>\n";

      $html .= "</div>\n";
      
      // then the link to make a new booking
      $query_string = "room=$room&amp;area=$area&amp;year=$year&amp;month=$month&amp;day=$cday";
      if ($enable_periods)
      {
        $query_string .= "&amp;period=0";
      }
      else
      {
        $query_string .= "&amp;hour=$morningstarts&amp;minute=$morningstarts_minutes";
      }
      
      $html .= "<a class=\"new_booking\" href=\"edit_entry.php?$query_string\">\n";
      if ($show_plus_link)
      {
        $html .= "<img src=\"images/new.gif\" alt=\"New\" width=\"10\" height=\"10\">\n";
      }
      $html .= "</a>\n";
      
      // then any bookings for the day
      if (isset($d[$cday]["id"][0]))
      {
        $html .= "<div class=\"booking_list\">\n";
        $n = count($d[$cday]["id"]);
        // Show the start/stop times, 1 or 2 per line, linked to view_entry.
        for ($i = 0; $i < $n; $i++)
        {
          // give the enclosing div the appropriate width: full width if both,
          // otherwise half-width (but use 49.9% to avoid rounding problems in some browsers)
          $class = $d[$cday]["color"][$i]; 
          if ($d[$cday]["status"][$i] & STATUS_PRIVATE)
          {
            $class .= " private";
          }
          if ($approval_enabled && ($d[$cday]["status"][$i] & STATUS_AWAITING_APPROVAL))
          {
            $class .= " awaiting_approval";
          }
          if ($confirmation_enabled && ($d[$cday]["status"][$i] & STATUS_TENTATIVE))
          {
            $class .= " tentative";
          }
          $class .= " $monthly_view_entries_details";
          $html .= "<div class=\"" . $class . "\">\n";
          $booking_link = "view_entry.php?id=" . $d[$cday]["id"][$i] . "&amp;day=$cday&amp;month=$month&amp;year=$year";
          $slot_text = $d[$cday]["data"][$i];
          $description_text = utf8_substr($d[$cday]["shortdescrip"][$i], 0, 255);
          $full_text = $slot_text . " " . $description_text;
          switch ($monthly_view_entries_details)
          {
            case "description":
            {
              $display_text = $description_text;
              break;
            }
            case "slot":
            {
              $display_text = $slot_text;
              break;
            }
            case "both":
            {
              $display_text = $full_text;
              break;
            }
            default:
            {
              $html .= "error: unknown parameter";
            }
          }
          $html .= "<a href=\"$booking_link\" title=\"$full_text\">";
          $html .= ($d[$cday]['is_repeat'][$i]) ? "<img class=\"repeat_symbol\" src=\"images/repeat.png\" alt=\"" . get_vocab("series") . "\" title=\"" . get_vocab("series") . "\" width=\"10\" height=\"10\">" : '';
          $html .= "$display_text</a>\n";
          $html .= "</div>\n";
        }
        $html .= "</div>\n";
      }
      
      $html .= "</div>\n";
      $html .= "</td>\n";
    }
    
    // increment the day of the week counter
    if (++$weekcol == 7)
    {
      $weekcol = 0;
    }

  } // end of for loop going through valid days of the month

  // Skip from end of month to end of week:
  if ($weekcol > 0)
  {
    for (; $weekcol < 7; $weekcol++)
    {
      $html .= get_blank_day($weekcol);
    }
  }
  
  $html .= "</tr>\n";
  $html .= "</tbody>\n";
  
  return $html;
}


$debug_flag = get_form_var('debug_flag', 'int');
$ajax = get_form_var('ajax', 'int');

// Check the user is authorised for this page
if (!checkAuthorised($just_check = $ajax))
{
  exit;
}
$user = getUserName();

$inner_html = month_table_innerhtml($day, $month, $year, $room, $area);

if ($ajax)
{
  echo $inner_html;
  exit;
}


// print the page header
print_header($day, $month, $year, $area, isset($room) ? $room : null);


// Note $room will be 0 if there are no rooms; this is checked for below.

// Month view start time. This ignores morningstarts/eveningends because it
// doesn't make sense to not show all entries for the day, and it messes
// things up when entries cross midnight.
$month_start = mktime(0, 0, 0, $month, 1, $year);

if ($enable_periods)
{
  $resolution = 60;
  $morningstarts = 12;
  $morningstarts_minutes = 0;
  $eveningends = 12;
  $eveningends_minutes = count($periods)-1;
}




// Section with areas, rooms, minicals.
echo "<div id=\"dwm_header\" class=\"screenonly\">\n";

// Get the area and room names (we will need them later for the heading)
$this_area_name = get_area_name($area);
$this_room_name = get_room_name($room);
// The room is invalid if it doesn't exist, or else it has been disabled, either explicitly
// or implicitly because the area has been disabled
$room_invalid = !isset($this_area_name) || ($this_area_name === FALSE) ||
                !isset($this_room_name) || ($this_room_name === FALSE);
                          
// Show all available areas
echo make_area_select_html('month.php', $area, $year, $month, $day);  
// Show all available rooms in the current area:
echo make_room_select_html('month.php', $area, $room, $year, $month, $day);
    
// Draw the three month calendars
if (!$display_calendar_bottom)
{
  minicals($year, $month, $day, $area, $room, 'month');
}

echo "</div>\n";


// Don't continue if this room is invalid, which could be because the area
// has no rooms, or else the room or area has been disabled
if ($room_invalid)
{
  echo "<h1>".get_vocab("no_rooms_for_area")."</h1>";
  output_trailer();
  exit;
}

// Show Month, Year, Area, Room header:
echo "<div id=\"dwm\">\n";
echo "<h2>" . utf8_strftime($strftime_format['monthyear'], $month_start)
  . " - " . htmlspecialchars("$this_area_name - $this_room_name") . "</h2>\n";
echo "</div>\n";

// Show Go to month before and after links
//y? are year and month and day of the previous month.
//t? are year and month and day of the next month.
//c? are year and month of this month.   But $cd is the day that was passed to us.

$i= mktime(12,0,0,$month-1,1,$year);
$yy = date("Y",$i);
$ym = date("n",$i);
$yd = $day;
while (!checkdate($ym, $yd, $yy) && ($yd > 1))
{
  $yd--;
}

$i= mktime(12,0,0,$month+1,1,$year);
$ty = date("Y",$i);
$tm = date("n",$i);
$td = $day;
while (!checkdate($tm, $td, $ty) && ($td > 1))
{
  $td--;
}

$cy = date("Y");
$cm = date("m");
$cd = $day;    // preserve the day information
while (!checkdate($cm, $cd, $cy) && ($cd > 1))
{
  $cd--;
}

$href_before = "month.php?area=$area&amp;room=$room&amp;year=$yy&amp;month=$ym&amp;day=$yd";
$href_now    = "month.php?area=$area&amp;room=$room&amp;year=$cy&amp;month=$cm&amp;day=$cd";
$href_after  = "month.php?area=$area&amp;room=$room&amp;year=$ty&amp;month=$tm&amp;day=$td";

$before_after_links_html = "
<nav class=\"date_nav\">
  <a class=\"date_before\" href=\"$href_before\">" . get_vocab("monthbefore") . "</a>
  <a class=\"date_now\" href=\"$href_now\">" . get_vocab("gotothismonth") . "</a>
  <a class=\"date_after\" href=\"$href_after\">" . get_vocab("monthafter") . "</a>
</nav>\n";

echo $before_after_links_html;

if ($debug_flag)
{
  $days_in_month = date("t", $month_start);
  $month_end = mktime(23, 59, 59, $month, $days_in_month, $year);
  // What column the month starts in: 0 means $weekstarts weekday.
  $weekday_start = (date("w", $month_start) - $weekstarts + 7) % 7;
  echo "<p>DEBUG: month=$month year=$year start=$weekday_start range=$month_start:$month_end</p>\n";
}

echo "<table class=\"dwm_main\" id=\"month_main\">\n";
echo $inner_html;
echo "</table>\n";

echo $before_after_links_html;
show_colour_key();

// Draw the three month calendars
if ($display_calendar_bottom)
{
  minicals($year, $month, $day, $area, $room, 'month');
}

output_trailer();

