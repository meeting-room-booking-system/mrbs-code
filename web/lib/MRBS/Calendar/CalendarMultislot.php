<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use MRBS\DateTime;
use MRBS\Period;
use MRBS\Periods;
use function MRBS\escape_html;
use function MRBS\get_vocab;
use function MRBS\getWritable;
use function MRBS\hm_before;
use function MRBS\hour_min;
use function MRBS\is_book_admin;
use function MRBS\multisite;
use function MRBS\period_index_nominal;
use function MRBS\session;

abstract class CalendarMultislot extends Calendar
{
  protected $timetohighlight;


  // $s is nominal seconds
  protected function getQueryVars(int $room, int $month, int $day, int $year, int $s) : array
  {
    global $morningstarts, $morningstarts_minutes;

    $result = [];

    // check to see if the time is really on the next day
    $date = getdate(mktime(0, 0, $s, $month, $day, $year));
    if (hm_before($date, ['hours' => $morningstarts, 'minutes' => $morningstarts_minutes]))
    {
      $date['hours'] += 24;
    }
    $hour = $date['hours'];
    $minute = $date['minutes'];
    $period = period_index_nominal($s);

    $vars = [
      'view'  => $this->view,
      'year'  => $year,
      'month' => $month,
      'day'   => $day,
      'area'  => $this->area_id
    ];

    $result['booking']     = $vars;
    $result['new_periods'] = array_merge($vars, ['room' => $room, 'period' => $period]);
    $result['new_times']   = array_merge($vars, ['room' => $room, 'hour' => $hour, 'minute' => $minute]);

    return $result;
  }


  protected function tdHTML(array $cell, array $query_vars, bool $is_invalid = false) : string
  {
    // Draws a single cell in the main table of the day and week views
    //
    // $cell is an array of entries that occupy that cell.  There can be none, one or many
    // bookings in a cell.  If there are no bookings, then a blank cell is drawn with a link
    // to the edit entry form.  If there is one booking, then the booking is shown in that
    // cell.  If there is more than one booking, then all the bookings are shown.

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

    // Don't put in a <td> cell if the slot contains a single booking whose n_slots is NULL.
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
        $classes = array_merge($classes, $this->getDateClasses($date));
      }

      // If there's no booking, or if there are multiple bookings, then make the slot one unit long
      $slots = (count($cell) == 1) ? $cell[0]['n_slots'] : 1;

      $html .= $this->tdOpeningTagHTML($classes, $slots);

      // If the room isn't booked, then allow it to be booked
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

          $html .= '<a href="' . escape_html(multisite("edit_entry.php?$query")) . '"' .
            ' aria-label="' . escape_html(get_vocab('create_new_booking')) . "\">";
          if ($show_plus_link)
          {
            $html .= "<img src=\"images/new.gif\" alt=\"New\" width=\"10\" height=\"10\">";
          }
          $html .= "</a>";
        }
      }
      else  // if it is booked, then show the booking
      {
        foreach ($cell as $booking)
        {
          $vars = $query_vars['booking'];
          $vars['id'] = $booking['id'];
          $query = http_build_query($vars, '', '&');

          // We have to wrap the booking in a <div> because we want the booking itself to be given
          // an absolute position, and we can't use position relative on a <td> in IE11 and below.
          // We also need the bookings in a container because jQuery UI resizable has problems
          // with border-box (see https://stackoverflow.com/questions/18344272). And we need
          // border-box for the bookings because we are using padding on the bookings and we want
          // 'width: 100%' and 'height: 100%' to fill the table-cell with the entire booking
          // including content.

          $classes = $this->getEntryClasses($booking);
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
          $html .= '<a href="' . escape_html(multisite("view_entry.php?$query")) . '"' .
            ' title="' . escape_html($booking['description'] ?? '') . '"' .
            ' class="' . $booking['type'] . '"' .
            ' data-id="' . $booking['id'] . '"' .
            ' data-type="' . $booking['type'] . '">';
          $html .= escape_html($booking['name']) . '</a>';
          $html .= "</div>";
        }
      }

      $html .= "</td>\n";
    }

    return $html;
  }


  // Output a start table cell tag <td> with class of $classes.
  // $classes can be either a string or an array of classes
  // empty or row_highlight if highlighted.
  // $slots is the number of time slots high that the cell should be
  //
  // $data is an optional third parameter which if set passes an
  // associative array of name-value pairs to be used in data attributes
  private function tdOpeningTagHTML(array $classes, int $slots, ?array $data=null) : string
  {
    global $times_along_top;

    $html = '<td';

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


  // Draw a time cell to be used in the first and last columns of the day and week views
  //    $s                 the number of seconds since the start of the day (nominal - not adjusted for DST)
  //    $url               the url to form the basis of the link in the time cell
  function tbodyThTimeCellHTML(int $s, string $url) : string
  {
    global $enable_periods, $resolution, $area;

    $html = '';

    $html .= "<th data-seconds=\"$s\">";
    $html .= '<a href="' . escape_html($url) . '"  title="' . get_vocab("highlight_line") . "\">";

    if ($enable_periods)
    {
      $periods = Periods::getForArea($area);
      $html .= escape_html($periods->offsetGetByNominalSeconds($s)->name);
    }
    else
    {
      $html .= escape_html($this->timeslotText($s, $resolution));
    }

    $html .= "</a></th>\n";

    return $html;
  }


  protected function theadThTimeCellsHTML(int $start, int $end, int $increment) : string
  {
    global $enable_periods, $area;

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
        $periods = Periods::getForArea($area);
        $html .= escape_html($periods->offsetGetByNominalSeconds($s)->name);
      }
      else
      {
        $html .= escape_html($this->timeslotText($s, $increment));
      }
      $html .= "</span>";
      $html .= "</th>\n";
    }

    return $html;
  }


  private function timeslotText(int $s, int $resolution) : string
  {
    global $show_slot_endtime;

    $result = hour_min($s);

    if ($show_slot_endtime)
    {
      $result .= '-' . hour_min($s + $resolution);
    }

    return $result;
  }
}
