<?php
namespace MRBS;

                            
function map_add_booking(array $entry, &$column, $start_first_slot, $start_last_slot)
{
  // Enters the contents of the booking found in $entry into $column, which is
  // a column of the map of the bookings being prepared ready for display.
  //
  // $column            the column of the map that is being prepared (see below)
  // $entry             a booking from the database
  // $start_first_slot  the start of the first slot of the booking day (Unix timestamp)
  // $start_last_slot   the start of the last slot of the booking day (Unix timestamp)
  
  // $entry is expected to have the following keys, when present:
  //       room_id
  //       start_time
  //       end_time
  //       name
  //       repeat_id
  //       id
  //       type
  //       description
  //       create_by
  //       awaiting_approval
  //       private
  //       tentative
  
  // $column is a column of the map of the screen that will be displayed, and is an array
  // indexed by the number of nominal seconds (ie ignoring DST changes] since the
  // start of the calendar day which has the start of the booking day.  Each element of
  // the array consists of an array of entries that fall in that slot.
  //
  // Normally of course there will only be one entry per slot, but it is possible to have
  // multiple entries per slot if the resolution is increased, the day shifted since the
  // original bookings were made, or if the bookings were made using an older version of MRBS
  // that had faulty conflict detection.  For example if you previously had a resolution of 1800
  // seconds you might have a booking (A) for 1000-1130 and another (B) for 1130-1230.
  // If you then increase the resolution to 3600 seconds, these two bookings 
  // will both occupy the 1100-1200 time slot.
  //
  // Each entry also has the following keys added:
  //       is_multiday_start  a boolean indicating if the booking stretches beyond the day start
  //       is_multiday_end    a boolean indicating if the booking stretches beyond the day end                            
  //       n_slots            the number of slots the booking lasts (tentatively set to 1)
  
  // s is the number of nominal seconds (ie ignoring DST changes] since the
  // start of the calendar day which has the start of the booking day
  

  // Fill in the map for this meeting. Start at the meeting start time,
  // or the day start time, whichever is later. End one slot before the
  // meeting end time (since the next slot is for meetings which start then),
  // or at the last slot in the day, whichever is earlier.
  // Time is of the format HHMM without leading zeros.

  
  global $resolution;
  global $is_private_field;
  
  // Check whether the event is private
  if (is_private_event($entry['private']) &&
      !getWritable($entry['create_by'], $entry['room_id']))
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

  // Adjust the starting and ending times so that bookings which don't
  // start or end at a recognized time still appear.
  $start_t = max(round_t_down($entry['start_time'], $resolution, $start_first_slot), $start_first_slot);
  $end_t = min(round_t_up($entry['end_time'], $resolution, $start_first_slot) - $resolution, $start_last_slot);
  
  // Calculate the times used for indexing - we index by nominal seconds since the start
  // of the calendar day which has the start of the booking day
  $start_s = nominal_seconds($start_t);
  $end_s = nominal_seconds($end_t);
  
  // Get some additional information about the entry related to the way it displays on the page
  $entry['is_multiday_start'] = ($entry['start_time'] < $start_first_slot);
  $entry['is_multiday_end'] = ($entry['end_time'] > ($start_last_slot + $resolution));

  // Tentatively assume that this booking occupies 1 slot.  Call map_coalesce_bookings()
  // later to fix it.
  $entry['n_slots'] = 1;
              
  for ($s = $start_s; $s <= $end_s; $s += $resolution)
  { 
    if (!isset($column[$s]))
    {
      $column[$s] = array();
    }
    array_push($column[$s], $entry);

  }
} // end function map_add_booking()


