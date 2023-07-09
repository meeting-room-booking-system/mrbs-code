<?php
namespace MRBS;

function get_date_classes(DateTime $date) : array
{
  $result = array();

  if ($date->isWeekend())
  {
    $result[] = 'weekend';
  }
  if ($date->isHoliday())
  {
    $result[] = 'holiday';
  }

  return $result;
}


// Prepares an entry for display by (a) adding in registration level information
// and (b) replacing the text in private fields if necessary.
function prepare_entry(array $entry) : array
{
  global $is_private_field, $show_registration_level, $auth, $kiosk;

  // Add in the registration level details
  if ($show_registration_level && $entry['allow_registration'])
  {
    // Check whether we should be showing the registrants' names
    $show_names = ($auth['show_registrant_names_in_calendar'] && ($entry['n_registered'] > 0));
    if ($show_names && !$auth['show_registrant_names_in_public_calendar'])
    {
      // If we're not allowed to show names in the public calendar, check that the user is logged in
      // and has an access level of at least 1
      $mrbs_user = session()->getCurrentUser();
      $show_names = isset($mrbs_user) && ($mrbs_user->level > 0);
    }
    $names = ($show_names) ? implode(', ', auth()->getRegistrantsDisplayNames($entry)) : '';
    if ($entry['registrant_limit_enabled'])
    {
      $tag = ($show_names) ? 'registration_level_limited_with_names' : 'registration_level_limited';
      $entry['name'] .= get_vocab($tag, $entry['n_registered'], $entry['registrant_limit'], $names);
    }
    else
    {
      $tag = ($show_names) ? 'registration_level_unlimited_with_names' : 'registration_level_unlimited';
      $entry['name'] .= get_vocab($tag, $entry['n_registered'], $names);
    }
  }

  // Check whether the event is private
  if (is_private_event($entry['private']) &&
      ($kiosk || !getWritable($entry['create_by'], $entry['room_id'])))
  {
    $entry['private'] = true;

    foreach (array('name', 'description') as $key)
    {
      if ($is_private_field["entry.$key"])
      {
        $entry[$key] = '[' . get_vocab('unavailable') . ']';
      }
    }

    if (!empty($is_private_field['entry.type']))
    {
      $entry['type'] = 'private_type';
    }
  }
  else
  {
    $entry['private'] = false;
  }

  return $entry;
}


// Returns an array of classes to be used for the entry
function get_entry_classes(array $entry) : array
{
  global $approval_enabled, $confirmation_enabled;

  $classes = array($entry['type']);

  if ($entry['private'])
  {
    $classes[] = 'private';
  }

  if ($approval_enabled && ($entry['awaiting_approval']))
  {
    $classes[] = 'awaiting_approval';
  }

  if ($confirmation_enabled && ($entry['tentative']))
  {
    $classes[] = 'tentative';
  }

  if (isset($entry['repeat_id']))
  {
    $classes[] = 'series';
  }

  if ($entry['allow_registration'])
  {
    if ($entry['registrant_limit_enabled'] &&
        ($entry['n_registered'] >= $entry['registrant_limit']))
    {
      $classes[] = 'full';
    }
    else
    {
      $classes[] = 'spaces';
    }
  }

  return $classes;
}


function cell_html(array $cell, array $query_vars, bool $is_invalid = false) : string
{
  // draws a single cell in the main table of the day and week views
  //
  // $cell is an array of entries that occupy that cell.  There can be none, one or many
  // bookings in a cell.  If there are no bookings then a blank cell is drawn with a link
  // to the edit entry form.     If there is one booking, then the booking is shown in that
  // cell.    If there is more than one booking then all the bookings are shown.

  // $query_vars is an array containing the query vars to be used in the link for the cell.
  // It is indexed as follows:
  //    ['new_periods']   the vars to be used for an empty cell if using periods
  //    ['new_times']     the vars to be used for an empty cell if using times
  //    ['booking']       the vars to be used for a full cell
  //
  // $is_invalid specifies whether the slot actually exists or is one of the non-existent
  // slots in the transition to DST

  global $enable_periods, $show_plus_link, $prevent_booking_on_holidays, $prevent_booking_on_weekends;

  $html = '';
  $classes = array();

  // Don't put in a <td> cell if the slot is contains a single booking whose n_slots is NULL.
  // This would mean that it's the second or subsequent slot of a booking and so the
  // <td> for the first slot would have had a rowspan that extended the cell down for
  // the number of slots of the booking.

  if (empty($cell) || !is_null($cell[0]['n_slots']))
  {
    if (!empty($cell))
    {
      $classes[] = 'booked';
      if (count($cell) > 1)
      {
        $classes[] = 'multiply';
      }
    }
    elseif ($is_invalid)
    {
      $classes[] = 'invalid';
    }
    else
    {
      $classes[] = 'new';
      // Add classes for weekends and holidays
      $date = new DateTime();
      $date->setDate(
        $query_vars['new_times']['year'],
        $query_vars['new_times']['month'],
        $query_vars['new_times']['day']
      );
      $classes = array_merge($classes, get_date_classes($date));
    }

    // If there's no booking or if there are multiple bookings then make the slot one unit long
    $slots = (count($cell) == 1) ? $cell[0]['n_slots'] : 1;

    $html .= tdcell($classes, $slots);

    // If the room isn't booked then allow it to be booked
    if (empty($cell))
    {
      // Don't provide a link if the slot doesn't really exist or if the user is logged in, but not a booking admin,
      // and it's a holiday/weekend and bookings on holidays/weekends are not allowed.  (We provide a link if they
      // are not logged in because they might want to click and login as an admin).
      if ($is_invalid ||
          ((null !== session()->getCurrentUser()) && !is_book_admin($query_vars['new_times']['room']) &&
            (($prevent_booking_on_holidays && in_array('holiday', $classes)) ||
             ($prevent_booking_on_weekends && in_array('weekend', $classes)))))
      {
        $html .= '<span class="not_allowed"></span>';
      }
      else
      {
        $vars = ($enable_periods) ? $query_vars['new_periods'] : $query_vars['new_times'];
        $query = http_build_query($vars, '', '&');

        $html .= '<a href="' . htmlspecialchars(multisite("edit_entry.php?$query")) . '"' .
                 ' aria-label="' . htmlspecialchars(get_vocab('create_new_booking')) . "\">";
        if ($show_plus_link)
        {
          $html .= "<img src=\"images/new.gif\" alt=\"New\" width=\"10\" height=\"10\">";
        }
        $html .= "</a>";
      }
    }
    else                 // if it is booked then show the booking
    {
      foreach ($cell as $booking)
      {
        $vars = $query_vars['booking'];
        $vars['id'] = $booking['id'];
        $query = http_build_query($vars, '', '&');

        // We have to wrap the booking in a <div> because we want the booking itself to be given
        // an absolute position and we can't use position relative on a <td> in IE11 and below.
        // We also need the bookings in a container because jQuery UI resizable has problems
        // with border-box (see https://stackoverflow.com/questions/18344272). And we need
        // border-box for the bookings because we are using padding on the bookings and we want
        // 'width: 100%' and 'height: 100%' to fill the table-cell with the entire booking
        // including content.

        $classes = get_entry_classes($booking);
        $classes[] = 'booking';

        if ($booking['is_multiday_start'])
        {
          $classes[] = 'multiday_start';
        }

        if ($booking['is_multiday_end'])
        {
          $classes[] = 'multiday_end';
        }

        // Tell JavaScript to make bookings resizable
        if ((count($cell) == 1) &&
            getWritable($booking['create_by'], $booking['room_id']))
        {
          $classes[] = 'writable';
        }

        $html .= '<div class="' . implode(' ', $classes) . '">';
        $html .= '<a href="' . htmlspecialchars(multisite("view_entry.php?$query")) . '"' .
                   ' title="' . htmlspecialchars($booking['description'] ?? '') . '"' .
                   ' class="' . $booking['type'] . '"' .
                   ' data-id="' . $booking['id'] . '"' .
                   ' data-type="' . $booking['type'] . '">';
        $html .= htmlspecialchars($booking['name']) . '</a>';
        $html .= "</div>";
      }
    }

    $html .= "</td>\n";
  }

  return $html;
}  // end function draw_cell


