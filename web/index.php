<?php
namespace MRBS;

use MRBS\Form\Form;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementSelect;

require "defaultincludes.inc";
require_once "functions_table.inc";


// Display the entry-type color key.
function get_color_key()
{
  global $booking_types;

  $html = '';

  // No point in showing the color key if we aren't using entry types.  (Note:  count()
  // returns 0 if its parameter is not set).
  if (count($booking_types) > 1)
  {
    $html .= "<div class=\"color_key js_hidden\">\n";

    foreach ($booking_types as $key)
    {
      $html .= "<div class=\"$key\">" . get_type_vocab($key) . "</div>\n";
    }

    $html .= "</div>\n";
  }

  return $html;
}


// generates some html that can be used to select which area should be
// displayed.
function make_area_select_html($view, $year, $month, $day, $current)
{
  $out_html = '';
  
  $areas = get_area_names();

  // Only show the areas if there are more than one of them, otherwise
  // there's no point
  if (count($areas) > 1)
  {
    $page_date = format_iso_date($year, $month, $day);
    
    $form = new Form();
    
    $form->setAttributes(array('class'  => 'areaChangeForm',
                               'method' => 'get',
                               'action' => 'index.php'));
                               
    $form->addHiddenInputs(array('view'      => $view,
                                 'page_date' => $page_date));
    
    $select = new ElementSelect();
    $select->setAttributes(array('class'      => 'room_area_select',
                                 'name'       => 'area',
                                 'aria-label' => get_vocab('select_area'),
                                 'onchange'   => 'this.form.submit()'))
           ->addSelectOptions($areas, $current, true);
    $form->addElement($select);
    
    // Note:  the submit button will not be displayed if JavaScript is enabled
    $submit = new ElementInputSubmit();
    $submit->setAttributes(array('class' => 'js_none',
                                 'value' => get_vocab('change')));
    $form->addElement($submit);
    
    $out_html .= $form->toHTML();
  }
  
  return $out_html;
} // end make_area_select_html


function make_room_select_html ($view, $year, $month, $day, $area, $current)
{
  $out_html = '';
  
  $rooms = get_room_names($area);
  
  if (count($rooms) > 0)
  {
    $page_date = format_iso_date($year, $month, $day);
    
    $form = new Form();
    
    $form->setAttributes(array('class'  => 'roomChangeForm',
                               'method' => 'get',
                               'action' => 'index.php'));
                               
    $form->addHiddenInputs(array('view'      => $view,
                                 'page_date' => $page_date,
                                 'area'      => $area));
    
    $select = new ElementSelect();
    $select->setAttributes(array('class'      => 'room_area_select',
                                 'name'       => 'room',
                                 'aria-label' => get_vocab('select_room'),
                                 'onchange'   => 'this.form.submit()'))
           ->addSelectOptions($rooms, $current, true);
    $form->addElement($select);
    
    // Note:  the submit button will not be displayed if JavaScript is enabled
    $submit = new ElementInputSubmit();
    $submit->setAttributes(array('class' => 'js_none',
                                 'value' => get_vocab('change')));
    $form->addElement($submit);
    
    $out_html .= $form->toHTML();
  }
  
  return $out_html;
} // end make_room_select_html



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
      while (is_hidden_day(date('w', $time)) && (abs($d - $day) < DAYS_PER_WEEK));  // break the loop if all days are hidden
      break;
    case 'week':
      $time = mktime(12, 0, 0, $month, $day + (($next) ? DAYS_PER_WEEK : -DAYS_PER_WEEK), $year);
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
  
  $vars = array('view'      => $view,
                'page_date' => format_iso_date($date['year'], $date['mon'], $date['mday']),
                'area'      => $area,
                'room'      => $room);
  
  return 'index.php?' . http_build_query($vars, '', '&');
}


// Gets the link for today
function get_today_link($view, $area, $room)
{
  $date = getdate();
  
  $vars = array('view'      => $view,
                'page_date' => format_iso_date($date['year'], $date['mon'], $date['mday']),
                'area'      => $area,
                'room'      => $room);
  
  return 'index.php?' . http_build_query($vars, '', '&');
}


function get_location_nav($view, $year, $month, $day, $area, $room)
{
  $html = '';
  
  $html .= "<nav class=\"location js_hidden\">\n";  // JavaScript will show it
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
  
  $html .= '<nav class="view">';
  $html .= '<div class="container">';  // helps the CSS
  
  $views = array('day' => 'nav_day',
                 'week' => 'nav_week',
                 'month' => 'nav_month');
  
  foreach ($views as $view => $token)
  {
    $vars = array('view'      => $view,
                  'page_date' => format_iso_date($year, $month, $day),
                  'area'      => $area,
                  'room'      => $room);
                  
    $query = http_build_query($vars, '', '&');
    $html .= '<a';
    $html .= ($view == $current_view) ? ' class="selected"' : '';
    $html .= ' href="index.php?' . htmlspecialchars($query) . '">' . htmlspecialchars(get_vocab($token)) . '</a>';
  }
  
  $html .= '</div>';
  $html .= '</nav>';
  
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

  $title_prev = htmlspecialchars($title_prev);
  $title_next = htmlspecialchars($title_next);

  $link_prev = get_adjacent_link($view, $year, $month, $day, $area, $room, false);
  $link_today = get_today_link($view, $area, $room);
  $link_next = get_adjacent_link($view, $year, $month, $day, $area, $room, true);
  
  $html .= "<nav class=\"arrow\">\n";
  $html .= "<a class=\"prev\" title=\"$title_prev\" aria-label=\"$title_prev\" href=\"" . htmlspecialchars($link_prev) . "\"></a>";  // Content will be filled in by CSS
  $html .= "<a href=\"" . htmlspecialchars($link_today) . "\">" . get_vocab('today') . "</a>";
  $html .= "<a class=\"next\" title=\"$title_next\" aria-label=\"$title_next\" href=\"" . htmlspecialchars($link_next) . "\"></a>";  // Content will be filled in by CSS
  $html .= "</nav>";
  
  return $html;
}


