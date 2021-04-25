<?php
namespace MRBS;

use MRBS\ICalendar\Series;


// Extracts a VTIMEZONE component from a VCALENDAR.
function extract_vtimezone($vcalendar)
{
  // The VTIMEZONE components are enclosed in a VCALENDAR, so we want to
  // extract the VTIMEZONE component.
  $vtimezone = strstr($vcalendar, "BEGIN:VTIMEZONE");
  // Would be simpler to use strstr() again, but the optional
  // third parameter was only introduced in PHP 5.3.0
  $pos = utf8_strpos($vtimezone, "END:VTIMEZONE");
  if ($pos !== false)
  {
    $vtimezone = substr($vtimezone, 0, $pos);
    if (!empty($vtimezone))
    {
      $vtimezone .= "END:VTIMEZONE";
      return $vtimezone;
    }
  }
  return '';  // There wasn't a VTIMEZONE component
}


// Get a VTIMEZONE component for the timezone $tz.
// If none available returns FALSE.
//
// We cache the latest VTIMEZONE component in the database.  If it has expired
// we go to the web for the latest version or if there's nothing in the database
// in the first place we try and populate it from the VTIMEZONE definitions in
// the filesystem.
function get_vtimezone($tz)
{
  global $zoneinfo_update, $zoneinfo_expiry, $zoneinfo_outlook_compatible;

  static $vtimezone = array();  // Cache the componernts for performance

  if (!isset($vtimezone[$tz]))
  {
    $vtimezone[$tz] = false;  // Default value in case we don't find one

    $tz_dir = ($zoneinfo_outlook_compatible) ? TZDIR_OUTLOOK : TZDIR;
    $tz_file = "$tz_dir/$tz.ics";  // A fallback for a VTIMEZONE definition

    // Convert booleans into 1 or 0 (necessary for the database)
    $zoneinfo_outlook_compatible = ($zoneinfo_outlook_compatible) ? 1 : 0;

    // Look and see if there's a component in the database
    $sql = "SELECT vtimezone, last_updated
              FROM " . _tbl('zoneinfo') . "
             WHERE timezone=?
               AND outlook_compatible=?
             LIMIT 1";
    $res = db()->query($sql, array($tz,$zoneinfo_outlook_compatible));

    if ($res->count() > 0)
    {
      // If there's something in the database and it hasn't expired, then
      // that's it.
      $row = $res->next_row_keyed();
      $vtimezone[$tz] = $row['vtimezone'];
      if ($zoneinfo_update && ((time() - $row['last_updated']) >= $zoneinfo_expiry))
      {
        // Otherwise, provided that the config variable $zoneinfo_update
        // is true, we get the URL of the latest version on the web,
        // and update the database.
        //
        // (Note that a VTIMEZONE component can contain a TZURL property which
        // gives the URL of the most up to date version.  Calendar applications
        // should be able to check this themselves, but we might as well give them
        // the most up to date version in the first place).
        $properties = explode("\r\n", ical_unfold($vtimezone[$tz]));
        foreach ($properties as $property)
        {
          if (utf8_strpos($property, "TZURL:") === 0)
          {
            $tz_url = substr($property, 6);  // 6 is the length of "TZURL:"
          }
        }

        $contents = url_get_contents($tz_url);
        $vcalendar = $contents['body'];

        if ($vcalendar === false)
        {
          trigger_error("MRBS: failed to download a new timezone definition from $tz_url", E_USER_WARNING);
        }
        else
        {
          $new_vtimezone = extract_vtimezone($vcalendar);
          if (empty($new_vtimezone))
          {
            trigger_error("MRBS: $tz_url did not contain a valid VTIMEZONE", E_USER_WARNING);
          }
          else
          {
            // We've got a valid VTIMEZONE, so we can overwrite $vtimezone
            $vtimezone[$tz] = $new_vtimezone;
          }
        }

        // Update the database, whether we've successfully got a new VTIMEZONE or not.
        // If we didn't manage to get a new VTIMEZONE, updating the database will update
        // the last_updated field and mean that MRBS will not try again until after the
        // expiry interval has passed.  This will mean that we don't introduce a few
        // seconds delay every time this file is included.  (The most likely reason that
        // we couldn't get a new VTIMEZONE is that the site doesn't have external internet
        // access, so there's no point in retrying for a while).
        $sql = "UPDATE " . _tbl('zoneinfo') . "
                   SET vtimezone=?,
                       last_updated=?
                 WHERE timezone=?
                   AND outlook_compatible=?";
        db()->command($sql, array($vtimezone[$tz], time(), $tz, $zoneinfo_outlook_compatible));
      }
    }
    // There's nothing in the database, so try and get a VTIMEZONE component
    // from the filesystem.
    elseif (is_readable($tz_file))
    {
      $vcalendar = file_get_contents($tz_file);

      if ($vcalendar)
      {
        $vtimezone[$tz] = extract_vtimezone($vcalendar);
        if (!empty($vtimezone[$tz]))
        {
          try
          {
            $sql = "INSERT INTO " . _tbl('zoneinfo') . "
                                (timezone, outlook_compatible, vtimezone, last_updated)
                         VALUES (?, ?, ?, ?)";
            db()->command($sql, array($tz, $zoneinfo_outlook_compatible, $vtimezone[$tz], time()));
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
      }
    }
  }

  return $vtimezone[$tz];
}


// Returns a UNIX timestamp given an RFC5545 date or date-time
// $params is an optional second argument and is an array of property parameters
function get_time($value)
{
  if (func_num_args() > 1)
  {
    $params = func_get_arg(1);
  }
  // If we haven't got any parameters default to "UTC".   Not strictly correct,
  // but in most cases it will be true.  Need to do something better.
  if (empty($params))
  {
    $this_timezone = "UTC";
  }

  $value_type = "DATE_TIME";  // the default


  // Work out which, if any, parameters have been set
  if (isset($params))
  {
    foreach ($params as $param_name => $param_value)
    {
      switch ($param_name)
      {
        case 'VALUE':
          $value_type = $param_value;
          break;
        case 'TZID':
          $this_timezone = $param_value;
          break;
      }
    }
  }

  if (utf8_strpos($value, 'Z') !== false)
  {
    $value = str_replace('Z', '', $value);
    $this_timezone = 'UTC';
  }
  if ($value_type == "DATE_TIME")
  {
    list($date, $time) = explode('T', $value, 2);
  }
  else
  {
    $date = $value;
    $time = '000000';
  }
  $year = substr($date, 0, 4);
  $month = substr($date, 4, 2);
  $day = substr($date, 6, 2);
  $hour = substr($time, 0, 2);
  $minute = substr($time, 2, 2);
  $second = substr($time, 4, 2);

  if (isset($this_timezone))
  {
    if (utf8_strtoupper($this_timezone) == 'UTC')
    {
      $result = gmmktime($hour, $minute, $second, $month, $day, $year);
    }
    else
    {
      $old_timezone = mrbs_default_timezone_get();
      mrbs_default_timezone_set($this_timezone);
      $result = mktime($hour, $minute, $second, $month, $day, $year);
      mrbs_default_timezone_set($old_timezone);
    }
  }
  else
  {
    if ($value_type == "DATE_TIME")
    {
      trigger_error("Floating times not supported", E_USER_WARNING);
    }
    $result = mktime($hour, $minute, $second, $month, $day, $year);
  }
  return $result;
}


// Gets a username given an ORGANIZER value.   Returns NULL if none found
function get_create_by($organizer)
{
  global $auth, $mail_settings;

  // Get the email address.   Stripping off the 'mailto' is a very simplistic
  // method.  It will work in the majority of cases, but this needs to be improved
  $email = preg_replace('/^mailto:/', '', $organizer);

  // If we're using the 'db' auth rtpe, then look the username up in the users table
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


// Returns an MRBS rep_opt string given an array of RFC 5545 days
function get_rep_opt($byday_days)
{
  global $RFC_5545_days;

  $rep_opt = '';
  foreach ($RFC_5545_days as $day)
  {
    $rep_opt .= (in_array($day, $byday_days)) ? '1' : '0';
  }
  return $rep_opt;
}


// Given an RFC 5545 recurrence rule, returns an array giving the MRBS repeat
// details.   Indexed by rep_type, rep_interval, rep_opt, end_date
// Returns FALSE on failure with error messages being returned in the array $errors
function get_repeat_details($rrule, $start_time, &$errors)
{
  global $RFC_5545_days;

  // Set up the result array with safe defaults
  $result = array('rep_type' => REP_NONE,
                  'rep_opt' => '0000000',
                  'rep_interval' => 1,
                  'end_date' => 0);
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

  switch ($rules['FREQ'])
  {
    case 'DAILY':
      $result['rep_type'] = REP_DAILY;
      break;
    case 'WEEKLY':
      $result['rep_type'] = REP_WEEKLY;
      if (isset($rules['BYDAY']))
      {
        $byday_days = explode(',', $rules['BYDAY']);
      }
      else
      {
        // If there's no repeat day specified in the RRULE then
        // 'the day is gotten from "DTSTART"'
        $byday_days = array($RFC_5545_days[date('w', $start_time)]);
      }
      $result['rep_opt'] = get_rep_opt($byday_days);
      break;
    case 'MONTHLY':
      $result['rep_type'] = REP_MONTHLY;
      if (!isset($rules['BYDAY']))
      {
        $result['month_absolute'] = $rules['BYMONTHDAY'];
      }
      else
      {
        $byday_days = explode(',', $rules['BYDAY']);
        if (count($byday_days) > 1)
        {
          $errors[] = get_vocab("more_than_one_BYDAY") . $result['freq'];
        }
        foreach ($byday_days as $byday_day)
        {
          $day = substr($byday_day, -2);     // the last two characters of the string
          $nth = substr($byday_day, 0, -2);  // everything except the last two characters
          if ($nth === false)
          {
            // "If an integer modifier is not present, it means all days of this
            // type within the specified frequency.  For example, within a MONTHLY
            // rule, MO represents all Mondays within the month." [RFC 5545]
            // So that comes to the same thing as a WEEKLY repeat
            $result['rep_type'] = REP_WEEKLY;
            $result['rep_opt'] = get_rep_opt(array($day));
          }
          elseif (($nth == '5') || ($nth == '-5'))
          {
            $errors[] = get_vocab("BYDAY_equals_5") . " $nth$day";
          }
          else
          {
            $result['relative'] = $byday_day;
          }
        }
      }
      break;
    case 'YEARLY':
      $result['rep_type'] = REP_YEARLY;
      break;
    default:
      $errors[] = get_vocab("unsupported_FREQ") . $rules['FREQ'];
      break;
  }

  if (isset($rules['interval']) && ($rules['interval'] > 1))
  {
    $result['rep_interval'] = $rules['interval'];
  }
  else
  {
    $result['rep_interval'] = 1;
  }

  if (isset($rules['UNTIL']))
  {
    // Strictly speaking "the value of the UNTIL rule part MUST have the same
    // value type as the "DTSTART" property".   So we should really tell get_time()
    // the value type.  But "if the "DTSTART" property is specified as a date with UTC
    // time or a date with local time and time zone reference, then the UNTIL rule
    // part MUST be specified as a date with UTC time" - so in nearly all cases
    // supported by MRBS the value will be a UTC time.
    $result['end_date'] = get_time($rules['UNTIL']);
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

  return (empty($errors)) ? $result : false;
}


// "Folds" lines longer than 75 octets.  Multi-byte safe.
//
// "Lines of text SHOULD NOT be longer than 75 octets, excluding the line
// break.  Long content lines SHOULD be split into a multiple line
// representations using a line "folding" technique.  That is, a long
// line can be split between any two characters by inserting a CRLF
// immediately followed by a single linear white-space character (i.e.,
// SPACE or HTAB).  Any sequence of CRLF followed immediately by a
// single linear white-space character is ignored (i.e., removed) when
// processing the content type."  (RFC 5545)
function ical_fold($str)
{
  $line_split = "\r\n ";  // The RFC also allows a tab instead of a space

  // We assume that we are using UTF-8 and therefore that a space character
  // is one octet long.   If we ever switched for some reason to using for
  // example UTF-16 this assumption would be invalid.
  if ((get_charset() != 'utf-8') || (get_mail_charset() != 'utf-8'))
  {
    trigger_error("MRBS: internal error - using unsupported character set", E_USER_WARNING);
  }
  $space_octets = 1;

  $octets_max = 75;

  $result = '';
  $octets = 0;
  $byte_index = 0;

  while (isset($byte_index))
  {
    // Get the next character
    $prev_byte_index = $byte_index;
    $char = utf8_seq($str, $byte_index);

    $char_octets = $byte_index - $prev_byte_index;
    // If it's a CR then look ahead to the following character, if there is one
    if (($char == "\r") && isset($byte_index))
    {
      $this_byte_index = $byte_index;
      $next_char = utf8_seq($str, $byte_index);
      // If that's a LF then take the CR, and advance by one character
      if ($next_char == "\n")
      {
        $result .= $char;    // take the CR
        $char = $next_char;  // advance by one character
        $octets = 0;         // reset the octet counter to the beginning of the line
        $char_octets = 0;    // and pretend the LF is zero octets long so that after
                             // we've added it in we're still at the beginning of the line
      }
      // otherwise stay where we were
      else
      {
        $byte_index = $this_byte_index;
      }
    }
    // otherwise if this character will take us over the octet limit for the line,
    // fold the line and set the octet count to however many octets a space takes
    // (the folding involves adding a CRLF followed by one character, a space or a tab)
    //
    // [Note:  It's not entirely clear from the RFC whether the octet that is introduced
    // when folding counts towards the 75 octets.   Some implementations (eg Google
    // Calendar as of Jan 2011) do not count it.   However it can do no harm to err on
    // the safe side and include the initial whitespace in the count.]
    elseif (($octets + $char_octets) > $octets_max)
    {
      $result .= $line_split;
      $octets = $space_octets;
    }
    // finally add the character to the result string and up the octet count
    $result .= $char;
    $octets += $char_octets;
  }
  return $result;
}


// Reverse the RFC 5545 folding process, which splits lines into groups
// of max 75 octets separated by 'CRLFspace' or 'CRLFtab'
function ical_unfold($str)
{
  // Deal with the trivial cases
  if (!isset($str))
  {
    return null;
  }
  if ($str == '')
  {
    return $str;
  }

  // We've got a non-zero length string
  $result = '';
  $byte_index = 0;

  while (isset($byte_index))
  {
    // Get the next character
    $char = utf8_seq($str, $byte_index);
    // If it's a CR then look ahead to the following character, if there is one
    if (($char == "\r") && isset($byte_index))
    {
      $char = utf8_seq($str, $byte_index);
      // If that's a LF then look ahead to the next character, if there is one
      if (($char == "\n") && isset($byte_index))
      {
        $char = utf8_seq($str, $byte_index);
        // If that's a space or a tab then ignore it because we've just had a fold
        // sequence.    Otherwise add the characters into the result string.
        if (($char != " ") && ($char != "\t"))
        {
          $result .= "\r\n" . $char;
        }
      }
      else
      {
        $result .= "\r" . $char;
      }
    }
    else
    {
      $result .= $char;
    }
  }

  return $result;
}


// Escape text for use in an iCalendar text value
function ical_escape_text($str)
{
  // Escape '\'
  $str = str_replace("\\", "\\\\", $str);
  // Escape ';'
  $str = str_replace(";", "\;", $str);
  // Escape ','
  $str = str_replace(",", "\,", $str);
  // EOL can only be \n
  $str = str_replace("\r\n", "\n", $str);
  // Escape '\n'
  $str = str_replace("\n", "\\n", $str);
  // Escape '\N'
  $str = str_replace("\N", "\\N", $str);
  return $str;
}


// Reverses RFC 5545 escaping of text
function ical_unescape_text($str)
{
  // Unescape '\N'
  $str = str_replace("\\N", "\N", $str);
  // Unescape '\n'
  $str = str_replace("\\n", "\n", $str);
  // Unescape ','
  $str = str_replace("\,", ",", $str);
  // Unescape ';'
  $str = str_replace("\;", ";", $str);
  // Unescape '\'
  $str = str_replace("\\\\", "\\", $str);
  return $str;
}


// Escape text for use in an iCalendar quoted string
function ical_escape_quoted_string($str)
{
  // From RFC 5545:
  //    quoted-string = DQUOTE *QSAFE-CHAR DQUOTE

  //    QSAFE-CHAR    = WSP / %x21 / %x23-7E / NON-US-ASCII
  //    ; Any character except CONTROL and DQUOTE

  // We'll just get rid of any double quotes, replacing them with a space.
  // (There is no way of escaping double quotes)
  $str = str_replace('"', ' ', $str);
  return $str;
}


function ical_unescape_quoted_string($str)
{
  return trim($str, '"');
}


// Splits a string at the first colon or semicolon (the delimiter) unless the delimiter
// is inside a quoted string.  Used for parsing iCalendar lines to get property parameters
// It assumes the string will always have at least one more delimiter to come, so can
// only be used when you know you've still got the colon to come.
//
// Returns an array of three elements (the second is the delimiter)
// or just one element if the delimiter is not found
function ical_split($string)
{
  // We want to split the string up to the first delimiter which isn't inside a quoted
  // string.   So the look ahead must not contain exactly one double quote before the next
  // delimiter.   Note that (a) you cannot escape double quotes inside a quoted string, so we
  // we don't have to worry about that complication (b) we assume there will always be a
  // second delimiter
  return preg_split('/([:;](?![^"]*"{1}[:;]))/', $string, 2, PREG_SPLIT_DELIM_CAPTURE);
}



// Parse a content line which is a property (ie is inside a component).   Returns
// an associative array:
//   'name'       the property name
//   'params'     an associative array of parameters indexed by parameter name
//   'value'      the property value.  The value will have escaping reversed
function parse_ical_property($line)
{
  $result = array();
  // First of all get the string up to the first colon or semicolon.   This will
  // be the property name.   We also want to get the delimiter so that we know
  // whether there are any parameters to come.   The split will return an array
  // with three elements:  0 - the string before the delimiter, 1 - the delimiter
  // and 2 the rest of the string
  $tmp = ical_split($line);
  $result['name'] = $tmp[0];
  $params = array();
  if ($tmp[1] != ':')
  {
    // Get all the property parameters
    do
    {
      $tmp = ical_split($tmp[2]);
      list($param_name, $param_value) = explode('=', $tmp[0], 2);
      // The parameter value can be a quoted string, so get rid of any double quotes
      $params[$param_name] = ical_unescape_quoted_string($param_value);
    }
    while ($tmp[1] != ':');
  }
  $result['params'] = $params;
  $result['value'] = ical_unescape_text($tmp[2]);
  return $result;
}


// Create an iCalendar Recurrence Rule
function create_rrule($data)
{
  global $RFC_5545_days;

  $rule = '';

  if (!isset($data['rep_type']) || ($data['rep_type'] === REP_NONE))
  {
    return $rule;
  }

  switch($data['rep_type'])
  {
    case REP_DAILY:
      $rule .= "FREQ=DAILY";
      break;

    case REP_WEEKLY:
      $rule .= "FREQ=WEEKLY";
      // Get the repeat days of the week
      $days_of_week = array();
      for ($i = 0; $i < DAYS_PER_WEEK; $i++)
      {
        if ($data['rep_opt'][$i])
        {
          $days_of_week[] = $RFC_5545_days[$i];
        }
      }
      $dow_list = implode(',', $days_of_week);
      if (!empty($dow_list))
      {
        $rule .= ";BYDAY=$dow_list";
      }
      break;

    case REP_MONTHLY:
      $rule .= "FREQ=MONTHLY";
      if (isset($data['month_absolute']))
      {
        $rule .= ";BYMONTHDAY=" . $data['month_absolute'];
      }
      elseif (isset($data['month_relative']))
      {
        $rule .= ";BYDAY=" . $data['month_relative'];
      }
      else
      {
        trigger_error("Unknown monthly repeat type, E_USER_NOTICE");
      }
      break;

    case REP_YEARLY:
      $rule .= "FREQ=YEARLY";
      break;
  }

  // The interval (if necessary)
  if ($data['rep_interval'] > 1)
  {
    $rule .= ";INTERVAL=" . $data['rep_interval'];
  }

  // The UNTIL date-time "MUST be specified in UTC time"
  $rule .= ";UNTIL=" . gmdate(RFC5545_FORMAT . '\Z', $data['end_date']);

  return $rule;
}


// Create a comma separated list of dates for use with the EXDATE property.
// The TZID parameter if used must be added separately
function create_exdate_list($dates)
{
  global $timezone;

  $vtimezone = get_vtimezone($timezone);

  $results = array();

  foreach ($dates as $date)
  {
    if ($vtimezone === false)
    {
      $results[] = gmdate(RFC5545_FORMAT . '\Z', $date);
    }
    else
    {
      $results[] = date(RFC5545_FORMAT, $date);
    }
  }
  return implode(',', $results);
}


// Create an RFC 5545 iCalendar Event component
function create_ical_event($method, $data, $addresses=null, $series=false)
{
  require_once "functions_mail.inc";

  global $mail_settings, $timezone,
         $default_area_room_delimiter;

  $vtimezone = get_vtimezone($timezone);

  $results = array();

  $results[] = "BEGIN:VEVENT";
  $results[] = "UID:" . $data['ical_uid'];  // compulsory
  $results[] = "DTSTAMP:" . gmdate(RFC5545_FORMAT . '\Z');  // compulsory
  $last_modified = empty($data['last_updated']) ? time() : $data['last_updated'];
  $results[] = "LAST-MODIFIED:" . gmdate(RFC5545_FORMAT . '\Z', $last_modified);


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
    $results[] = "DTSTART:" . gmdate(RFC5545_FORMAT . '\Z', $data['start_time']);
    $results[] = "DTEND:" . gmdate(RFC5545_FORMAT . '\Z', $data['end_time']);
  }
  else
  {
    $results[] = "DTSTART;TZID=$timezone:" . date(RFC5545_FORMAT, $data['start_time']);
    $results[] = "DTEND;TZID=$timezone:" . date(RFC5545_FORMAT, $data['end_time']);
  }

  if ($series)
  {
    $results[] = "RRULE:" . create_rrule($data);
    if (!empty($data['skip_list']))
    {
      $results[] = "EXDATE" .
                   (($vtimezone === false) ? ":" : ";TZID=$timezone:") .
                   create_exdate_list($data['skip_list']);
    }
  }
  $results[] = "SUMMARY:" . ical_escape_text($data['name']);
  $results[] = "DESCRIPTION:" . ical_escape_text($data['description']);
  $results[] = "LOCATION:" . ical_escape_text($data['area_name'] . $default_area_room_delimiter . $data['room_name']);
  $results[] = "X-MRBS-TYPE:" . ical_escape_text(get_type_vocab($data['type']));
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
  // is strictly necessary but it can do no harm and there are some apps that seem to need it -
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

  $organizer = parse_address($mail_settings['organizer']);
  if (empty($organizer['common_name']))
  {
    $organizer['common_name'] = get_mail_vocab('mrbs');
  }

  if (!empty($organizer['address']))
  {
    $results[] = "ORGANIZER;CN=\"" . ical_escape_quoted_string($organizer['common_name']) . "\":mailto:" . $organizer['address'];
  }

  // Put the people on the "to" list as required participants and those on the cc
  // list as non participants.   In theory the email client can then decide whether
  // to enter the booking automatically on the user's calendar - although at the
  // time of writing (Dec 2010) there don't seem to be any that do so!
  if (!empty($addresses))
  {
    $attendees = $addresses;  // take a copy of $addresses as we're going to alter it
    $keys = array('to', 'cc');  // We won't do 'bcc' as they need to stay blind
    foreach ($keys as $key)
    {
      $attendees[$key] = explode(',', $attendees[$key]);  // convert the list into an array
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
                // for that address and we'll chose the REQ-PARTICIPANT.   (Apart
                // from two conflicting ATTENDEES not making sense, it also breaks
                // some applications, eg Apple Mail/iCal)
                continue 2;  // Move on to the next attendeee
              }
              $role = "NON-PARTICIPANT";
              break;
          }
          // Use the common name if there is one
          $attendee = parse_address($attendee);
          $results[] = "ATTENDEE;" .
                       ((!empty($attendee['common_name'])) ? "CN=\"" . ical_escape_quoted_string($attendee['common_name']) . "\";" : "") .
                       "PARTSTAT=NEEDS-ACTION;ROLE=$role:mailto:" . $attendee['address'];
        }
      }
    }
  }

  $results[] = "END:VEVENT";

  // No CRLF at end: that will be added later
  return implode(ICAL_EOL, $results);
}