function get_timeslot_text(int $s, int $resolution) : string
{
  global $show_slot_endtime;

  $result = hour_min($s);

  if ($show_slot_endtime)
  {
    $result .= '-' . hour_min($s + $resolution);
  }

  return $result;
}

// Draw a time cell to be used in the first and last columns of the day and week views
//    $s                 the number of seconds since the start of the day (nominal - not adjusted for DST)
//    $url               the url to form the basis of the link in the time cell
function time_cell_html(int $s, string $url) : string
{
  global $enable_periods, $resolution;

  $html = '';

  $html .= "<th data-seconds=\"$s\">";
  $html .= '<a href="' . htmlspecialchars($url) . '"  title="' . get_vocab("highlight_line") . "\">";

  if ($enable_periods)
  {
    $html .= htmlspecialchars(period_name_nominal($s));
  }
  else
  {
    $html .= htmlspecialchars(get_timeslot_text($s, $resolution));
  }

  $html .= "</a></th>\n";

  return $html;
}

// Draw a room cell to be used in the header rows/columns of the calendar views
//    $room    contains the room details
//    $vars    an associative array containing the variables to be used to build the link
function room_cell_html(array $room, array $vars) : string
{
  $link = 'index.php?' . http_build_query($vars, '', '&');
  $link = multisite($link);

  switch ($vars['view'])
  {
    case 'day':
      $tag = 'viewday';
      break;
    case 'week':
      $tag = 'viewweek';
      break;
    case 'month':
      $tag = 'viewmonth';
      break;
    default:
      trigger_error("Unknown view '" . $vars['view'] . "'", E_USER_NOTICE);
      $tag = 'viewweek';
      break;
  }

  $title = get_vocab($tag) . "\n\n" . $room['description'];
  $html = '';
  $html .= '<th data-room="' . htmlspecialchars($room['id']) . '">';
  $html .= '<a href="' . htmlspecialchars($link) . '"' .
           ' title = "' . htmlspecialchars($title) . '">';
  $html .= htmlspecialchars($room['room_name']);
  // Put the capacity in a span to give flexibility in styling
  $html .= '<span class="capacity';
  if ($room['capacity'] == 0)
  {
    $html .= ' zero';
  }
  $html .= '">' . htmlspecialchars($room['capacity']);
  $html .= '</span>';
  $html .= '</a>';
  $html .= "</th>\n";
  return $html;
}

// Draw a day cell to be used in the header rows/columns of the week view
//    $text     contains the date, formatted as a string (not escaped - allowed to contain HTML tags)
//    $link     the href to be used for the link
//    $date     the date in yyyy-mm-dd format
function day_cell_html(string $text, string $link, string $iso_date) : string
{
  $html = '';
  // Put the date into a data attribute so that it can be picked up by JavaScript
  $html .= '<th data-date="' . htmlspecialchars($iso_date) . '"';

  // Add classes for weekends and holidays
  $classes = get_date_classes(new DateTime($iso_date));
  if (!empty($classes))
  {
    $html .= ' class="' . implode(' ', $classes) . '"';
  }

  $html .= '>';
  $html .= '<a href="' . htmlspecialchars($link) . '"' .
           ' title="' . htmlspecialchars(get_vocab("viewday")) . '">';
  $html .= $text;  // allowed to contain HTML tags - do not escape
  $html .= '</a>';
  $html .= "</th>\n";
  return $html;
}


// Output a start table cell tag <td> with class of $classes.
// $classes can be either a string or an array of classes
// empty or row_highlight if highlighted.
// $slots is the number of time slots high that the cell should be
//
// $data is an optional third parameter which if set passes an
// associative array of name-value pairs to be used in data attributes
function tdcell(array $classes, int $slots, ?array $data=null) : string
{
  global $times_along_top;

  $html = '';
  $html .= '<td';

  if (!empty($classes))
  {
    $html.= ' class="' . implode(' ', $classes) . '"';
  }

  if ($slots > 1)
  // No need to output more HTML than necessary
  {
    $html .= (($times_along_top) ? ' colspan' : ' rowspan') . "=\"$slots\"";
  }

  if (isset($data))
  {
    foreach ($data as $name => $value)
    {
      $html .= " data-$name=\"$value\"";
    }
  }

  $html .= ">";

  return $html;
}


// Gets the number of time slots between the beginning and end of the booking
// day.   (This is the normal number on a non-DST transition day)
function get_n_time_slots() : int
{
  global $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes;
  global $resolution;

  $start_first = (($morningstarts * 60) + $morningstarts_minutes) * 60;           // seconds
  $end_last = ((($eveningends * 60) + $eveningends_minutes) * 60) + $resolution;  // seconds
  $end_last = $end_last % SECONDS_PER_DAY;
  if (day_past_midnight())
  {
    $end_last += SECONDS_PER_DAY;
  }

  // Force the result to be an int.  It normally will be, but might not be if, say,
  // $force_resolution is set.
  return intval(($end_last - $start_first)/$resolution);
}


// $s is nominal seconds
function get_query_vars(string $view, int $area, int $room, int $month, int $day, int $year, int $s) : array
{
  global $morningstarts, $morningstarts_minutes;

  $result = array();

  // check to see if the time is really on the next day
  $date = getdate(mktime(0, 0, $s, $month, $day, $year));
  if (hm_before($date,
                array('hours' => $morningstarts, 'minutes' => $morningstarts_minutes)))
  {
    $date['hours'] += 24;
  }
  $hour = $date['hours'];
  $minute = $date['minutes'];
  $period = period_index_nominal($s);

  $vars = array('view'  => $view,
                'year'  => $year,
                'month' => $month,
                'day'   => $day,
                'area'  => $area);

  $result['booking']     = $vars;
  $result['new_periods'] = array_merge($vars, array('room' => $room, 'period' => $period));
  $result['new_times']   = array_merge($vars, array('room' => $room, 'hour' => $hour, 'minute' => $minute));

  return $result;
}