function map_coalesce_bookings(&$column)
{
  // Coalesces map entries for a room that span consecutive time slots.
  //
  // The map_add_booking() function set n_slots=1 for all map entries.  For each
  // booking in the room that spans multiple consecutive time slots, and that
  // does not have conflicting bookings, the first entry will have its slot
  // count adjusted, and the continuation entries will have their n_slots
  // attribute set to NULL.
  //
  // $column            the column of the map to analyze, from map_add_booking()

  global $resolution;
  
  if (!isset($column))
  {
    return;
  }

  // Iterate through pairs of consecutive time slots in reverse chronological
  // order
  for (end($column); ($s = key($column)) !== null; prev($column))
  {
    $p = $s - $resolution;      // The preceding time slot
    if (isset($column[$p]))
    {
      if (count($column[$s]) == 1)
      {
        // Single booking for time slot $s.  If this event is a continuation
        // of a sole event from time slot $p (the previous slot), then
        // increment the slot count of the same booking in slot $p, and clear
        // out the redundant attributes in slot $s.
        if (count($column[$p]) == 1 && $column[$p][0]['id'] == $column[$s][0]['id'])
        {
          $this_booking = &$column[$s][0];
          $prev_booking = &$column[$p][0];
          $prev_booking['n_slots'] = 1 + $this_booking['n_slots'];
          $this_booking['n_slots'] = null;
        }
      }

      else
      {
        // Multiple bookings for time slot $s.  Mark all of them as 1 slot.
        foreach ($column[$s] as &$booking)
        {
          $booking['n_slots'] = 1;
        }
      }
    }
  }
} // end function map_coalesce_bookings()



function cell_html($s, array $cell, array $query_vars, $is_invalid = false)
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

  global $enable_periods, $show_plus_link;
  global $approval_enabled, $confirmation_enabled;
  
  $html = '';

  // Don't put in a <td> cell if the slot is contains a single booking whose n_slots is NULL.
  // This would mean that it's the second or subsequent slot of a booking and so the
  // <td> for the first slot would have had a rowspan that extended the cell down for
  // the number of slots of the booking.

  if (empty($cell) || !is_null($cell[0]['n_slots']))
  {
    if (!empty($cell))
    {
      $class = 'booked';
      if (count($cell) > 1)
      {
        $class .= ' multiply';
      }
    }
    elseif ($is_invalid)
    {
      $class = 'invalid';
    }
    else
    {
      $class = 'new';
    }
    
    // If there's no booking or if there are multiple bookings then make the slot one unit long
    $slots = (count($cell) == 1) ? $cell[0]['n_slots'] : 1;
    
    $html .= tdcell($class, $slots);
    
    // If the room isn't booked then allow it to be booked
    if (empty($cell))
    {
      // Don't provide a link if the slot doesn't really exist
      if (!$is_invalid)
      {
        $vars = ($enable_periods) ? $query_vars['new_periods'] : $query_vars['new_times'];
        $query = http_build_query($vars, '', '&');
        
        $html .= '<a href="edit_entry.php?' . htmlspecialchars($query) . '"' .
                 ' aria-label="' . htmlspecialchars(get_vocab('create_new_booking')) . "\">\n";
        if ($show_plus_link)
        {
          $html .= "<img src=\"images/new.gif\" alt=\"New\" width=\"10\" height=\"10\">\n";
        }
        $html .= "</a>\n";
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
              
        $classes = array('booking', $booking['type']);
        
        if ($booking['private'])
        {
          $classes[] = 'private';
        }
        
        if ($approval_enabled && ($booking['awaiting_approval']))
        {
          $classes[] = 'awaiting_approval';
        }
        
        if ($confirmation_enabled && ($booking['tentative']))
        {
          $classes[] = 'tentative';
        }
        
        if (isset($booking['repeat_id']))
        {
          $classes[] = 'series';
        }
        
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
        $html .= '<a href="view_entry.php?' . htmlspecialchars($query) . '"' .
                   ' title="' . htmlspecialchars($booking['description']) . '"' .
                   ' class="' . $booking['type'] . '"' .
                   ' data-id="' . $booking['id'] . '"' .
                   ' data-type="' . $booking['type'] . '">';
        $html .= htmlspecialchars($booking['name']) . '</a>';
        $html .= "</div>\n";
      }
    }
    
    $html .= "</td>\n";
  }
  
  return $html;
}  // end function draw_cell


