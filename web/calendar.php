<?php
namespace MRBS;

require "defaultincludes.inc";
require_once "functions_table.inc";


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
  
  $html .= "</nav>\n";
  
  return $html;
}


function get_arrow_nav($view, $year, $month, $day, $area, $room)
{
  $left_arrow  = '&#x276e';  // HEAVY LEFT-POINTING ANGLE QUOTATION MARK ORNAMENT
  $right_arrow = '&#x276f';  // HEAVY RIGHT-POINTING ANGLE QUOTATION MARK ORNAMENT
  
  $html = '';
  
  $html .= "<nav class=\"arrow\">\n";
  $html .= "<a href=\"#\">$left_arrow</a>";
  $html .= "<a href=\"#\">" . get_vocab('today') . "</a>";
  $html .= "<a href=\"#\">$right_arrow</a>";
  $html .= "</nav>\n";
  
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
