<?php
declare(strict_types=1);
namespace MRBS\Calendar;


use MRBS\DateTime;
use MRBS\Exception;
use function MRBS\datetime_format;
use function MRBS\escape_html;
use function MRBS\format_iso_date;
use function MRBS\get_entries_by_area;
use function MRBS\get_rooms;
use function MRBS\get_vocab;
use function MRBS\multisite;

class CalendarMultimonthMultiroom extends Calendar
{
  private $map;
  private $n_months;

  public function __construct(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id)
  {
    global $year_start, $resolution;

    $this->view = $view;
    $this->view_all = $view_all;
    $this->year = $year;
    $this->month = $month;
    $this->day = $day;
    $this->area_id = $area_id;
    $this->room_id = $room_id;

    if ($view === 'year')
    {
      $this->n_months = MONTHS_PER_YEAR;
    }
    else
    {
      throw new Exception("Invalid view: '$view'");
    }

    // Get the entries
    $start_date = (new DateTime())->setDate($this->year, $this->month, 1);
    $start_date->setMonthYearStart($year_start);
    $start_date->setStartFirstSlot();

    $end_date = clone $start_date;
    $end_date->modify('+' . $this->n_months . ' month');
    $end_date->modify('-1 day');
    $end_date->setEndLastSlot();

    // Get the data.  It's much quicker to do a single SQL query getting all the
    // entries for the interval in one go, rather than doing a query for each day.
    $entries = get_entries_by_area($this->area_id, $start_date, $end_date);

    // We want to build an array containing all the data we want to show and then spit it out.
    $this->map = new Map($start_date, $end_date, $resolution);
    $this->map->addEntries($entries);
  }


  public function innerHTML(): string
  {
    global $column_labels_both_ends;

    // Check to see whether there are any rooms in the area
    $rooms = get_rooms($this->area_id);

    if (count($rooms) == 0)
    {
      // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
      return "<tbody data-empty=1><tr><td><h1>" . get_vocab("no_rooms_for_area") . "</h1></td></tr></tbody>";
    }

    // Table header
    $thead = '<thead';
    // TODO: get_slots() for JavaScript
    $thead .= ">\n";
    $header_row = $this->headerRowHTML();
    $thead .= $header_row;
    $thead .= "</thead>\n";

    // Table body
    $tbody = "<tbody>\n";

    foreach ($rooms as $room)
    {
      $tbody .= $this->bodyRowHTML($room);
    }

    $tbody .= "<tbody>\n";

    // Table footer
    $tfoot = ($column_labels_both_ends) ? "<tfoot>\n$header_row</tfoot>\n" : '';

    return $thead . $tfoot . $tbody;
  }


