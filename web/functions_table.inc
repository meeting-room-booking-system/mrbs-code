<?php

// $Id$

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
  //       repeat_id
  //       entry_id
  //       type
  //       entry_description
  //       entry_create_by
  //       status
  
  // $column is a column of the map of the screen that will be displayed
  // It looks like:
  //     $column[Time][n][id]
  //                     [is_repeat]
  //                     [is_multiday_start]  a boolean indicating if the booking stretches
  //                                          beyond the day start
  //                     [is_multiday_end]    a boolean indicating if the booking stretches
  //                                          beyond the day end
  //                     [color]
  //                     [data]
  //                     [long_descr]
  //                     [create_by]
  //                     [room_id]
  //                     [start_time]
  //                     [slots]
  //                     [status]
  
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
  global $is_private_field;
  
  $user = getUserName();
  if (is_private_event($row['status'] & STATUS_PRIVATE) &&
         !getWritable($row['entry_create_by'], $user, $row['room_id']))
  {
    $row['status'] |= STATUS_PRIVATE;   // Set the private bit
    if ($is_private_field['entry.name'])
    {
      $row['name']= "[".get_vocab('unavailable')."]";
    }
    if ($is_private_field['entry.description'])
    {
      $row['entry_description']= "[".get_vocab('unavailable')."]";
    }
  }
  else
  {
    $row['status'] &= ~STATUS_PRIVATE;  // Clear the private bit
  }

  $is_multiday_start = ($row['start_time'] < $am7);
  $is_multiday_end = ($row['end_time'] > ($pm7 + $resolution));
              
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
    $column[$time_t][$n]["id"] = $row['entry_id'];
    $column[$time_t][$n]["is_repeat"] = !empty($row['repeat_id']);
    $column[$time_t][$n]["is_multiday_start"] = $is_multiday_start;
    $column[$time_t][$n]["is_multiday_end"] = $is_multiday_end;
    $column[$time_t][$n]["status"] = $row['status'];
    $column[$time_t][$n]["color"] = $row['type'];
    $column[$time_t][$n]["start_time"] = utf8_strftime(hour_min_format(), $row['start_time']);
    $column[$time_t][$n]["slots"] = null;  // to avoid undefined index NOTICE errors
    // if it's a multiple booking also fill in the name and description
    if ($n > 0)
    {
      $column[$time_t][$n]["data"] = $row['name'];
      $column[$time_t][$n]["long_descr"] = $row['entry_description'];
      $column[$time_t][$n]["create_by"] = $row['entry_create_by'];
      $column[$time_t][$n]["room_id"] = $row['room_id'];
    }
    // otherwise just leave them blank (we'll fill in the first whole slot later)
    else
    {
      $column[$time_t][$n]["data"] = "";
      $column[$time_t][$n]["long_descr"] = "";
      $column[$time_t][$n]["create_by"] = "";
      $column[$time_t][$n]["room_id"] = "";
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
        $column[date($format, $end_t + $resolution)][0]["create_by"] = $column[$time_end_t][0]["create_by"];
        $column[date($format, $end_t + $resolution)][0]["room_id"] = $column[$time_end_t][0]["room_id"];
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
      // Find the first slot ($s) of the first booking
      $first_booking_id = $column[$time_start_t][0]["id"];
      $s = $start_t;
      // If you've got to the first slot of the day then that must be the
      // first slot of the first booking
      while ($s > $am7)
      {
        // Otherwise, step back one slot.
        $s -= $resolution;
        // If that slot contains the first booking, then step back again
        if (isset($column[date($format,$s)]))
        {
          foreach ($column[date($format,$s)] as $booking)
          {
            if ($booking["id"] == $first_booking_id)
            {
              continue 2;  // next iteration of the while loop
            }
          }
        }
        // If not, then we've stepped back one slot past the start of
        // the first booking, so step forward again and finish
        $s += $resolution;
        break;
      } // end while
      
      // Now we've found the time ($s) of the first slot of the first booking
      // we need to find its index ($i)
      foreach ($column[date($format,$s)] as $i => $booking)
      {
        if ($booking["id"] == $first_booking_id)
        {
          break;
        }
      }

      // Finally decrement the slot count for the first booking
      // no need to worry about count going < 1: the multiple booking display
      // does not use the slot count.
      $column[date($format,$s)][$i]["slots"]--;
      // and put the name and description in the multiply booked slot
      $column[$time_start_t][0]["data"] = $column[date($format,$s)][$i]["data"];
      $column[$time_start_t][0]["long_descr"] = $column[date($format,$s)][$i]["long_descr"];
      $column[$time_start_t][0]["create_by"] = $column[date($format,$s)][$i]["create_by"];
      $column[$time_start_t][0]["room_id"] = $column[date($format,$s)][$i]["room_id"];
    }
  }
 
  // now we've got all the information we can enter it in the first complete
  // slot for the booking (provided it's not a multiple booking slot)
  if (!isset($column[date($format,$first_slot)][1]["id"]))
  {
    $column[date($format,$first_slot)][0]["data"] = $row['name'];
    $column[date($format,$first_slot)][0]["long_descr"] = $row['entry_description'];
    $column[date($format,$first_slot)][0]["create_by"] = $row['entry_create_by']; 
    $column[date($format,$first_slot)][0]["room_id"] = $row['room_id']; 
    $column[date($format,$first_slot)][0]["slots"] = $n_slots;
  }

} // end function map_add_booking()



