<?php
namespace MRBS;

require "defaultincludes.inc";
require_once "functions_table.inc";


// Gets the link to the next/previous day/week/month
function get_adjacent_link($view, $year, $month, $day, $area, $room, $next=false)
{
  switch ($view)
  {
    case 'day':
      // find the adjacent non-hidden day
      $d = $day;
      do
      { 
        $d += ($next) ? 1 : -1;
        $time = mktime(12, 0, 0, $month, $d, $year);
      }
      while (is_hidden_day(date('w', $time)) && (abs($d - $day) < 7));  // break the loop if all days are hidden
      break;
    case 'week':
      $time = mktime(12, 0, 0, $month, $day + (($next) ? 7 : -7), $year);
      break;
    case 'month':
      $time = mktime(12, 0, 0, $month + (($next) ? 1 : -1), 1, $year);
      // Keep the day information, but make sure it's a valid day in the new month
      $d = min($day, date('t', $time));
      $time = mktime(12, 0, 0, $month + (($next) ? 1 : -1), $d, $year);
      break;
    default:
      throw new \Exception("Unknown view '$view'");
      break;
  }
  
  $date = getdate($time);
  
  $vars = array('view'  => $view,
                'year'  => $date['year'],
                'month' => $date['mon'],
                'day'   => $date['mday'],
                'area'  => $area,
                'room'  => $room);
  
  return 'calendar.php?' . http_build_query($vars, '', '&amp;');
}


// Gets the link for today
function get_today_link($view, $area, $room)
{
  $date = getdate();
  
  $vars = array('view'  => $view,
                'year'  => $date['year'],
                'month' => $date['mon'],
                'day'   => $date['mday'],
                'area'  => $area,
                'room'  => $room);
  
  return 'calendar.php?' . http_build_query($vars, '', '&amp;');
}


function get_location_nav($view, $year, $month, $day, $area, $room)
{
  $html = '';
  
  $html .= "<nav class=\"location\">\n";
  $html .= make_area_select_html($view, $year, $month, $day, $area);
  
  if ($view !== 'day')
  {
    $html .= make_room_select_html($view, $year, $month, $day, $area, $room);
  }
  
  $html .= "</nav>\n";
  
  return $html;
}


function get_view_nav($current_view, $year, $month, $day, $area, $room)
{
  $html = '';
  
  $html .= "<nav class=\"view\">\n";
  
  $views = array('day', 'week', 'month');
  
  foreach ($views as $view)
  {
    $vars = array('view'  => $view,
                  'year'  => $year,
                  'month' => $month,
                  'day'   => $day,
                  'area'  => $area,
                  'room'  => $room);
                  
    $query = http_build_query($vars, '', '&amp;');
    $html .= "<a";
    $html .= ($view == $current_view) ? ' class="selected"' : '';
    $html .= " href=\"calendar.php?$query\">" . htmlspecialchars(get_vocab($view)) . "</a>";
  }
  
  $html .= "</nav>";
  
  return $html;
}


function get_arrow_nav($view, $year, $month, $day, $area, $room)
{
  $html = '';
  
  switch ($view)
  {
    case 'day':
      $title_prev = get_vocab('daybefore');
      $title_next = get_vocab('dayafter');
      break;
    case 'week':
      $title_prev = get_vocab('weekbefore');
      $title_next = get_vocab('weekafter');
      break;
    case 'month':
      $title_prev = get_vocab('monthbefore');
      $title_next = get_vocab('monthafter');
      break;
    default:
      throw new \Exception("Unknown view '$view'");
      break;
  }
  
  $link_prev = get_adjacent_link($view, $year, $month, $day, $area, $room, false);
  $link_today = get_today_link($view, $area, $room);
  $link_next = get_adjacent_link($view, $year, $month, $day, $area, $room, true);
  
  $html .= "<nav class=\"arrow\">\n";
  $html .= "<a class=\"prev\" title=\"$title_prev\" href=\"" . $link_prev . "\"></a>";  // Content will be filled in by CSS
  $html .= "<a href=\"" . $link_today . "\">" . get_vocab('today') . "</a>";
  $html .= "<a class=\"next\" title=\"$title_next\" href=\"" . $link_next . "\"></a>";  // Content will be filled in by CSS
  $html .= "</nav>";
  
  return $html;
}


function get_calendar_nav($view, $year, $month, $day, $area, $room)
{
  $html = '';
  
  $html .= "<nav id=\"calendar\">\n";
  
  $html .= get_arrow_nav($view, $year, $month, $day, $area, $room);
  $html .= get_location_nav($view, $year, $month, $day, $area, $room);
  $html .= get_view_nav($view, $year, $month, $day, $area, $room);
  
  $html .= "</nav>\n";
  
  return $html;
}


// Get non-standard form variables
$ajax = get_form_var('ajax', 'int');
$timetohighlight = get_form_var('timetohighlight', 'int');
$view = get_form_var('view', 'string', isset($default_view) ? $default_view : 'day');

// Check the user is authorised for this page
if (!checkAuthorised($just_check = $ajax))
{
  exit;
}

switch ($view)
{
  case 'day':
    $inner_html = day_table_innerhtml($year, $month, $day, $area, $room, $timetohighlight);
    break;
  case 'week':
    $inner_html = week_table_innerhtml($year, $month, $day, $area, $room, $timetohighlight);
    break;
  case 'month':
    $inner_html = month_table_innerhtml($year, $month, $day, $area, $room);
    break;
  default:
    throw new \Exception("Unknown view '$view'");
    break;
}


if ($ajax)
{
  echo $inner_html;
  exit;
}

// print the page header
print_header($day, $month, $year, $area, isset($room) ? $room : null);

echo get_calendar_nav($view, $year, $month, $day, $area, $room);

echo "<table class=\"dwm_main\" id=\"${view}_main\" data-resolution=\"$resolution\">\n";
echo $inner_html;
echo "</table>\n";

show_colour_key();

print_footer();
