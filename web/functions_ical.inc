<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\DB\DBStatement;
use MRBS\ICalendar\Calendar;
use MRBS\ICalendar\ComponentFactory;
use MRBS\ICalendar\Event;
use MRBS\ICalendar\Property;
use MRBS\ICalendar\RFC5545;
use MRBS\ICalendar\RFC5545Exception;
use MRBS\ICalendar\Series;
use MRBS\ICalendar\Timezone;


// Given an RFC 5545 recurrence rule, returns a RepeatRule object giving the MRBS repeat
// details.
// Returns FALSE on failure with error messages being returned in the array $errors
function get_repeat_rule(string $rrule, int $start_time, array &$errors)
{
  // Set up the result with safe defaults
  $repeat_rule = new RepeatRule();
  $repeat_rule->setType(RepeatRule::NONE);
  $repeat_rule->setInterval(1);
  $end_date = new DateTime();
  $end_date->setTimestamp(0);
  $repeat_rule->setEndDate($end_date);

  $rules = array();
  $recur_rule_parts = explode(';', $rrule);
  foreach ($recur_rule_parts as $recur_rule_part)
  {
    list($name, $value) = explode('=', $recur_rule_part);
    $rules[$name] = $value;
  }

  if (!isset($rules['FREQ']))
  {
    $errors[] = get_vocab("invalid_RRULE");
  }

  try
  {
    switch ($rules['FREQ'])
    {
      case 'DAILY':
        $repeat_rule->setType(RepeatRule::DAILY);
        break;
      case 'WEEKLY':
        $repeat_rule->setType(RepeatRule::WEEKLY);
        if (isset($rules['BYDAY']))
        {
          $repeat_rule->setDaysFromRFC5545(explode(',', $rules['BYDAY']));
        }
        else
        {
          // If there's no repeat day specified in the RRULE then
          // 'the day is gotten from "DTSTART"'
          $repeat_rule->setDays(array(date('w', $start_time)));
        }
        break;
      case 'MONTHLY':
        $repeat_rule->setType(RepeatRule::MONTHLY);
        if (!isset($rules['BYDAY']))
        {
          $repeat_rule->setMonthlyAbsolute((int)$rules['BYMONTHDAY']);
          $repeat_rule->setMonthlyType(RepeatRule::MONTHLY_ABSOLUTE);
        }
        else
        {
          $byday_days = explode(',', $rules['BYDAY']);
          if (count($byday_days) > 1)
          {
            $errors[] = get_vocab("more_than_one_BYDAY") . $rules['FREQ'];
          }
          foreach ($byday_days as $byday_day)
          {
            $rfc5545day = mb_substr($byday_day, -2);     // the last two characters of the string
            $nth = mb_substr($byday_day, 0, -2);  // everything except the last two characters
            if ($nth === '')
            {
              // "If an integer modifier is not present, it means all days of this
              // type within the specified frequency.  For example, within a MONTHLY
              // rule, MO represents all Mondays within the month." [RFC 5545]
              // So that comes to the same thing as a WEEKLY repeat
              $repeat_rule->setType(RepeatRule::WEEKLY);
              $repeat_rule->setDaysFromRFC5545(array($rfc5545day));
            }
            elseif (($nth == '5') || ($nth == '-5'))
            {
              $errors[] = get_vocab("BYDAY_equals_5") . " $nth$rfc5545day";
            }
            else
            {
              $repeat_rule->setMonthlyRelative($byday_day);
              $repeat_rule->setMonthlyType(RepeatRule::MONTHLY_RELATIVE);
            }
          }
        }
        break;
      case 'YEARLY':
        $repeat_rule->setType(RepeatRule::YEARLY);
        break;
      default:
        $errors[] = get_vocab("unsupported_FREQ") . $rules['FREQ'];
        break;
    }
  }
  catch (RFC5545Exception $e)
  {
    $errors[] = $e->getMessage();
  }

  if (isset($rules['INTERVAL']) && ($rules['INTERVAL'] > 1))
  {
    $repeat_rule->setInterval((int) $rules['INTERVAL']);
  }
  else
  {
    $repeat_rule->setInterval(1);
  }

  if (isset($rules['UNTIL']))
  {
    // Strictly speaking "the value of the UNTIL rule part MUST have the same
    // value type as the "DTSTART" property".   So we should really tell getTimestamp()
    // the value type.  But "if the "DTSTART" property is specified as a date with UTC
    // time or a date with local time and time zone reference, then the UNTIL rule
    // part MUST be specified as a date with UTC time" - so in nearly all cases
    // supported by MRBS the value will be a UTC time.
    $repeat_end_date = new DateTime();
    $repeat_end_date->setTimestamp(Property::convertDatetimeValue($rules['UNTIL']));
    $repeat_rule->setEndDate($repeat_end_date);
  }
  elseif (isset($rules['COUNT']))
  {
    // It would be quite easy to support COUNT, but we haven't done so yet
    $errors[] = get_vocab("unsupported_COUNT");
  }
  else
  {
    $errors[] = get_vocab("no_indefinite_repeats");
  }

  return (empty($errors)) ? $repeat_rule : false;
}


