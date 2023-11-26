<?php
namespace MRBS;

use MRBS\ICalendar\RFC5545;
use MRBS\ICalendar\RFC5545Exception;
use MRBS\ICalendar\Series;
use PHPMailer\PHPMailer\PHPMailer;


// Gets a VTIMEZONE definition from the file system.
// Returns NULL if none found or it's empty.
function get_vtimezone_file(string $tz) : ?string
{
  global $zoneinfo_outlook_compatible;

  $tz_dir = ($zoneinfo_outlook_compatible) ? TZDIR_OUTLOOK : TZDIR;
  $tz_file = "$tz_dir/$tz.ics";

  if (!is_readable($tz_file))
  {
    return null;
  }

  $vcalendar = file_get_contents($tz_file);

  if (empty($vcalendar))
  {
    return null;
  }

  $vtimezone = RFC5545::extractVtimezone($vcalendar);

  return (empty($vtimezone)) ? null : $vtimezone;
}


// Gets a VTIMEZONE definition from the TZURL defined in the $vtimezone component
function get_vtimezone_url(string $vtimezone) : ?string
{
  // (Note that a VTIMEZONE component can contain a TZURL property which
  // gives the URL of the most up-to-date version.  Calendar applications
  // should be able to check this themselves, but we might as well give them
  // the most up-to-date version in the first place).
  $properties = explode("\r\n", RFC5545::unfold($vtimezone));
  foreach ($properties as $property)
  {
    if (utf8_strpos($property, "TZURL:") === 0)
    {
      $tz_url = substr($property, 6);  // 6 is the length of "TZURL:"
      break;
    }
  }

  if (!isset($tz_url))
  {
    trigger_error("The VTIMEZONE component didn't contain a TZURL property.", E_USER_NOTICE);
    return null;
  }

  $contents = url_get_contents($tz_url);
  $vcalendar = $contents['body'];

  if ($vcalendar === false)
  {
    trigger_error("MRBS: failed to download a new timezone definition from $tz_url", E_USER_WARNING);
    return null;
  }

  $new_vtimezone = RFC5545::extractVtimezone($vcalendar);
  if (empty($new_vtimezone))
  {
    trigger_error("MRBS: $tz_url did not contain a valid VTIMEZONE", E_USER_WARNING);
    return null;
  }

  return $new_vtimezone;
}


// Get a VTIMEZONE definition from the database.  Returns an array indexed by
// 'vtimezone' and 'last_updated', or NULL if none found.
function get_vtimezone_db(string $tz) : ?array
{
  global $zoneinfo_outlook_compatible;

  $sql = "SELECT vtimezone, last_updated
            FROM " . _tbl('zoneinfo') . "
           WHERE timezone=:timezone
             AND outlook_compatible=:outlook_compatible
           LIMIT 1";

  $sql_params = array(
    ':timezone' => $tz,
    ':outlook_compatible' => ($zoneinfo_outlook_compatible) ? 1 : 0
  );

  $res = db()->query($sql, $sql_params);

  return ($res->count() === 0) ? null : $res->next_row_keyed();
}


// Update the last_updated time for a timezone in the database
function touch_vtimezone_db(string $tz) : void
{
  global $zoneinfo_outlook_compatible;

  $sql = "UPDATE " . _tbl('zoneinfo') . "
             SET last_updated=:last_updated
           WHERE timezone=:timezone
             AND outlook_compatible=:outlook_compatible";

  $sql_params = array(
    ':last_updated' => time(),
    ':timezone' => $tz,
    ':outlook_compatible' => ($zoneinfo_outlook_compatible) ? 1 : 0
  );

  db()->command($sql, $sql_params);
}


// Insert a new VTIMEZONE definition for a timezone into the database
function insert_vtimezone_db(string $tz, string $vtimezone) : void
{
  global $zoneinfo_outlook_compatible;

  try
  {
    $sql = "INSERT INTO " . _tbl('zoneinfo') . "
                      (vtimezone, last_updated, timezone, outlook_compatible)
               VALUES (:vtimezone, :last_updated, :timezone, :outlook_compatible)";

    $sql_params = array(
      ':vtimezone' => $vtimezone,
      ':last_updated' => time(),
      ':timezone' => $tz,
      ':outlook_compatible' => ($zoneinfo_outlook_compatible) ? 1 : 0
    );

    db()->command($sql, $sql_params);
  }
  catch (DBException $e)
  {
    // Catch any database exception because it's just possible that someone else has inserted a row just
    // before us and then we'll get an error because of the unique constraint.   If we do then that's
    // fine and we can ignore it, but trigger an error anyway just in case it's some other kind of error.
    // (Ideally we'd want to be more specific about looking for constraint errors, but we have to consider
    // both MySQL and PostgreSQL).
    //
    // We could have used an INSERT IGNORE, but there isn't an easy PostgreSQL alternative until
    // PostgreSQL 9.5.
    //
    // We can't put a mutex lock round the SELECT - INSERT operation because we're normally already
    // inside another lock from edit_entry_handler and you can only, in the MYSQL implementation, have
    // one active lock.
    trigger_error($e->getMessage(), E_USER_NOTICE);
  }
}