function cell_html($cell, $query_strings)
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
  //         [color]
  //         [data]
  //         [long_descr]
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
  
  // $query_strings is an array containg the query strings (or partial query strings) to be
  // appended to the link used for the cell.    It is indexed as follows:
  //    ['new_periods']   the string to be used for an empty cell if using periods
  //    ['new_times']     the string to be used for an empty cell if using times
  //    ['booking']       the string to be used for a full cell
    
  global $main_cell_height, $main_table_cell_border_width;
  global $area, $year, $month, $timetohighlight;
  global $enable_periods, $times_along_top, $show_plus_link;
  global $approval_enabled, $confirmation_enabled;
  
  $html = '';
  $user = getUserName();
  
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
    
    $row_height = $main_cell_height - (($n_bookings-1) * $main_table_cell_border_width);   // subtract the borders (first row has no top border)
    $row_height = $row_height/$n_bookings;  // split what's left between the bookings
    $row_height = (int) ceil($row_height);  // round up, so that (a) there's no empty space at the bottom
                                            // and (b) each stripe is at least 1 unit high
    for ($n=0; $n<$n_bookings; $n++)
    {
      $id          = $cell[$n]["id"];
      $is_repeat   = $cell[$n]["is_repeat"];
      $is_multiday_start = $cell[$n]["is_multiday_start"];
      $is_multiday_end   = $cell[$n]["is_multiday_end"];
      $status      = $cell[$n]["status"];
      $color       = $cell[$n]["color"];
      $descr       = htmlspecialchars($cell[$n]["data"]);
      $long_descr  = htmlspecialchars($cell[$n]["long_descr"]);
      $class = $color;
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
    $total_height = $n_bookings * $main_cell_height;
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
      $id          = $cell[$n]["id"];
      $is_repeat   = $cell[$n]["is_repeat"];
      $is_multiday_start = $cell[$n]["is_multiday_start"];
      $is_multiday_end   = $cell[$n]["is_multiday_end"];
      $status      = $cell[$n]["status"];
      $color       = $cell[$n]["color"];
      $descr       = htmlspecialchars($cell[$n]["start_time"] . " " . $cell[$n]["data"]);
      $long_descr  = htmlspecialchars($cell[$n]["long_descr"]);
      $class = $color;
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
      $html .= "<div class=\"celldiv slots1\">\n";     // we want clipping of overflow
      $html .= "<a href=\"view_entry.php?id=$id&amp;". $query_strings['booking'] . "\" title=\"$long_descr\">";
      $html .= ($is_repeat) ? "<img class=\"repeat_symbol\" src=\"images/repeat.png\" alt=\"" . get_vocab("series") . "\" title=\"" . get_vocab("series") . "\" width=\"10\" height=\"10\">" : '';
      $html .= "$descr</a>\n";
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
    if(isset($cell[0]["id"]))
    {       
      $id         = $cell[0]["id"];
      $is_repeat  = $cell[0]["is_repeat"];
      $is_multiday_start = $cell[0]["is_multiday_start"];
      $is_multiday_end   = $cell[0]["is_multiday_end"];
      $status     = $cell[0]["status"];
      $color      = $cell[0]["color"];
      $descr      = htmlspecialchars($cell[0]["data"]);
      $long_descr = htmlspecialchars($cell[0]["long_descr"]);
      $slots      = $cell[0]["slots"];
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
      if ($status & STATUS_PRIVATE)
      {
        $c .= " private";
      }
      if ($approval_enabled && ($status & STATUS_AWAITING_APPROVAL))
      {
        $c .= " awaiting_approval";
      }
      if ($confirmation_enabled && ($status & STATUS_TENTATIVE))
      {
        $c .= " tentative";
      }
      if ($is_multiday_start)
      {
        $c .= " multiday_start";
      }
      if ($is_multiday_end)
      {
        $c .= " multiday_end";
      }
      // Add a class to bookings that this user is allowed to edit so that the
      // JavaScript can turn them into resizable bookings
      if (getWritable($cell[0]['create_by'], $user, $cell[0]['room_id']))
      {
        $c .= " writable";
        if ($is_repeat)
        {
          $c .= " series";
        }
      }
    }
    else
    {
      $c = "new";
    }
    
    // Don't put in a <td> cell if the slot is booked and there's no description.
    // This would mean that it's the second or subsequent slot of a booking and so the
    // <td> for the first slot would have had a rowspan that extended the cell down for
    // the number of slots of the booking.

    if (!(isset($id) && ($descr == ""))) 
    {
      $html .= tdcell($c, $slots);

      // If the room isn't booked then allow it to be booked
      if (!isset($id))
      {
        $html .= "<div class=\"celldiv slots1\">\n";  // a bookable slot is only one unit high
        $html .= "<a href=\"edit_entry.php?" . 
                 (($enable_periods) ? $query_strings['new_periods'] : $query_strings['new_times']) . 
                 "\">\n";
        if ($show_plus_link)
        {
          $html .= "<img src=\"images/new.gif\" alt=\"New\" width=\"10\" height=\"10\">\n";
        }
        $html .= "</a>\n";
        $html .= "</div>\n";
      }
      else                 // if it is booked then show the booking
      {
        $html .= "<div data-id=\"$id\" class=\"celldiv slots" .
                 (($times_along_top) ? "1" : $slots) .
                 "\">\n";
        $html .= "<a href=\"view_entry.php?id=$id&amp;". $query_strings['booking'] . "\" title=\"$long_descr\">";
        $html .= ($is_repeat) ? "<img class=\"repeat_symbol $c\" src=\"images/repeat.png\" alt=\"" . get_vocab("series") . "\" title=\"" . get_vocab("series") . "\" width=\"10\" height=\"10\">" : '';
        $html .= "$descr</a>\n";
        $html .= "</div>\n";
      }
      $html .= "</td>\n";
    }
  }
  
  return $html;
}  // end function draw_cell


