<?php
namespace MRBS;

// width of the row label columns in the day and week views
$first_last_width = 1;  // (%)
$column_hidden_width  = 0;  // (%) width of the column for hidden days (set to 0 for no column at all; 1 for a narrow column);
                            //     when $times_along_top is TRUE, hidden days (rows) are not shown at all

                            
function map_add_booking ($entry, &$column, $start_first_slot, $start_last_slot)
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
  //       status
  
  // $column is a column of the map of the screen that will be displayed
  // It looks like:
  //     $column[s][n][id]
  //                  [is_repeat]
  //                  [is_multiday_start]  a boolean indicating if the booking stretches
  //                                       beyond the day start
  //                  [is_multiday_end]    a boolean indicating if the booking stretches
  //                                          beyond the day end
  //                  [type]
  //                  [name]
  //                  [description]
  //                  [create_by]
  //                  [room_id]
  //                  [start_time]
  //                  [slots]
  //                  [status]
  
  // s is the number of nominal seconds (ie ignoring DST changes] since the
  // start of the calendar day which has the start of the booking day
  
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
  // Adjust the starting and ending times so that bookings which don't
  // start or end at a recognized time still appear.
  
  global $resolution;
  global $is_private_field;
  
  $data_keys = array('name', 'description', 'create_by', 'room_id');
  
  $user = getUserName();
  if (is_private_event($entry['status'] & STATUS_PRIVATE) &&
         !getWritable($entry['create_by'], $user, $entry['room_id']))
  {
    $entry['status'] |= STATUS_PRIVATE;   // Set the private bit
    
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
    $entry['status'] &= ~STATUS_PRIVATE;  // Clear the private bit
  }

  $is_multiday_start = ($entry['start_time'] < $start_first_slot);
  $is_multiday_end = ($entry['end_time'] > ($start_last_slot + $resolution));
              
  $start_t = max(round_t_down($entry['start_time'], $resolution, $start_first_slot), $start_first_slot);
  $end_t = min(round_t_up($entry['end_time'], $resolution, $start_first_slot) - $resolution, $start_last_slot);
  
  // calculate the times used for indexing - we index by nominal seconds since the start
  // of the calendar day which has the start of the booking day
  $start_s = nominal_seconds($start_t);
  $end_s = nominal_seconds($end_t);
  
  for ($s = $start_s; $s <= $end_s; $s += $resolution)
  { 
    // find the first free index (in case there are multiple bookings in a timeslot)
    $n = 0;
    while (!empty($column[$s][$n]["id"]))
    {
      $n++;
    }
    
    // fill in the id, type and start time
    $column[$s][$n]["id"] = $entry['id'];
    $column[$s][$n]["is_repeat"] = isset($entry['repeat_id']);
    $column[$s][$n]["is_multiday_start"] = $is_multiday_start;
    $column[$s][$n]["is_multiday_end"] = $is_multiday_end;
    $column[$s][$n]["status"] = $entry['status'];
    $column[$s][$n]["type"] = $entry['type'];
    $column[$s][$n]["start_time"] = hour_min($start_s);
    $column[$s][$n]["slots"] = null;  // to avoid undefined index NOTICE errors
    
    foreach ($data_keys as $key)
    {
      // if it's a multiple booking also fill in the data key
      if ($n > 0)
      {
        $column[$s][$n][$key] = $entry[$key];
      }
      // otherwise just leave it blank (we'll fill in the first whole slot later)
      // use null rather than '' just in case the value really is ''
      else
      {
        $column[$s][$n][$key] = null;
      }
    }
  } // end for
  
  
  // Show the name of the booker, the description and the number of complete
  // slots in the first complete slot that the booking happens in, or at the 
  // start of the day if it started before today.

  // Find the number of time slots that the booking occupies, and the index
  // of the first slot that this booking has entirely to itself
  // We need to adjust the start and end times for DST transitions as the display
  // ignores DST
  $n_slots = intval((($end_t - $start_t) - cross_dst($start_t, $end_t))/$resolution) + 1;
  $first_slot = $start_s;
  
  // If the last time slot is already occupied, we have a multiple
  // booking in the last slot, so decrement the number of slots that
  // we will display for this booking
  if (isset($column[$end_s][1]["id"]))
  {
    $n_slots--;
    // If we're only the second booking to land on this time slot
    // then we'll have to adjust the information held for the first booking
    // (unless it's just one slot long in the first place, when it 
    // doesn't matter as it will now be part of a multiple booking).
    // If we are the third booking or more, then it will have already
    // been adjusted.
    if (!isset($column[$end_s][2]["id"]))
    {
      if ($column[$end_s][0]["slots"] > 1)
      {
        // Move the dats into the new first slot and decrement the number of slots
        foreach ($data_keys as $key)
        {
          $column[$end_s + $resolution][0][$key] = $column[$end_s][0][$key];
        }
        $column[$end_s + $resolution][0]['slots'] = $column[$end_s][0]['slots'] - 1;
      }
    }
  }
  
  // and if the first time slot is already occupied, decrement
  // again, adjust the first slot for this booking
  if (isset($column[$start_s][1]['id']))
  {
    $n_slots--;
    $first_slot += $resolution;
    // If we're only the second booking to land on this time slot
    // then we'll have to adjust the information held for the first booking
    if (!isset($column[$start_s][2]['id']))
    {
      // Find the first slot ($s) of the first booking
      $first_booking_id = $column[$start_s][0]['id'];
      $r = $start_s;
      // If you've got to the first slot of the day then that must be the
      // first slot of the first booking
      while ($r > $start_first_slot)
      {
        // Otherwise, step back one slot.
        $r -= $resolution;
        // If that slot contains the first booking, then step back again
        if (isset($column[$r]))
        {
          foreach ($column[$r] as $booking)
          {
            if ($booking['id'] == $first_booking_id)
            {
              continue 2;  // next iteration of the while loop
            }
          }
        }
        // If not, then we've stepped back one slot past the start of
        // the first booking, so step forward again and finish
        $r += $resolution;
        break;
      } // end while
      
      // Now we've found the time ($r) of the first slot of the first booking
      // we need to find its index ($i)
      foreach ($column[$r] as $i => $booking)
      {
        if ($booking["id"] == $first_booking_id)
        {
          break;
        }
      }

      // Finally decrement the slot count for the first booking
      // no need to worry about count going < 1: the multiple booking display
      // does not use the slot count.
      $column[$r][$i]["slots"]--;
      // and put the data in the multiply booked slot
      foreach ($data_keys as $key)
      {
        $column[$start_s][0][$key] = $column[$r][$i][$key];
      }
    }
  }
 
  // now we've got all the information we can enter it in the first complete
  // slot for the booking (provided it's not a multiple booking slot)
  if (!isset($column[$first_slot][1]["id"]))
  {
    foreach ($data_keys as $key)
    {
      $column[$first_slot][0][$key] = $entry[$key];
    }    
    $column[$first_slot][0]['slots'] = $n_slots;
  }

} // end function map_add_booking()