// Update the database with a new VTIMEZONE definition for a timezone
function update_vtimezone_db(string $tz, string $vtimezone) : void
{
  global $zoneinfo_outlook_compatible;

  $sql = "UPDATE " . _tbl('zoneinfo') . "
             SET vtimezone=:vtimezone,
                 last_updated=:last_updated
           WHERE timezone=:timezone
             AND outlook_compatible=:outlook_compatible";

  $sql_params = array(
    ':vtimezone' => $vtimezone,
    ':last_updated' => time(),
    ':timezone' => $tz,
    ':outlook_compatible' => ($zoneinfo_outlook_compatible) ? 1 : 0
  );

  db()->command($sql, $sql_params);
}


// Get a VTIMEZONE component for the timezone $tz.
// If none available returns FALSE.
//
// We cache the latest VTIMEZONE component in the database.  If it has expired
// we go to the web for the latest version or if there's nothing in the database
// in the first place we try and populate it from the VTIMEZONE definitions in
// the filesystem.
function get_vtimezone(string $tz)
{
  global $zoneinfo_update, $zoneinfo_expiry;

  static $vtimezones = array();  // Cache the components for performance

  if (!isset($vtimezones[$tz]))
  {
    // Look for a timezone definition in the database
    $vtimezone_db = get_vtimezone_db($tz);
    if (isset($vtimezone_db['vtimezone']))
    {
      $vtimezones[$tz] = $vtimezone_db['vtimezone'];
      // If the definition has expired, and we're updating it, then get a fresh definition from the URL
      if ($zoneinfo_update && ((time() - $vtimezone_db['last_updated']) >= $zoneinfo_expiry))
      {
        $vtimezone = get_vtimezone_url($vtimezone_db['vtimezone']);
        if (isset($vtimezone))
        {
          // We've got a valid VTIMEZONE, so we can update the database and the static variable
          update_vtimezone_db($tz, $vtimezone);
          $vtimezones[$tz] = $vtimezone;
        }
        else
        {
          // If we didn't manage to get a new VTIMEZONE, update the last_updated field
          // so that MRBS will not try again until after the expiry interval has passed.
          // This will mean that we don't keep encountering a timeout delay. (The most
          // likely reason that we couldn't get a new VTIMEZONE is that the site doesn't
          // have external internet access, so there's no point in retrying for a while).
          touch_vtimezone_db($tz);
        }
      }
    }
    else
    {
      // If there's nothing in the database, get one from the filesystem
      $vtimezone = get_vtimezone_file($tz);
      if (isset($vtimezone))
      {
        // And put it in the database if it's valid
        insert_vtimezone_db($tz, $vtimezone);
        $vtimezones[$tz] = $vtimezone;
      }
      else
      {
        // Everything has failed
        $vtimezones[$tz] = false;
      }
    }
  }

  return $vtimezones[$tz];
}