function get_times_header_cells(int $start, int $end, int $increment) : string
{
  global $enable_periods;

  $html = '';

  for ($s = $start; $s <= $end; $s += $increment)
  {
    // Put the number of seconds since the start of the day (nominal, ignoring DST)
    // in a data attribute so that JavaScript can pick it up
    $html .= "<th data-seconds=\"$s\">";
    // We need the span so that we can apply some padding.   We can't apply it
    // to the <th> because that is used by jQuery.offset() in resizable bookings
    $html .= "<span>";
    if ( $enable_periods )
    {
      $html .= htmlspecialchars(period_name_nominal($s));
    }
    else
    {
      $html .= htmlspecialchars(get_timeslot_text($s, $increment));
    }
    $html .= "</span>";
    $html .= "</th>\n";
  }

  return $html;
}


function get_rooms_header_cells(array $rooms, array $vars) : string
{
  $html = '';

  foreach($rooms as $room)
  {
    $vars['room'] = $room['id'];
    $html .= room_cell_html($room, $vars);
  }

  return $html;
}


function day_table_innerhtml(string $view, int $year, int $month, int $day, int $area_id, int $room_id, ?int $timetohighlight=null, ?string $kiosk=null) : string
{
  global $enable_periods;
  global $times_along_top, $row_labels_both_sides, $column_labels_both_ends;
  global $resolution, $morningstarts, $morningstarts_minutes;

  if ($kiosk === 'room')
  {
    $rooms = array(get_room_details($room_id));
  }
  else
  {
    $rooms = get_rooms($area_id);
  }

  $n_rooms = count($rooms);

  if ($n_rooms == 0)
  {
    // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
    return "<tbody data-empty=1><tr><td><h1>" . get_vocab("no_rooms_for_area") . "</h1></td></tr></tbody>";
  }

  $start_first_slot = get_start_first_slot($month, $day, $year);
  $start_last_slot = get_start_last_slot($month, $day, $year);
  // Keep a count of the number of slots at the start of the day that we're
  // not showing (will only be relevant in kiosk mode).
  $skipped_slots = 0;

  // If we are in kiosk mode we are not interested in what has already happened.
  // But if we are in periods mode we don't know when the periods occur, so show them all.
  if (isset($kiosk) && !$enable_periods)
  {
    $now = time();
    $start_next_slot = $start_first_slot + $resolution;
    while (($now > $start_next_slot) && ($start_next_slot < $start_last_slot))
    {
      $start_first_slot = $start_next_slot;
      $skipped_slots++;
      $start_next_slot = $start_first_slot + $resolution;
    }
  }

  // Work out whether there's a possibility that a time slot is invalid,
  // in other words whether the booking day includes a transition into DST.
  // If we know that there's a transition into DST then some of the slots are
  // going to be invalid.   Knowing whether or not there are possibly invalid slots
  // saves us bothering to do the detailed calculations of which slots are invalid.
  $is_possibly_invalid = !$enable_periods && is_possibly_invalid($start_first_slot, $start_last_slot);

  $entries = get_entries_by_area($area_id, $start_first_slot, $start_last_slot + $resolution);

  // We want to build a map containing all the data we want to show
  // and then spit it out.
  $map = new Map($resolution);
  foreach ($entries as $entry)
  {
    $entry = prepare_entry($entry);
    $map->add($entry, $day, $start_first_slot, $start_last_slot);
  }

  $n_time_slots = get_n_time_slots() - $skipped_slots;
  $morning_slot_seconds = ((($morningstarts * 60) + $morningstarts_minutes) * 60) + ($skipped_slots * $resolution);
  $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

  // TABLE HEADER
  $thead = '<thead';

  $slots = get_slots($month, $day, $year);
  if (isset($slots))
  {
    // Remove the skipped slots from the start of the first day's array
    for ($i=0; $i<$skipped_slots; $i++)
    {
      array_shift($slots[0]);
    }
    // Add some data to enable the JavaScript to draw the timeline
    $thead .= ' data-slots="' . htmlspecialchars(json_encode($slots)) . '"';
    $thead .= ' data-timeline-vertical="' . (($times_along_top) ? 'true' : 'false') . '"';
    $thead .= ' data-timeline-full="true"';
  }

  $thead .= ">\n";

  $header_inner = "<tr>\n";

  if ($times_along_top)
  {
    $tag = 'room';
  }
  elseif ($enable_periods)
  {
    $tag = 'period';
  }
  else
  {
    $tag = 'time';
  }

  $first_last_html = '<th class="first_last">' . get_vocab($tag) . "</th>\n";
  $header_inner .= $first_last_html;

  // We can display the table in two ways
  if ($times_along_top)
  {
    $header_inner .= get_times_header_cells($morning_slot_seconds, $evening_slot_seconds, $resolution);
  }
  else
  {
    $vars = array('view'     => 'week',
                  'view_all' => 0,
                  'year'     => $year,
                  'month'    => $month,
                  'day'      => $day,
                  'area'     => $area_id);

    $header_inner .= get_rooms_header_cells($rooms, $vars);
  }  // end standard view (for the header)

  // next: line to display times on right side
  if ($row_labels_both_sides)
  {
    $header_inner .= $first_last_html;
  }

  $header_inner .= "</tr>\n";
  $thead .= $header_inner;
  $thead .= "</thead>\n";

  // Now repeat the header in a footer if required
  $tfoot = ($column_labels_both_ends) ? "<tfoot>\n$header_inner</tfoot>\n" : '';

  // TABLE BODY LISTING BOOKINGS
  $tbody = "<tbody>\n";

  // This is the main bit of the display
  // We loop through time and then the rooms we just got

  // if the today is a day which includes a DST change then use
  // the day after to generate timesteps through the day as this
  // will ensure a constant time step

  // We can display the table in two ways
  if ($times_along_top)
  {
    // with times along the top and rooms down the side
    foreach ($rooms as $room)
    {
      $tbody .= "<tr>\n";

      $vars = array('view'     => 'week',
                    'view_all' => 0,
                    'year'     => $year,
                    'month'    => $month,
                    'day'      => $day,
                    'area'     => $area_id,
                    'room'     => $room['id']);

      $row_label = room_cell_html($room, $vars);
      $tbody .= $row_label;
      $is_invalid = array();
      for ($s = $morning_slot_seconds;
           $s <= $evening_slot_seconds;
           $s += $resolution)
      {
        // Work out whether this timeslot is invalid and save the result, so that we
        // don't have to repeat the calculation for every room
        if (!isset($is_invalid[$s]))
        {
          $is_invalid[$s] = $is_possibly_invalid && is_invalid_datetime(0, 0, $s, $month, $day, $year);
        }
        // set up the query vars to be used for the link in the cell
        $query_vars = get_query_vars($view, $area_id, $room['id'], $month, $day, $year, $s);

        // and then draw the cell
        $tbody .= cell_html($map->slot($room['id'], $day, $s), $query_vars, $is_invalid[$s]);
      }  // end for (looping through the times)
      if ($row_labels_both_sides)
      {
        $tbody .= $row_label;
      }
      $tbody .= "</tr>\n";
    }  // end for (looping through the rooms)
  }  // end "times_along_top" view (for the body)

  else
  {
    // the standard view, with rooms along the top and times down the side
    for ($s = $morning_slot_seconds;
         $s <= $evening_slot_seconds;
         $s += $resolution)
    {
      // Show the time linked to the URL for highlighting that time
      $classes = array();

      $vars = array(
          'view' => 'day',
          'year' => $year,
          'month' => $month,
          'day' => $day,
          'area' => $area_id
        );

      if (isset($room_id))
      {
        $vars['room'] = $room_id;
      }

      if (isset($timetohighlight) && ($s == $timetohighlight))
      {
        $classes[] = 'row_highlight';
      }
      else
      {
        $vars['timetohighlight'] = $s;
      }

      $url = "index.php?" . http_build_query($vars, '', '&');
      $url = multisite($url);

      $tbody .= '<tr';
      if (!empty($classes))
      {
        $tbody .= ' class="' . implode(' ', $classes) . '"';
      }
      $tbody .= ">\n";

      $tbody .= time_cell_html($s, $url);
      $is_invalid = $is_possibly_invalid && is_invalid_datetime(0, 0, $s, $month, $day, $year);
      // Loop through the list of rooms we have for this area
      foreach ($rooms as $room)
      {
        // set up the query vars to be used for the link in the cell
        $query_vars = get_query_vars($view, $area_id, $room['id'], $month, $day, $year, $s);
        $tbody .= cell_html($map->slot($room['id'], $day, $s), $query_vars, $is_invalid);
      }

      // next lines to display times on right side
      if ($row_labels_both_sides)
      {
        $tbody .= time_cell_html($s, $url);
      }

      $tbody .= "</tr>\n";
    }
  }  // end standard view (for the body)

  $tbody .= "</tbody>\n";

  return $thead . $tfoot . $tbody;
}


