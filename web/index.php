<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Calendar\CalendarFactory;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementSelect;
use MRBS\Form\Form;
use MRBS\Intl\IntlDateFormatter;
use OpenPsa\Ranger\Ranger;

require "defaultincludes.inc";

// Display the entry-type color key.
function get_color_key() : string
{
  global $booking_types;

  $html = '';

  // No point in showing the color key if we aren't using entry types.  (Note:  count()
  // returns 0 if its parameter is not set).
  if (isset($booking_types) && (count($booking_types) > 1))
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
function make_area_select_html(string $view, int $year, int $month, int $day, int $current) : string
{
  global $multisite, $site;

  $out_html = '';

  $areas = new Areas();
  $options = $areas->getNames();

  // Only show the areas if there are more than one of them, otherwise
  // there's no point
  if (count($options) > 1)
  {
    $page_date = format_iso_date($year, $month, $day);

    $form = new Form();

    $form->setAttributes(array('class'  => 'areaChangeForm',
                               'action' => multisite(this_page())));

    $form->addHiddenInputs(array('view'      => $view,
                                 'page_date' => $page_date));

    if ($multisite && isset($site) && ($site !== ''))
    {
      $form->addHiddenInput('site', $site);
    }

    $select = new ElementSelect();
    $select->setAttributes(array('class'      => 'room_area_select',
                                 'name'       => 'area',
                                 'disabled'   => is_kiosk_mode(),
                                 'aria-label' => get_vocab('select_area'),
                                 'onchange'   => 'this.form.submit()'))
           ->addSelectOptions($options, $current, true);
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


function make_room_select_html (string $view, int $view_all, int $year, int $month, int $day, int $area, int $current) : string
{
  global $multisite, $site, $always_offer_view_all;

  $out_html = '';

  $rooms = new Rooms($area);
  $n_rooms = count($rooms);

  if ($n_rooms > 0)
  {
    $page_date = format_iso_date($year, $month, $day);
    $options = $rooms->getNames();

    // If we are in the week or month views and there is more than one room, then add the 'all'
    // option to the room select, which allows the user to display all rooms in the view.
    // And if we are viewing all the rooms then make sure the current room is negative.
    // (The room select uses a negative value of $room to signify that we want to view all
    // rooms in an area.   The absolute value of $room is the current room.)
    if (in_array($view, array('week', 'month')) && ($always_offer_view_all || ($n_rooms > 1)))
    {
      $all = -abs($current);
      if ($view_all)
      {
        $current = -abs($current);
      }
      $options = array($all => get_vocab('all')) + $options;
    }

    $form = new Form();

    $form->setAttributes(array('class'  => 'roomChangeForm',
                               'action' => multisite(this_page())));

    $form->addHiddenInputs(array('view'      => $view,
                                 'view_all'  => 0,
                                 'page_date' => $page_date,
                                 'area'      => $area));

    if ($multisite && isset($site) && ($site !== ''))
    {
      $form->addHiddenInput('site', $site);
    }

    $select = new ElementSelect();
    $select->setAttributes(array('class'      => 'room_area_select',
                                 'name'       => 'room',
                                 'disabled'   => is_kiosk_mode(),
                                 'aria-label' => get_vocab('select_room'),
                                 'onchange'   => 'this.form.submit()'))
           ->addSelectOptions($options, $current, true);
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
function get_adjacent_link(string $view, int $view_all, int $year, int $month, int $day, int $area, int $room, int $increment) : string
{
  $date = new DateTime();
  $date->setDate($year, $month, $day);

  switch ($view)
  {
    case 'day':
      $modifier = "$increment day";
      $date->modify($modifier);
      // find the next non-hidden day
      $i = 0;
      while ($date->isHiddenDay() && ($i < DAYS_PER_WEEK)) // break the loop if all days are hidden
      {
        $i++;
        $date->modify($modifier);
      }
      break;
    case 'week':
      $modifier = "$increment week";
      $date->modify($modifier);
      break;
    case 'month':
      $date->modifyMonthsNoOverflow($increment);
      break;
    default:
      throw new \Exception("Unknown view '$view'");
      break;
  }

  $vars = array('view'      => $view,
                'view_all'  => $view_all,
                'page_date' => $date->getISODate(),
                'area'      => $area,
                'room'      => $room);

  return multisite('index.php?' . http_build_query($vars, '', '&'));
}


// Gets the link for today
function get_today_link(string $view, int $view_all, int $area, int $room) : string
{
  // Don't include a date in order to make sure that the link will result in the current
  // day at the time it is clicked, not the time the page was loaded (which might have been
  // a few days ago.
  $vars = array('view'      => $view,
                'view_all'  => $view_all,
                'area'      => $area,
                'room'      => $room);

  return multisite('index.php?' . http_build_query($vars, '', '&'));
}


function get_location_nav(string $view, int $view_all, int $year, int $month, int $day, int $area, int $room) : string
{
  $html = '';

  $html .= "<nav class=\"location js_hidden\">\n";  // JavaScript will show it
  $html .= make_area_select_html($view, $year, $month, $day, $area);

  if ($view !== 'day')
  {
    $html .= make_room_select_html($view, $view_all, $year, $month, $day, $area, $room);
  }

  $html .= "</nav>\n";

  return $html;
}


function get_view_nav(string $current_view, int $view_all, int $year, int $month, int $day, int $area, int $room) : string
{
  $html = '';

  $html .= '<nav class="view">';
  $html .= '<div class="container">';  // helps the CSS

  $views = array('day' => 'nav_day',
                 'week' => 'nav_week',
                 'month' => 'nav_month');

  foreach ($views as $view => $token)
  {
    $this_view_all = $view_all ?? 1;

    $vars = array('view'      => $view,
                  'view_all'  => $this_view_all,
                  'page_date' => format_iso_date($year, $month, $day),
                  'area'      => $area,
                  'room'      => $room);

    $query = http_build_query($vars, '', '&');
    $href = multisite("index.php?$query");
    $html .= '<a';
    $html .= ($view == $current_view) ? ' class="selected"' : '';
    $html .= ' href="' . escape_html($href) . '">' . escape_html(get_vocab($token)) . '</a>';
  }

  $html .= '</div>';
  $html .= '</nav>';

  return $html;
}


function get_arrow_nav(string $view, int $view_all, int $year, int $month, int $day, int $area, int $room) : string
{
  $html = '';

  switch ($view)
  {
    case 'day':
      $title_prev = get_vocab('daybefore');
      $title_this = get_vocab('gototoday');
      $title_next = get_vocab('dayafter');
      $title_prev_week = get_vocab('weekbefore');
      $title_next_week = get_vocab('weekafter');
      $link_prev_week = get_adjacent_link($view, $view_all, $year, $month, $day, $area, $room, -7);
      $link_next_week = get_adjacent_link($view, $view_all, $year, $month, $day, $area, $room, +7);
      break;
    case 'week':
      $title_prev = get_vocab('weekbefore');
      $title_this = get_vocab('gotothisweek');
      $title_next = get_vocab('weekafter');
      break;
    case 'month':
      $title_prev = get_vocab('monthbefore');
      $title_this = get_vocab('gotothismonth');
      $title_next = get_vocab('monthafter');
      break;
    default:
      throw new \Exception("Unknown view '$view'");
      break;
  }

  $title_prev = escape_html($title_prev);
  $title_next = escape_html($title_next);

  $link_prev = get_adjacent_link($view, $view_all, $year, $month, $day, $area, $room, -1);
  $link_today = get_today_link($view, $view_all, $area, $room);
  $link_next = get_adjacent_link($view, $view_all, $year, $month, $day, $area, $room, 1);

  // For the day view we also offer buttons to go back/forward by one week.
  // The links without any text have their content filled by CSS.
  $html .= "<nav class=\"arrow\">\n";
  if ($view == 'day')
  {
    $html .= "<a class=\"prev week symbol prefetch\" title=\"$title_prev_week\" aria-label=\"$title_prev_week\" href=\"" . escape_html($link_prev_week) . "\"></a>";
  }
  $html .= "<a class=\"prev symbol prefetch\" title=\"$title_prev\" aria-label=\"$title_prev\" href=\"" . escape_html($link_prev) . "\"></a>";
  $html .= "<a title= \"$title_this\" aria-label=\"$title_this\" href=\"" . escape_html($link_today) . "\">" . get_vocab('today') . "</a>";
  $html .= "<a class=\"next symbol prefetch\" title=\"$title_next\" aria-label=\"$title_next\" href=\"" . escape_html($link_next) . "\"></a>";
  if ($view == 'day')
  {
    $html .= "<a class=\"next week symbol prefetch\" title=\"$title_next_week\" aria-label=\"$title_next_week\" href=\"" . escape_html($link_next_week) . "\"></a>";
  }
  $html .= "</nav>";

  return $html;
}


function get_calendar_nav(string $view, int $view_all, int $year, int $month, int $day, int $area, int $room, bool $hidden=false) : string
{
  $html = '';

  $html .= "<nav class=\"main_calendar" .
           (($hidden) ? ' js_hidden' : '') .
           "\">\n";

  $html .= get_arrow_nav($view, $view_all, $year, $month, $day, $area, $room);
  $html .= get_location_nav($view, $view_all, $year, $month, $day, $area, $room);
  $html .= get_view_nav($view, $view_all, $year, $month, $day, $area, $room);

  $html .= "</nav>\n";

  return $html;
}


function get_date_heading(string $view, int $year, int $month, int $day) : string
{
  global $datetime_formats, $display_timezone, $timezone,
         $weekstarts, $view_week_number;

  $html = '';
  $time = mktime(12, 0, 0, $month, $day, $year);

  $html .= '<h2 class="date">';

  switch ($view)
  {
    case 'day':
      $html .= datetime_format($datetime_formats['view_day'], $time);
      break;

    case 'week':
      // Display the week number if required, provided the MRBS week starts on the first day
      // of the week, otherwise it's spanning two weeks and doesn't make sense.
      if ($view_week_number && ($weekstarts == DateTime::firstDayOfWeek($timezone, get_mrbs_locale())))
      {
        $html .= '<span class="week_number">' .
                 get_vocab('week_number', datetime_format($datetime_formats['week_number'], $time)) .
                 '</span>';
      }
      // Then display the actual dates
      $day_of_week = date('w', $time);
      $our_day_of_week = ($day_of_week + DAYS_PER_WEEK - $weekstarts) % DAYS_PER_WEEK;
      $ranger = new Ranger(get_mrbs_locale());
      $ranger
        ->setRangeSeparator(get_vocab('range_separator'))
        ->setDateType($datetime_formats['view_week']['date_type'] ?? IntlDateFormatter::LONG)
        ->setTimeType($datetime_formats['view_week']['time_type'] ?? IntlDateFormatter::NONE);
      $range = $ranger->format(
        format_iso_date($year, $month, $day - $our_day_of_week),
        format_iso_date($year, $month, $day +6 - $our_day_of_week)
      );
      $html .= $range;
      break;

    case 'month':
      $html .= datetime_format($datetime_formats['view_month'], $time);
      break;

    default:
      throw new \Exception("Unknown view '$view'");
      break;
  }

  $html .= '</h2>';

  if ($display_timezone)
  {
    $html .= '<span class="timezone">';
    $html .= get_vocab("timezone") . ": " . datetime_format($datetime_formats['timezone'], $time);
    $html .= '</span>';
  }

  return $html;
}


function message_html() : string
{
  $message = Message::getInstance();
  $message->load();

  if ($message->hasSomethingToDisplay())
  {
    return '<p class="message_top">' . $message->getEscapedText() . "</p>\n";
  }

  return '';
}

// Get non-standard form variables
$refresh = get_form_var('refresh', 'int');
$timetohighlight = get_form_var('timetohighlight', 'int');

// The room select uses a negative value of $room to signify that we want to view all
// rooms in an area.   The absolute value of $room is the current room.
if ($room < 0)
{
  $room = abs($room);
  $view_all = 1;
}

// We only support the day view in kiosk mode at the moment
if (isset($kiosk))
{
  $view = 'day';
}

$is_ajax = is_ajax();

// If we're using an authentication type that supports the creation of users, eg 'db'
// or an extension of that type, then check to see if MRBS has just been installed
// and, if so, redirect to the edit_users page so that they can set up users.
if (auth()->canCreateUsers())
{
  $users = new Users();
  if (count($users) == 0)
  {
    location_header('edit_user.php');
  }
}

// Check the user is authorised for this page
if (!checkAuthorised(this_page(), $refresh))
{
  exit;
}

$calendar = CalendarFactory::create($view, $view_all, $year, $month, $day, $area, $room, $timetohighlight, $kiosk);
$inner_html = $calendar->innerHTML();
$date_heading = get_date_heading($view, $year, $month, $day);

if ($refresh)
{
  echo json_encode(array(
    'date_heading' => $date_heading,
    'inner_html' => $inner_html
  ));
  exit;
}

// print the page header
$context = array(
    'view'      => $view,
    'view_all'  => $view_all,
    'year'      => $year,
    'month'     => $month,
    'day'       => $day,
    'area'      => $area,
    'room'      => $room ?? null,
    'kiosk'     => $kiosk ?? null
  );

print_header($context);

echo "<div class=\"minicalendars\">\n";
echo "</div>\n";

echo "<div class=\"view_container js_hidden\">\n";
echo "<div class=\"date_heading\">$date_heading</div>";
echo get_calendar_nav($view, $view_all, $year, $month, $day, $area, $room);

echo message_html();

$classes = array('dwm_main');
if ($times_along_top)
{
  $classes[] .= 'times-along-top';
}
if ($view_all && ($view !== 'day'))
{
  $classes[] = 'all_rooms';
}

echo "<div class=\"table_container\">\n";
echo '<table class="' . implode(' ', $classes) . "\" id=\"{$view}_main\" data-resolution=\"$resolution\">\n";
echo $inner_html;
echo "</table>\n";
echo "</div>\n";

// The bottom navigation bar is controlled by JavaScript
echo get_calendar_nav($view, $view_all, $year, $month, $day, $area, $room, true);

echo get_color_key();
echo "</div>\n";

print_footer();