// Gets a username given an ORGANIZER value.   Returns NULL if none found
function get_create_by(string $organizer)
{
  global $auth, $mail_settings;

  // Get the email address.   Stripping off the 'mailto' is a very simplistic
  // method.  It will work in the majority of cases, but this needs to be improved
  $email = preg_replace('/^mailto:/', '', $organizer);

  // If we're using the 'db' auth type, then look the username up in the users table
  if ($auth['type'] == 'db')
  {
    $sql = "SELECT name
              FROM " . _tbl('users') . "
             WHERE email=?";

    $res = db()->query($sql, array($email));

    if ($res->count() == 0)
    {
      return null;
    }
    else
    {
      if ($res->count() > 1)
      {
        // Could maybe do something better here
        trigger_error("ORGANIZER email address not unique", E_USER_NOTICE);
      }
      $row = $res->next_row_keyed();
      return $row['name'];
    }
  }
  // If we're using LDAP we can get the username given the email address
  else if($auth['type'] == 'ldap')
  {
    // We need a function that is the inverse of authLdapGetEmail()
    trigger_error("Cannot yet get username when using LDAP", E_USER_WARNING);
    return null;
  }
  // Otherwise we derive the username from the email address
  else
  {
    // We just want the string up to the '@'
    $name = preg_replace('/@.*$/', '', $email);
    // And add on the suffix if there is one
    if (isset($mail_settings['username_suffix']))
    {
      $name .= $mail_settings['username_suffix'];
    }
    return $name;
  }
}


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
            $rfc5545day = substr($byday_day, -2);     // the last two characters of the string
            $nth = substr($byday_day, 0, -2);  // everything except the last two characters
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
  require_once "functions_mail.inc";

  global $mail_settings, $timezone, $default_area_room_delimiter;
  global $partstat_accepted;

  $vtimezone = get_vtimezone($timezone);

  $results = array();

  $results[] = "BEGIN:VEVENT";
  $results[] = "UID:" . $data['ical_uid'];  // compulsory
  $results[] = "DTSTAMP:" . gmdate(RFC5545::DATETIME_FORMAT . '\Z');  // compulsory
  $last_modified = empty($data['last_updated']) ? time() : $data['last_updated'];
  $results[] = "LAST-MODIFIED:" . gmdate(RFC5545::DATETIME_FORMAT . '\Z', $last_modified);


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
    $results[] = "DTSTART:" . gmdate(RFC5545::DATETIME_FORMAT . '\Z', $data['start_time']);
    $results[] = "DTEND:" . gmdate(RFC5545::DATETIME_FORMAT . '\Z', $data['end_time']);
  }
  else
  {
    $results[] = "DTSTART;TZID=$timezone:" . date(RFC5545::DATETIME_FORMAT, $data['start_time']);
    $results[] = "DTEND;TZID=$timezone:" . date(RFC5545::DATETIME_FORMAT, $data['end_time']);
  }

  if ($series)
  {
    $results[] = "RRULE:" . $data['repeat_rule']->toRFC5545Rule();
    if (!empty($data['skip_list']))
    {
      $results[] = RFC5545::createExdateProperty(
        $data['skip_list'],
        ($vtimezone === false) ? null : $timezone
      );
    }
  }
  $results[] = "SUMMARY:" . RFC5545::escapeText($data['name']);
  $results[] = "DESCRIPTION:" . RFC5545::escapeText($data['description']);
  $results[] = "LOCATION:" . RFC5545::escapeText($data['area_name'] . $default_area_room_delimiter . $data['room_name']);
  $results[] = "SEQUENCE:" . $data['ical_sequence'];
  // If this is an individual member of a series then set the recurrence id
  if (!$series && ($data['entry_type'] != ENTRY_SINGLE))
  {
    $results[] = "RECURRENCE-ID:" . $data['ical_recur_id'];
  }
  // STATUS:  As we can have confirmed and tentative bookings we will send that information
  // in the Status property, as some calendar apps will use it.   For example Outlook 2007 will
  // distinguish between tentative and confirmed bookings.  However, having sent it we need to
  // send a STATUS:CANCELLED on cancellation.    It's not clear to me from the spec whether this
  // is strictly necessary, but it can do no harm and there are some apps that seem to need it -
  // for example Outlook 2003 (but not 2007).
  if ($method == "CANCEL")
  {
    $results[] = "STATUS:CANCELLED";
  }
  else
  {
    $results[] = "STATUS:" . ((empty($data['tentative'])) ? "CONFIRMED" : "TENTATIVE");
  }

  /*
  Class is commented out for the moment.  To be useful it probably needs to go
  hand in hand with an ORGANIZER, otherwise people won't be able to see their own
  bookings
  // CLASS
  $results[] = "CLASS:" . (($data['private']) ? "PRIVATE" : "PUBLIC");
  */

  // ORGANIZER
  // The organizer is MRBS.   We don't make the create_by user the organizer because there
  // are some mail systems such as IBM Domino that silently discard the email notification
  // if the organizer's email address is the same as the recipient's - presumably because
  // they assume that the recipient already knows about the event.

  $organizer = PHPMailer::parseAddresses($mail_settings['organizer'], true, get_mail_charset())[0];
  if (!isset($organizer['name']) || ($organizer['name'] === ''))
  {
    $organizer['name'] = get_mail_vocab('mrbs');
  }

  if (isset($organizer['address']) && ($organizer['address'] !== ''))
  {
    $results[] = 'ORGANIZER;CN=' . RFC5545::escapeQuotedString($organizer['name']) . ':mailto:' . $organizer['address'];
  }

  // Put the people on the "to" list as required participants and those on the cc
  // list as non-participants.   In theory the email client can then decide whether
  // to enter the booking automatically on the user's calendar - although at the
  // time of writing (Dec 2010) there don't seem to be any that do so!
  if (!empty($addresses))
  {
    $attendees = $addresses;  // take a copy of $addresses as we're going to alter it
    $keys = array('to', 'cc');  // We won't do 'bcc' as they need to stay blind
    foreach ($keys as $key)
    {
      $attendees[$key] = PHPMailer::parseAddresses($attendees[$key], true, get_mail_charset());  // convert the list into an array
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
          // Use the common name if there is one
          $partstat = ($partstat_accepted) ? "ACCEPTED" : "NEEDS-ACTION";
          $results[] = "ATTENDEE;" .
                       ((isset($attendee['name']) && ($attendee['name'] !== '')) ? 'CN=' . RFC5545::escapeQuotedString($attendee['name']) . ';' : '') .
                       "PARTSTAT=$partstat;ROLE=$role:mailto:" . $attendee['address'];
        }
      }
      // TODO: custom fields
    }
  }

  // MRBS specific properties
  // Type
  $results[] = "X-MRBS-TYPE:" . RFC5545::escapeText(get_type_vocab($data['type']));
  // Registration properties
  if (isset($data['allow_registration']))
  {
    $properties = array(
      'allow_registration',
      'registrant_limit',
      'registrant_limit_enabled',
      'registration_opens',
      'registration_opens_enabled',
      'registration_closes',
      'registration_closes_enabled'
    );
    foreach ($properties as $property) {
      $results[] = "X-MRBS-" . strtoupper(str_replace('_', '-', $property)) . ":" . $data[$property];
    }
    // Registrants (but only for individual entries)
    if (!$series) {
      $registrants = get_registrants($data['id'], false);
      foreach ($registrants as $registrant) {
        // We can't use the ATTENDEE property because its value has to be a URI.
        $results[] = "X-MRBS-REGISTRANT;X-MRBS-REGISTERED=" . $registrant['registered'] .
          ';' . 'X-MRBS-CREATE-BY=' . RFC5545::escapeQuotedString($registrant['create_by']) .
          ':' . RFC5545::escapeText($registrant['username']);
      }
    }
  }

  $results[] = "END:VEVENT";

  // No CRLF at end: that will be added later
  return implode(RFC5545::EOL, $results);
}


