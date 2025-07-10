<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use MRBS\DateTime;
use function MRBS\escape_html;
use function MRBS\format_iso_date;
use function MRBS\get_date;
use function MRBS\get_date_classes;
use function MRBS\get_day;
use function MRBS\is_hidden_day;
use function MRBS\multisite;

abstract class Calendar
{
  protected $day;
  protected $month;
  protected $year;
  protected $area_id;
  protected $room_id;
  protected $view;
  protected $view_all;


  abstract public function innerHTML() : string;

  protected function multidayHeaderRowsHTML(
    string $view,
    int $view_all,
    int $year,
    int $month,
    int $day_start_interval,
    int $area_id,
    int $room_id,
    int $n_days,
    int $start_dow,
    string $label=''
  ) : array
  {
    global $row_labels_both_sides;

    $result = array();
    $n_rows = 2;
    // Loop through twice: one row for the days of the week, the next for the date.
    for ($i = 0; $i < $n_rows; $i++)
    {
      $result[$i] = "<tr>\n";

      // Could use a rowspan here, but we'd need to make sure the sticky cells work
      // and change the JavaScript in refresh.js.php
      $text = ($i == 0) ? '' : $label;
      $first_last_html = '<th class="first_last">' . escape_html($text) . "</th>\n";
      $result[$i] .= $first_last_html;

      $vars = array(
        'view' => 'day',
        'view_all' => $view_all,
        'area' => $area_id,
        'room' => $room_id
      );

      // the standard view, with days along the top and rooms down the side
      for ($j = 0; $j < $n_days; $j++)
      {
        if (is_hidden_day(($j + $start_dow) % DAYS_PER_WEEK))
        {
          continue;
        }
        $vars['page_date'] = format_iso_date($year, $month, $day_start_interval + $j);
        $link = "index.php?" . http_build_query($vars, '', '&');
        $link = multisite($link);
        $t = mktime(12, 0, 0, $month, $day_start_interval + $j, $year);
        $text = ($i === 0) ? get_day($t, $view) : get_date($t, $view);
        $date = new DateTime();
        $date->setTimestamp($t);
        $classes = get_date_classes($date);
        $result[$i] .= '<th' .
          // Add the date for JavaScript.  Only really necessary for the first row in
          // the week view when not viewing all the rooms, but just add it always.
          ' data-date="' . escape_html($date->getISODate()) . '"' .
          ((!empty($classes)) ? ' class="' . implode(' ', $classes) . '"' : '') .
          '><a href="' . escape_html($link) . '">' . escape_html($text) . "</a></th>\n";
      }

      // next line to display rooms on right side
      if ($row_labels_both_sides)
      {
        $result[$i] .= $first_last_html;
      }

      $result[$i] .= "</tr>\n";
    }

    return $result;
  }
}
