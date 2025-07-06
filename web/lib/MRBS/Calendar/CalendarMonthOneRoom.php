<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use MRBS\DateTime;
use function MRBS\datetime_format;
use function MRBS\day_name;
use function MRBS\escape_html;
use function MRBS\get_date_classes;
use function MRBS\get_end_last_slot;
use function MRBS\get_entries_by_room;
use function MRBS\get_entry_classes;
use function MRBS\get_mrbs_locale;
use function MRBS\get_room_name;
use function MRBS\get_start_first_slot;
use function MRBS\get_vocab;
use function MRBS\hour_min_format;
use function MRBS\is_book_admin;
use function MRBS\is_hidden_day;
use function MRBS\is_visible;
use function MRBS\is_weekend;
use function MRBS\multisite;
use function MRBS\period_time_string;
use function MRBS\prepare_entry;
use function MRBS\session;

class CalendarMonthOneRoom extends Calendar
{

  public function __construct(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id)
  {
    $this->view = $view;
    $this->view_all = $view_all;  // TODO: is this used?
    $this->year = $year;
    $this->month = $month;
    $this->day = $day;  // TODO: is this used?
    $this->area_id = $area_id;
    $this->room_id = $room_id;
  }


  public function innerHTML(): string
  {
    global $weekstarts, $view_week_number, $show_plus_link, $monthly_view_entries_details;
    global $enable_periods, $morningstarts, $morningstarts_minutes;
    global $prevent_booking_on_holidays, $prevent_booking_on_weekends;

    // Check that we've got a valid, enabled room
    if (is_null(get_room_name($this->room_id)) || !is_visible($this->room_id))
    {
      // No rooms have been created yet, or else they are all disabled
      // Add an 'empty' data flag so that the JavaScript knows whether this is a real table or not
      return "<tbody data-empty=1><tr><td><h1>".get_vocab("no_rooms_for_area")."</h1></td></tr></tbody>";
    }

    $html = '';

    // Month view start time. This ignores morningstarts/eveningends because it
    // doesn't make sense to not show all entries for the day, and it messes
    // things up when entries cross midnight.
    $month_start = mktime(0, 0, 0, $this->month, 1, $this->year);
    // What column the month starts in: 0 means $weekstarts weekday.
    $weekday_start = (date("w", $month_start) - $weekstarts + DAYS_PER_WEEK) % DAYS_PER_WEEK;
    $last_day_of_month = (int) date("t", $month_start);

    $html .= $this->theadHTML();

    // Main body
    $html .= "<tbody>\n";
    $html .= "<tr>\n";

    // Skip days in week before the start of the month:
    for ($weekcol = 0; $weekcol < $weekday_start; $weekcol++)
    {
      $html .= $this->tdBlankDayHTML($weekcol);
    }

    $start_date = (new DateTime())->setTimestamp(get_start_first_slot($this->month, 1, $this->year));
    $end_date = (new DateTime())->setTimestamp(get_end_last_slot($this->month, $last_day_of_month, $this->year));

    // Get the data.  It's much quicker to do a single SQL query getting all the
    // entries for the interval in one go, rather than doing a query for each day.
    $entries = get_entries_by_room($this->room_id, $start_date, $end_date);

    // Draw the days of the month:
    for ($d = 1, $date = clone $start_date; $d <= $last_day_of_month; $d++, $date->modify('+1 day'))
    {
      // Get the slot times
      $start_first_slot = get_start_first_slot($this->month, $d, $this->year);
      $end_last_slot = get_end_last_slot($this->month, $d, $this->year);

      // if we're at the start of the week (and it's not the first week), start a new row
      if (($weekcol == 0) && ($d > 1))
      {
        $html .= "</tr><tr>\n";
      }

      // output the day cell
      if ($date->isHiddenDay())
      {
        // These days are to be hidden in the display (as they are hidden, just give the
        // day of the week in the header row)
        $html .= "<td class=\"hidden_day\">\n";
        $html .= "<div class=\"cell_container\">\n";
        $html .= "<div class=\"cell_header\">\n";
        // first put in the day of the month
        $html .= "<span>$d</span>\n";
        $html .= "</div>\n";
        $html .= "</div>\n";
        $html .= "</td>\n";
      }
      else
      {
        // Add classes for weekends and holidays
        $classes = get_date_classes($date);

        $html .= '<td' . ((empty($classes)) ? '' : ' class="' . implode(' ', $classes) . '"') . ">\n";
        $html .= "<div class=\"cell_container\">\n";

        $html .= "<div class=\"cell_header\">\n";

        $vars = [
          'page_date' => $date->getISODate(),
          'area'  => $this->area_id,
          'room'  => $this->room_id
        ];

        // If it's the first day of the week, show the week number
        if ($view_week_number && $date->isFirstDayOfWeek(get_mrbs_locale()))
        {
          $vars['view'] = 'week';
          $query = http_build_query($vars, '', '&');
          $html .= '<a class="week_number" href="' . escape_html(multisite("index.php?$query")) . '">';
          $html .= $date->format('W');
          $html .= "</a>\n";
        }
        // then put in the day of the month
        $vars['view'] = 'day';
        $query = http_build_query($vars, '', '&');
        $html .= '<a class="monthday" href="' . escape_html(multisite("index.php?$query")) . "\">$d</a>\n";

        $html .= "</div>\n";

        // Then the link to make a new booking.
        // Don't provide a link if the slot doesn't really exist or if the user is logged in, but not a booking admin,
        // and it's a holiday/weekend and bookings on holidays/weekends are not allowed.  (We provide a link if they
        // are not logged in because they might want to click and login as an admin).
        if ((null !== session()->getCurrentUser()) && !is_book_admin($this->room_id) &&
          (($prevent_booking_on_holidays && in_array('holiday', $classes)) ||
            ($prevent_booking_on_weekends && in_array('weekend', $classes))))
        {
          $html .= '<span class="not_allowed"></span>';
        }
        else
        {
          $vars['view'] = $this->view;

          if ($enable_periods)
          {
            $vars['period'] = 0;
          }
          else
          {
            $vars['hour'] = $morningstarts;
            $vars['minute'] = $morningstarts_minutes;
          }

          $query = http_build_query($vars, '', '&');

          $html .= '<a class="new_booking" href="' . escape_html(multisite("edit_entry.php?$query")) . '"' .
            ' aria-label="' . escape_html(get_vocab('create_new_booking')) . "\">\n";
          if ($show_plus_link)
          {
            $html .= "<img src=\"images/new.gif\" alt=\"New\" width=\"10\" height=\"10\">\n";
          }
          $html .= "</a>\n";
        }

        // then any bookings for the day
        $html .= "<div class=\"booking_list\">\n";
        // Show the start/stop times, 1 or 2 per line, linked to view_entry.
        foreach ($entries as $entry)
        {
          // We are only interested in this day's entries
          if (($entry['start_time'] >= $end_last_slot) ||
            ($entry['end_time'] <= $start_first_slot))
          {
            continue;
          }

          $entry = prepare_entry($entry);

          $classes = get_entry_classes($entry);
          $classes[] = $monthly_view_entries_details;

          $html .= '<div class="' . implode(' ', $classes) . '">';

          $vars = [
            'id'    => $entry['id'],
            'year'  => $this->year,
            'month' => $this->month,
            'day'   => $d
          ];

          $query = http_build_query($vars, '', '&');
          $booking_link = multisite("view_entry.php?$query");
          $slot_text = $this->bookingSummaryHTML(
            $entry['start_time'],
            $entry['end_time'],
            $start_first_slot,
            $end_last_slot
          );
          $description_text = mb_substr($entry['name'], 0, 255);
          $full_text = $slot_text . " " . $description_text;
          switch ($monthly_view_entries_details)
          {
            case "description":
            {
              $display_text = $description_text;
              break;
            }
            case "slot":
            {
              $display_text = $slot_text;
              break;
            }
            case "both":
            {
              $display_text = $full_text;
              break;
            }
            default:
            {
              $html .= "error: unknown parameter";
            }
          }
          $title_text = $full_text;
          if (isset($entry['description']) && ($entry['description'] !== ''))
          {
            $title_text .= "\n\n" . $entry['description'];
          }
          $html .= '<a href="' . escape_html($booking_link) . '"' .
            ' title="' . escape_html($title_text) . '">';
          $html .= escape_html($display_text) . '</a>';
          $html .= "</div>\n";
        }
        $html .= "</div>\n";

        $html .= "</div>\n";
        $html .= "</td>\n";
      }

      // increment the day of the week counter
      if (++$weekcol == DAYS_PER_WEEK)
      {
        $weekcol = 0;
      }

    } // end of for loop going through valid days of the month

    // Skip from end of month to end of week:
    if ($weekcol > 0)
    {
      for (; $weekcol < DAYS_PER_WEEK; $weekcol++)
      {
        $html .= $this->tdBlankDayHTML($weekcol);
      }
    }

    $html .= "</tr>\n";
    $html .= "</tbody>\n";

    return $html;
  }