function get_timeslot_text($s, $resolution)
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
function time_cell_html($s, $url)
{
  global $enable_periods, $resolution;
  
  $html = '';
  
  $html .= "<th data-seconds=\"$s\">";
  $html .= "<a href=\"$url\"  title=\"" . get_vocab("highlight_line") . "\">";
  
  if ($enable_periods)
  {
    $html .= htmlspecialchars(period_name($s));
  }
  else
  {
    $html .= htmlspecialchars(get_timeslot_text($s, $resolution));
  }
  
  $html .= "</a></th>\n";
  
  return $html;
}

// Draw a room cell to be used in the first and last columns of the day view
//    $room    contains the room details
//    $link    the href to be used for the link
function room_cell_html(array $room, $link)
{
  $title = get_vocab("viewweek") . "\n\n" . $room['description'];
  $text = $room['room_name'] . (($room['capacity'] > 0) ? ' (' . $room['capacity'] . ')' : '');
  $html = '';
  $html .= '<th data-room="' . $room['id'] . '">';
  $html .= '<a href="' . htmlspecialchars($link) . '"' .
           ' title = "' . htmlspecialchars($title) . '">';
  $html .= htmlspecialchars($text);
  $html .= '</a>';
  $html .= "</th>\n";
  return $html;
}

// Draw a day cell to be used in the header rows/columns of the week view
//    $text     contains the date, formatted as a string (not escaped - allowed to contain HTML tags)
//    $link     the href to be used for the link
//    $date     the date in yyyy-mm-dd format
function day_cell_html($text, $link, $date)
{
  $html = '';
  // Put the date into a data attribute so that it can be picked up by JavaScript
  $html .= '<th data-date="' . htmlspecialchars($date) . '">';
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
function tdcell($classes, $slots, array $data=null)
{
  global $times_along_top;
  
  if (isset($classes) && ($classes !== '') && !is_array($classes))
  {
    $classes = array($classes);
  }
  
  $html = '';
  
  $html .= '<td';
  
  if (isset($classes))
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
  
  $html .= ">\n";
  
  return $html;
}


// Gets the number of time slots between the beginning and end of the booking
// day.   (This is the normal number on a non-DST transition day)
function get_n_time_slots()
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
  $n_slots = ($end_last - $start_first)/$resolution;

  return $n_slots;
}


// $s is nominal seconds
function get_query_vars($view, $area, $room, $month, $day, $year, $s)
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
  $period = period_index($s);
  
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


