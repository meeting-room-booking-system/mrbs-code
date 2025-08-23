<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use MRBS\DateTime;
use MRBS\Exception;

abstract class CalendarMultimonth extends Calendar
{
  protected $map;
  protected $n_months;
  protected $start_date;
  protected $end_date;

  public function __construct(string $view, int $view_all, int $year, int $month, int $day, int $area_id, int $room_id)
  {
    global $year_start;

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

    // Get the start and end dates
    $this->start_date = (new DateTime())->setDate($this->year, $this->month, 1);
    $this->start_date->setMonthYearStart($year_start);
    $this->start_date->setStartFirstSlot();

    $this->end_date = clone $this->start_date;
    $this->end_date->modify('+' . $this->n_months . ' month');
    $this->end_date->modify('-1 day');
    $this->end_date->setEndLastSlot();
  }


  protected function flexDivsHTML(int $room_id, int $start_day_index, int $end_day_index) : string
  {
    global $morningstarts, $morningstarts_minutes, $resolution;

    $html = '';

    // Get the time slots
    $n_time_slots = self::getNTimeSlots();
    $morning_slot_seconds = (($morningstarts * 60) + $morningstarts_minutes) * 60;
    $evening_slot_seconds = $morning_slot_seconds + (($n_time_slots - 1) * $resolution);

    // Loop through the days in the interval
    for ($i=$start_day_index; $i<=$end_day_index; $i++)
    {
      $s = $morning_slot_seconds;

      // Loop through the slots in the day
      while ($s <= $evening_slot_seconds)
      {
        // Get the entry for this slot
        $this_slot = $this->map->slot($room_id, $i, $s);
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
    }

    // Output the final FlexDiv
    if (isset($flex_div))
    {
      $html .= $flex_div->html();
    }

    return $html;
  }

}