// Creates an iCalendar object in RFC 5545 format
//    $method      string   the RFC 5545 METHOD (eg "REQUEST", "PUBLISH", "CANCEL")
//    $components  array    an array of iCalendar components, each a string
function create_icalendar($method, $components)
{
  require_once "version.inc";

  global $timezone;

  $vtimezone = get_vtimezone($timezone);

  $results = array();
  $results[] = "BEGIN:VCALENDAR";
  // Compulsory properties
  $results[] = "PRODID:-//MRBS//NONSGML " . get_mrbs_version() . " //EN";
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

  $result = implode(ICAL_EOL, $results);
  $result .= ICAL_EOL;  // Has to end with a CRLF

  $result = ical_fold($result);

  return $result;
}


// outputs an iCalendar based on the data in $res, the result of an SQL query.
//
//    &$res       resource  the result of an SQL query on the entry table, which
//                          has been sorted by repeat_id, ical_recur_id (both ascending).
//                          As well as all the fields in the entry table, the rows will
//                          also contain the area name, the room name and the repeat
//                          details (rep_type, end_date, rep_opt, rep_interval)
//    $export_end int       a Unix timestamp giving the end limit for the export
function export_icalendar(DBStatement $res, $keep_private, $export_end=PHP_INT_MAX)
{
  require_once "functions_view.inc";
  require_once "mrbs_sql.inc";

  // We construct an iCalendar by going through the rows from the SQL query.  Because
  // it was sorted by repeat_id and then by ical_recur_id we will
  //    - get all the individual entries (which will not have a rep_type)
  //    - then get the series.    For each series we have to:
  //        - identify the series information.   This is the original series information
  //          so we can only get it from an entry that has not been changed, ie has
  //          entry_type == ENTRY_RPT_ORIGINAL.   If there isn't one of these then it
  //          does not matter, because every entry will have been changed and so there
  //          is no need for the original data. [Note: another way of getting the
  //          series information would have been to get it as part of the query]
  //        - identify any events that have been changed from the standard, ie events
  //          with entry_type == ENTRY_RPT_CHANGED
  //        - identify any events from the original series that have been cancelled.  We
  //          can do this because we know from the repeat information the events that
  //          should be there and we can tell from the recurrence-id the events that
  //          are actually there.   We can then issue cancellations for the missing
  //          events.

  // We use PUBLISH rather than REQUEST because we're not inviting people to these meetings,
  // we're just exporting the calendar.   Furthermore if we don't use PUBLISH then some
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
    if (!isset($row['rep_type']) || ($row['rep_type'] == REP_NONE))
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

      // Otherwise, if this row is a member of the current series, add the row to the series
      elseif ($row['repeat_id'] == $series->repeat_id)
      {
        $series->addRow($row);
      }

      // If it's a series that we haven't seen yet, or we've got no more
      // rows then process the series
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