// Draw a time cell to be used in the first and last columns of the day and week views
//    $t                 the timestamp for the start of the slot
//    $time_t            the time converted to HHMM format without leading zeros
//    $time_t_stripped   a stripped version of the time for use with periods
//    $slot_seconds      the number of seconds since the start of the day (nominal - not adjusted for DST)
//    $url               the url to form the basis of the link in the time cell
function time_cell_html($t, $time_t, $time_t_stripped, $slot_seconds, $url)
{
  global $enable_periods, $periods;
  
  $html = '';
  
  $html .= tdcell("row_labels", 1, array('name' => 'seconds', 'value' => $slot_seconds));
  $html .= "<div class=\"celldiv slots1\">\n";
  if ( $enable_periods )
  {
    
    $html .= "<a href=\"$url\"  title=\""
             . get_vocab("highlight_line") . "\">"
             . $periods[$time_t_stripped] . "</a>\n";
  }
  else
  {
    $html .= "<a href=\"$url\" title=\""
             . get_vocab("highlight_line") . "\">"
             . utf8_strftime(hour_min_format(),$t) . "</a>\n";
  }
  $html .= "</div></td>\n";
  
  return $html;
}

// Draw a room cell to be used in the first and last columns of the day view
//    $row     contains the room details; comes from an SQL query
//    $link    the href to be used for the link
function room_cell_html($row, $link)
{
  $html = '';
  $html .= tdcell("row_labels", 1, array('name' => 'room', 'value' => $row['id']));
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
  $html .= tdcell("row_labels", 1, array('name' => 'date', 'value' => $date));
  $html .= "<div class=\"celldiv slots1\">\n";
  $html .= "<a href=\"$link\" title=\"" . get_vocab("viewday") . "\">$text</a>\n";
  $html .= "</div></td>\n";
  return $html;
}


// Output a start table cell tag <td> with color class.
// $colclass is an entry type (A-J), zebra stripes if
// empty or row_highlight if highlighted.
// $slots is the number of time slots high that the cell should be
//
// $data is an optional third parameter which if set passes the data name and value
// for use in the data- attribute.  It is an associative array with two elements,
// data['name'] and data['value'].
function tdcell($colclass, $slots)
{
  global $times_along_top;
  
  $html = '';
  if (func_num_args() > 2)
  {
    $data = func_get_arg(2);
  }
  
  $html .= "<td class=\"$colclass\"";
  if ($slots > 1)
  // No need to output more HTML than necessary
  {
    $html .= " " . (($times_along_top) ? "colspan" : "rowspan") . "=\"$slots\"";
  }
  if (isset($data))
  {
    $html .= " data-" . $data['name'] . "=\"" . $data['value'] . "\"";
  }
  $html .= ">\n";
  
  return $html;
}


// Returns TRUE if the time $hm1 is before $hm2
// $hm1 and $hm2 are associative arrays indexed by 'h' (hour) and 'm' (minute)
function hm_before($hm1, $hm2)
{
  return ($hm1['h'] < $hm2['h']) ||
         (($hm1['h'] == $hm2['h']) && ($hm1['m'] < $hm2['m']));
}


// Returns TRUE if $evening_ends is on the day after $morning_starts
function day_past_midnight()
{
  global $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes;
  
  return hm_before(array('h' => $eveningends, 'm' => $eveningends_minutes),
                   array('h' => $morningstarts, 'm' => $morningstarts_minutes));
}


// Gets the UNIX timestamp for the start of the first slot on the given day
function get_start_first_slot($month, $day, $year)
{
  global $morningstarts, $morningstarts_minutes;
  
  $t = mktime($morningstarts, $morningstarts_minutes, 0,
              $month, $day, $year, is_dst($month,$day,$year,$morningstarts));
  return $t;
}