// outputs an iCalendar based on the data in $res, the result of an SQL query.
//
//    &$res       resource  the result of an SQL query on the entry table, which
//                          has been sorted by repeat_id, start_time (both ascending).
//                          As well as all the fields in the entry table, the rows will
//                          also contain the area name, the room name and the repeat
//                          details (rep_type, end_date, rep_opt, rep_interval)
//    $export_end int       a Unix timestamp giving the end limit for the export
function export_icalendar(DBStatement $res, bool $keep_private, int $export_end=PHP_INT_MAX) : void
{
  global $timezone;

  require_once "functions_view.inc";
  require_once "mrbs_sql.inc";

  // We construct an iCalendar by going through the rows from the SQL query.  Because
  // it was sorted by repeat_id we will
  //    - get all the individual entries (which will not have a repeat_id)
  //    - then get the series.    For each series we have to:
  //        - identify the series information.
  //        - identify any events that have been changed from the standard, ie events
  //          with entry_type == ENTRY_RPT_CHANGED
  //        - identify any events from the original series that have been cancelled.  We
  //          can do this because we know from the repeat information the events that
  //          should be there, and we can tell from the start times the events that are
  //          actually there.

  // We use PUBLISH rather than REQUEST because we're not inviting people to these meetings,
  // we're just exporting the calendar.   Furthermore, if we don't use PUBLISH then some
  // calendar apps (eg Outlook, at least 2010 and 2013) won't open the full calendar.
  $method = "PUBLISH";
  $calendar = new Calendar($method);
  $vtimezone = Timezone::createFromTimezoneName($timezone);
  if ($vtimezone)
  {
    $calendar->addComponent($vtimezone);
  }

  $n_rows = $res->count();

  for ($i=0; (false !== ($row = $res->next_row_keyed())); $i++)
  {
    row_cast_columns($row, 'entry');
    // Turn the last_updated column into an int (some MySQL drivers will return a string,
    // and it won't have been caught by row_cast_columns as it's a derived result).
    $row['last_updated'] = intval($row['last_updated']);
    unpack_status($row);
    // If this is an individual entry, then construct an event
    if (!isset($row['rep_type']) || ($row['rep_type'] == RepeatRule::NONE))
    {
      $calendar->addComponent(Event::createFromData($method, $row));
    }

    // Otherwise it's a series
    else
    {
      // If we haven't started a series, then start one
      if (!isset($series))
      {
        $series = new Series($row, $export_end);
      }

      // Otherwise, if this row is a member of the current series, add the row to the series.
      elseif ($row['repeat_id'] == $series->repeat_id)
      {
        $series->addRow($row);
      }

      // If it's a series that we haven't seen yet, or we've got no more
      // rows, then process the series
      if (($row['repeat_id'] != $series->repeat_id) || ($i == $n_rows - 1))
      {
        $calendar->addComponents($series->toEvents($method));
        // If we're at the start of a new series then create a new series
        if ($row['repeat_id'] != $series->repeat_id)
        {
          $series = new Series($row, $export_end);
          // And if this is the last row, ie the only member of the new series
          // then process the new series
          if ($i == $n_rows - 1)
          {
            $calendar->addComponents($series->toEvents($method));
          }
        }
      }
    }
  }

  echo $calendar->toString();
}
