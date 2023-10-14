<?php

namespace MRBS\ICalendar;

use MRBS\DateTime;
use MRBS\Exception;
use MRBS\RepeatRule;
use function MRBS\_tbl;
use function MRBS\create_ical_event;
use function MRBS\entry_has_registrants;

require_once MRBS_ROOT . '/functions_ical.inc';
require_once MRBS_ROOT . '/mrbs_sql.inc';


class Series
{
  public $repeat_id;

  private $data;
  private $repeat;
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

    // Get the repeat data and save it, so that we can construct the repeat event later
    $this->repeat = self::get_repeat($this->repeat_id);
    if (!isset($this->repeat))
    {
      throw new Exception("Repeat data not available");
    }
    // Add in the area and room names, which we can get from this row
    $this->repeat['area_name'] = $row['area_name'];
    $this->repeat['room_name'] = $row['room_name'];
    // Copy the registration settings from this row (they won't necessarily be correct if
    // this is a changed entry, but we'll look out for an original row later)
    // TODO: the registration settings should really be in the repeat table to begin with
    $this->repeat = self::copyRegistrationSettings($this->repeat, $row);
    $this->repeat['entry_type'] = $row['entry_type'];
    // Limit the series (before we create the repeat rule)
    if (isset($limit))
    {
      $this->repeat['end_date'] = min($limit, $this->repeat['end_date']);
    }
    // Create the repeat rule
    $this->repeat = self::addRepeatRule($this->repeat);

    // Construct an array of the entries we'd expect to see in this series so that
    // we can check whether any are missing and if so set their status to "cancelled".
    $this->expected_entries = $this->repeat['repeat_rule']->getRepeatStartTimes($this->repeat['start_time']);

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
      $this->actual_entries[] = $row['start_time'];
    }

    // And if we haven't yet seen an original row, and this is one, then grab
    // the registration settings.  (If we never see an original row it doesn't matter,
    // because all the rows will be changed rows and have their own registration settings.)
    if (($this->repeat['entry_type'] == ENTRY_RPT_CHANGED) &&
        ($row['entry_type'] == ENTRY_RPT_ORIGINAL))
    {
      $this->repeat = self::copyRegistrationSettings($this->repeat, $row);
      $this->repeat['entry_type'] = ENTRY_RPT_ORIGINAL;
    }
  }


  // Convert the series to an array of iCalendar events.
  // $method ids the METHOD, eg 'PUBLISH'.
  public function toEvents($method)
  {
    $events = array();

    $this->repeat['skip_list'] = array_diff($this->expected_entries, $this->actual_entries);
    $events[] = create_ical_event($method, $this->repeat, null, true);

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


  private function get_repeat(int $repeat_id) : ?array
  {
    $sql = "SELECT *
              FROM " . _tbl('repeat') . "
             WHERE id=:repeat_id
             LIMIT 1";

    $res = \MRBS\db()->query($sql, array(':repeat_id' => $repeat_id));

    return ($res->count() == 0) ? null : $res->next_row_keyed();
  }


  private static function copyRegistrationSettings(array $to, array $from) : array
  {
    $keys = array(
      'allow_registration',
      'registrant_limit',
      'registrant_limit_enabled',
      'registration_opens',
      'registration_opens_enabled',
      'registration_closes',
      'registration_closes_enabled'
    );
    foreach ($keys as $key)
    {
      $to[$key] = $from[$key];
    }

    return $to;
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