function get_times_header_cells($year, $month, $day, $start, $end, $increment)
{
  global $enable_periods;

  $html = '';

  for ($s = $start; $s <= $end; $s += $increment)
  {
    // Put the number of seconds since the start of the day (nominal, ignoring DST)
    // in a data attribute so that JavaScript can pick it up
    $html .= "<th data-seconds=\"$s\"";
    // If we're not using periods add data attributes showing the start and end timestamps for the slot
    // so that the JavaScript knows where to draw the timeline.
    if (!$enable_periods)
    {
      $start_timestamp = mktime(0, 0, $s, $month, $day, $year);
      $end_timestamp = $start_timestamp + $increment;
      $html .= ' data-start_timestamp="' . $start_timestamp . '" data-end_timestamp="' . $end_timestamp . '"';
    }
    $html .= ">";
    // We need the span so that we can apply some padding.   We can't apply it
    // to the <th> because that is used by jQuery.offset() in resizable bookings
    $html .= "<span>";
    if ( $enable_periods )
    {
      $html .= htmlspecialchars(period_name($s));
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


function get_rooms_header_cells($rooms, $vars)
{
  $html = '';

  foreach($rooms as $room)
  {
    $vars['room'] = $room['id'];
    $query = http_build_query($vars, '', '&');
    // Put the room_id in a data attribute so that JavaScript can pick it up
    $html .= '<th data-room="' . $room['id'] . '">';

    $html .= '<a href="index.php?' . htmlspecialchars($query) . '" ' .
             'title="' . get_vocab("viewweek") . " &#10;&#10;" . $room['description'] . '">' .
             htmlspecialchars($room['room_name']) . ($room['capacity'] > 0 ? ' (' . $room['capacity'] . ')' : '') .
             '</a>';

    $html .= "</th>\n";
  }

  return $html;
}


function day_table_innerhtml($view, $year, $month, $day, $area_id, $room_id, $timetohighlight=null)
{
  global $enable_periods;
  global $times_along_top, $row_labels_both_sides, $column_labels_both_ends;
  global $resolution, $morningstarts, $morningstarts_minutes;

  $rooms = get_rooms($area_id);
  $n_rooms = count($rooms);

  if ($n_rooms == 0)
  {
    // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
    return "<tbody data-empty=1><tr><td><h1>" . get_vocab("no_rooms_for_area") . "</h1></td></tr></tbody>";
  }

  $start_first_slot = get_start_first_slot($month, $day, $year);
  $start_last_slot = get_start_last_slot($month, $day, $year);
  
  // Work out whether there's a possibility that a time slot is invalid,
  // in other words whether the booking day includes a transition into DST.
  // If we know that there's a transition into DST then some of the slots are
  // going to be invalid.   Knowing whether or not there are possibly invalid slots
  // saves us bothering to do the detailed calculations of which slots are invalid.
  $is_possibly_invalid = !$enable_periods && is_possibly_invalid($start_first_slot, $start_last_slot);
  
  $entries = get_entries_by_area($area_id, $start_first_slot, $start_last_slot + $resolution);
  
  // We want to build an array containing all the data we want to show
  // and then spit it out. 
  $today = array();

  foreach ($entries as $entry)
  {
    map_add_booking($entry, $today[$entry['room_id']][$day], $start_first_slot, $start_last_slot);
  }

  foreach ($rooms as $room)
  {
    if (isset($today[$room['id']]))
    {
      map_coalesce_bookings($today[$room['id']][$day]);
    }
  }

  $n_time_slots = get_n_time_slots();
  $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
  $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);
  
  // TABLE HEADER
  $thead = '<thead data-start_first_slot="' . $start_first_slot .
           '" data-end_last_slot="' . ($start_last_slot + $resolution) . "\">\n";
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
    $header_inner .= get_times_header_cells($year, $month, $day, $morning_slot_seconds, $evening_slot_seconds, $resolution);
  }
  else
  {
    $vars = array('view' => 'week',
                  'year' => $year,
                  'month' => $month,
                  'day' => $day,
                  'area' => $area_id);

    $header_inner .= get_rooms_header_cells($rooms, $vars);
  }  // end standard view (for the header)

  // next: line to display times on right side
  if (!empty($row_labels_both_sides))
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
      
      $vars = array('view'  => 'week',
                    'year'  => $year,
                    'month' => $month,
                    'day'   => $day,
                    'area'  => $area_id,
                    'room'  => $room['id']);
                    
      $room_cell_link = "index.php?" . http_build_query($vars, '', '&');
      
      $tbody .= room_cell_html($room, $room_cell_link);
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
        if (!isset($today[$room['id']][$day][$s]))
        {
          $today[$room['id']][$day][$s] = array();
        }
        $tbody .= cell_html($s, $today[$room['id']][$day][$s], $query_vars, $is_invalid[$s]);
      }  // end for (looping through the times)
      if ( false != $row_labels_both_sides )
      {
        $tbody .= room_cell_html($room, $room_cell_link);
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

      $url = "index.php?" . htmlspecialchars(http_build_query($vars, '', '&'));

      $tbody .= '<tr';
      if (!empty($classes))
      {
        $tbody .= ' class="' . implode(' ', $classes) . '"';
      }
      // If we're not using periods add data attributes showing the start and end timestamps for the slot
      // so that the JavaScript knows where to draw the timeline.
      if (!$enable_periods)
      {
        $start_timestamp = mktime(0, 0, $s, $month, $day, $year);
        $end_timestamp = $start_timestamp + $resolution;
        $tbody .= ' data-start_timestamp="' . $start_timestamp . '" data-end_timestamp="' . $end_timestamp . '"';
      }
      $tbody .= ">\n";
      
      $tbody .= time_cell_html($s, $url);
      $is_invalid = $is_possibly_invalid && is_invalid_datetime(0, 0, $s, $month, $day, $year);
      // Loop through the list of rooms we have for this area
      foreach ($rooms as $room)
      {
        // set up the query vars to be used for the link in the cell
        $query_vars = get_query_vars($view, $area_id, $room['id'], $month, $day, $year, $s);
        
        if (!isset($today[$room['id']][$day][$s]))
        {
          $today[$room['id']][$day][$s] = array();
        }
        
        $tbody .= cell_html($s, $today[$room['id']][$day][$s], $query_vars, $is_invalid);
      }
      
      // next lines to display times on right side
      if ( false != $row_labels_both_sides )
      {
        $tbody .= time_cell_html($s, $url);
      }
  
      $tbody .= "</tr>\n";
    }
  }  // end standard view (for the body)
  
  $tbody .= "</tbody>\n";
  
  return $thead . $tfoot . $tbody;
}


