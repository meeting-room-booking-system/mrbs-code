<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use MRBS\DateTime;
use function MRBS\datetime_format;
use function MRBS\escape_html;
use function MRBS\get_entries_by_room;
use function MRBS\multisite;

class CalendarMultimonthOneRoom extends CalendarMultimonth
{

  public function __construct($view, $view_all, $year, $month, $day, $area_id, $room_id)
  {
    global $resolution;

    parent::__construct($view, $view_all, $year, $month, $day, $area_id, $room_id);

    // Get the entries.  It's much quicker to do a single SQL query getting all the
    // entries for the interval in one go, rather than doing a query for each day.
    $entries = get_entries_by_room($this->room_id, $this->start_date, $this->end_date);

    // We want to build an array containing all the data we want to show and then spit it out.
    $this->map = new Map($this->start_date, $this->end_date, $resolution);
    $this->map->addEntries($entries);
  }

  public function innerHTML(): string
  {
    global $column_labels_both_ends, $row_labels_both_sides, $year_start, $datetime_formats;

    // Table header
    $thead = '<thead';
    // TODO: get_slots() for JavaScript
    $thead .= ">\n";
    $header_row = $this->headerRowHTML();
    $thead .= $header_row;
    $thead .= "</thead>\n";

    // Table body
    $tbody = $this->bodyHTML();

    // Table footer
    $tfoot = ($column_labels_both_ends) ? "<tfoot>\n$header_row</tfoot>\n" : '';

    return $thead . $tfoot . $tbody;
  }


  private function bodyHTML(): string
  {
    global $year_start, $datetime_formats, $row_labels_both_sides;
    global $morningstarts, $morningstarts_minutes, $resolution;

    $html = "<tbody>\n";

    // Get the time slots
    $n_time_slots = self::getNTimeSlots();
    $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
    $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

    // The variables for the link query string
    $vars = [
      'view_all' => $this->view_all,
      'area' => $this->area_id,
      'room' => $this->room_id
    ];

    $date = (new DateTime())->setDate($this->year, $this->month, 1);  // Set to first day of month
    $date->setMonthYearStart($year_start);

    $d = 0; // Need to keep track of the day in the Calendar interval (zero indexed)

    for ($i=0; $i<$this->n_months; $i++)
    {
      $html .= "<tr>\n";
      $vars['page_date'] = $date->getISODate();
      $vars['view'] = 'month';
      $link = 'index.php?' . http_build_query($vars, '', '&');
      $link = multisite($link);
      $month_name = datetime_format($datetime_formats['month_name_year_view'], $date->getTimestamp());
      $first_last_html = '<th><a href="' . escape_html($link) . '">' . escape_html($month_name) . "</a></th>\n";
      $html .= $first_last_html;

      for ($j=1; $j<=$date->getDaysInMonth(); $j++)
      {
        $date->setDay($j);
        $html .= "<td>";
        $vars['page_date'] = $date->getISODate();
        $vars['view'] = 'day';
        $link = 'index.php?' . http_build_query($vars, '', '&');
        $link = multisite($link);
        $html .= '<a href="' . escape_html($link) . '">';

        // Clear the FlexDiv at the beginning of the day (they don't cross day boundaries)
        unset($flex_div);
        $s = $morning_slot_seconds;

        // Loop through the slots in the day
        while ($s <= $evening_slot_seconds)
        {
          // Get the entry for this slot
          $this_slot = $this->map->slot($this->room_id, $d, $s);
          // Start a FlexDiv if we haven't got one
          if (!isset($flex_div))
          {
            $flex_div = new FlexDiv($this_slot[0]['id'] ?? null);
            // If it's a booking, set the properties
            if (!empty($this_slot))
            {
              $this_entry = $this_slot[0];
              $flex_div->setClasses($this->getEntryClasses($this_entry));
              $flex_div->setLength($this_entry['n_slots']);
              $flex_div->setName($this_entry['name']);
            }
            // Work out how many slots to advance
            $n = $flex_div->getLength();
          }
          // Otherwise, look to see whether this is a continuation of the stored entry,
          // or else a change, in which case output the stored entry and reset.
          else
          {
            // Another free slot
            if (empty($this_slot) && !isset($flex_div->id))
            {
              $n = 1;
              $flex_div->addLength($n);
            }
            // A continuation of an existing booking
            elseif (!empty($this_slot) && isset($flex_div->id) && ($flex_div->id == $this_slot[0]['id']))
            {
              $n = $this_slot[0]['n_slots'];
              $flex_div->addLength($n);
            }
            // There's been a change.  Output the FlexDiv and reset
            else
            {
              $html .= $flex_div->html();
              unset($flex_div);
              $n = 0;
            }
          }
          $s = $s + ($n * $resolution);  // Advance n slots
        }

        // Output the final FlexDiv
        if (isset($flex_div))
        {
          $html .= $flex_div->html();
        }

        $html .= '</a>';
        $html .= "</td>";
        $d++;
      }

      // Fill in the remaining, invalid, days
      while ($j <= 31)
      {
        $html .= '<td class="invalid"></td>';
        $j++;
      }

      // The right-hand header column, if required
      if ($row_labels_both_sides)
      {
        $html .= $first_last_html;
      }
      $html .= "</tr>\n";
      $date->modify('+1 day'); // Advance to the next month
    }

    $html .= "</tbody>\n";

    return $html;
  }


  private function headerRowHTML(string $label='') : string
  {
    global $row_labels_both_sides;

    $html = "<tr>\n";

    // The left-hand header column
    $first_last_html = '<th class="first_last">' . escape_html($label) . "</th>\n";
    $html .= $first_last_html;

    // Choose a month (January) with 31 days and cycle through all the days.
    // We can't add a link to the header cells because we don't know which month they refer to.
    for ($d=1; $d <= 31; $d++)
    {
      $t = mktime(12, 0, 0, 1, $d, $this->year);
      $html .= "<th>" . escape_html($this->getDate($t)) . "</th>\n";
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