function get_calendar_nav($view, $year, $month, $day, $area, $room, $hidden=false)
{
  $html = '';
  
  $html .= "<nav class=\"main_calendar" .
           (($hidden) ? ' js_hidden' : '') .
           "\">\n";
  
  $html .= get_arrow_nav($view, $year, $month, $day, $area, $room);
  $html .= get_location_nav($view, $year, $month, $day, $area, $room);
  $html .= get_view_nav($view, $year, $month, $day, $area, $room);
  
  $html .= "</nav>\n";
  
  return $html;
}


function get_date_heading($view, $year, $month, $day)
{
  global $strftime_format, $display_timezone,
         $weekstarts, $mincals_week_numbers;
  
  $html = '';
  $time = mktime(12, 0, 0, $month, $day, $year);
  
  $html .= '<h2 class="date">';
  
  switch ($view)
  {
    case 'day':
      $html .= utf8_strftime($strftime_format['view_day'], $time);
      break;
      
    case 'week':
      // Display the week number if required, provided the week starts on Monday,
      // otherwise it's spanning two ISO weeks and doesn't make sense.
      if ($mincals_week_numbers && ($weekstarts == 1))
      {
        $html .= '<span class="week_number">' .
                 get_vocab('week_number', date('W', $time)) .
                 '</span>';
      }
      // Then display the actual dates
      $day_of_week = date('w', $time);
      $our_day_of_week = ($day_of_week + DAYS_PER_WEEK - $weekstarts) % DAYS_PER_WEEK;
      $start_of_week = mktime(12, 0, 0, $month, $day - $our_day_of_week, $year);
      $end_of_week = mktime(12, 0, 0, $month, $day + 6 - $our_day_of_week, $year);
      // We have to cater for three possible cases.  For example
      //    Years differ:                   26 Dec 2016 - 1 Jan 2017
      //    Years same, but months differ:  30 Jan - 5 Feb 2017
      //    Years and months the same:      6 - 12 Feb 2017
      if (date('Y', $start_of_week) != date('Y', $end_of_week))
      {
        $start_format = $strftime_format['view_week_start_y'];
      }
      elseif (date('m', $start_of_week) != date('m', $end_of_week))
      {
        $start_format = $strftime_format['view_week_start_m'];
      }
      else
      {
        $start_format = $strftime_format['view_week_start'];
      }
      $html .= utf8_strftime($start_format, $start_of_week) . '-' .
               utf8_strftime($strftime_format['view_week_end'], $end_of_week);
      break;
      
    case 'month':
      $html .= utf8_strftime($strftime_format['view_month'], $time);
      break;
      
    default:
      throw new \Exception("Unknown view '$view'");
      break;
  }
  
  $html .= '</h2>';
  
  if ($display_timezone)
  {
    $html .= '<span class="timezone">';
    $html .= get_vocab("timezone") . ": " . date('T', $time) . " (UTC" . date('O', $time) . ")";
    $html .= '</span>';
  }
  
  return $html;
}



// Get non-standard form variables
$refresh = get_form_var('refresh', 'int');
$timetohighlight = get_form_var('timetohighlight', 'int');

$is_ajax = is_ajax();

// Check the user is authorised for this page
if (!checkAuthorised(this_page(), $just_check = $is_ajax))
{
  exit;
}

switch ($view)
{
  case 'day':
    $inner_html = day_table_innerhtml($view, $year, $month, $day, $area, $room, $timetohighlight);
    break;
  case 'week':
    $inner_html = week_table_innerhtml($view, $year, $month, $day, $area, $room, $timetohighlight);
    break;
  case 'month':
    $inner_html = month_table_innerhtml($view, $year, $month, $day, $area, $room);
    break;
  default:
    throw new \Exception("Unknown view '$view'");
    break;
}


if ($refresh)
{
  echo $inner_html;
  exit;
}


// If we're using the 'db' authentication type, check to see if MRBS has just been installed
// and, if so, redirect to the edit_users page so that they can set up users.
if (($auth['type'] == 'db') && (count(authGetUsers()) == 0))
{
  header('Location: edit_users.php');
  exit;
}


// print the page header
print_header($view, $year, $month, $day, $area, isset($room) ? $room : null);

echo "<div class=\"minicalendars\">\n";
echo "</div>\n";

echo "<div class=\"view_container js_hidden\">\n";
echo get_date_heading($view, $year, $month, $day);
echo get_calendar_nav($view, $year, $month, $day, $area, $room);

$class = 'dwm_main';
if ($times_along_top)
{
  $class .= ' times-along-top';
}

echo "<div class=\"table_container\">\n";
echo "<table class=\"$class\" id=\"${view}_main\" data-resolution=\"$resolution\">\n";
echo $inner_html;
echo "</table>\n";
echo "</div>\n";

// The bottom navigation bar is controlled by JavaScript
echo get_calendar_nav($view, $year, $month, $day, $area, $room, true);

echo get_color_key();
echo "</div>\n";

print_footer();
