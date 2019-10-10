<?php

namespace MRBS\ICalendar;

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
  public function __construct(array $row, $limit=null)
  {
    if (!isset($limit))
    {
      $limit = PHP_INT_MAX;
    }

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
    $this->start_row['end_date'] = min($limit, $this->start_row['end_date']);
    $duration = $this->start_row['end_time'] - $this->start_row['start_time'];
    $this->start_row['start_time'] = strtotime($row['ical_recur_id']);
    $this->start_row['end_time'] = $this->start_row['start_time'] + $duration;

    // Construct an array of the entries we'd expect to see in this series so that
    // we can check whether any are missing and if so set their status to cancelled.
    // (We use PHP_INT_MAX rather than $max_rep_entrys because $max_rep_entrys may
    // have changed since the series was created.)
    $rep_details = array();

    foreach (array('rep_type', 'rep_opt', 'rep_interval', 'month_absolute', 'month_relative') as $key)
    {
      if (isset($this->start_row[$key]))
      {
        $rep_details[$key] = $this->start_row[$key];
      }
    }

    $this->expected_entries = \MRBS\mrbsGetRepeatEntryList(
      $this->start_row['start_time'],
      $this->start_row['end_date'],
      $rep_details,
      PHP_INT_MAX
    );

    // And keep an array of all the entries we actually see
    $this->actual_entries = array();

    // And finally add the row to the series
    $this->addRow($row);
  }


  // Add a row to the series
  public function addRow(array $row)
  {
    // Add the row to the data array
    $this->data[] = $row;

    // Add this entry to the array of ones we've seen
    $this->actual_entries[] = strtotime($row['ical_recur_id']);

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
    $events[] = \MRBS\create_ical_event($method, $this->original_row, null, true);

    // Then iterate through the series looking for changed entries
    foreach($this->data as $entry)
    {
      if ($entry['entry_type'] == ENTRY_RPT_CHANGED)
      {
        $events[] = \MRBS\create_ical_event($method, $entry);
      }
    }

    return $events;
  }

}