function cell_html($cell, $query_vars, $is_invalid = false)
{
  // draws a single cell in the main table of the day and week views
  //
  // $cell is a two dimensional array that is part of the map of the whole
  // display and looks like this:
  // 
  // $cell[n][id]
  //         [is_repeat]
  //         [is_multiday_start]
  //         [is_multiday_end]
  //         [type]
  //         [name]
  //         [description]
  //         [create_by]
  //         [room_id]
  //         [start_time]
  //         [slots]
  //         [status]
  //
  // where n is the number of the booking in the cell.    There can be none, one or many 
  // bookings in a cell.    If there are no bookings then a blank cell is drawn with a link
  // to the edit entry form.     If there is one booking, then the booking is shown in that
  // cell.    If there is more than one booking then all the bookings are shown, but they can 
  // be shown in two different ways: minimised and maximised.   By default they are shown
  // minimised, so that the standard row height is preserved.   By clicking a control
  // the cell can be maximised.   (Multiple bookings can arise in a cell if the resolution
  // of an existing database in increased or the booking day is shifted).
  
  // $query_vars is an array containg the query vars to be used in the link for the cell.
  // It is indexed as follows:
  //    ['new_periods']   the vars to be used for an empty cell if using periods
  //    ['new_times']     the vars to be used for an empty cell if using times
  //    ['booking']       the vars to be used for a full cell
  //
  // $is_invalid specifies whether the slot actually exists or is one of the non-existent
  // slots in the transition to DST
    
  global $main_table_cell_border_width;
  global $area, $year, $month, $timetohighlight;
  global $enable_periods, $times_along_top, $show_plus_link;
  global $approval_enabled, $confirmation_enabled;
  
  $html = '';
  $user = getUserName();
  
  $vars = array('id',
                'is_repeat',
                'is_multiday_start',
                'is_multiday_end',
                'status',
                'type',
                'name',
                'description');
  
  // if the time slot has got multiple bookings, then draw a mini-table
  if(isset($cell[1]["id"]))
  {
    // Find out how many bookings there are (needed to calculate heights)
    $n_bookings = 0;
    while (isset($cell[$n_bookings]["id"]))
    {
      $n_bookings++;
    }
    
    // Make the class maximized by default so that if you don't have JavaScript then
    // you can still see all the bookings.    If you have JavaScript it will overwrite
    // the class and make it minimized.
    $html .= "<td class=\"multiple_booking maximized\">\n";
    
    // First draw the mini table
    $html .= "<div class=\"celldiv slots1 mini\">\n";
    $html .= "<div class=\"multiple_control\">+</div>\n";
    $html .= "<table>\n";
    $html .= "<tbody>\n";
    
    $row_height = 500;  // STUPID VALUE TO SHOW THAT THIS NEEDS TO BE REWRITTEN!!!!
    $row_height = $row_height/$n_bookings;  // split what's left between the bookings
    $row_height = (int) ceil($row_height);  // round up, so that (a) there's no empty space at the bottom
                                            // and (b) each stripe is at least 1 unit high
    for ($n=0; $n<$n_bookings; $n++)
    {
      foreach ($vars as $var)
      {
        $$var = $cell[$n][$var];
      }
      
      $class = "booked $type";
      
      if ($status & STATUS_PRIVATE)
      {
        $class .= " private";
      }
      if ($approval_enabled && ($status & STATUS_AWAITING_APPROVAL))
      {
        $class .= " awaiting_approval";
      }
      if ($confirmation_enabled && ($status & STATUS_TENTATIVE))
      {
        $class .= " tentative";
      }
      if ($is_multiday_start)
      {
        $class .= " multiday_start";
      }
      if ($is_multiday_end)
      {
        $class .= " multiday_end";
      }

      $html .= "<tr>\n";
      $html .= "<td class=\"$class\"" .
               (($n==0) ? " style=\"border-top-width: 0\"" : "") .   // no border for first row
               ">\n";
      $html .= "<div style=\"overflow: hidden; " .
               "height: " . $row_height . "px; " .
               "max-height: " . $row_height . "px; " .
               "min-height: " . $row_height . "px\">\n";
      $html .= "&nbsp;\n";
      $html .= "</div>\n";
      $html .= "</td>\n";
      $html .= "</tr>\n";
    }
    $html .= "</tbody>\n";
    $html .= "</table>\n";
    $html .= "</div>\n";
    
    // Now draw the maxi table
    $html .= "<div class=\"maxi\">\n";
    $total_height = $n_bookings * 500;  // STUPID VALUE TO SHOW THAT THIS NEEDS TO BE REWRITTEN
    $total_height += ($n_bookings - 1) * $main_table_cell_border_width;  // (first row has no top border)
    $html .= "<div class=\"multiple_control\" " .
             "style =\"height: " . $total_height . "px; " .
                      "min-height: " . $total_height . "px; " .
                      "max-height: " . $total_height . "px; " .
             "\">-</div>\n";  
    $html .= "<table>\n";
    $html .= "<tbody>\n";
    for ($n=0; $n<$n_bookings; $n++)
    {
      foreach ($vars as $var)
      {
        $$var = $cell[$n][$var];
      }
      
      $name = $cell[$n]["start_time"] . ' ' . $name;
      
      $classes = array('booked', $type);
      if ($status & STATUS_PRIVATE)
      {
        $classes[] = 'private';
      }
      if ($approval_enabled && ($status & STATUS_AWAITING_APPROVAL))
      {
        $classes[] = 'awaiting_approval';
      }
      if ($confirmation_enabled && ($status & STATUS_TENTATIVE))
      {
        $classes[] = 'tentative';
      }
      if ($is_multiday_start)
      {
        $classes[] = 'multiday_start';
      }
      if ($is_multiday_end)
      {
        $classes[] = 'multiday_end';
      }
      if ($is_repeat)
      {
        $classes[] = 'series';
      }
      
      $html .= "<tr>\n";
      $html .= '<td class="' . implode(' ', $classes) . '"' .
               (($n==0) ? " style=\"border-top-width: 0\"" : "") .   // no border for first row
               ">\n";
      $html .= "<div class=\"celldiv slots1\">\n";     // we want clipping of overflow
      
      $vars = $query_vars['booking'];
      $vars['id'] = $id;
      $query = http_build_query($vars, '', '&');
      
      $html .= '<a href="view_entry.php?' . htmlspecialchars($query) . '"' .
                  'title="' . htmlspecialchars($description). '">';
      $html .= htmlspecialchars($name) . "</a>\n";
      $html .= "</div>\n";
     
      $html .= "</td>\n";
      $html .= "</tr>\n";
    }
    $html .= "</tbody>\n";
    $html .= "</table>\n";
    $html .= "</div>\n";
    
    $html .= "</td>\n";
  }  // end of if isset ( ...[1]..)
  
  // otherwise draw a cell, showing either the booking or a blank cell
  else
  {  
    if(isset($cell[0]['id']))
    {      
      foreach ($vars as $var)
      {
        $$var = isset($cell[0][$var]) ? $cell[0][$var] : null;
      }
      $slots       = $cell[0]['slots'];
    }
    else  // id not set
    {
      unset($id);
      $slots = 1;
    }

    $classes = array();
    
    // We tell if its booked by $id and $name having something in them
    if (isset($id) && isset($name))
    {
      $classes[] = 'booked';
      $classes[] = $type;
      
      if ($status & STATUS_PRIVATE)
      {
        $classes[] = 'private';
      }
      if ($approval_enabled && ($status & STATUS_AWAITING_APPROVAL))
      {
        $classes[] = 'awaiting_approval';
      }
      if ($confirmation_enabled && ($status & STATUS_TENTATIVE))
      {
        $classes[] = 'tentative';
      }
      if ($is_multiday_start)
      {
        $classes[] = 'multiday_start';
      }
      if ($is_multiday_end)
      {
        $classes[] = 'multiday_end';
      }
      // Add a class to bookings that this user is allowed to edit so that the
      // JavaScript can turn them into resizable bookings
      if (getWritable($cell[0]['create_by'], $user, $cell[0]['room_id']))
      {
        $classes[] = 'writable';
        if ($is_repeat)
        {
          $classes[] = 'series';
        }
      }
    }
    else
    {
      $classes[] = ($is_invalid) ? 'invalid' : 'new';
    }
    
    // Don't put in a <td> cell if the slot is booked and there's no description.
    // This would mean that it's the second or subsequent slot of a booking and so the
    // <td> for the first slot would have had a rowspan that extended the cell down for
    // the number of slots of the booking.

    if (!isset($id) || isset($name))
    {
      $html .= tdcell($classes, $slots);

      // If the room isn't booked then allow it to be booked
      if (!isset($id))
      {
        // Don't provide a link if the slot doesn't really exist
        if (!$is_invalid)
        {
          $vars = ($enable_periods) ? $query_vars['new_periods'] : $query_vars['new_times'];
          $query = http_build_query($vars, '', '&');
          
          $html .= "<a href=\"edit_entry.php?" . htmlspecialchars($query) . "\">\n";
          if ($show_plus_link)
          {
            $html .= "<img src=\"images/new.gif\" alt=\"New\" width=\"10\" height=\"10\">\n";
          }
          $html .= "</a>\n";
        }
      }
      else                 // if it is booked then show the booking
      {
        $vars = $query_vars['booking'];
        $vars['id'] = $id;
        $query = http_build_query($vars, '', '&');
        // We have to wrap the booking in a <div> because we want the booking itself to be given
        // an absolute position and we can't use position relative on a <td> in IE11 and below.
        // We also need the bookings in a container because jQuery UI resizable has problems
        // with border-box (see https://stackoverflow.com/questions/18344272). And we need
        // border-box for the bookings because we are using padding on the bookings and we want
        // 'width: 100%' and 'height: 100%' to fill the table-cell with the entire booking
        // including content.
        $html .= '<div class="booking-wrapper">';
        $html .= '<a href="view_entry.php?' . htmlspecialchars($query) . '"' .
                   ' title="' . htmlspecialchars($description) . '"' .
                   ' class="' . $type . '"' .
                   ' data-id="' .$id . '"' .
                   ' data-type="' . $type . '">';
        $html .= htmlspecialchars($name) . '</a>';
        $html .= "</div>\n";
      }
      $html .= "</td>\n";
    }
  }
  
  return $html;
}  // end function draw_cell