// Returns the HTML for a booking, or a free set of slots
//    $slots    The number of slots occupied
//    $classes  A scalar or array giving the class or classes to be used in the class attribute
//    $title    The value of the title attribute
//    $text     The value of the text to be used in the div
function get_flex_div(int $slots, $classes, string $title=null, string $text=null) : string
{
  $result = "<div style=\"flex: $slots\"";

  if (isset($classes))
  {
    $value = (is_array($classes)) ? implode(' ', $classes) : $classes;
    $result .= ' class="' . htmlspecialchars($value) . '"';
  }

  if (isset($title) && ($title !== ''))
  {
    $result .= ' title="' . htmlspecialchars($title) . '"';
  }

  $result .= '>';

  if (isset($text) && ($text !== ''))
  {
    $result .= htmlspecialchars($text);
  }

  $result .= '</div>';

  return $result;
}


function week_table_innerhtml(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id, ?int $timetohighlight) : string
{
  if ($view_all)
  {
    return multiday_view_all_rooms_innerhtml($view, $view_all, $year, $month, $day, $area_id, $room_id);
  }
  else
  {
    return week_room_table_innerhtml($view, $view_all, $year, $month, $day, $area_id, $room_id, $timetohighlight);
  }
}


function get_date(int $t, string $view) : string
{
  global $datetime_formats;

  if ($view == 'month')
  {
    return datetime_format(['pattern' => 'd'], $t);
  }
  else
  {
    return datetime_format($datetime_formats['view_week_day_month'], $t);
  }
}


function get_day(int $t, string $view) : string
{
  // In the month view use a pattern which will tend to give a narrower result, to save space.
  $pattern = ($view == 'month') ? 'cccccc' : 'ccc';

  return datetime_format(['pattern' => $pattern], $t);
}


function multiday_header_rows(
  string $view,
  int $view_all,
  int $year,
  int $month,
  int $day_start_interval,
  int $area_id,
  int $room_id,
  int $n_days,
  int $start_dow,
  string $label=''
) : array
{
  global $row_labels_both_sides;

  $result = array();
  $n_rows = 2;
  // Loop through twice: one row for the days of the week, the next for the date.
  for ($i = 0; $i < $n_rows; $i++)
  {
    $result[$i] = "<tr>\n";

    // Could use a rowspan here, but we'd need to make sure the sticky cells work
    // and change the JavaScript in refresh.js.php
    $text = ($i == 0) ? '' : $label;
    $first_last_html = '<th class="first_last">' . htmlspecialchars($text) . "</th>\n";
    $result[$i] .= $first_last_html;

    $vars = array(
      'view' => 'day',
      'view_all' => $view_all,
      'area' => $area_id,
      'room' => $room_id
    );

    // the standard view, with days along the top and rooms down the side
    for ($j = 0; $j < $n_days; $j++)
    {
      if (is_hidden_day(($j + $start_dow) % DAYS_PER_WEEK))
      {
        continue;
      }
      $vars['page_date'] = format_iso_date($year, $month, $day_start_interval + $j);
      $link = "index.php?" . http_build_query($vars, '', '&');
      $link = multisite($link);
      $t = mktime(12, 0, 0, $month, $day_start_interval + $j, $year);
      $text = ($i === 0) ? get_day($t, $view) : get_date($t, $view);
      $date = new DateTime();
      $date->setTimestamp($t);
      $classes = get_date_classes($date);
      $result[$i] .= '<th' .
        ((!empty($classes)) ? ' class="' . implode(' ', $classes) . '"' : '') .
        '><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($text) . "</a></th>\n";
    }

    // next line to display rooms on right side
    if ($row_labels_both_sides)
    {
      $result[$i] .= $first_last_html;
    }

    $result[$i] .= "</tr>\n";
  }

  return $result;
}


