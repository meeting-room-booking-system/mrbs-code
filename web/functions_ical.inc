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