// Creates an iCalendar object in RFC 5545 format
//    $method      string   the RFC 5545 METHOD (eg "REQUEST", "PUBLISH", "CANCEL")
//    $components  array    an array of iCalendar components, each a string
function create_icalendar(string $method, array $components) : string
{
  require_once "version.inc";

  global $timezone;

  $vtimezone = get_vtimezone($timezone);

  $results = array();
  $results[] = "BEGIN:VCALENDAR";
  // Compulsory properties
  $results[] = "PRODID:-//MRBS//NONSGML " . get_mrbs_version() . "//EN";
  $results[] = "VERSION:2.0";
  // Optional properties
  $results[] = "CALSCALE:GREGORIAN";
  $results[] = "METHOD:$method";

  // Add in the VTIMEZONE component if there is one (see the comment in
  // create_ical_event() above)
  if ($vtimezone)
  {
    $results[] = $vtimezone;
  }

  // Add in each component
  foreach ($components as $component)
  {
    $results[] = $component;
  }

  $results[] = "END:VCALENDAR";

  $result = implode(RFC5545::EOL, $results);
  $result .= RFC5545::EOL;  // Has to end with the correct line ending
  $result = RFC5545::fold($result);

  return $result;
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

  // Initialize an array to hold the events and a variable to keep track
  // of the last repeat id we've seen
  $ical_events = array();
  $n_rows = $res->count();

  for ($i=0; (false !== ($row = $res->next_row_keyed())); $i++)
  {
    unpack_status($row);
    // If this is an individual entry, then construct an event
    if (!isset($row['rep_type']) || ($row['rep_type'] == RepeatRule::NONE))
    {
      $ical_events[] = create_ical_event($method, $row);
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
        $ical_events = array_merge($ical_events, $series->toEvents($method));
        // If we're at the start of a new series then create a new series
        if ($row['repeat_id'] != $series->repeat_id)
        {
          $series = new Series($row, $export_end);
          // And if this is the last row, ie the only member of the new series
          // then process the new series
          if ($i == $n_rows - 1)
          {
            $ical_events = array_merge($ical_events, $series->toEvents($method));
          }
        }

      }
    }
  }

  // Build the iCalendar from the array of events and output it
  $icalendar = create_icalendar($method, $ical_events);
  echo $icalendar;
}