// Draw a time cell to be used in the first and last columns of the day and week views
//    $s                 the number of seconds since the start of the day (nominal - not adjusted for DST)
//    $url               the url to form the basis of the link in the time cell
function time_cell_html($s, $url)
{
  global $enable_periods, $periods;
  
  $html = '';
  
  $html .= tdcell('row_labels', 1, array('seconds' => $s));
  
  if ( $enable_periods )
  {
    
    $html .= "<a href=\"$url\"  title=\""
             . get_vocab("highlight_line") . "\">"
             . htmlspecialchars(period_name($s)) . "</a>\n";
  }
  else
  {
    $html .= "<a href=\"$url\" title=\""
             . get_vocab("highlight_line") . "\">"
             . hour_min($s) . "</a>\n";
  }
  
  return $html;
}

// Draw a room cell to be used in the first and last columns of the day view
//    $row     contains the room details; comes from an SQL query
//    $link    the href to be used for the link
function room_cell_html($row, $link)
{
  $html = '';
  $html .= tdcell('row_labels', 1, array('room' => $row['id']));
  $html .= "<div class=\"celldiv slots1\">\n";
  $html .= "<a href=\"$link\" title=\"" . get_vocab("viewweek") . " &#10;&#10;" . $row['description'] . "\">";
  $html .= htmlspecialchars($row['room_name']) . ($row['capacity'] > 0 ? "(".$row['capacity'].")" : "");
  $html .= "</a>\n";
  $html .= "</div></td>\n";
  return $html;
}