  // Describe the start and end time, accounting for "all day"
  // and for entries starting before/ending after today.
  // There are 9 cases, for start time < = or > midnight this morning,
  // and end time < = or > midnight tonight.
  private function bookingSummaryHTML(int $start, int $end, int $day_start, int $day_end) : string
  {
    global $enable_periods, $area;

    // Use ~ (not -) to separate the start and stop times, because MSIE
    // will incorrectly line break after a -.
    $separator = '~';
    $after_today = "==>";
    $before_today = "<==";
    $midnight = "24:00";  // need to fix this so it works with AM/PM configurations (and for that matter 24h)
    $all_day = get_vocab('all_day');

    if ($enable_periods)
    {
      $start_str = escape_html(period_time_string($start, $area));
      $end_str   = escape_html(period_time_string($end, $area, -1));
    }
    else
    {
      $start_str = escape_html(datetime_format(hour_min_format(), $start));
      $end_str   = escape_html(datetime_format(hour_min_format(), $end));
    }

    switch (self::cmp3($start, $day_start) . self::cmp3($end, $day_end + 1))
    {
      case "> < ":         // Starts after midnight, ends before midnight
      case "= < ":         // Starts at midnight, ends before midnight
        $result = $start_str;
        // Don't bother showing the end if it's the same as the start period
        if ($end_str !== $start_str)
        {
          $result .= $separator . $end_str;
        }
        break;
      case "> = ":         // Starts after midnight, ends at midnight
        $result = $start_str . $separator . $midnight;
        break;
      case "> > ":         // Starts after midnight, continues tomorrow
        $result = $start_str . $separator . $after_today;
        break;
      case "= = ":         // Starts at midnight, ends at midnight
        $result = $all_day;
        break;
      case "= > ":         // Starts at midnight, continues tomorrow
        $result = $all_day . $after_today;
        break;
      case "< < ":         // Starts before today, ends before midnight
        $result = $before_today . $separator .  $end_str;
        break;
      case "< = ":         // Starts before today, ends at midnight
        $result = $before_today . $all_day;
        break;
      case "< > ":         // Starts before today, continues tomorrow
        $result = $before_today . $all_day . $after_today;
        break;
    }

    return $result;
  }