// Gets the UNIX timestamp for the start of the last slot on the given day
function get_start_last_slot($month, $day, $year)
{
  global $eveningends, $eveningends_minutes;
  
  // Work out if $evening_ends is really on the next day
  if (day_past_midnight())
  {
    $day++;
  }
  $t = mktime($eveningends, $eveningends_minutes, 0,
              $month, $day, $year, is_dst($month,$day,$year,$eveningends));
  return $t;
}


function get_query_strings($area, $room, $month, $day, $year, $t, $time_t_stripped)
{
  global $morningstarts, $morningstarts_minutes;
  
  $query_strings = array();
  
  $hour = date("H", $t);
  $minute = date("i", $t);
  // check to see if the time is really on the next day
  if (hm_before(array('h' => $hour, 'm' => $minute),
                array('h' => $morningstarts, 'm' => $morningstarts_minutes)))
  {
    $day++;
    if (!checkdate($month, $day, $year))
    {
      $day = 1;
      $month++;
      if (!checkdate($month, $day, $year))
      {
        $month = 1;
        $year++;
      }
    }
  }
  $query_strings['new_periods'] = "area=$area&amp;room=$room&amp;period=$time_t_stripped&amp;year=$year&amp;month=$month&amp;day=$day";
  $query_strings['new_times']   = "area=$area&amp;room=$room&amp;hour=$hour&amp;minute=$minute&amp;year=$year&amp;month=$month&amp;day=$day";
  $query_strings['booking']     = "area=$area&amp;day=$day&amp;month=$month&amp;year=$year";
 
  return $query_strings;
}