function multiday_view_all_rooms_innerhtml(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id) : string
{
  global $row_labels_both_sides, $column_labels_both_ends;
  global $weekstarts;
  global $resolution, $morningstarts, $morningstarts_minutes;

  // It's theoretically possible to display a transposed table with rooms along the top and days
  // down the side.  However it doesn't seem a very useful display and so hasn't yet been implemented.
  // The problem is that the results don't look good whether you have the flex direction as 'row' or
  // 'column'.  If you set it to 'row' the bookings are read from left to right within a day, but from
  // top to bottom within the interval (week/month), so you have to read the display by snaking down
  // the columns, which is potentially confusing.  If you set it to 'column' then the bookings are in
  // order reading straight down the column, but the text within the bookings is usually clipped unless
  // the booking lasts the whole day.  When the days are along the top and the text is clipped you can
  // at least see the first few characters which is useful, but when the days are down the side you only
  // see the top of the line.
  //
  // As a result $days_along_top is always true, but is here so that there can be stubs in the code in
  // case people want a transposed view in future.
  $days_along_top = true;

  $rooms = get_rooms($area_id);
  $n_rooms = count($rooms);

  // Check to see whether there are any rooms in the area
  if ($n_rooms == 0)
  {
    // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
    return "<tbody data-empty=1><tr><td><h1>" . get_vocab("no_rooms_for_area") . "</h1></td></tr></tbody>";
  }

  // Calculate/get:
  //    the first day of the interval
  //    how many days there are in it
  //    the day of the week of the first day in the interval
  $time = mktime(12, 0, 0, $month, $day, $year);
  switch ($view)
  {
    case 'week':
      $skipback = day_of_MRBS_week($time);
      $day_start_interval = $day - $skipback;
      $n_days = DAYS_PER_WEEK;
      $start_dow = $weekstarts;
      break;
    case 'month':
      $day_start_interval = 1;
      $n_days = (int) date('t', $time);
      $start_dow = (int) date('N', mktime(12, 0, 0, $month, 1, $year));
      break;
    default:
      trigger_error("Unsupported view '$view'", E_USER_WARNING);
      break;
  }

  // Get the time slots
  $n_time_slots = get_n_time_slots();
  $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
  $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

  // Get the data.  It's much quicker to do a single SQL query getting all the
  // entries for the interval in one go, rather than doing a query for each day.
  $entries = get_entries_by_area($area_id,
                                 get_start_first_slot($month, $day_start_interval, $year),
                                 get_end_last_slot($month, $day_start_interval + $n_days-1, $year));

  // We want to build an array containing all the data we want to show and then spit it out.
  $map = new Map($resolution);

  for ($j = 0; $j < $n_days; $j++)
  {
    $d = $day_start_interval + $j;
    $start_first_slot = get_start_first_slot($month, $d, $year);
    $start_last_slot = get_start_last_slot($month, $d, $year);

    foreach ($entries as $entry)
    {
      $entry = prepare_entry($entry);
      $map->add($entry, $d, $start_first_slot, $start_last_slot);
    }
  }

  // TABLE HEADER
  $thead = '<thead';

  $slots = get_slots($month, $day_start_interval, $year, $n_days, true);
  if (isset($slots))
  {
    // Add some data to enable the JavaScript to draw the timeline
    $thead .= ' data-slots="' . htmlspecialchars(json_encode($slots)) . '"';
    $thead .= ' data-timeline-vertical="' . (($days_along_top) ? 'true' : 'false') . '"';
    $thead .= ' data-timeline-full="true"';
  }

  $thead .= ">\n";

  if ($days_along_top)
  {
    $header_inner_rows = multiday_header_rows($view, $view_all, $year, $month, $day_start_interval, $area_id, $room_id, $n_days, $start_dow);
  }
  else
  {
    // See comment above
    trigger_error("Not yet implemented", E_USER_WARNING);
  }

  $thead .= implode('', $header_inner_rows);
  $thead .= "</thead>\n";

  // Now repeat the header in a footer if required
  $tfoot = ($column_labels_both_ends) ? "<tfoot>\n" . implode('',array_reverse($header_inner_rows)) . "</tfoot>\n" : '';

  // TABLE BODY LISTING BOOKINGS
  $tbody = "<tbody>\n";

  $room_link_vars = array(
      'view'      => $view,
      'view_all'  => 0,
      'page_date' => format_iso_date($year, $month, $day),
      'area'      => $area_id
    );

  if ($days_along_top)
  {
    // the standard view, with days along the top and rooms down the side
    foreach ($rooms as $room)
    {
      $room_id = $room['id'];
      $room_link_vars['room'] = $room_id;
      $tbody .= "<tr>\n";
      $row_label = room_cell_html($room, $room_link_vars);
      $tbody .= $row_label;

      for ($j = 0; $j < $n_days; $j++)
      {
        if (is_hidden_day(($j + $start_dow) % DAYS_PER_WEEK))
        {
          continue;
        }

        $d = $day_start_interval + $j;

        // Add a classes for weekends and classes
        $date = new DateTime();
        $date->setDate($year, $month, $d);
        $classes = get_date_classes($date);

        $tbody .= '<td';
        if (!empty($classes))
        {
          $tbody .= ' class="' . implode(' ', $classes) . '"';
        }
        $tbody .= '>';
        $vars = array(
            'view'      => 'day',
            'view_all'  => $view_all,
            'page_date' => format_iso_date($year, $month, $d),
            'area'      => $area_id,
            'room'      => $room['id']
          );

        $link = "index.php?" . http_build_query($vars, '', '&');
        $link = multisite($link);
        $tbody .= '<a href="' . htmlspecialchars($link) . "\">\n";
        $s = $morning_slot_seconds;
        $slots = 0;
        while ($s <= $evening_slot_seconds)
        {
          $this_slot = $map->slot($room_id, $d, $s);
          if (!empty($this_slot))
          {
            if ($slots > 0)
            {
              $tbody .= get_flex_div($slots, 'free');
            }
            $this_entry = $this_slot[0];
            $n =    $this_entry['n_slots'];
            $text = $this_entry['name'];
            $classes = get_entry_classes($this_entry);
            $tbody .= get_flex_div($n, $classes, $text, $text);
            $slots = 0;
          }
          else
          {
            $n = 1;
            $slots++;
          }
          $s = $s + ($n * $resolution);
        }

        if ($slots > 0)
        {
          $tbody .= get_flex_div($slots, 'free');
        }

        $tbody .= "</a>\n";
        $tbody .= "</td>\n";
      }

      if ($row_labels_both_sides)
      {
        $tbody .= $row_label;
      }
      $tbody .= "</tr>\n";
    }
  }
  else
  {
    // See comment above
    trigger_error("Not yet implemented", E_USER_WARNING);
  }

  $tbody .= "</tbody>\n";
  return $thead . $tfoot . $tbody;
}


