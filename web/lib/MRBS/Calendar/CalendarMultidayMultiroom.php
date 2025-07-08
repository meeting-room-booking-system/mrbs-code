<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use MRBS\DateTime;
use function MRBS\day_of_MRBS_week;
use function MRBS\escape_html;
use function MRBS\format_iso_date;
use function MRBS\get_date_classes;
use function MRBS\get_end_last_slot;
use function MRBS\get_entries_by_area;
use function MRBS\get_entry_classes;
use function MRBS\get_n_time_slots;
use function MRBS\get_rooms;
use function MRBS\get_slots;
use function MRBS\get_start_first_slot;
use function MRBS\get_vocab;
use function MRBS\multiday_header_rows;
use function MRBS\multisite;
use function MRBS\room_cell_html;

class CalendarMultidayMultiroom extends Calendar
{
  public function __construct(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id)
  {
    $this->view = $view;
    $this->view_all = $view_all;
    $this->year = $year;
    $this->month = $month;
    $this->day = $day;
    $this->area_id = $area_id;
    $this->room_id = $room_id;
  }


  // TODO: Handle the case where there is more than one booking per slot
  public function innerHTML(): string
  {
    global $row_labels_both_sides, $column_labels_both_ends;
    global $weekstarts;
    global $resolution, $morningstarts, $morningstarts_minutes;
    global $view_all_always_go_to_day_view;

    // It's theoretically possible to display a transposed table with rooms along the top and days
    // down the side.  However, it doesn't seem a very useful display and so hasn't yet been implemented.
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

    $rooms = get_rooms($this->area_id);
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
    $time = mktime(12, 0, 0, $this->month, $this->day, $this->year);
    switch ($this->view)
    {
      case 'week':
        $skipback = day_of_MRBS_week($time);
        $day_start_interval = $this->day - $skipback;
        $n_days = DAYS_PER_WEEK;
        $start_dow = $weekstarts;
        break;
      case 'month':
        $day_start_interval = 1;
        $n_days = (int) date('t', $time);
        $start_dow = (int) date('N', mktime(12, 0, 0, $this->month, 1, $this->year));
        break;
      default:
        trigger_error("Unsupported view '$this->view'", E_USER_WARNING);
        break;
    }

    // Get the time slots
    $n_time_slots = get_n_time_slots();
    $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
    $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

    $start_date = (new DateTime())->setTimestamp(get_start_first_slot($this->month, $day_start_interval, $this->year));
    $end_date = (new DateTime())->setTimestamp(get_end_last_slot($this->month, $day_start_interval + $n_days-1, $this->year));

    // Get the data.  It's much quicker to do a single SQL query getting all the
    // entries for the interval in one go, rather than doing a query for each day.
    $entries = get_entries_by_area($this->area_id, $start_date, $end_date);

    // We want to build an array containing all the data we want to show and then spit it out.
    $map = new Map($start_date, $end_date, $resolution);
    $map->addEntries($entries);

    // TABLE HEADER
    $thead = '<thead';

    $slots = get_slots($this->month, $day_start_interval, $this->year, $n_days, true);
    if (isset($slots))
    {
      // Add some data to enable the JavaScript to draw the timeline
      $thead .= ' data-slots="' . escape_html(json_encode($slots)) . '"';
      $thead .= ' data-timeline-vertical="' . (($days_along_top) ? 'true' : 'false') . '"';
      $thead .= ' data-timeline-full="true"';
    }

    $thead .= ">\n";

    if ($days_along_top)
    {
      $header_inner_rows = multiday_header_rows($this->view, $this->view_all, $this->year, $this->month, $day_start_interval, $this->area_id, $this->room_id, $n_days, $start_dow);
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

    $room_link_vars = [
      'view'      => $this->view,
      'view_all'  => 0,
      'page_date' => format_iso_date($this->year, $this->month, $this->day),
      'area'      => $this->area_id
    ];

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

        for ($j = 0, $date = clone $start_date; $j < $n_days; $j++, $date->modify('+1 day'))
        {
          if ($date->isHiddenDay())
          {
            continue;
          }

          // Add a classes for weekends and classes
          $classes = get_date_classes($date);

          $tbody .= '<td';
          if (!empty($classes))
          {
            $tbody .= ' class="' . implode(' ', $classes) . '"';
          }
          $tbody .= '>';
          $vars = [
            'view_all'  => $this->view_all,
            'page_date' => $date->getISODate(),
            'area'      => $this->area_id,
            'room'      => $room['id']
          ];

          // If there is more than one slot per day, then it can be very difficult to pick
          // out an individual one, which could be just one pixel wide, so we go to the
          // day view first where it's easier to see what you are doing.  Otherwise, we go
          // direct to edit_entry.php if the slot is free, or view_entry.php if it is not.
          // Note: the structure of the cell, with a single link and multiple flex divs,
          // only allows us to direct to the booking if there's only one slot per day.
          if ($view_all_always_go_to_day_view || ($n_time_slots > 1))
          {
            $page = 'index.php';
            $vars['view'] = 'day';
          }
          else
          {
            $vars['view'] = $this->view;
            $this_slot = $map->slot($room_id, $j, $morning_slot_seconds);
            if (empty($this_slot))
            {
              $page = 'edit_entry.php';
            }
            else
            {
              $page = 'view_entry.php';
              $vars['id'] = $this_slot[0]['id'];
            }
          }

          $link = "$page?" . http_build_query($vars, '', '&');
          $link = multisite($link);
          $tbody .= '<a href="' . escape_html($link) . "\">\n";
          $s = $morning_slot_seconds;
          $slots = 0;
          while ($s <= $evening_slot_seconds)
          {
            $this_slot = $map->slot($room_id, $j, $s);
            if (!empty($this_slot))
            {
              if ($slots > 0)
              {
                $tbody .= $this->flexDivHTML($slots, 'free');
              }
              $this_entry = $this_slot[0];
              $n =    $this_entry['n_slots'];
              $text = $this_entry['name'];
              $classes = get_entry_classes($this_entry);
              $tbody .= $this->flexDivHTML($n, $classes, $text, $text);
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
            $tbody .= $this->flexDivHTML($slots, 'free');
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


  // Returns the HTML for a booking, or a free set of slots
  //    $slots    The number of slots occupied
  //    $classes  A scalar or array giving the class or classes to be used in the class attribute
  //    $title    The value of the title attribute
  //    $text     The value of the text to be used in the div
  private function flexDivHTML(int $slots, $classes, ?string $title=null, ?string $text=null) : string
  {
    $result = "<div style=\"flex: $slots\"";

    if (isset($classes))
    {
      $value = (is_array($classes)) ? implode(' ', $classes) : $classes;
      $result .= ' class="' . escape_html($value) . '"';
    }

    if (isset($title) && ($title !== ''))
    {
      $result .= ' title="' . escape_html($title) . '"';
    }

    $result .= '>';

    if (isset($text) && ($text !== ''))
    {
      $result .= escape_html($text);
    }

    $result .= '</div>';

    return $result;
  }

}