  // 3-value compare: Returns result of compare as "< " "= " or "> ".
  private static function cmp3(int $a, int $b) : string
  {
    if ($a < $b)
    {
      return "< ";
    }
    if ($a == $b)
    {
      return "= ";
    }
    return "> ";
  }


  private function tdBlankDayHTML(int $col) : string
  {
    global $weekstarts;

    $td_class = (is_hidden_day(($col + $weekstarts) % DAYS_PER_WEEK)) ? 'hidden_day' : 'invalid';
    return "<td class=\"$td_class\"><div class=\"cell_container\">&nbsp;</div></td>\n";
  }


  // Gets the table head for the single room month view
  private function theadHTML() : string
  {
    global $weekstarts;

    $html = '';

    // Weekday name header row:
    $html .= "<thead>\n";
    $html .= "<tr>\n";
    for ($i = 0; $i< DAYS_PER_WEEK; $i++)
    {
      $classes = [];
      $dow = ($i + $weekstarts) % DAYS_PER_WEEK;
      if (is_hidden_day($dow))
      {
        $classes[] = 'hidden_day';
      }
      if (is_weekend($dow))
      {
        $classes[] = 'weekend';
      }
      $html .= '<th';
      if (!empty($classes))
      {
        $html .= ' class="' . implode(' ', $classes) . '"';
      }
      $html .= '>' . day_name(($i + $weekstarts)%DAYS_PER_WEEK) . '</th>';
    }
    $html .= "\n</tr>\n";
    $html .= "</thead>\n";

    return $html;
  }

}
