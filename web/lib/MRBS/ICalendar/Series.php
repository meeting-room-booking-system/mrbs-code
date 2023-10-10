<?php

namespace MRBS\ICalendar;

use MRBS\DateTime;
use MRBS\Exception;
use MRBS\RepeatRule;
use function MRBS\create_ical_event;
use function MRBS\entry_has_registrants;

require_once MRBS_ROOT . '/functions_ical.inc';
require_once MRBS_ROOT . '/mrbs_sql.inc';


class Series
{
  public $repeat_id;

  private $data;
  private $original_row;
  private $start_row;
  private $expected_entries;
  private $actual_entries;

  // Constructs a new Series object and adds $row to it.
  // $limit is the limiting UNIX timestamp for the series.  This may be before the actual end
  // of the series.   Defaults to null, ie no limit.  This enables the series extract to be truncated.
  public function __construct(array $row, int $limit=null)
  {
    $row = self::fixUpRow($row);

    $this->data = array();
    $this->repeat_id = $row['repeat_id'];

    // Save the row as an original row, in case we never get a better one
    $this->original_row = $row;

    // We need to set the repeat start and end dates because we've only been
    // asked to export dates in the report range.  The end date will be the earlier
    // of the series end date and the report end date.  The start date of the series
    // will be the recurrence-id of the first entry in the series, which is this one
    // thanks to the SQL query which ordered the entries by recurrence-id.
    $this->start_row = $row;  // Make a copy of the data because we are going to tweak it.
    if (isset($limit))
    {
      $this->start_row['end_date'] = min($limit, $this->start_row['end_date']);
    }
    $duration = $this->start_row['end_time'] - $this->start_row['start_time'];
    $this->start_row['start_time'] = strtotime($row['ical_recur_id']);
    $this->start_row['end_time'] = $this->start_row['start_time'] + $duration;

    // Construct an array of the entries we'd expect to see in this series so that
    // we can check whether any are missing and if so set their status to "cancelled".
    $this->expected_entries = $this->start_row['repeat_rule']->getRepeatStartTimes($this->start_row['start_time']);

    // And keep an array of all the entries we actually see
    $this->actual_entries = array();

    // And finally add the row to the series
    $this->addRow($row);
  }


  // Add a row to the series
  public function addRow(array $row)
  {
    $row = self::fixUpRow($row);

    // Add the row to the data array
    $this->data[] = $row;

    // If this is an original entry, add it to the array of ones we've seen
    if ($row['entry_type'] == ENTRY_RPT_ORIGINAL)
    {
      $this->actual_entries[] = strtotime($row['ical_recur_id']);
    }

    // And if it's an original row save it, because it will have all the original data,
    // eg description, which we can use later.
    if (($this->original_row['entry_type'] == ENTRY_RPT_CHANGED) &&
        ($row['entry_type'] == ENTRY_RPT_ORIGINAL))
    {
      $this->original_row = $row;
    }
  }


  // Convert the series to an array of iCalendar events.
  // $method ids the METHOD, eg 'PUBLISH'.
  public function toEvents($method)
  {
    $events = array();

    $this->original_row['start_time'] = $this->start_row['start_time'];
    $this->original_row['end_time']   = $this->start_row['end_time'];
    $this->original_row['end_date']   = $this->start_row['end_date'];

    // Create the series event
    $this->original_row['skip_list'] = array_diff($this->expected_entries, $this->actual_entries);
    $events[] = create_ical_event($method, $this->original_row, null, true);

    // Then iterate through the series looking for changed entries
    foreach($this->data as $entry)
    {
      if ($entry['entry_type'] == ENTRY_RPT_CHANGED)
      {
        $events[] = create_ical_event($method, $entry);
      }
    }

    return $events;
  }


  // Temporary fix-up to add a repeat_rule key to the $row array
  // TODO: fix this properly
  private static function addRepeatRule(array $row) : array
  {
    // Construct the repeat rule and add it to the row
    $repeat_rule = new RepeatRule();
    $repeat_rule->setType($row['rep_type']);
    $repeat_rule->setInterval($row['rep_interval']);
    $repeat_end_date = new DateTime();
    $repeat_end_date->setTimestamp($row['end_date']);
    $repeat_rule->setEndDate($repeat_end_date);
    $repeat_rule->setDaysFromOpt($row['rep_opt']);

    if ($repeat_rule->getType() == RepeatRule::MONTHLY)
    {
      if (isset($row['month_absolute'])) {
        $repeat_rule->setMonthlyAbsolute($row['month_absolute']);
        $repeat_rule->setMonthlyType(RepeatRule::MONTHLY_ABSOLUTE);
      }
      elseif (isset($row['month_relative'])) {
        $repeat_rule->setMonthlyRelative($row['month_relative']);
        $repeat_rule->setMonthlyType(RepeatRule::MONTHLY_RELATIVE);
      }
      else {
        throw new Exception("The repeat type is monthly but both the absolute and relative days are null.");
      }
    }

    $row['repeat_rule'] = $repeat_rule;

    return $row;
  }


  private static function fixUpRow(array $row) : array
  {
    // Temporary fix-up
    $row = self::addRepeatRule($row);

    // Another fix-up: if the entry has registrants then treat it like
    // a changed entry so that it appears as individual event in the calendar
    // and can therefore have registrants associated with it.
    // TODO: fix this properly.  (Maybe entries with registrants should be
    // TODO: ENTRY_RPT_CHANGED in the database in the first place? That would
    // TODO: also solve the problem of not being able to edit series with registrants.)
    if (entry_has_registrants($row['id']))
    {
      $row['entry_type'] = ENTRY_RPT_CHANGED;
    }

    return $row;
  }
}