// If we're not using periods, construct an array describing the slots to pass to the JavaScript so that
// it can calculate where the timeline should be drawn.  (If we are using periods then the timeline is
// meaningless because we don't know when periods begin and end.)
//    $month, $day, $year   the start of the interval
//    $n_days               the number of days in the interval
//    $day_cells            if the columns/rows represent a full day (as in the week/month all rooms views)
function get_slots(int $month, int $day, int $year, int $n_days=1, bool $day_cells=false) : ?array
{
  global $enable_periods, $morningstarts, $morningstarts_minutes, $resolution;

  $slots = null;

  if (!$enable_periods)
  {
    $slots = array();

    $n_time_slots = get_n_time_slots();
    $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
    $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

    for ($j = 0; $j < $n_days; $j++)
    {
      $d = $day + $j;

      // If there's more than one day in the interval then don't include the hidden days in the array, because
      // they don't appear in the DOM.  If there's only one day then we've managed to display the hidden day.
      if (($n_days > 1) &&
          is_hidden_day(date('w', mktime($morningstarts, $morningstarts_minutes, 0, $month, $d, $year))))
      {
        continue;
      }

      $this_day = array();

      if ($day_cells)
      {
        $this_day[] = mktime(0, 0, $morning_slot_seconds, $month, $d, $year);
        // Need to do mktime() again for the end of the slot as we can't assume that the end slot is $resolution
        // seconds after the start of the slot because of the possibility of DST transitions
        $this_day[] = mktime(0, 0, $evening_slot_seconds + $resolution, $month, $d, $year);
      }
      else
      {
        for ($s = $morning_slot_seconds;
             $s <= $evening_slot_seconds;
             $s += $resolution)
        {
          $this_slot = array();
          $this_slot[] = mktime(0, 0, $s, $month, $d, $year);
          // Need to do mktime() again for the end of the slot as we can't assume that the end slot is $resolution
          // seconds after the start of the slot because of the possibility of DST transitions
          $this_slot[] = mktime(0, 0, $s + $resolution, $month, $d, $year);
          $this_day[] = $this_slot;
        }
      }
      $slots[] = $this_day;
    }
  }

  if ($day_cells)
  {
    $slots = array($slots);
  }

  return $slots;
}


function week_room_table_innerhtml(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id, ?int $timetohighlight=null) : string
{
  global $enable_periods;
  global $times_along_top, $row_labels_both_sides, $column_labels_both_ends;
  global $resolution, $morningstarts, $morningstarts_minutes;
  global $weekstarts, $datetime_formats;

  // Check that we've got a valid, enabled room
  $room_name = get_room_name($room_id);

  if (is_null($room_name) || (!is_visible($room_id)))
  {
    // No rooms have been created yet, or else they are all disabled
    // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
    return "<tbody data-empty=1><tr><td><h1>".get_vocab("no_rooms_for_area")."</h1></td></tr></tbody>";
  }

  // We have a valid room
  // Calculate how many days to skip back to get to the start of the week
  $time = mktime(12, 0, 0, $month, $day, $year);
  $skipback = day_of_MRBS_week($time);
  $day_start_week = $day - $skipback;
  // We will use $day for links and $day_start_week for anything to do with showing the bookings,
  // because we want the booking display to start on the first day of the week (eg Sunday if $weekstarts is 0)
  // but we want to preserve the notion of the current day (or 'sticky day') when switching between pages

  // Define the start and end of each day of the week in a way which is not
  // affected by daylight saving...
  for ($j = 0; $j < DAYS_PER_WEEK; $j++)
  {
    $start_first_slot[$j] = get_start_first_slot($month, $day_start_week+$j, $year);
    $start_last_slot[$j] = get_start_last_slot($month, $day_start_week+$j, $year);
    // Work out whether there's a possibility that a time slot is invalid,
    // in other words whether the booking day includes a transition into DST.
    // If we know that there's a transition into DST then some of the slots are
    // going to be invalid.   Knowing whether or not there are possibly invalid slots
    // saves us bothering to do the detailed calculations of which slots are invalid.
    $is_possibly_invalid[$j] = !$enable_periods && is_possibly_invalid($start_first_slot[$j], $start_last_slot[$j]);
  }
  unset($j);  // Just so that we pick up any accidental attempt to use it later

  // Get the data.  It's much quicker to do a single SQL query getting all the
  // entries for the interval in one go, rather than doing a query for each day.
  $entries = get_entries_by_room($room_id,
                                 $start_first_slot[0],
                                 $start_last_slot[DAYS_PER_WEEK - 1] + $resolution);

  $map = new Map($resolution);

  for ($j = 0; $j < DAYS_PER_WEEK; $j++)
  {
    foreach ($entries as $entry)
    {
      $map->add($entry, $j, $start_first_slot[$j], $start_last_slot[$j]);
    }
  }
  unset($j);  // Just so that we pick up any accidental attempt to use it later

  // START DISPLAYING THE MAIN TABLE
  $n_time_slots = get_n_time_slots();
  $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
  $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

  // TABLE HEADER
  $thead = '<thead';

  $slots = get_slots($month, $day_start_week, $year, DAYS_PER_WEEK);
  if (isset($slots))
  {
    // Add some data to enable the JavaScript to draw the timeline
    $thead .= ' data-slots="' . htmlspecialchars(json_encode($slots)) . '"';
    $thead .= ' data-timeline-vertical="' . (($times_along_top) ? 'true' : 'false') . '"';
    $thead .= ' data-timeline-full="false"';
  }
  $thead .= ">\n";

  if ($times_along_top)
  {
    $tag = 'date';
  }
  elseif ($enable_periods)
  {
    $tag = 'period';
  }
  else
  {
    $tag = 'time';
  }
  $label = get_vocab($tag);

  // We can display the table in two ways
  if ($times_along_top)
  {
    $header_inner = "<tr>\n";
    $first_last_html = '<th class="first_last">' . $label . "</th>\n";
    $header_inner .= $first_last_html;
    $header_inner .= get_times_header_cells($morning_slot_seconds, $evening_slot_seconds, $resolution);
    // next line to display times on right side
    if ($row_labels_both_sides)
    {
      $header_inner .= $first_last_html;
    }
    $header_inner .= "</tr>\n";
    $header_inner_rows = [$header_inner];
  }

  else
  {
    $header_inner_rows = multiday_header_rows($view, $view_all, $year, $month, $day_start_week, $area_id, $room_id, DAYS_PER_WEEK, $weekstarts, $label);
  }


  $thead .= implode('', $header_inner_rows);
  $thead .= "</thead>\n";

  // Now repeat the header in a footer if required
  $tfoot = ($column_labels_both_ends) ? "<tfoot>\n" . implode('',array_reverse($header_inner_rows)) . "</tfoot>\n" : '';

  // TABLE BODY LISTING BOOKINGS
  $tbody = "<tbody>\n";

  // We can display the table in two ways
  if ($times_along_top)
  {
    $format = $datetime_formats['view_week_day_date_month'];
    // with times along the top and days of the week down the side
    // See note above: weekday==0 is day $weekstarts, not necessarily Sunday.
    for ($thisday = 0; $thisday < DAYS_PER_WEEK; $thisday++)
    {
      if (is_hidden_day(($thisday + $weekstarts) % DAYS_PER_WEEK))
      {
        // These days are to be hidden in the display: don't display a row
        continue;
      }

      $tbody .= "<tr>\n";

      $wt = mktime( 12, 0, 0, $month, $day_start_week + $thisday, $year );
      $wday = date('d', $wt);
      $wmonth = date('m', $wt);
      $wyear = date('Y', $wt);
      $wdate = date('Y-n-d', $wt);

      $day_cell_text = datetime_format($format, $wt);

      $vars = array('view'     => 'day',
                    'view_all' => $view_all,
                    'year'     => date('Y', $wt),
                    'month'    => date('m', $wt),
                    'day'      => date('d', $wt),
                    'area'     => $area_id,
                    'room'     => $room_id);

      $day_cell_link = 'index.php?' . http_build_query($vars, '', '&');
      $day_cell_link = multisite($day_cell_link);
      $row_label = day_cell_html($day_cell_text, $day_cell_link, $wdate);
      $tbody .= $row_label;

      for ($s = $morning_slot_seconds;
           $s <= $evening_slot_seconds;
           $s += $resolution)
      {
        $is_invalid = $is_possibly_invalid[$thisday] && is_invalid_datetime(0, 0, $s, $wmonth, $wday, $wyear);
        // set up the query vars to be used for the link in the cell
        $query_vars = get_query_vars($view, $area_id, $room_id, $wmonth, $wday, $wyear, $s);
        // and then draw the cell
        $tbody .= cell_html($map->slot($room_id, $thisday, $s), $query_vars, $is_invalid);
      }  // end looping through the time slots
      if ($row_labels_both_sides)
      {
        $tbody .= $row_label;
      }
      $tbody .= "</tr>\n";

    }  // end looping through the days of the week

  } // end "times along top" view (for the body)

  else
  {
    // the standard view, with days of the week along the top and times down the side
    for ($s = $morning_slot_seconds;
         $s <= $evening_slot_seconds;
         $s += $resolution)
    {
      // Show the time linked to the URL for highlighting that time:
      $classes = array();

      $vars = array('view'     => 'week',
                    'view_all' => $view_all,
                    'year'     => $year,
                    'month'    => $month,
                    'day'      => $day,
                    'area'     => $area_id,
                    'room'     => $room_id);

      if (isset($timetohighlight) && ($s == $timetohighlight))
      {
        $classes[] = 'row_highlight';
      }
      else
      {
        $vars['timetohighlight'] = $s;
      }

      $url = 'index.php?' . http_build_query($vars, '', '&');
      $url = multisite($url);

      $tbody.= '<tr';
      if (!empty($classes))
      {
        $tbody .= ' class="' . implode(' ', $classes) . '"';
      }
      $tbody .= ">\n";

      $tbody .= time_cell_html($s, $url);


      // See note above: weekday==0 is day $weekstarts, not necessarily Sunday.
      for ($thisday = 0; $thisday < DAYS_PER_WEEK; $thisday++)
      {
        if (is_hidden_day(($thisday + $weekstarts) % DAYS_PER_WEEK))
        {
          // These days are to be hidden in the display
          continue;
        }

        // set up the query vars to be used for the link in the cell
        $wt = mktime(12, 0, 0, $month, $day_start_week+$thisday, $year);
        $wday = date("d", $wt);
        $wmonth = date("m", $wt);
        $wyear = date("Y", $wt);
        $is_invalid = $is_possibly_invalid[$thisday] && is_invalid_datetime(0, 0, $s, $wmonth, $wday, $wyear);
        $query_vars = get_query_vars($view, $area_id, $room_id, $wmonth, $wday, $wyear, $s);

        // and then draw the cell
        $tbody .= cell_html($map->slot($room_id, $thisday, $s), $query_vars, $is_invalid);
      }

      // next lines to display times on right side
      if ($row_labels_both_sides)
        {
          $tbody .= time_cell_html($s, $url);
        }

      $tbody .= "</tr>\n";
    }
  }  // end standard view (for the body)
  $tbody .= "</tbody>\n";

  return $thead . $tfoot . $tbody;
}