  private function bodyRowHTML(array $room): string
  {
    global $row_labels_both_sides, $year_start;
    global $morningstarts, $morningstarts_minutes, $resolution;

    $room_link_vars = [
      'view'      => $this->view,
      'view_all'  => 0,
      'page_date' => format_iso_date($this->year, $this->month, $this->day),
      'area'      => $this->area_id
    ];

    $html = "<tr>\n";
    $room_link_vars['room'] = $room['id'];
    $row_label = $this->roomCellHTML($room, $room_link_vars);
    $html .= $row_label;

    $date = (new DateTime())->setDate($this->year, $this->month, $this->day);
    $date->setMonthYearStart($year_start);

    // Get the time slots
    $n_time_slots = self::getNTimeSlots();
    $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
    $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

    // The variables for the link query string
    $vars = [
      'view' => 'month',
      'view_all' => 0,
      'area' => $this->area_id,
      'room' => $room['id']
    ];

    $j = 0;
    for ($i=0; $i<$this->n_months; $i++)
    {
      $html .= "<td>\n";
      $vars['page_date'] = $date->getISODate();
      $link = 'index.php?' . http_build_query($vars, '', '&');
      $link = multisite($link);
      $html .= '<a href="' . escape_html($link) . '">';

      $days_in_month = $date->getDaysInMonth();
      for ($d=1; $d<=$days_in_month; $d++)
      {
        $s = $morning_slot_seconds;
        $slots = 0;
        while ($s <= $evening_slot_seconds)
        {
          $this_slot = $this->map->slot($room['id'], $j, $s);
          // If this is the first slot of the day, and we've held over an entry, and
          // this slot is empty or is the start of a different entry, then record the
          // held entry.
          if (($s == $morning_slot_seconds) && isset($held_entry))
          {
            if (empty($this_slot) || ($this_slot[0]['id'] != $held_entry['id']))
            {
              $held_slot_is_complete = true;
            }
            elseif ($this_slot[0]['n_slots'] < $n_time_slots)
            {
              $n = $this_slot[0]['n_slots'];
              $held_entry['n_slots'] += $n;
              $held_slot_is_complete = true;
              $s = $s + ($n * $resolution);
              $this_slot = $this->map->slot($room['id'], $j, $s);
            }

            if (!empty($held_slot_is_complete))
            {
              $text = $held_entry['name'];
              $classes = $this->getEntryClasses($held_entry);
              $html .= $this->flexDivHTML($held_entry['n_slots'], $classes, $text, $text);
              $slots = 0;
              unset($held_entry);
              unset($held_slot_is_complete);
            }
          }
          if (empty($this_slot))
          {
            // This is just a continuation of the previous free slot, so
            // increment the slot count and proceed to the next slot.
            $n = 1;
            $slots++;
          }
          else
          {
            // We've found a booking.
            // If we've been accumulating a free slot, then record it.
            if ($slots > 0)
            {
              $html .= $this->flexDivHTML($slots, 'free');
              $slots = 0;
            }
            $this_entry = $this_slot[0];
            $n = $this_entry['n_slots'];
            // If this is the last booking of the day, and it's not the last day of the month, then
            // hold this booking in case it continues the next day.
            if ((($s + (($n-1) * $resolution)) == $evening_slot_seconds) && ($d != $days_in_month))
            {
              // If we've already got a held entry and this entry has the same id, then
              // increase the number of slots by this one's.
              if (isset($held_entry) && ($held_entry['id'] == $this_entry['id']))
              {
                $held_entry['n_slots'] += $n;
              }
              // Otherwise, create a held entry.
              else
              {
                $held_entry = $this_entry;
              }
            }
            // Otherwise record the booking.
            else
            {
              $text = $this_entry['name'];
              $classes = $this->getEntryClasses($this_entry);
              $html .= $this->flexDivHTML($n, $classes, $text, $text);
              $slots = 0;
            }
          }
          $s = $s + ($n * $resolution);
        }

        // We've got to the end of the day, so record the free slot, if there is one.
        if ($slots > 0)
        {
          $html .= $this->flexDivHTML($slots, 'free');
        }

        $j++;
      }
      $html .= '</a>';
      $html .= "</td>\n";
      $date->modifyMonthsNoOverflow(1, true);
    }

    if ($row_labels_both_sides)
    {
      $html .= $row_label;
    }
    $html .= "</tr>\n";

    return $html;
  }


  private function headerRowHTML(string $label='') : string
  {
    global $datetime_formats, $row_labels_both_sides, $year_start;

    $html = "<tr>\n";

    // The left-hand header column
    $first_last_html = '<th class="first_last">' . escape_html($label) . "</th>\n";
    $html .= $first_last_html;

    // The main header cells
    $date = (new DateTime())->setDate($this->year, $this->month, $this->day);
    $date->setMonthYearStart($year_start);
    // The variables for the link query string
    $vars = [
      'view' => 'month',
      'view_all' => $this->view_all,
      'area' => $this->area_id,
      'room' => $this->room_id
    ];

    for ($i=0; $i<$this->n_months; $i++)
    {
      $vars['page_date'] = $date->getISODate();
      $link = 'index.php?' . http_build_query($vars, '', '&');
      $link = multisite($link);
      $month_name = datetime_format($datetime_formats['month_name_year_view'], $date->getTimestamp());
      $html .= '<th><a href="' . escape_html($link) . '">' . escape_html($month_name) . "</a></th>\n";
      $date->modifyMonthsNoOverflow(1, true);
    }

    // The right-hand header column, if required
    if ($row_labels_both_sides)
    {
      $html .= $first_last_html;
    }

    $html .= "</tr>\n";

    return $html;
  }

}
