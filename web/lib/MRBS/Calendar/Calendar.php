<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use MRBS\DateTime;
use MRBS\Room;
use function MRBS\datetime_format;
use function MRBS\escape_html;
use function MRBS\format_iso_date;
use function MRBS\get_day;
use function MRBS\get_vocab;
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


  private function getDate(int $t, string $view) : string
  {
    global $datetime_formats;

    if ($view == 'month')
    {
      return datetime_format(['pattern' => 'd'], $t);
    }
    else
    {
      return datetime_format($datetime_formats['view_week_day_month'], $t);
    }
  }


  protected function getDateClasses(DateTime $date) : array
  {
    $result = [];

    if ($date->isWeekend())
    {
      $result[] = 'weekend';
    }
    if ($date->isHoliday())
    {
      $result[] = 'holiday';
    }
    if ($date->isToday())
    {
      $result[] = 'today';
    }

    return $result;
  }


  // Returns an array of classes to be used for the entry
  protected function getEntryClasses(array $entry) : array
  {
    global $approval_enabled, $confirmation_enabled;

    $classes = array($entry['type']);

    if ($entry['private'])
    {
      $classes[] = 'private';
    }

    if ($approval_enabled && ($entry['awaiting_approval']))
    {
      $classes[] = 'awaiting_approval';
    }

    if ($confirmation_enabled && ($entry['tentative']))
    {
      $classes[] = 'tentative';
    }

    if (isset($entry['repeat_id']))
    {
      $classes[] = 'series';
    }

    if ($entry['allow_registration'])
    {
      if ($entry['registrant_limit_enabled'] &&
        ($entry['n_registered'] >= $entry['registrant_limit']))
      {
        $classes[] = 'full';
      }
      else
      {
        $classes[] = 'spaces';
      }
    }

    return $classes;
  }


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
        $text = ($i === 0) ? get_day($t, $view) : $this->getDate($t, $view);
        $date = new DateTime();
        $date->setTimestamp($t);
        $classes = $this->getDateClasses($date);
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


  // Draw a room cell to be used in the header rows/columns of the calendar views
  //    $room    a Room object
  //    $vars    an associative array containing the variables to be used to build the link
  protected function roomCellHTML(Room $room, array $vars) : string
  {
    $link = 'index.php?' . http_build_query($vars, '', '&');
    $link = multisite($link);

    switch ($vars['view'])
    {
      case 'day':
        $tag = 'viewday';
        break;
      case 'week':
        $tag = 'viewweek';
        break;
      case 'month':
        $tag = 'viewmonth';
        break;
      default:
        trigger_error("Unknown view '" . $vars['view'] . "'", E_USER_NOTICE);
        $tag = 'viewweek';
        break;
    }

    $title = get_vocab($tag) . "\n\n" . $room->description;
    $html = '';
    $html .= '<th data-room="' . escape_html($room->id) . '">';
    $html .= '<a href="' . escape_html($link) . '"' .
      ' title = "' . escape_html($title) . '">';
    $html .= escape_html($room->room_name);
    // Put the capacity in a span to give flexibility in styling
    $html .= '<span class="capacity';
    if ($room->capacity == 0)
    {
      $html .= ' zero';
    }
    $html .= '">' . escape_html($room->capacity);
    $html .= '</span>';
    $html .= '</a>';
    $html .= "</th>\n";
    return $html;
  }

}