// 3-value compare: Returns result of compare as "< " "= " or "> ".
function cmp3(int $a, int $b) : string
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


// Gets the table header row for the single room month view
function get_table_head() : string
{
  global $weekstarts;

  $html = '';

  // Weekday name header row:
  $html .= "<thead>\n";
  $html .= "<tr>\n";
  for ($i = 0; $i< DAYS_PER_WEEK; $i++)
  {
    $classes = [];
    $dow = ($i + $weekstarts) % DAYS_PER_WEEK;
    if (is_hidden_day($dow))
    {
      $classes[] = 'hidden_day';
    }
    if (is_weekend($dow))
    {
      $classes[] = 'weekend';
    }
    $html .= '<th';
    if (!empty($classes))
    {
      $html .= ' class="' . implode(' ', $classes) . '"';
    }
    $html .= '>' . day_name(($i + $weekstarts)%DAYS_PER_WEEK) . '</th>';
  }
  $html .= "\n</tr>\n";
  $html .= "</thead>\n";

  return $html;
}


function get_blank_day(int $col) : string
{
  global $weekstarts;

  $td_class = (is_hidden_day(($col + $weekstarts) % DAYS_PER_WEEK)) ? 'hidden_day' : 'invalid';
  return "<td class=\"$td_class\"><div class=\"cell_container\">&nbsp;</div></td>\n";
}


