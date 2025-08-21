<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use function MRBS\escape_html;
use function MRBS\get_entries_by_room;

class CalendarMultimonthOneRoom extends CalendarMultimonth
{

  public function __construct($view, $view_all, $year, $month, $day, $area_id, $room_id)
  {
    global $resolution;

    parent::__construct($view, $view_all, $year, $month, $day, $area_id, $room_id);

    // Get the entries.  It's much quicker to do a single SQL query getting all the
    // entries for the interval in one go, rather than doing a query for each day.
    $entries = get_entries_by_room($this->area_id, $this->start_date, $this->end_date);

    // We want to build an array containing all the data we want to show and then spit it out.
    $this->map = new Map($this->start_date, $this->end_date, $resolution);
    $this->map->addEntries($entries);
  }

  public function innerHTML(): string
  {
    global $column_labels_both_ends;

    // Table header
    $thead = '<thead';
    // TODO: get_slots() for JavaScript
    $thead .= ">\n";
    $header_row = $this->headerRowHTML();
    $thead .= $header_row;
    $thead .= "</thead>\n";

    // Table body
    $tbody = "<tbody>\n";
    $tbody .= "<tbody>\n";

    // Table footer
    $tfoot = ($column_labels_both_ends) ? "<tfoot>\n$header_row</tfoot>\n" : '';

    return $thead . $tfoot . $tbody;
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