function day_table_innerhtml($day, $month, $year, $room, $area, $timetohighlight=NULL)
{
  global $tbl_entry, $tbl_room, $tbl_area;
  global $enable_periods, $periods;
  global $times_along_top, $row_labels_both_sides, $column_labels_both_ends;
  global $resolution, $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes;
  
  $sql = "SELECT COUNT(*)
            FROM $tbl_room R, $tbl_area A
           WHERE R.area_id=$area
             AND R.area_id = A.id
             AND R.disabled = 0
             AND A.disabled = 0";
           
  $n_rooms = sql_query1($sql);

  if ($n_rooms < 0)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(FALSE, get_vocab("fatal_db_error"));
  }
  elseif ($n_rooms == 0)
  {
    // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
    return "<tbody data-empty=1><tr><td><h1>".get_vocab("no_rooms_for_area")."</h1></td></tr></tbody>";
  }

  $format = ($enable_periods) ? "i" : "Gi";
  
  // form the room parameter for use in query strings.    We want to preserve room information
  // if possible when switching between views
  $room_param = (empty($room)) ? "" : "&amp;room=$room";
  
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

  // Define the start and end of each day in a way which is not affected by
  // daylight saving...
  // dst_change:
  // -1 => no change
  //  0 => entering DST
  //  1 => leaving DST
  $dst_change = is_dst($month, $day, $year);
  // Work out if $evening_ends is really on the next day
  $day_increment = (day_past_midnight()) ? 1 : 0;
  $am7 = get_start_first_slot($month, $day, $year);
  $pm7 = get_start_last_slot($month, $day, $year);
            
  // We want to build an array containing all the data we want to show
  // and then spit it out. 

  // Get all appointments for today in the area that we care about.  We
  // only get the data for enabled rooms.  (If the whole area is disabled
  // then the main table won't get displayed anyway).

  // Note: The predicate clause 'start_time <= ...' is an equivalent but simpler
  // form of the original which had 3 BETWEEN parts. It selects all entries which
  // occur on or cross the current day.
  $sql = "SELECT R.id AS room_id, start_time, end_time, name, repeat_id,
                 E.id AS entry_id, type,
                 E.description AS entry_description, status,
                 E.create_by AS entry_create_by
            FROM $tbl_entry E, $tbl_room R
           WHERE E.room_id = R.id
             AND R.area_id = $area
             AND R.disabled = 0
             AND start_time <= $pm7 AND end_time > $am7
        ORDER BY start_time";   // necessary so that multiple bookings appear in the right order

  $res = sql_query($sql);
  if (! $res)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(FALSE, get_vocab("fatal_db_error"));
  }

  $today = array();

  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    // Each row we've got here is an appointment.
    //  row['room_id'] = Room ID
    //  row['start_time'] = start time
    //  row['end_time'] = end time
    //  row['name'] = short description
    //  row['repeat_id'] = repeat type
    //  row['entry_id'] = id of this booking
    //  row['type'] = type (internal/external)
    //  row['entry_description'] = description
    //  row['entry_create_by'] = Creator/owner of entry
    //  row['status'] = Status code of the entry
  
    map_add_booking($row, $today[$row['room_id']][$day], $am7, $pm7, $format);

  }

  // We need to know what all the rooms are called, so we can show them all.
  // Pull the data from the db and store it. Convienently we can print the room
  // headings and capacities at the same time
  $sql = "SELECT R.room_name, R.capacity, R.id, R.description
            FROM $tbl_room R, $tbl_area A
           WHERE R.area_id=$area
             AND R.area_id = A.id
             AND R.disabled = 0
             AND A.disabled = 0
           ORDER BY sort_key";

  $res = sql_query($sql);

  // It might be that there are no rooms defined for this area.
  // If there are none then show an error and don't bother doing anything
  // else
  if (! $res)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(FALSE, get_vocab("fatal_db_error"));
  }

  
  ($dst_change != -1) ? $j = 1 : $j = 0;
  
  // TABLE HEADER
  $thead = "<thead>\n";
  $header_inner = "<tr>\n";
  
  
  // We can display the table in two ways
  if ($times_along_top)
  {
    // with times along the top and rooms down the side
    $morning = ($morningstarts * 60) + $morningstarts_minutes;   // minutes
    $evening = (($eveningends + ($day_increment*24)) * 60) + $eveningends_minutes;  // minutes
    if (day_past_midnight())
    {
      $evening += 24*60;
    }
    $start_difference = ($evening - $morning) * 60;    // seconds
    $n_slots = ($start_difference/$resolution) + 1;
    $column_width = (int)(95 / $n_slots);
    $header_inner .= "<th class=\"first_last\">" . get_vocab("room") . ":</th>";
    for (
         $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day+$j, $year),
           $slot_seconds = $morning * 60;
         $t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day+$j+$day_increment, $year);
         $t += $resolution,
           $slot_seconds += $resolution
        )
    {
      // Put the number of seconds since the start of the day (nominal, ignoring DST)
      // in a data attribure so that JavaScript can pick it up
      $header_inner .= "<th data-seconds=\"$slot_seconds\" style=\"width: $column_width%\">";
      // We need the span so that we can apply some padding.   We can't apply it
      // to the <th> because that is used by jQuery.offset() in resizable bookings
      $header_inner .= "<span>";
      if ( $enable_periods )
      {
        // convert timestamps to HHMM format without leading zeros
        $time_t = date($format, $t);
        // and get a stripped version of the time for use with periods
        $time_t_stripped = preg_replace( "/^0/", "", $time_t );
        $header_inner .= $periods[$time_t_stripped];
      }
      else
      {
        $header_inner .= utf8_strftime(hour_min_format(),$t);
      }
      $header_inner .= "</span>";
      $header_inner .= "</th>\n";
    }
    // next: line to display times on right side
    if ( FALSE != $row_labels_both_sides )
    {
      $header_inner .= "<th class=\"first_last\">" . get_vocab("room") . ":</th>";
    }
  } // end "times_along_top" view (for the header)
  
  else
  {
    // the standard view, with rooms along the top and times down the side
    $header_inner .= "<th class=\"first_last\">" . ($enable_periods ? get_vocab("period") : get_vocab("time")) . ":</th>";
  
    $column_width = (int)(95 / sql_count($res));
    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
    {
      // Put the room_id in a data attrribute so that JavaScript can pick it up
      $header_inner .= "<th data-room=\"" . $row['id'] . "\" style=\"width: $column_width%\">
                        <a href=\"week.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$area&amp;room=".$row['id']."\"
                        title=\"" . get_vocab("viewweek") . " &#10;&#10;".$row['description']."\">" .
                        htmlspecialchars($row['room_name']) . ($row['capacity'] > 0 ? "(".$row['capacity'].")" : "") . "</a></th>";
      $rooms[] = $row['id'];
    }
  
    // next line to display times on right side
    if ( FALSE != $row_labels_both_sides )
    {
      $header_inner .= "<th class=\"first_last\">" . ( $enable_periods  ? get_vocab("period") : get_vocab("time") ) . ":</th>";
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
  
  // URL for highlighting a time. Don't use REQUEST_URI or you will get
  // the timetohighlight parameter duplicated each time you click.
  $base_url="day.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$area$room_param";
  
  
   
  $row_class = "even_row";
  
  // We can display the table in two ways
  if ($times_along_top)
  {
    // with times along the top and rooms down the side
    for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++, $row_class = ($row_class == "even_row")?"odd_row":"even_row")
    {
      $tbody .= "<tr class=\"$row_class\">\n";
      $room_id = $row['id']; 
      $room_cell_link = "week.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$area&amp;room=$room_id";
      $tbody .= room_cell_html($row, $room_cell_link);
      for (
           $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day+$j, $year);
           $t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day+$j+$day_increment, $year);
           $t += $resolution
          )
      {
        // convert timestamps to HHMM format without leading zeros
        $time_t = date($format, $t);
        // and get a stripped version of the time for use with periods
        $time_t_stripped = preg_replace( "/^0/", "", $time_t );
        
        // set up the query strings to be used for the link in the cell
        $query_strings = get_query_strings($area, $room_id, $month, $day, $year, $t, $time_t_stripped);

        // and then draw the cell
        if (!isset($today[$room_id][$day][$time_t]))
        {
          $today[$room_id][$day][$time_t] = array();  // to avoid an undefined index NOTICE error
        }   
        $tbody .= cell_html($today[$room_id][$day][$time_t], $query_strings);
      }  // end for (looping through the times)
      if ( FALSE != $row_labels_both_sides )
      {
        $tbody .= room_cell_html($row, $room_cell_link);
      }
      $tbody .= "</tr>\n";
    }  // end for (looping through the rooms)
  }  // end "times_along_top" view (for the body)
  
  else
  {
    // the standard view, with rooms along the top and times down the side
    for (
         $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day+$j, $year),
           $slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
         $t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day+$j+$day_increment, $year);
         $t += $resolution,
           $slot_seconds += $resolution,
           $row_class = ($row_class == "even_row") ? "odd_row" : "even_row"
        )
    {
      // convert timestamps to HHMM format without leading zeros
      $time_t = date($format, $t);
      // and get a stripped version of the time for use with periods
      $time_t_stripped = preg_replace( "/^0/", "", $time_t );
  
      // Show the time linked to the URL for highlighting that time
      $class = $row_class;
      if (isset($timetohighlight) && ($time_t == $timetohighlight))
      {
        $class .= " row_highlight";
        $url = $base_url;
      }
      else
      {
        $url = $base_url . "&amp;timetohighlight=$time_t";
      }
      $tbody .= "<tr class=\"$class\">\n";
      $tbody .= time_cell_html($t, $time_t, $time_t_stripped, $slot_seconds, $url);
  
      // Loop through the list of rooms we have for this area
      while (list($key, $room_id) = each($rooms))
      {
        // set up the query strings to be used for the link in the cell
        $query_strings = get_query_strings($area, $room_id, $month, $day, $year, $t, $time_t_stripped);
        
        // and then draw the cell
        if (!isset($today[$room_id][$day][$time_t]))
        {
          $today[$room_id][$day][$time_t] = array();  // to avoid an undefined index NOTICE error
        }
        $tbody .= cell_html($today[$room_id][$day][$time_t], $query_strings);
      }
      
      // next lines to display times on right side
      if ( FALSE != $row_labels_both_sides )
      {
        $tbody .= time_cell_html($t, $time_t, $time_t_stripped, $slot_seconds, $url);
      }
  
      $tbody .= "</tr>\n";
      reset($rooms);
    }
  }  // end standard view (for the body)
  
  $tbody .= "</tbody>\n";
  
  return $thead . $tfoot . $tbody;
}


