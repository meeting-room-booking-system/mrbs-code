<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use MRBS\DateTime;
use function MRBS\datetime_format;
use function MRBS\day_past_midnight;
use function MRBS\escape_html;
use function MRBS\format_iso_date;
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


  private function getDate(int $t) : string
  {
    global $datetime_formats;

    if ($this->view === 'month')
    {
      return datetime_format(['pattern' => 'd'], $t);
    }
    else
    {
      return datetime_format($datetime_formats['view_week_day_month'], $t);
    }
  }


  private function getDay(int $t) : string
  {
    // In the month view use a pattern which will tend to give a narrower result, to save space.
    $pattern = ($this->view == 'month') ? 'cccccc' : 'ccc';

    return datetime_format(['pattern' => $pattern], $t);
  }


  // Gets the number of time slots between the beginning and end of the booking
  // day.   (This is the normal number on a non-DST transition day)
  protected function getNTimeSlots() : int
  {
    global $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes;
    global $resolution;

    $start_first = (($morningstarts * 60) + $morningstarts_minutes) * 60;           // seconds
    $end_last = ((($eveningends * 60) + $eveningends_minutes) * 60) + $resolution;  // seconds
    $end_last = $end_last % SECONDS_PER_DAY;
    if (day_past_midnight())
    {
      $end_last += SECONDS_PER_DAY;
    }

    // Force the result to be an int.  It normally will be, but might not be if, say,
    // $force_resolution is set.
    return intval(($end_last - $start_first)/$resolution);
  }


  // If we're not using periods, construct an array describing the slots to pass to the JavaScript so that
  // it can calculate where the timeline should be drawn.  (If we are using periods then the timeline is
  // meaningless because we don't know when periods begin and end.)
  //    $month, $day, $year   the start of the interval
  //    $n_days               the number of days in the interval
  //    $day_cells            if the columns/rows represent a full day (as in the week/month all rooms views)
  protected function getSlots(int $month, int $day, int $year, int $n_days=1, bool $day_cells=false) : ?array
  {
    global $enable_periods, $morningstarts, $morningstarts_minutes, $resolution;

    if ($enable_periods)
    {
      return null;
    }

    $slots = array();

    $n_time_slots = $this->getNTimeSlots();
    $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
    $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

    for ($j = 0; $j < $n_days; $j++)
    {
      $d = $day + $j;

      // If there's more than one day in the interval then don't include the hidden days in the array, because
      // they don't appear in the DOM.  If there's only one day then we've managed to display the hidden day.
      if (($n_days > 1) &&
        is_hidden_day(intval(date('w', mktime($morningstarts, $morningstarts_minutes, 0, $month, $d, $year)))))
      {
        continue;
      }

      $this_day = array();

      if ($day_cells)
      {
        $this_day[] = mktime(0, 0, $morning_slot_seconds, $month, $d, $year);
        // Need to do mktime() again for the end of the slot as we can't assume that the end slot is $resolution
        // seconds after the start of the slot because of the possibility of DST transitions
        $this_day[] = mktime(0, 0, $evening_slot_seconds + $resolution, $month, $d, $year);
      }
      else
      {
        for ($s = $morning_slot_seconds;
             $s <= $evening_slot_seconds;
             $s += $resolution)
        {
          $this_slot = array();
          $this_slot[] = mktime(0, 0, $s, $month, $d, $year);
          // Need to do mktime() again for the end of the slot as we can't assume that the end slot is $resolution
          // seconds after the start of the slot because of the possibility of DST transitions
          $this_slot[] = mktime(0, 0, $s + $resolution, $month, $d, $year);
          $this_day[] = $this_slot;
        }
      }
      $slots[] = $this_day;
    }

    if ($day_cells)
    {
      $slots = array($slots);
    }

    return $slots;
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


  protected function multidayHeaderRowsHTML(int $day_start_interval, int $n_days, int $start_dow, string $label='') : array
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

      $vars = [
        'view' => 'day',
        'view_all' => $this->view_all,
        'area' => $this->area_id,
        'room' => $this->room_id
      ];

      // the standard view, with days along the top and rooms down the side
      for ($j = 0; $j < $n_days; $j++)
      {
        if (is_hidden_day(($j + $start_dow) % DAYS_PER_WEEK))
        {
          continue;
        }
        $vars['page_date'] = format_iso_date($this->year, $this->month, $day_start_interval + $j);
        $link = "index.php?" . http_build_query($vars, '', '&');
        $link = multisite($link);
        $t = mktime(12, 0, 0, $this->month, $day_start_interval + $j, $this->year);
        $text = ($i === 0) ? $this->getDay($t) : $this->getDate($t);
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
  //    $room    contains the room details
  //    $vars    an associative array containing the variables to be used to build the link
  protected function roomCellHTML(array $room, array $vars) : string
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
      case 'year':
        $tag = 'viewyear';
        break;
      default:
        trigger_error("Unknown view '" . $vars['view'] . "'", E_USER_NOTICE);
        $tag = 'viewweek';
        break;
    }

    $title = get_vocab($tag) . "\n\n" . $room['description'];
    $html = '';
    $html .= '<th data-room="' . escape_html($room['id']) . '">';
    $html .= '<a href="' . escape_html($link) . '"' .
      ' title = "' . escape_html($title) . '">';
    $html .= escape_html($room['room_name']);
    // Put the capacity in a span to give flexibility in styling
    $html .= '<span class="capacity';
    if ($room['capacity'] == 0)
    {
      $html .= ' zero';
    }
    $html .= '">' . escape_html($room['capacity']);
    $html .= '</span>';
    $html .= '</a>';
    $html .= "</th>\n";
    return $html;
  }

}