// Describe the start and end time, accounting for "all day"
// and for entries starting before/ending after today.
// There are 9 cases, for start time < = or > midnight this morning,
// and end time < = or > midnight tonight.
function get_booking_summary(int $start, int $end, int $day_start, int $day_end) : string
{
  global $enable_periods, $area;

  // Use ~ (not -) to separate the start and stop times, because MSIE
  // will incorrectly line break after a -.
  $separator = '~';
  $after_today = "==>";
  $before_today = "<==";
  $midnight = "24:00";  // need to fix this so it works with AM/PM configurations (and for that matter 24h)
  $all_day = get_vocab('all_day');

  if ($enable_periods)
  {
    $start_str = htmlspecialchars(period_time_string($start, $area));
    $end_str   = htmlspecialchars(period_time_string($end, $area, -1));
  }
  else
  {
    $start_str = htmlspecialchars(datetime_format(hour_min_format(), $start));
    $end_str   = htmlspecialchars(datetime_format(hour_min_format(), $end));
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


function month_table_innerhtml(string $view, int $view_all, int $year, int $month, int $day, int $area, int $room) : string
{

  if ($view_all)
  {
    return multiday_view_all_rooms_innerhtml($view, $view_all, $year, $month, $day, $area, $room);
  }
  else
  {
    return month_room_table_innerhtml($view, $view_all, $year, $month, $day, $area, $room);
  }
}


function month_room_table_innerhtml(string $view, int $view_all, int $year, int $month, int $day, int $area, int $room) : string
{
  global $weekstarts, $view_week_number, $show_plus_link, $monthly_view_entries_details;
  global $enable_periods, $morningstarts, $morningstarts_minutes;
  global $prevent_booking_on_holidays, $prevent_booking_on_weekends;

  // Check that we've got a valid, enabled room
  if (is_null(get_room_name($room)) || !is_visible($room))
  {
    // No rooms have been created yet, or else they are all disabled
    // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
    return "<tbody data-empty=1><tr><td><h1>".get_vocab("no_rooms_for_area")."</h1></td></tr></tbody>";
  }

  $html = '';

  // Month view start time. This ignores morningstarts/eveningends because it
  // doesn't make sense to not show all entries for the day, and it messes
  // things up when entries cross midnight.
  $month_start = mktime(0, 0, 0, $month, 1, $year);
  // What column the month starts in: 0 means $weekstarts weekday.
  $weekday_start = (date("w", $month_start) - $weekstarts + DAYS_PER_WEEK) % DAYS_PER_WEEK;
  $last_day_of_month = (int) date("t", $month_start);

  $html .= get_table_head();

  // Main body
  $html .= "<tbody>\n";
  $html .= "<tr>\n";

  // Skip days in week before start of month:
  for ($weekcol = 0; $weekcol < $weekday_start; $weekcol++)
  {
    $html .= get_blank_day($weekcol);
  }

  // Get the data.  It's much quicker to do a single SQL query getting all the
  // entries for the interval in one go, rather than doing a query for each day.
  $entries = get_entries_by_room($room,
                                 get_start_first_slot($month, 1, $year),
                                 get_end_last_slot($month, $last_day_of_month, $year));

  // Draw the days of the month:
  for ($d = 1; $d <= $last_day_of_month; $d++)
  {
    // Get the slot times
    $start_first_slot = get_start_first_slot($month, $d, $year);
    $end_last_slot = get_end_last_slot($month, $d, $year);

    // if we're at the start of the week (and it's not the first week), start a new row
    if (($weekcol == 0) && ($d > 1))
    {
      $html .= "</tr><tr>\n";
    }

    // output the day cell
    if (is_hidden_day(($weekcol + $weekstarts) % DAYS_PER_WEEK))
    {
      // These days are to be hidden in the display (as they are hidden, just give the
      // day of the week in the header row
      $html .= "<td class=\"hidden_day\">\n";
      $html .= "<div class=\"cell_container\">\n";
      $html .= "<div class=\"cell_header\">\n";
      // first put in the day of the month
      $html .= "<span>$d</span>\n";
      $html .= "</div>\n";
      $html .= "</div>\n";
      $html .= "</td>\n";
    }
    else
    {
      // Add classes for weekends and holidays
      $date = new DateTime();
      $date->setDate($year, $month, $d);
      $classes = get_date_classes($date);

      $html .= '<td class="' . implode(' ', $classes) . "\">\n";
      $html .= "<div class=\"cell_container\">\n";

      $html .= "<div class=\"cell_header\">\n";

      $vars = array('year'  => $year,
                    'month' => $month,
                    'day'   => $d,
                    'area'  => $area,
                    'room'  => $room);

      // If it's a Monday (the start of the ISO week), show the week number
      if ($view_week_number && (($weekcol + $weekstarts)%DAYS_PER_WEEK == 1))
      {
        $vars['view'] = 'week';
        $query = http_build_query($vars, '', '&');
        $html .= '<a class="week_number" href="' . htmlspecialchars(multisite("index.php?$query")) . '">';
        $html .= date("W", gmmktime(12, 0, 0, $month, $d, $year));
        $html .= "</a>\n";
      }
      // then put in the day of the month
      $vars['view'] = 'day';
      $query = http_build_query($vars, '', '&');
      $html .= '<a class="monthday" href="' . htmlspecialchars(multisite("index.php?$query")) . "\">$d</a>\n";

      $html .= "</div>\n";

      // Then the link to make a new booking.
      // Don't provide a link if the slot doesn't really exist or if the user is logged in, but not a booking admin,
      // and it's a holiday/weekend and bookings on holidays/weekends are not allowed.  (We provide a link if they
      // are not logged in because they might want to click and login as an admin).
      if ((null !== session()->getCurrentUser()) && !is_book_admin($room) &&
          (($prevent_booking_on_holidays && in_array('holiday', $classes)) ||
           ($prevent_booking_on_weekends && in_array('weekend', $classes))))
      {
        $html .= '<span class="not_allowed"></span>';
      }
      else
      {
        $vars['view'] = $view;

        if ($enable_periods)
        {
          $vars['period'] = 0;
        }
        else
        {
          $vars['hour'] = $morningstarts;
          $vars['minute'] = $morningstarts_minutes;
        }

        $query = http_build_query($vars, '', '&');

        $html .= '<a class="new_booking" href="' . htmlspecialchars(multisite("edit_entry.php?$query")) . '"' .
                 ' aria-label="' . htmlspecialchars(get_vocab('create_new_booking')) . "\">\n";
        if ($show_plus_link)
        {
          $html .= "<img src=\"images/new.gif\" alt=\"New\" width=\"10\" height=\"10\">\n";
        }
        $html .= "</a>\n";
      }

      // then any bookings for the day
      $html .= "<div class=\"booking_list\">\n";
      // Show the start/stop times, 1 or 2 per line, linked to view_entry.
      foreach ($entries as $entry)
      {
        // We are only interested in this day's entries
        if (($entry['start_time'] >= $end_last_slot) ||
            ($entry['end_time'] <= $start_first_slot))
        {
          continue;
        }

        $entry = prepare_entry($entry);

        $classes = get_entry_classes($entry);
        $classes[] = $monthly_view_entries_details;

        $html .= '<div class="' . implode(' ', $classes) . '">';

        $vars = array('id'    => $entry["id"],
                      'year'  => $year,
                      'month' => $month,
                      'day'   => $d);

        $query = http_build_query($vars, '', '&');
        $booking_link = multisite("view_entry.php?$query");
        $slot_text = get_booking_summary(
                       $entry['start_time'],
                       $entry['end_time'],
                       $start_first_slot,
                       $end_last_slot);
        $description_text = utf8_substr($entry['name'], 0, 255);
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
        $html .= '<a href="' . htmlspecialchars($booking_link) . '"' .
                 ' title="' . htmlspecialchars($full_text) . '">';
        $html .= htmlspecialchars($display_text) . '</a>';
        $html .= "</div>\n";
      }
      $html .= "</div>\n";

      $html .= "</div>\n";
      $html .= "</td>\n";
    }

    // increment the day of the week counter
    if (++$weekcol == DAYS_PER_WEEK)
    {
      $weekcol = 0;
    }

  } // end of for loop going through valid days of the month

  // Skip from end of month to end of week:
  if ($weekcol > 0)
  {
    for (; $weekcol < DAYS_PER_WEEK; $weekcol++)
    {
      $html .= get_blank_day($weekcol);
    }
  }

  $html .= "</tr>\n";
  $html .= "</tbody>\n";

  return $html;
}