function week_table_innerhtml($day, $month, $year, $room, $area, $timetohighlight=NULL)
{
  global $tbl_entry, $tbl_room, $tbl_area;
  global $enable_periods, $periods;
  global $times_along_top, $row_labels_both_sides, $column_labels_both_ends;
  global $resolution, $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes;
  global $weekstarts, $strftime_format;

  // Check that we've gor a valid, enabled room
  $sql = "SELECT COUNT(*)
            FROM $tbl_room R, $tbl_area A
           WHERE R.id=$room
             AND R.area_id=A.id
             AND R.disabled=0
             AND A.disabled = 0";
             
  if (sql_query1($sql) != 1)
  {
    // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
    return "<tbody data-empty=1><tr><td><h1>".get_vocab("no_rooms_for_area")."</h1></td></tr></tbody>";
  }
  
  $num_of_days=7; // days in a week
  
  $format = ($enable_periods) ? "i" : "Gi";

  // ensure that $morningstarts_minutes defaults to zero if not set
  if (!isset($morningstarts_minutes))
  {
    $morningstarts_minutes = 0;
  }
  
  if( $enable_periods )
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
  
  // Work out if $evening_ends is really on the next day
  $day_increment = (day_past_midnight()) ? 1 : 0;
  
  // Define the start and end of each day of the week in a way which is not
  // affected by daylight saving...
  for ($j = 0; $j<=($num_of_days-1); $j++)
  {
    // are we entering or leaving daylight saving
    // dst_change:
    // -1 => no change
    //  0 => entering DST
    //  1 => leaving DST
    $dst_change[$j] = is_dst($month, $day_start_week+$j, $year);
    $am7[$j] = get_start_first_slot($month, $day_start_week+$j, $year);
    $pm7[$j] = get_start_last_slot($month, $day_start_week+$j, $year);
  }

  // Get all appointments for this week in the room that we care about.
  //
  // row['room_id'] = Room ID
  // row['start_time'] = Start time
  // row['end_time'] = End time
  // row['type'] = Entry type
  // row['name'] = Entry name (brief description)
  // row['entry_id'] = Entry ID
  // row['entry_description'] = Complete description
  // row['status'] = status code
  // row['entry_create_by'] = User who created entry
  // This data will be retrieved day-by-day

  $week_map = array();

  for ($j = 0; $j<=($num_of_days-1) ; $j++)
  {
    $sql = "SELECT room_id, start_time, end_time, type, name, status, repeat_id,
                   id AS entry_id, description AS entry_description,
                   create_by AS entry_create_by
              FROM $tbl_entry
             WHERE room_id = $room
               AND start_time <= $pm7[$j] AND end_time > $am7[$j]
          ORDER BY start_time";   // necessary so that multiple bookings appear in the right order

    // Each row returned from the query is a meeting. Build an array of the
    // form:  $week_map[room][weekday][slot][x], where x = id, color, data, long_desc.
    // [slot] is based at 000 (HHMM) for midnight, but only slots within
    // the hours of interest (morningstarts : eveningends) are filled in.
    // [id], [data] and [long_desc] are only filled in when the meeting
    // should be labeled,  which is once for each meeting on each weekday.
    // Note: weekday here is relative to the $weekstarts configuration variable.
    // If 0, then weekday=0 means Sunday. If 1, weekday=0 means Monday.
  
    $res = sql_query($sql);
    if (! $res)
    {
      trigger_error(sql_error(), E_USER_WARNING);
      fatal_error(TRUE, get_vocab("fatal_db_error"));
    }
    else
    {
      for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
      {
        map_add_booking($row, $week_map[$room][$j], $am7[$j], $pm7[$j], $format);
      }
    }
  } 

  // START DISPLAYING THE MAIN TABLE
  // if the first day of the week to be displayed contains a DST change then
  // move to the next day to get the hours in the day.
  ( $dst_change[0] != -1 ) ? $j = 1 : $j = 0;


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
    $morning = ($morningstarts*60) + $morningstarts_minutes;   // minutes
    $evening = ($eveningends*60) + $eveningends_minutes;       // minutes
    if (day_past_midnight())
    {
      $evening += 24*60;
    }
    $start_difference = ($evening - $morning) * 60;    // seconds
    $n_slots = ($start_difference/$resolution) + 1;
    $column_width = (int)(95 / $n_slots);
    $header_inner .= "<th class=\"first_last\">" . get_vocab("date") . ":</th>";
    for (
         $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day_start_week+$j, $year),
           $slot_seconds = $morning * 60;
         $t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day_start_week+$j+$day_increment, $year);
         $t += $resolution,
           $slot_seconds += $resolution
        )
    {
      // Put the seconds since the start of the day (nominal, not adjusted for DST)
      // into a data attribute so that it can be picked up by JavaScript
      $header_inner .= "<th data-seconds=\"$slot_seconds\" style=\"width: $column_width%\">";
      // We need the span so that we can apply some padding.   We can't apply it
      // to the <th> because that is used by jQuery.offset() in resizable bookings
      $header_inner .= "<span>";
      if ( $enable_periods )
      {
        // convert timestamps to HHMM format without leading zeros
        $time_t = date($format, $t);
        // and get a stripped version of the time for use with periods
        $time_t_stripped = preg_replace( "/^0/", "", $time_t );
        $header_inner .= $periods[$time_t_stripped];
      }
      else
      {
        $header_inner .= utf8_strftime(hour_min_format(),$t);
      }
      $header_inner .= "</span>";
      $header_inner .= "</th>\n";
    }
    // next: line to display times on right side
    if ( FALSE != $row_labels_both_sides )
    {
      $header_inner .= "<th class=\"first_last\">" . get_vocab("date") . ":</th>";
    } 
  } // end "times_along_top" view (for the header)

  else
  {
    // the standard view, with days along the top and times down the side
    $header_inner .= "<th class=\"first_last\">".($enable_periods ? get_vocab("period") : get_vocab("time")).":</th>";
    for ($j = 0; $j<=($num_of_days-1) ; $j++)
    {
      $t = mktime( 12, 0, 0, $month, $day_start_week+$j, $year);
      $date = date('Y-n-d', $t);
    
      if (is_hidden_day(($j + $weekstarts) % 7))
      {
        // These days are to be hidden in the display (as they are hidden, just give the
        // day of the week in the header row 
        $header_inner .= "<th class=\"hidden_day\">" . utf8_strftime($strftime_format['dayname_cal'], $t) . "</th>\n";
      }
  
      else  
      {
        // Put the date into a data attribute so that it can be picked up by JavaScript
        $header_inner .= "<th data-date=\"$date\"><a href=\"day.php?year=" . strftime("%Y", $t) . 
                         "&amp;month=" . strftime("%m", $t) . "&amp;day=" . strftime("%d", $t) . 
                         "&amp;area=$area\" title=\"" . get_vocab("viewday") . "\">" .
                         utf8_strftime($dformat, $t) . "</a></th>\n";
      }
    }
    // next line to display times on right side
    if ( FALSE != $row_labels_both_sides )
    {
      $header_inner .= "<th class=\"first_last\">" .
                       ( $enable_periods  ? get_vocab("period") : get_vocab("time") ) .
                       ":</th>";
    }
  }  // end standard view (for the header)

  $header_inner .= "</tr>\n";
  $thead .= $header_inner;
  $thead .= "</thead>\n";

  // Now repeat the header in a footer if required
  $tfoot = ($column_labels_both_ends) ? "<tfoot>\n$header_inner</tfoot>\n" : '';

  // TABLE BODY LISTING BOOKINGS
  $tbody = "<tbody>\n";

  // URL for highlighting a time. Don't use REQUEST_URI or you will get
  // the timetohighlight parameter duplicated each time you click.
  $base_url="week.php?year=$year&amp;month=$month&amp;day=$day&amp;area=$area&amp;room=$room";
  $row_class = "even_row";

  // We can display the table in two ways
  if ($times_along_top)
  {
    // with times along the top and days of the week down the side
    // See note above: weekday==0 is day $weekstarts, not necessarily Sunday.
    for ($thisday = 0; $thisday<=($num_of_days-1) ; $thisday++, $row_class = ($row_class == "even_row")?"odd_row":"even_row")
    {
      if (is_hidden_day(($thisday + $weekstarts) % 7))
      {
        // These days are to be hidden in the display: don't display a row
        // Toggle the row class back to keep it in sequence
        $row_class = ($row_class == "even_row")?"odd_row":"even_row";
        continue;
      }
    
      else
      {
        $tbody .= "<tr class=\"$row_class\">\n";
      
        $wt = mktime( 12, 0, 0, $month, $day_start_week+$thisday, $year );
        $wday = date("d", $wt);
        $wmonth = date("m", $wt);
        $wyear = date("Y", $wt);
        $wdate = date('Y-n-d', $wt);
      
        $day_cell_text = utf8_strftime($dformat, $wt);
        $day_cell_link = "day.php?year=" . strftime("%Y", $wt) . 
                         "&amp;month=" . strftime("%m", $wt) . 
                         "&amp;day=" . strftime("%d", $wt) . 
                         "&amp;area=$area";
                       
        $tbody .= day_cell_html($day_cell_text, $day_cell_link, $wdate);
        for (
             $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day_start_week+$j, $year);
             $t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day_start_week+$j+$day_increment, $year);
             $t += $resolution
            )
        {
          // use hour:minute format
          $time_t = date($format, $t);
          // and get a stripped version for use with periods
          $time_t_stripped = preg_replace( "/^0/", "", $time_t );
        
          // set up the query strings to be used for the link in the cell
          $query_strings = get_query_strings($area, $room, $wmonth, $wday, $wyear, $t, $time_t_stripped);

          // and then draw the cell
          if (!isset($week_map[$room][$thisday][$time_t]))
          {
            $week_map[$room][$thisday][$time_t] = array();  // to avoid an undefined index NOTICE error
          }
          $tbody .= cell_html($week_map[$room][$thisday][$time_t], $query_strings);
        }  // end looping through the time slots
        if ( FALSE != $row_labels_both_sides )
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
    for (
         $t = mktime($morningstarts, $morningstarts_minutes, 0, $month, $day_start_week+$j, $year),
           $slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
         $t <= mktime($eveningends, $eveningends_minutes, 0, $month, $day_start_week+$j+$day_increment, $year);
         $t += $resolution,
           $slot_seconds += $resolution,
           $row_class = ($row_class == "even_row") ? "odd_row" : "even_row"
    )
    {
      // use hour:minute format
      $time_t = date($format, $t);
      // and get a stripped version for use with periods
      $time_t_stripped = preg_replace( "/^0/", "", $time_t );
    
      // calculate hour and minute (needed for links)
      $hour = date("H",$t);
      $minute  = date("i",$t);
    
      // Show the time linked to the URL for highlighting that time:
      $class = $row_class;
      if (isset($timetohighlight) && ($time_t == $timetohighlight))
      {
        $class .= " row_highlight";
        $url = $base_url;
      }
      else
      {
        $url = $base_url . "&amp;timetohighlight=$time_t";
      }
      $tbody.= "<tr class=\"$class\">";
      $tbody .= time_cell_html($t, $time_t, $time_t_stripped, $slot_seconds, $url);
  
  
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
          // set up the query strings to be used for the link in the cell
          $wt = mktime( 12, 0, 0, $month, $day_start_week+$thisday, $year );
          $wday = date("d", $wt);
          $wmonth = date("m", $wt);
          $wyear = date("Y", $wt);
        
          $query_strings = array();
          $query_strings['new_periods'] = "room=$room&amp;area=$area&amp;period=$time_t_stripped&amp;year=$wyear&amp;month=$wmonth&amp;day=$wday";
          $query_strings['new_times']   = "room=$room&amp;area=$area&amp;hour=$hour&amp;minute=$minute&amp;year=$wyear&amp;month=$wmonth&amp;day=$wday";
          $query_strings['booking']     = "area=$area&amp;day=$wday&amp;month=$wmonth&amp;year=$wyear";
        
          // and then draw the cell
          if (!isset($week_map[$room][$thisday][$time_t]))
          {
            $week_map[$room][$thisday][$time_t] = array();  // to avoid an undefined index NOTICE error
          }
          $tbody .= cell_html($week_map[$room][$thisday][$time_t], $query_strings);
        }
  
      }    // for loop
  
      // next lines to display times on right side
      if ( FALSE != $row_labels_both_sides )
        {
          $tbody .= time_cell_html($t, $time_t, $time_t_stripped, $slot_seconds, $url);
        }
  
      $tbody .= "</tr>\n";
    }
  }  // end standard view (for the body)
  $tbody .= "</tbody>\n";
  
  return $thead . $tfoot . $tbody;
}