function week_table_innerhtml($view, $year, $month, $day, $area_id, $room_id, $timetohighlight=null)
{
  global $enable_periods;
  global $times_along_top, $row_labels_both_sides, $column_labels_both_ends;
  global $resolution, $morningstarts, $morningstarts_minutes;
  global $weekstarts, $strftime_format;

  // Check that we've got a valid, enabled room
  $room_name = get_room_name($room_id);
  
  if (is_null($room_name) || (!is_visible($room_id)))
  {
    // No rooms have been created yet, or else they are all disabled
    // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
    return "<tbody data-empty=1><tr><td><h1>".get_vocab("no_rooms_for_area")."</h1></td></tr></tbody>";
  }
  
  // We have a valid room
  $num_of_days = 7; // days in a week
  
  // Calculate how many days to skip back to get to the start of the week
  $time = mktime(12, 0, 0, $month, $day, $year);
  $skipback = day_of_MRBS_week($time);
  $day_start_week = $day - $skipback;
  // We will use $day for links and $day_start_week for anything to do with showing the bookings,
  // because we want the booking display to start on the first day of the week (eg Sunday if $weekstarts is 0)
  // but we want to preserve the notion of the current day (or 'sticky day') when switching between pages
  
  // Define the start and end of each day of the week in a way which is not
  // affected by daylight saving...
  for ($j = 0; $j < $num_of_days; $j++)
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

  $week_map = array();

  for ($j = 0; $j < $num_of_days; $j++)
  {
    $entries = get_entries_by_room($room_id, $start_first_slot[$j], $start_last_slot[$j] + $resolution);
    foreach ($entries as $entry)
    {
      map_add_booking($entry, $week_map[$room_id][$j], $start_first_slot[$j], $start_last_slot[$j]);
    }
    map_coalesce_bookings($week_map[$room_id][$j]);
  }
  unset($j);  // Just so that we pick up any accidental attempt to use it later
  
  // START DISPLAYING THE MAIN TABLE
  $n_time_slots = get_n_time_slots();
  $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
  $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

  // TABLE HEADER
  $thead = "<thead>\n";
  $header_inner = "<tr>\n";

  $dformat = $strftime_format['weekview_headers'];
  // If we've got a table with times along the top then put everything on the same line
  // (ie replace the <br> with a space).   It looks slightly better
  if ($times_along_top)
  {
    $dformat = preg_replace("/<br>/", " ", $dformat);
  }

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

  $first_last_html = '<th class="first_last">' . get_vocab($tag) . "</th>\n";
  $header_inner .= $first_last_html;

  // We can display the table in two ways
  if ($times_along_top)
  {
    $header_inner .= get_times_header_cells($year, $month, $day, $morning_slot_seconds, $evening_slot_seconds, $resolution);
  }

  else
  {
    // the standard view, with days along the top and times down the side
    for ($j = 0; $j < $num_of_days; $j++)
    {
      $t = mktime(12, 0, 0, $month, $day_start_week+$j, $year);
      $date = date('Y-m-d', $t);
    
      if (is_hidden_day(($j + $weekstarts) % 7))
      {
        // These days are to be hidden in the display (as they are hidden, just give the
        // day of the week in the header row
        $header_inner .= '<th class="hidden_day">' .
                         utf8_strftime($dformat, $t) . 
                         "</th>\n";
      }
      else  
      {
        $vars = array('view'  => 'day',
                      'year'  => strftime("%Y", $t),
                      'month' => strftime("%m", $t),
                      'day'   => strftime('%d', $t),
                      'area'  => $area_id,
                      'room'  => $room_id);

        $link = 'index.php?' . http_build_query($vars, '', '&');
        $text = utf8_strftime($dformat, $t);
        $header_inner .= day_cell_html($text, $link, $date);
      }
    }  // for ($j = 0 ...
    unset($j);  // Just so that we pick up any accidental attempt to use it later
  }  // end standard view (for the header)

  // next line to display times on right side
  if (!empty($row_labels_both_sides))
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

  // We can display the table in two ways
  if ($times_along_top)
  {
    // with times along the top and days of the week down the side
    // See note above: weekday==0 is day $weekstarts, not necessarily Sunday.
    for ($thisday = 0; $thisday < $num_of_days; $thisday++)
    {
      if (is_hidden_day(($thisday + $weekstarts) % 7))
      {
        // These days are to be hidden in the display: don't display a row
        continue;
      }

      $tbody .= "<tr>\n";

      $wt = mktime( 12, 0, 0, $month, $day_start_week + $thisday, $year );
      $wday = date("d", $wt);
      $wmonth = date("m", $wt);
      $wyear = date("Y", $wt);
      $wdate = date('Y-n-d', $wt);

      $day_cell_text = utf8_strftime($dformat, $wt);

      $vars = array('view'  => 'day',
                    'year'  => strftime("%Y", $wt),
                    'month' => strftime("%m", $wt),
                    'day'   => strftime("%d", $wt),
                    'area'  => $area_id,
                    'room'  => $room_id);

      $day_cell_link = 'index.php?' . http_build_query($vars, '', '&');

      $tbody .= day_cell_html($day_cell_text, $day_cell_link, $wdate);

      for ($s = $morning_slot_seconds;
           $s <= $evening_slot_seconds;
           $s += $resolution)
      {
        $is_invalid = $is_possibly_invalid[$thisday] && is_invalid_datetime(0, 0, $s, $wmonth, $wday, $wyear);
        // set up the query vars to be used for the link in the cell
        $query_vars = get_query_vars($view, $area_id, $room_id, $wmonth, $wday, $wyear, $s);

        // and then draw the cell
        if (!isset($week_map[$room_id][$thisday][$s]))
        {
          $week_map[$room_id][$thisday][$s] = array();  // to avoid an undefined index NOTICE error
        }
        $tbody .= cell_html($s, $week_map[$room_id][$thisday][$s], $query_vars, $is_invalid);
      }  // end looping through the time slots
      if ( false != $row_labels_both_sides )
      {
        $tbody .= day_cell_html($day_cell_text, $day_cell_link, $wdate);
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
      
      $vars = array('view'  => 'week',
                    'year'  => $year,
                    'month' => $month,
                    'day'   => $day,
                    'area'  => $area_id,
                    'room'  => $room_id);
                    
      if (isset($timetohighlight) && ($s == $timetohighlight))
      {
        $classes[] = 'row_highlight';
      }
      else
      {
        $vars['timetohighlight'] = $s;
      }
      
      $url = 'index.php?' . htmlspecialchars(http_build_query($vars, '', '&'));
  
      $tbody.= '<tr';
      if (!empty($classes))
      {
        $tbody .= ' class="' . implode(' ', $classes) . '"';
      }
      $tbody .= ">\n";
      
      $tbody .= time_cell_html($s, $url);
  
  
      // See note above: weekday==0 is day $weekstarts, not necessarily Sunday.
      for ($thisday = 0; $thisday < $num_of_days; $thisday++)
      {
        if (is_hidden_day(($thisday + $weekstarts) % 7))
        {
          // These days are to be hidden in the display
          $tbody .= "<td class=\"hidden_day\">&nbsp;</td>\n";
        }
        else
        {
          // set up the query vars to be used for the link in the cell
          $wt = mktime(12, 0, 0, $month, $day_start_week+$thisday, $year);
          $wday = date("d", $wt);
          $wmonth = date("m", $wt);
          $wyear = date("Y", $wt);
          $is_invalid = $is_possibly_invalid[$thisday] && is_invalid_datetime(0, 0, $s, $wmonth, $wday, $wyear);
          $query_vars = get_query_vars($view, $area_id, $room_id, $wmonth, $wday, $wyear, $s);
     
          // and then draw the cell
          if (!isset($week_map[$room_id][$thisday][$s]))
          {
            $week_map[$room_id][$thisday][$s] = array();  // to avoid an undefined index NOTICE error
          }
          $tbody .= cell_html($s, $week_map[$room_id][$thisday][$s], $query_vars, $is_invalid);
        }
  
      }    // for loop
  
      // next lines to display times on right side
      if ( false != $row_labels_both_sides )
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


function month_table_innerhtml($view, $year, $month, $day, $area, $room)
{
  global $weekstarts, $view_week_number, $show_plus_link, $monthly_view_entries_details;
  global $enable_periods, $morningstarts, $morningstarts_minutes;
  global $approval_enabled, $confirmation_enabled;
  global $is_private_field;

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
      // Handle private events
      if (is_private_event($entry['private']) &&
          !getWritable($entry['create_by'], $room))
      {
        $entry['private'] = true;
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
        $entry['private'] = false;
      }
      
      $d[$day_num]['shortdescrip'][]      = htmlspecialchars($entry['name']);
      $d[$day_num]['id'][]                = $entry['id'];
      $d[$day_num]['type'][]              = $entry['type'];
      $d[$day_num]['awaiting_approval'][] = $entry['awaiting_approval'];
      $d[$day_num]['private'][]           = $entry['private'];
      $d[$day_num]['tentative'][]         = $entry['tentative'];
      $d[$day_num]['is_repeat'][]         = isset($entry['repeat_id']);
      $d[$day_num]['data'][]              = get_booking_summary($entry['start_time'],
                                                                $entry['end_time'],
                                                                $start_first_slot,
                                                                $end_last_slot);
    }
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
      
      $vars = array('year'  => $year,
                    'month' => $month,
                    'day'   => $cday,
                    'area'  => $area,
                    'room'  => $room);
                      
      // If it's a Monday (the start of the ISO week), show the week number
      if ($view_week_number && (($weekcol + $weekstarts)%7 == 1))
      {
        $vars['view'] = 'week';          
        $query = http_build_query($vars, '', '&');
        $html .= '<a class="week_number" href="index.php?' . htmlspecialchars($query) . '">';
        $html .= date("W", gmmktime(12, 0, 0, $month, $cday, $year));
        $html .= "</a>\n";
      }
      // then put in the day of the month
      $vars['view'] = 'day';          
      $query = http_build_query($vars, '', '&');
      $html .= '<a class="monthday" href="index.php?' . htmlspecialchars($query) . "\">$cday</a>\n";

      $html .= "</div>\n";
      
      // then the link to make a new booking
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
      
      $html .= '<a class="new_booking" href="edit_entry.php?' . htmlspecialchars($query) . '"' .
               ' aria-label="' . htmlspecialchars(get_vocab('create_new_booking')) . "\">\n";
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
          $classes = array($d[$cday]['type'][$i],
                           $monthly_view_entries_details); 
          if ($d[$cday]['private'][$i])
          {
            $classes[] = 'private';
          }
          if ($approval_enabled && ($d[$cday]['awaiting_approval'][$i]))
          {
            $classes[] = 'awaiting_approval';
          }
          if ($confirmation_enabled && ($d[$cday]['tentative'][$i]))
          {
            $classes[] = 'tentative';
          }
          if ($d[$cday]['is_repeat'][$i])
          {
            $classes[] = 'series';
          }
          
          $html .= '<div class="' . implode(' ', $classes) . "\">\n";
          
          $vars = array('id'    => $d[$cday]["id"][$i],
                        'year'  => $year,
                        'month' => $month,
                        'day'   => $cday);
                        
          $query = http_build_query($vars, '', '&');
          $booking_link = 'view_entry.php?' . htmlspecialchars($query);
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
