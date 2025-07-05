<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use MRBS\DateTime;
use function MRBS\cell_html;
use function MRBS\datetime_format;
use function MRBS\day_of_MRBS_week;
use function MRBS\escape_html;
use function MRBS\get_date_classes;
use function MRBS\get_entries_by_room;
use function MRBS\get_n_time_slots;
use function MRBS\get_room_name;
use function MRBS\get_slots;
use function MRBS\get_start_first_slot;
use function MRBS\get_start_last_slot;
use function MRBS\get_times_header_cells;
use function MRBS\get_vocab;
use function MRBS\is_invalid_datetime;
use function MRBS\is_possibly_invalid;
use function MRBS\is_visible;
use function MRBS\multiday_header_rows;
use function MRBS\multisite;
use function MRBS\time_cell_html;

class CalendarSlotsWeek extends CalendarSlots
{
  private $view_all;


  public function __construct(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id, ?int $timetohighlight=null)
  {
    $this->view = $view;
    $this->view_all = $view_all;
    $this->year = $year;
    $this->month = $month;
    $this->day = $day;
    $this->area_id = $area_id;
    $this->room_id = $room_id;
    $this->timetohighlight = $timetohighlight;
  }


  public function innerHTML(): string
  {
    global $enable_periods;
    global $times_along_top, $row_labels_both_sides, $column_labels_both_ends;
    global $resolution, $morningstarts, $morningstarts_minutes;
    global $weekstarts, $datetime_formats;

    // Check that we've got a valid, enabled room
    $room_name = get_room_name($this->room_id);

    if (is_null($room_name) || (!is_visible($this->room_id)))
    {
      // No rooms have been created yet, or else they are all disabled
      // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
      return "<tbody data-empty=1><tr><td><h1>".get_vocab("no_rooms_for_area")."</h1></td></tr></tbody>";
    }

    // We have a valid room
    // Calculate how many days to skip back to get to the start of the week
    $time = mktime(12, 0, 0, $this->month, $this->day, $this->year);
    $skipback = day_of_MRBS_week($time);
    $day_start_week = $this->day - $skipback;
    // We will use $day for links and $day_start_week for anything to do with showing the bookings,
    // because we want the booking display to start on the first day of the week (eg Sunday if $weekstarts is 0)
    // but we want to preserve the notion of the current day (or 'sticky day') when switching between pages

    // Define the start and end of each day of the week in a way which is not
    // affected by daylight saving...
    for ($j = 0; $j < DAYS_PER_WEEK; $j++)
    {
      $start_first_slot[$j] = get_start_first_slot($this->month, $day_start_week+$j, $this->year);
      $start_last_slot[$j] = get_start_last_slot($this->month, $day_start_week+$j, $this->year);
      // Work out whether there's a possibility that a time slot is invalid,
      // in other words whether the booking day includes a transition into DST.
      // If we know that there's a transition into DST then some of the slots are
      // going to be invalid.   Knowing whether or not there are possibly invalid slots
      // saves us bothering to do the detailed calculations of which slots are invalid.
      $is_possibly_invalid[$j] = !$enable_periods && is_possibly_invalid($start_first_slot[$j], $start_last_slot[$j]);
    }
    unset($j);  // Just so that we pick up any accidental attempt to use it later

    $start_date = (new DateTime())->setTimestamp($start_first_slot[0]);
    $end_date = (new DateTime())->setTimestamp($start_last_slot[DAYS_PER_WEEK - 1] + $resolution);

    // Get the data.  It's much quicker to do a single SQL query getting all the
    // entries for the interval in one go, rather than doing a query for each day.
    $entries = get_entries_by_room($this->room_id, $start_date, $end_date);

    $map = new Map($start_date, $end_date, $resolution);
    $map->addEntries($entries);

    // START DISPLAYING THE MAIN TABLE
    $n_time_slots = get_n_time_slots();
    $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
    $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

    // TABLE HEADER
    $thead = '<thead';

    $slots = get_slots($this->month, $day_start_week, $this->year, DAYS_PER_WEEK);
    if (isset($slots))
    {
      // Add some data to enable the JavaScript to draw the timeline
      $thead .= ' data-slots="' . escape_html(json_encode($slots)) . '"';
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
      $header_inner_rows = multiday_header_rows($this->view, $this->view_all, $this->year, $this->month, $day_start_week, $this->area_id, $this->room_id, DAYS_PER_WEEK, $weekstarts, $label);
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
      for ($j = 0, $date = clone $start_date; $j < DAYS_PER_WEEK; $j++, $date->modify('+1 day'))
      {
        if ($date->isHiddenDay())
        {
          // These days are to be hidden in the display: don't display a row
          continue;
        }

        $tbody .= "<tr>\n";

        $day_cell_text = datetime_format($format, $date->getTimestamp());

        $vars = array('view'      => 'day',
          'view_all'  => $this->view_all,
          'page_date' => $date->getISODate(),
          'area'      => $this->area_id,
          'room'      => $this->room_id);

        $day_cell_link = 'index.php?' . http_build_query($vars, '', '&');
        $day_cell_link = multisite($day_cell_link);
        $row_label = $this->dayCellHTML($day_cell_text, $day_cell_link, $date);
        $tbody .= $row_label;

        for ($s = $morning_slot_seconds;
             $s <= $evening_slot_seconds;
             $s += $resolution)
        {
          $is_invalid = $is_possibly_invalid[$j] && is_invalid_datetime(0, 0, $s, $date->getMonth(), $date->getDay(), $date->getYear());
          // set up the query vars to be used for the link in the cell
          $query_vars = $this->getQueryVars($this->room_id, $date->getMonth(), $date->getDay(), $date->getYear(), $s);
          // and then draw the cell
          $tbody .= cell_html($map->slot($this->room_id, $j, $s), $query_vars, $is_invalid);
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
          'view_all' => $this->view_all,
          'year'     => $this->year,
          'month'    => $this->month,
          'day'      => $this->day,
          'area'     => $this->area_id,
          'room'     => $this->room_id);

        if (isset($this->timetohighlight) && ($s == $this->timetohighlight))
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
        for ($j = 0, $date = clone $start_date; $j < DAYS_PER_WEEK; $j++, $date->modify('+1 day'))
        {
          if ($date->isHiddenDay())
          {
            // These days are to be hidden in the display
            continue;
          }

          // Set up the query vars to be used for the link in the cell.
          $cell_day = $date->getDay();
          $cell_month = $date->getMonth();
          $cell_year = $date->getYear();
          $is_invalid = $is_possibly_invalid[$j] && is_invalid_datetime(0, 0, $s, $cell_month, $cell_day, $cell_year);
          $query_vars = $this->getQueryVars($this->room_id, $cell_month, $cell_day, $cell_year, $s);

          // and then draw the cell
          $tbody .= cell_html($map->slot($this->room_id, $j, $s), $query_vars, $is_invalid);
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


  // Draw a day cell to be used in the header rows/columns of the week view
  //    $text     contains the date, formatted as a string (not escaped - allowed to contain HTML tags)
  //    $link     the href to be used for the link
  //    $date     the date
  private function dayCellHTML(string $text, string $link, DateTime $date) : string
  {
    $html = '';
    // Put the date into a data attribute so that it can be picked up by JavaScript
    $html .= '<th data-date="' . escape_html($date->getISODate()) . '"';

    // Add classes for weekends and holidays
    $classes = get_date_classes($date);
    if (!empty($classes))
    {
      $html .= ' class="' . implode(' ', $classes) . '"';
    }

    $html .= '>';
    $html .= '<a href="' . escape_html($link) . '"' .
      ' title="' . escape_html(get_vocab("viewday")) . '">';
    $html .= $text;  // allowed to contain HTML tags - do not escape
    $html .= '</a>';
    $html .= "</th>\n";
    return $html;
  }
}