// Draw a day cell to be used in the first and last columns of the week view
//    $text     contains the date, formatted as a string
//    $link     the href to be used for the link
//    $date     the date in yyyy-mm-dd format
function day_cell_html($text, $link, $date)
{
  $html = '';
  $html .= tdcell('row_labels', 1, array('date' => $date));
  $html .= "<div class=\"celldiv slots1\">\n";
  $html .= "<a href=\"$link\" title=\"" . get_vocab("viewday") . "\">$text</a>\n";
  $html .= "</div></td>\n";
  return $html;
}


// Output a start table cell tag <td> with class of $classes.
// $classes can be either a string or an array of classes
// empty or row_highlight if highlighted.
// $slots is the number of time slots high that the cell should be
//
// $data is an optional third parameter which if set passes an
// associative array of name-value pairs to be used in data aatributes
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
function get_query_vars($area, $room, $month, $day, $year, $s)
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
  
  $vars = array('year'  => $year,
                'month' => $month,
                'day'   => $day,
                'area'  => $area);
                
  $result['booking']     = $vars;
  $result['new_periods'] = array_merge($vars, array('room' => $room, 'period' => $period));
  $result['new_times']   = array_merge($vars, array('room' => $room, 'hour' => $hour, 'minute' => $minute));
 
  return $result;
}

