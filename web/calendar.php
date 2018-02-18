<?php
namespace MRBS;

require "defaultincludes.inc";


function get_location_nav($view, $year, $month, $day, $area, $room)
{
  $html = '';
  
  $html .= "<nav id=\"location\">\n";
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
  
  $html .= "<nav id=\"location\">\n";
  
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
    $html .= "<a href=\"calendar.php?$query\">" . htmlspecialchars(get_vocab($view)) . "</a>";
  }
  
  $html .= "</nav>\n";
  
  return $html;
}


function get_calendar_nav($view, $year, $month, $day, $area, $room)
{
  $html = '';
  
  $html .= "<nav id=\"calendar\">\n";
  
  $html .= get_location_nav($view, $year, $month, $day, $area, $room);
  $html .= get_view_nav($view, $year, $month, $day, $area, $room);
  
  $html .= "</nav>\n";
  
  return $html;
}


// Get non-standard form variables
$ajax = get_form_var('ajax', 'int');
$view = get_form_var('view', 'string', isset($default_view) ? $default_view : 'day');

// Check the user is authorised for this page
if (!checkAuthorised($just_check = $ajax))
{
  exit;
}

// print the page header
print_header($day, $month, $year, $area, isset($room) ? $room : null);

echo get_calendar_nav($view, $year, $month, $day, $area, $room);

print_footer();
