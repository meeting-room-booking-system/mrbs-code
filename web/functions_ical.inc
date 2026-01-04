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
    $repeat_end_date->setTimestamp(RFC5545::getTimestamp($rules['UNTIL']));
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


// Create an RFC 5545 iCalendar Event component
function create_ical_event(string $method, array $data, ?array $addresses=null, bool $series=false) : string
{
  // TODO: return an Event object, rather than a string
  // TODO: custom fields

  global $mail_settings, $timezone, $default_area_room_delimiter, $standard_fields;
  global $partstat_accepted;

  $vtimezone = Timezone::createFromTimezoneName($timezone);

  $event = new Event();
  // REQUIRED properties, but MUST NOT occur more than once
  $event->addProperty(new Property('UID', $data['ical_uid']));
  $event->addProperty(new Property('DTSTAMP', gmdate(RFC5545::DATETIME_FORMAT . '\Z')));
  // Optional properties
  $last_modified = empty($data['last_updated']) ? time() : $data['last_updated'];
  $event->addProperty(new Property('LAST-MODIFIED', gmdate(RFC5545::DATETIME_FORMAT . '\Z', $last_modified)));

  // Note: we try and write the event times in the format of a local time with
  // a timezone reference (ie RFC 5545 Form #3).   Only if we can't do that do we
  // fall back to a UTC time (ie RFC 5545 Form #2).
  //
  // The reason for this is that although this is not required by RFC 5545 (see
  // Appendix A.2), its predecessor, RFC 2445, did require it for recurring
  // events and is the standard against which older applications, notably Exchange
  // 2007, are written.   Note also that when using a local timezone format the
  // VTIMEZONE component must be provided (this is done in create_icalendar() ).  Some
  // applications will work without the VTIMEZONE component, but many follow the
  // standard and do require it.  Here is an extract from RFC 2445:

  // 'When used with a recurrence rule, the "DTSTART" and "DTEND" properties MUST be
  // specified in local time and the appropriate set of "VTIMEZONE" calendar components
  // MUST be included.'

  if ($vtimezone === false)
  {
    $event->addProperty(new Property('DTSTART', gmdate(RFC5545::DATETIME_FORMAT . '\Z', $data['start_time'])));
    $event->addProperty(new Property('DTEND', gmdate(RFC5545::DATETIME_FORMAT . '\Z', $data['end_time'])));
  }
  else
  {
    $property = new Property('DTSTART', date(RFC5545::DATETIME_FORMAT, $data['start_time']));
    $property->addParameter('TZID', $timezone);
    $event->addProperty($property);
    $property = new Property('DTEND', date(RFC5545::DATETIME_FORMAT, $data['end_time']));
    $property->addParameter('TZID', $timezone);
    $event->addProperty($property);
  }

  if ($series)
  {
    $event->addProperty(new Property('RRULE', $data['repeat_rule']->toRFC5545Rule()));
    if (!empty($data['skip_list']))
    {
      $property = Property::createFromTimestamps('EXDATE', $data['skip_list'], ($vtimezone === false) ? null : $timezone);
      $event->addProperty($property);
    }
  }

  $event->addProperty(new Property('SUMMARY', $data['name']));
  if (isset($data['description']))
  {
    $event->addProperty(new Property('DESCRIPTION', $data['description']));
  }
  $event->addProperty(new Property('LOCATION', $data['area_name'] . $default_area_room_delimiter . $data['room_name']));
  $event->addProperty(new Property('SEQUENCE', $data['ical_sequence']));
  // If this is an individual member of a series, then set the recurrence id.
  if (!$series && ($data['entry_type'] != ENTRY_SINGLE))
  {
    $event->addProperty(new Property('RECURRENCE-ID', $data['ical_recur_id']));
  }
  // STATUS: As we can have confirmed and tentative bookings, we will send that information
  // in the Status property, as some calendar apps will use it.  For example, Outlook 2007 will
  // distinguish between tentative and confirmed bookings.  However, having sent it, we need to
  // send a STATUS:CANCELLED on cancellation.  It's not clear from the spec whether this is
  // strictly necessary, but it can do no harm, and there are some apps that seem to need it -
  // for example, Outlook 2003 (but not 2007).
  if ($method === 'CANCEL')
  {
    $status = 'CANCELLED';
  }
  else
  {
    $status = (empty($data['tentative'])) ? 'CONFIRMED' : 'TENTATIVE';
  }
  $event->addProperty(new Property('STATUS', $status));

  /*
  Class is commented out for the moment.  To be useful it probably needs to go
  hand in hand with an ORGANIZER, otherwise people won't be able to see their own
  bookings
  $event->addProperty(new Property('CLASS', ($data['private']) ? 'PRIVATE' : 'PUBLIC'));
  */

  // ORGANIZER
  // The organizer is MRBS.  We don't make the create_by user the organizer because there
  // are some mail systems such as IBM Domino that silently discard the email notification
  // if the organizer's email address is the same as the recipient's - presumably because
  // they assume that the recipient already knows about the event.

  $organizer_addresses = parse_addresses($mail_settings['organizer']);
  if (empty($organizer_addresses))
  {
    // TODO: Review whether the ORGANIZER property is required.
    // RFC 5545 states:
    // "This property MUST be specified in an iCalendar object
    // that specifies a group-scheduled calendar entity.  This property
    // MUST be specified in an iCalendar object that specifies the
    // publication of a calendar user's busy time.  This property MUST
    // NOT be specified in an iCalendar object that specifies only a time
    // zone definition or that defines calendar components that are not
    // group-scheduled components, but are components only on a single
    // user's calendar."
    // Does MRBS count as a user? If so, does this mean that as long as
    // there is at least one ATTENDEE the property MUST be specified?
    $message = "The value '" . $mail_settings['organizer'] . "' supplied for " . '$mail_settings["organizer"]' .
      " is not a valid RFC822-style email address.  Please check your MRBS config file.";
    throw new Exception($message);
  }

  $organizer = $organizer_addresses[0];
  if (isset($organizer['address']) && ($organizer['address'] !== ''))
  {
    if (!isset($organizer['name']) || ($organizer['name'] === ''))
    {
      $organizer['name'] = get_mail_vocab('mrbs');
    }
    $property = new Property('ORGANIZER', 'mailto:' . $organizer['address']);
    $property->addParameter('CN', $organizer['name']);
    $event->addProperty($property);
  }

  // Put the people on the "to" list as required participants and those on the cc
  // list as non-participants.  In theory the email client can then decide whether
  // to enter the booking automatically on the user's calendar - although at the
  // time of writing (Dec 2010) there don't seem to be any that do so!
  if (!empty($addresses))
  {
    $attendees = $addresses;  // take a copy of $addresses as we're going to alter it
    $keys = array('to', 'cc');  // We won't do 'bcc' as they need to stay blind
    foreach ($keys as $key)
    {
      $attendees[$key] = parse_addresses($attendees[$key]);  // convert the list into an array
    }
    foreach ($keys as $key)
    {
      foreach ($attendees[$key] as $attendee)
      {
        if (!empty($attendee))
        {
          switch ($key)
          {
            case 'to':
              $role = "REQ-PARTICIPANT";
              break;
            default:
              if (in_array($attendee, $attendees['to']))
              {
                // It's possible that an address could appear on more than one
                // line, in which case we only want to have one ATTENDEE property
                // for that address and we'll choose the REQ-PARTICIPANT.   (Apart
                // from two conflicting ATTENDEES not making sense, it also breaks
                // some applications, eg Apple Mail/iCal)
                continue 2;  // Move on to the next attendeee
              }
              $role = "NON-PARTICIPANT";
              break;
          }
          $property = new Property('ATTENDEE', 'mailto:' . $attendee['address']);
          // Use the common name if there is one
          if (isset($attendee['name']) && ($attendee['name'] !== ''))
          {
            $property->addParameter('CN', $attendee['name']);
          }
          $property->addParameter('ROLE', $role);
          $property->addParameter('PARTSTAT', ($partstat_accepted) ? 'ACCEPTED' : 'NEEDS-ACTION');
          $event->addProperty($property);
        }
      }
    }
  }

  // MRBS specific properties
  // Type
  $event->addProperty(new Property('X-MRBS-TYPE', get_type_vocab($data['type'])));

  // Registration properties
  if (isset($data['allow_registration']))
  {
    $properties = [
      'allow_registration',
      'registrant_limit',
      'registrant_limit_enabled',
      'registration_opens',
      'registration_opens_enabled',
      'registration_closes',
      'registration_closes_enabled'
    ];
    foreach ($properties as $property)
    {
      $event->addProperty(new Property('X-MRBS-' . strtoupper(str_replace('_', '-', $property)), strval($data[$property])));
    }
    // Registrants (but only for individual entries)
    if (!$series)
    {
      $registrants = get_registrants($data['id'], false);
      foreach ($registrants as $registrant)
      {
        // We can't use the ATTENDEE property because its value has to be a URI.
        $property = new Property('X-MRBS-REGISTRANT', $registrant['username']);
        $property->addParameter('X-MRBS-REGISTERED', strval($registrant['registered']));
        $property->addParameter('X-MRBS-CREATE-BY', $registrant['create_by']);
        $event->addProperty($property);
      }
    }
  }

  // Custom fields
  // These fields have already been handled above.
  $already_handled = [
    'last_updated',
    'tentative',
    'area_name',
    'room_name'
  ];

  // These fields are in the area table and can be ignored.
  $area_table_fields = [
    'approval_enabled',
    'confirmation_enabled'
  ];

  // These fields are in the entry table and can be ignored for the moment.  However we need to do something
  // in the future about 'awaiting_approval' and 'private'.
  // TODO
  $special_fields = [
    'awaiting_approval',
    'private',
    'repeat_rule',
    'skip_list'
  ];

  $ignore_fields = array_merge($standard_fields['entry'], $already_handled, $area_table_fields, $special_fields);

  foreach ($data as $key => $value)
  {
    if (!in_array($key, $ignore_fields) && isset($value))
    {
      // Column names are case-insensitive in MySQL, so we can safely convert them to upper-case to comply with
      // the RFC 5545 standard that they are case-insensitive but by convention written in upper-case.  In PostgreSQL
      // column names are also case-insensitive, unless they are quoted, in which case they are case-sensitive.  For
      // PostgreSQL it is therefore recommended to use unquoted column names.
      // Property names can only consist of ALPHA, DIGIT and "-" characters, so we convert "_" to "-".
      $property = new Property('X-MRBS-' . mb_strtoupper(str_replace('_', '-', $key)), $value);
      $event->addProperty($property);
    }
  }

  return $event->toString();
}


// Creates an iCalendar object in RFC 5545 format
//    $method      string   the RFC 5545 METHOD (eg "REQUEST", "PUBLISH", "CANCEL")
//    $components  array    an array of iCalendar components, each a string
function create_icalendar(string $method, array $components) : string
{
  global $timezone;

  $calendar = new Calendar($method);

  // Add in the VTIMEZONE component if there is one (see the comment in
  // create_ical_event() above)
  $vtimezone = Timezone::createFromTimezoneName($timezone);
  if ($vtimezone)
  {
    $calendar->addComponent($vtimezone);
  }

  // Add in each component
  foreach ($components as $component)
  {
    $calendar->addComponent(ComponentFactory::createFromString($component));
  }

  return $calendar->toString();
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
      $calendar->addComponent(ComponentFactory::createFromString(create_ical_event($method, $row)));
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
