<?php
declare(strict_types=1);
namespace MRBS\Calendar;


use MRBS\DateTime;
use function MRBS\datetime_format;
use function MRBS\escape_html;
use function MRBS\format_iso_date;
use function MRBS\get_entries_by_area;
use function MRBS\get_rooms;
use function MRBS\get_vocab;
use function MRBS\multisite;

class CalendarMultimonthMultiroom extends CalendarMultimonth
{
  public function __construct(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id)
  {
    global $resolution;

    parent::__construct($view, $view_all, $year, $month, $day, $area_id, $room_id);

    // Get the entries.  It's much quicker to do a single SQL query getting all the
    // entries for the interval in one go, rather than doing a query for each day.
    $entries = get_entries_by_area($this->area_id, $this->start_date, $this->end_date);

    // We want to build an array containing all the data we want to show and then spit it out.
    $this->map = new Map($this->start_date, $this->end_date, $resolution);
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

    // The variables for the link query string
    $vars = [
      'view' => 'month',
      'view_all' => 0,
      'area' => $this->area_id,
      'room' => $room['id']
    ];

    $j = 0; // Need to keep track of the day in the Calendar interval (zero indexed)

    // Loop through the months in the interval
    for ($i=0; $i<$this->n_months; $i++)
    {
      $html .= "<td>\n";
      $vars['page_date'] = $date->getISODate();
      $link = 'index.php?' . http_build_query($vars, '', '&');
      $link = multisite($link);
      $html .= '<a href="' . escape_html($link) . '">';
      $days_in_month = $date->getDaysInMonth();
      $html .= $this->flexDivsHTML($room['id'], $j, $j + $days_in_month - 1);
      $html .= '</a>';
      $html .= "</td>\n";
      $j += $days_in_month;
      $date->modifyMonthsNoOverflow(1, true);  // Advance 1 month
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
