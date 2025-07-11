<?php
declare(strict_types=1);
namespace MRBS\Calendar;


use MRBS\DateTime;
use function MRBS\datetime_format;
use function MRBS\escape_html;
use function MRBS\format_iso_date;
use function MRBS\get_rooms;
use function MRBS\get_vocab;
use function MRBS\multisite;

class CalendarMultimonthMultiroom extends Calendar
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


  public function innerHTML(): string
  {
    global $column_labels_both_ends, $row_labels_both_sides;

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
    $n_months = MONTHS_PER_YEAR;
    $header_row = $this->headerRowHTML($n_months);
    $thead .= $header_row;
    $thead .= "</thead>\n";

    // Table body
    $tbody = "<tbody>\n";

    $room_link_vars = [
      'view'      => $this->view,
      'view_all'  => 0,
      'page_date' => format_iso_date($this->year, $this->month, $this->day),
      'area'      => $this->area_id
    ];

    foreach ($rooms as $room)
    {
      $tbody .= "<tr>\n";
      $room_link_vars['room'] = $room['id'];
      $row_label = $this->roomCellHTML($room, $room_link_vars);
      $tbody .= $row_label;

      for ($i=0; $i<$n_months; $i++)
      {
        $tbody .= "<td></td>";
      }

      if ($row_labels_both_sides)
      {
        $tbody .= $row_label;
      }
      $tbody .= "</tr>\n";
    }
    $tbody .= "<tbody>\n";

    // Table footer
    $tfoot = ($column_labels_both_ends) ? "<tfoot>\n$header_row</tfoot>\n" : '';

    return $thead . $tfoot . $tbody;
  }


  private function headerRowHTML(
    int $n_months,
    string $label=''
  ) : string
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
    for ($i=0; $i<$n_months; $i++)
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
