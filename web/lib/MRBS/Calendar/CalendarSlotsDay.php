<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use MRBS\DateTime;
use MRBS\Rooms;
use function MRBS\cell_html;
use function MRBS\escape_html;
use function MRBS\get_entries_by_area;
use function MRBS\get_n_time_slots;
use function MRBS\get_slots;
use function MRBS\get_start_first_slot;
use function MRBS\get_start_last_slot;
use function MRBS\get_vocab;
use function MRBS\is_invalid_datetime;
use function MRBS\is_possibly_invalid;
use function MRBS\multisite;
use function MRBS\room_cell_html;
use function MRBS\time_cell_html;

class CalendarSlotsDay extends CalendarSlots
{
  private $kiosk;


  public function __construct(string $view, int $year, int $month, int $day, int $area_id, int $room_id, ?int $timetohighlight=null, ?string $kiosk=null)
  {
    $this->view = $view;
    $this->year = $year;
    $this->month = $month;
    $this->day = $day;
    $this->area_id = $area_id;
    $this->room_id = $room_id;
    $this->timetohighlight = $timetohighlight;
    $this->kiosk = $kiosk;
  }

  public function innerHTML() : string
  {
    global $enable_periods;
    global $times_along_top, $row_labels_both_sides, $column_labels_both_ends;
    global $resolution, $morningstarts, $morningstarts_minutes;

    if ($this->kiosk === 'room')
    {
      $rooms = new Rooms(null, $this->room_id);
    }
    else
    {
      $rooms = new Rooms($this->area_id);
    }

    if ($rooms->countVisible() == 0)
    {
      // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
      return "<tbody data-empty=1><tr><td><h1>" . get_vocab("no_rooms_for_area") . "</h1></td></tr></tbody>";
    }

    $start_first_slot = get_start_first_slot($this->month, $this->day, $this->year);
    $start_last_slot = get_start_last_slot($this->month, $this->day, $this->year);
    // Keep a count of the number of slots at the start of the day that we're
    // not showing (will only be relevant in kiosk mode).
    $skipped_slots = 0;

    // If we are in kiosk mode we are not interested in what has already happened.
    // But if we are in periods mode we don't know when the periods occur, so show them all.
    if (isset($this->kiosk) && !$enable_periods)
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

    $start_date = (new DateTime())->setTimestamp($start_first_slot);
    $end_date = (new DateTime())->setTimestamp($start_last_slot + $resolution);

    $entries = get_entries_by_area($this->area_id, $start_date, $end_date);

    // We want to build a map containing all the data we want to show
    // and then spit it out.
    $map = new Map($start_date, $end_date, $resolution);
    $map->addEntries($entries);

    $n_time_slots = get_n_time_slots() - $skipped_slots;
    $morning_slot_seconds = ((($morningstarts * 60) + $morningstarts_minutes) * 60) + ($skipped_slots * $resolution);
    $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);


    // TABLE HEADER
    $thead = '<thead';

    $slots = get_slots($this->month, $this->day, $this->year);
    if (isset($slots))
    {
      // Remove the skipped slots from the start of the first day's array
      for ($i=0; $i<$skipped_slots; $i++)
      {
        array_shift($slots[0]);
      }
      // Add some data to enable the JavaScript to draw the timeline
      $thead .= ' data-slots="' . escape_html(json_encode($slots)) . '"';
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
      $header_inner .= $this->timesHeaderCellsHTML($morning_slot_seconds, $evening_slot_seconds, $resolution);
    }
    else
    {
      $vars = array('view'     => 'week',
        'view_all' => 0,
        'year'     => $this->year,
        'month'    => $this->month,
        'day'      => $this->day,
        'area'     => $this->area_id);

      $header_inner .= $this->roomsHeaderCellsHTML($rooms, $vars);
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
        if ($room->isDisabled() || !$room->isVisible())
        {
          continue;
        }

        $tbody .= "<tr>\n";

        $vars = array('view'     => 'week',
          'view_all' => 0,
          'year'     => $this->year,
          'month'    => $this->month,
          'day'      => $this->day,
          'area'     => $this->area_id,
          'room'     => $room->id);

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
            $is_invalid[$s] = $is_possibly_invalid && is_invalid_datetime(0, 0, $s, $this->month, $this->day, $this->year);
          }
          // set up the query vars to be used for the link in the cell
          $query_vars = $this->getQueryVars($room->id, $this->month, $this->day, $this->year, $s);

          // and then draw the cell
          $tbody .= cell_html($map->slot($room->id, 0, $s), $query_vars, $is_invalid[$s]);
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
          'year' => $this->year,
          'month' => $this->month,
          'day' => $this->day,
          'area' => $this->area_id
        );

        if (isset($room_id))
        {
          $vars['room'] = $room_id;
        }

        if (isset($this->timetohighlight) && ($s == $this->timetohighlight))
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
        $is_invalid = $is_possibly_invalid && is_invalid_datetime(0, 0, $s, $this->month, $this->day, $this->year);
        // Loop through the list of rooms we have for this area
        foreach ($rooms as $room)
        {
          if ($room->isDisabled() || !$room->isVisible())
          {
            continue;
          }

          // set up the query vars to be used for the link in the cell
          $query_vars = $this->getQueryVars($room->id, $this->month, $this->day, $this->year, $s);
          $tbody .= cell_html($map->slot($room->id, 0, $s), $query_vars, $is_invalid);
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


  private function roomsHeaderCellsHTML(Rooms $rooms, array $vars) : string
  {
    $html = '';

    foreach($rooms as $room)
    {
      if ($room->isDisabled() || !$room->isVisible())
      {
        continue;
      }
      $vars['room'] = $room->id;
      $html .= room_cell_html($room, $vars);
    }

    return $html;
  }

}