// Gets the width in % of the main data columns in the day and week views
// given the number of columns, taking into account the row label columns
// and hidden columns
function get_main_column_width($n_columns, $n_hidden=0)
{
  global $row_labels_both_sides, $first_last_width, $column_hidden_width;
  
  // Calculate the percentage width of each of the main columns.   We use number_format
  // because in some locales you would get a comma for the decimal point which
  // would break the CSS
  $column_width = 100 - $first_last_width;
  if (!empty($row_labels_both_sides))
  {
    $column_width = $column_width - $first_last_width;
  }
  // Subtract the hidden columns (unless they are all hidden)
  if ($n_hidden < $n_columns)
  {
    $column_width = $column_width - ($n_hidden * $column_hidden_width);
    $column_width = number_format($column_width/($n_columns - $n_hidden), 6);
  }
  
  return $column_width;
}


function day_table_innerhtml($year, $month, $day, $area_id, $room_id, $timetohighlight=null)
{
  global $enable_periods, $periods;
  global $times_along_top, $row_labels_both_sides, $column_labels_both_ends;
  global $resolution, $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes;
  global $first_last_width;
  
  $rooms = get_rooms($area_id);
  $n_rooms = count($rooms);

  if ($n_rooms == 0)
  {
    // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
    return "<tbody data-empty=1><tr><td><h1>".get_vocab("no_rooms_for_area")."</h1></td></tr></tbody>";
  }
  
  // ensure that $morningstarts_minutes defaults to zero if not set
  if (!isset($morningstarts_minutes))
  {
    $morningstarts_minutes = 0;
  }
  
  if ($enable_periods)
  {
    $resolution = 60;
    $morningstarts = 12;
    $morningstarts_minutes = 0;
    $eveningends = 12;
    $eveningends_minutes = count($periods) - 1;
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

  $n_time_slots = get_n_time_slots();
  $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
  $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);
  
  // TABLE HEADER
  $thead = "<thead>\n";
  $header_inner = "<tr>\n";
  
  // We can display the table in two ways
  if ($times_along_top)
  {
    // with times along the top and rooms down the side
    $first_last_html = '<th class="first_last" style="width: ' . $first_last_width . '%">' . 
                       get_vocab('room') . "</th>\n";
    $header_inner .= $first_last_html;
    
    $column_width = get_main_column_width($n_time_slots);
    
    for ($s = $morning_slot_seconds;
         $s <= $evening_slot_seconds;
         $s += $resolution)
    {
      // Put the number of seconds since the start of the day (nominal, ignoring DST)
      // in a data attribute so that JavaScript can pick it up
      $header_inner .= "<th data-seconds=\"$s\" style=\"width: $column_width%\">";
      // We need the span so that we can apply some padding.   We can't apply it
      // to the <th> because that is used by jQuery.offset() in resizable bookings
      $header_inner .= "<span>";
      if ( $enable_periods )
      {
        $header_inner .= htmlspecialchars(period_name($s));
      }
      else
      {
        $header_inner .= hour_min($s);
      }
      $header_inner .= "</span>";
      $header_inner .= "</th>\n";
    }
    
    // next: line to display times on right side
    if (!empty($row_labels_both_sides))
    {
      $header_inner .= $first_last_html;
    }
  } // end "times_along_top" view (for the header)
  
  else
  {
    // the standard view, with rooms along the top and times down the side
    $first_last_html = '<th class="first_last" style="width: ' . $first_last_width . '%">' . 
                       ($enable_periods ? get_vocab('period') : get_vocab('time')) . "</th>\n";
    $header_inner .= $first_last_html;
  
    $column_width = get_main_column_width(count($rooms));
    
    foreach($rooms as $room)
    {
      $vars = array('view'  => 'week',
                    'year'  => $year,
                    'month' => $month,
                    'day'   => $day,
                    'area'  => $area_id,
                    'room'  => $room['id']);
                    
      $query = http_build_query($vars, '', '&'); 
      // Put the room_id in a data attribute so that JavaScript can pick it up        
      $header_inner .= "<th data-room=\"" . $room['id'] . "\" style=\"width: $column_width%\">" .
                       '<a href="index.php?' . htmlspecialchars($query) . '" ' .
                       'title="' . get_vocab("viewweek") . " &#10;&#10;" . $room['description'] . '">' .
                       htmlspecialchars($room['room_name']) . ($room['capacity'] > 0 ? ' (' . $room['capacity'] . ')' : '') . "</a></th>\n";
    }
  
    // next line to display times on right side
    if (!empty($row_labels_both_sides))
    {
      $header_inner .= $first_last_html;
    }
  }  // end standard view (for the header)
  
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
                    
      $room_cell_link = "index.php?" . htmlspecialchars(http_build_query($vars, '', '&')); 
      
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
        $query_vars = get_query_vars($area_id, $room['id'], $month, $day, $year, $s);

        // and then draw the cell
        if (!isset($today[$room['id']][$day][$s]))
        {
          $today[$room['id']][$day][$s] = array();
        }
        $tbody .= cell_html($today[$room['id']][$day][$s], $query_vars, $is_invalid[$s]);
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
      
      $vars = array('view'  => 'day',
                    'year'  => $year,
                    'month' => $month,
                    'day'   => $day,
                    'area'  => $area_id);
                    
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
      
      $tbody.= '<tr';
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
        $query_vars = get_query_vars($area_id, $room['id'], $month, $day, $year, $s);
        
        // and then draw the cell
        if (!isset($today[$room['id']][$day][$s]))
        {
          $today[$room['id']][$day][$s] = array();  // to avoid an undefined index NOTICE error
        }
        $tbody .= cell_html($today[$room['id']][$day][$s], $query_vars, $is_invalid);
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


function week_table_innerhtml($year, $month, $day, $area_id, $room_id, $timetohighlight=null)
{
  global $enable_periods, $periods;
  global $times_along_top, $row_labels_both_sides, $column_labels_both_ends;
  global $resolution, $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes;
  global $weekstarts, $strftime_format;
  global $first_last_width, $column_hidden_width, $hidden_days;

  // Check that we've got a valid, enabled room
  $room_name = get_room_name($room_id);
  
  if (is_null($room_name))
  {
    // No rooms have been created yet, or else they are all disabled
    // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
    return "<tbody data-empty=1><tr><td><h1>".get_vocab("no_rooms_for_area")."</h1></td></tr></tbody>";
  }
  
  // We have a valid room
  $num_of_days=7; // days in a week

  // ensure that $morningstarts_minutes defaults to zero if not set
  if (!isset($morningstarts_minutes))
  {
    $morningstarts_minutes = 0;
  }
  
  if ($enable_periods)
  {
    $resolution = 60;
    $morningstarts = 12;
    $morningstarts_minutes = 0;
    $eveningends = 12;
    $eveningends_minutes = count($periods) - 1;
  }
  
  // Calculate how many days to skip back to get to the start of the week
  $time = mktime(12, 0, 0, $month, $day, $year);
  $skipback = day_of_MRBS_week($time);
  $day_start_week = $day - $skipback;
  // We will use $day for links and $day_start_week for anything to do with showing the bookings,
  // because we want the booking display to start on the first day of the week (eg Sunday if $weekstarts is 0)
  // but we want to preserve the notion of the current day (or 'sticky day') when switching between pages
  
  // Define the start and end of each day of the week in a way which is not
  // affected by daylight saving...
  for ($j = 0; $j<=($num_of_days-1); $j++)
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

  for ($j = 0; $j<=($num_of_days-1) ; $j++)
  {
    $entries = get_entries_by_room($room_id, $start_first_slot[$j], $start_last_slot[$j] + $resolution);
    foreach ($entries as $entry)
    {
      map_add_booking($entry, $week_map[$room_id][$j], $start_first_slot[$j], $start_last_slot[$j]);
    }
  }
  unset($j);  // Just so that we pick up any accidental attempt to use it later
  
  // START DISPLAYING THE MAIN TABLE
  $n_time_slots = get_n_time_slots();
  $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
  $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

  // TABLE HEADER
  $thead = "<thead>\n";
  $header_inner = "<tr>\n";

  $dformat = "%a<br>" . $strftime_format['daymonth'];
  // If we've got a table with times along the top then put everything on the same line
  // (ie replace the <br> with a space).   It looks slightly better
  if ($times_along_top)
  {
    $dformat = preg_replace("/<br>/", " ", $dformat);
  }


  // We can display the table in two ways
  if ($times_along_top)
  {
    // with times along the top and days of the week down the side
    $first_last_html = '<th class="first_last" style="width: ' . $first_last_width . '%">' . 
                       get_vocab('date') . "</th>\n";
    $header_inner .= $first_last_html;
    
    $column_width = get_main_column_width($n_time_slots);
    
    for ($s = $morning_slot_seconds;
         $s <= $evening_slot_seconds;
         $s += $resolution)
    {
      // Put the seconds since the start of the day (nominal, not adjusted for DST)
      // into a data attribute so that it can be picked up by JavaScript
      $header_inner .= "<th data-seconds=\"$s\" style=\"width: $column_width%\">";
      // We need the span so that we can apply some padding.   We can't apply it
      // to the <th> because that is used by jQuery.offset() in resizable bookings
      $header_inner .= "<span>";
      if ( $enable_periods )
      {
        $header_inner .= htmlspecialchars(period_name($s));
      }
      else
      {
        $header_inner .= hour_min($s);
      }
      $header_inner .= "</span>";
      $header_inner .= "</th>\n";
    }
    // next: line to display times on right side
    if (!empty($row_labels_both_sides))
    {
      $header_inner .= $first_last_html;
    }
  } // end "times_along_top" view (for the header)

  else
  {
    // the standard view, with days along the top and times down the side
    $first_last_html = '<th class="first_last" style="width: ' . $first_last_width . '%">' . 
                       ($enable_periods ? get_vocab('period') : get_vocab('time')) . '</th>';
    $header_inner .= $first_last_html;
    
    $column_width = get_main_column_width($num_of_days, count($hidden_days));
    
    for ($j = 0; $j<=($num_of_days-1) ; $j++)
    {
      $t = mktime(12, 0, 0, $month, $day_start_week+$j, $year);
      $date = date('Y-m-d', $t);
    
      if (is_hidden_day(($j + $weekstarts) % 7))
      {
        // These days are to be hidden in the display (as they are hidden, just give the
        // day of the week in the header row
        $style = ($column_hidden_width == 0) ? 'display: none' : 'width: ' . $column_hidden_width . '%';
        $header_inner .= '<th class="hidden_day" style="' . $style . '">' .
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
                      
        $query = http_build_query($vars, '', '&');
        // Put the date into a data attribute so that it can be picked up by JavaScript
        $header_inner .= '<th data-date="' . $date . '" style="width: ' . $column_width . '%">' .
                         '<a href="index.php?' . htmlspecialchars($query) . '" title="' . get_vocab('viewday') . '">' .
                         utf8_strftime($dformat, $t) . "</a></th>\n";
      }
    }  // for ($j = 0 ...
    unset($j);  // Just so that we pick up any accidental attempt to use it later
    
    // next line to display times on right side
    if (!empty($row_labels_both_sides))
    {
      $header_inner .= $first_last_html;
    }
  }  // end standard view (for the header)

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
    for ($thisday = 0; $thisday<=($num_of_days-1); $thisday++)
    {
      if (is_hidden_day(($thisday + $weekstarts) % 7))
      {
        // These days are to be hidden in the display: don't display a row
        continue;
      }
    
      else
      {
        $tbody .= "<tr>\n";
      
        $wt = mktime( 12, 0, 0, $month, $day_start_week+$thisday, $year );
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
                      
        $day_cell_link = 'index.php?' . htmlspecialchars(http_build_query($vars, '', '&'));
                       
        $tbody .= day_cell_html($day_cell_text, $day_cell_link, $wdate);
        
        for ($s = $morning_slot_seconds;
             $s <= $evening_slot_seconds;
             $s += $resolution)
        {
          $is_invalid = $is_possibly_invalid[$thisday] && is_invalid_datetime(0, 0, $s, $wmonth, $wday, $wyear);
          // set up the query vars to be used for the link in the cell
          $query_vars = get_query_vars($area_id, $room_id, $wmonth, $wday, $wyear, $s);

          // and then draw the cell
          if (!isset($week_map[$room_id][$thisday][$s]))
          {
            $week_map[$room_id][$thisday][$s] = array();  // to avoid an undefined index NOTICE error
          }
          $tbody .= cell_html($week_map[$room_id][$thisday][$s], $query_vars, $is_invalid);
        }  // end looping through the time slots
        if ( false != $row_labels_both_sides )
        {
          $tbody .= day_cell_html($day_cell_text, $day_cell_link, $wdate);
        }
        $tbody .= "</tr>\n";
      }
    
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
      for ($thisday = 0; $thisday<=($num_of_days-1) ; $thisday++)
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
          $query_vars = get_query_vars($area_id, $room_id, $wmonth, $wday, $wyear, $s);
     
          // and then draw the cell
          if (!isset($week_map[$room_id][$thisday][$s]))
          {
            $week_map[$room_id][$thisday][$s] = array();  // to avoid an undefined index NOTICE error
          }
          $tbody .= cell_html($week_map[$room_id][$thisday][$s], $query_vars, $is_invalid);
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


function month_table_innerhtml($year, $month, $day, $area, $room)
{
  global $tbl_entry;
  global $weekstarts, $view_week_number, $show_plus_link, $monthly_view_entries_details;
  global $periods, $enable_periods, $morningstarts, $morningstarts_minutes;
  global $approval_enabled, $confirmation_enabled;
  global $is_private_field;
  global $user;
  
  // Check that we've got a valid, enabled room
  if (is_null(get_room_name($room)))
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
  
  if ($enable_periods)
  {
    $resolution = 60;
    $morningstarts = 12;
    $morningstarts_minutes = 0;
    $eveningends = 12;
    $eveningends_minutes = count($periods)-1;
  }
  
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
      $d[$day_num]["type"][]        = $entry['type'];
      $d[$day_num]["status"][]       = $entry['status'];
      $d[$day_num]["is_repeat"][]    = isset($entry['repeat_id']);
      $d[$day_num]["data"][]         = get_booking_summary($entry['start_time'],
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
      unset($vars['view']);
      
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
      $html .= '<a class="new_booking" href="edit_entry.php?' . htmlspecialchars($query) . "\">\n";
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
          if ($d[$cday]["status"][$i] & STATUS_PRIVATE)
          {
            $classes[] = 'private';
          }
          if ($approval_enabled && ($d[$cday]['status'][$i] & STATUS_AWAITING_APPROVAL))
          {
            $classes[] = 'awaiting_approval';
          }
          if ($confirmation_enabled && ($d[$cday]['status'][$i] & STATUS_TENTATIVE))
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
