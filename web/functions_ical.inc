<?php

// $Id$


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
  $line_split = "\r\n ";
  $octets_max = 75;
  
  $result = '';
  $octets = 0;
  $n_chars = utf8_strlen($str);
  for ($i=0; $i<$n_chars; $i++)
  {
    // If we've reached a CRLF, reset the column counter 
    // to the beginning of the line
    $char = utf8_substr($str, $i, 1);  // Get the next multi-byte character
    $char_octets = utf8_bytecount($char);  // Get the string length in bytes
    if (($char == "\r")  && 
        (($i+1) < $n_chars) &&
        (utf8_substr($str, $i+1, 1) == "\n"))
    {
      $octets = -2;  // -2 because we'll then be at 0 after the \r\n
    }
    // Otherwise if this character will take us over 75 octets, insert
    // a CRLF followed by a space (and so the column counter will be 1)
    elseif (($octets + $char_octets) > $octets_max)
    {
      $result .= $line_split;
      $octets = 1;
    }
    $result .= $char;
    $octets += $char_octets;
  }
  return $result;
}

// Escape text for use in an iCalendar object
function ical_escape_text($str)
{
  // Escape '\'
  $str = str_replace("\\", "\\\\", $str);
  // Escape ';'
  $str = str_replace(";", "\;", $str);
  // Escape ','
  $str = str_replace(",", "\,", $str);
  // Escape '\n'
  $str = str_replace("\n", "\\n", $str);
  return $str;
}

// Create an iCalendar Recurrence Rule
function create_rrule($data)
{
  // Create an array which can be used to map day of the week numbers (0..6)
  // onto days of the week as defined in RFC 5545
  $RFC_5545_days = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');
  $rule = '';
  if (!isset($data['rep_type']))
  {
    return $rule;
  }
  switch($data['rep_type'])
  {
    case REP_NONE:
      return $rule;
      break;
    case REP_DAILY:
      $rule .= "FREQ=DAILY";
      break;
    case REP_WEEKLY:
    case REP_N_WEEKLY;
      $rule .= "FREQ=WEEKLY";
      // Interval for N_WEEKLY (Interval defaults to 1)
      if ($data['rep_type'] == REP_N_WEEKLY)
      {
        $rule .= ";INTERVAL=" . $data['rep_num_weeks'];
      }
      // Get the repeat days of the week
      $days_of_week = array();
      for ($i = 0; $i < 7; $i++)
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
    case REP_MONTHLY_SAMEDAY:
      $dow = date('w', $data['start_time']);  // day of the week
      $dom = date('j', $data['start_time']);  // day of the month
      // Work out the week of the month.    If this is the fifth week of the month
      // then make it -1 (last week of the month in RFC 5545 terms)
      $wom = intval($dom/7) + 1;
      if ($wom > 4)
      {
        $wom = -1;
      }
      $wom = sprintf('%+d', $wom);
      $rule .= ";BYDAY=";
      $rule .= $wom;
      $rule .= $RFC_5545_days[$dow];
    case REP_MONTHLY:
      $rule .= "FREQ=MONTHLY";
      break;
    case REP_YEARLY:
      $rule .= "FREQ=YEARLY";
      break;
  }
  $rule .= ";UNTIL=" . gmdate(RFC5545_FORMAT . '\Z', $data['end_date']);

  return $rule;
}


// Create an RFC 5545 iCalendar Event component
function create_ical_event($data, $text_description, $html_description, $addresses, $series=FALSE)
{
  global $confirmation_enabled, $mail_settings;
  
  $use_html = $mail_settings['html'] && !empty($html_description);
  
  $results = array();

  $results[] = "BEGIN:VEVENT";
  $results[] = "UID:" . $data['ical_uid'];  // compulsory
  $results[] = "DTSTAMP:" . gmdate(RFC5545_FORMAT . '\Z');  // compulsory
  if ($series)
  {
    $results[] = "RRULE:" . create_rrule($data);
  }
  // Note: we use UTC time here.  If you are going to use a timezone and the TZID
  // parameter then RFC 5545 says you MUST provide a corresponding VTIMEZONE component.
  // Some calendar apps, eg Microsoft Exchange, enforce the standard and fail to import
  // calendar objects that use TZID without a corresponding VTIMEZONE.
  $results[] = "DTSTART:" . gmdate(RFC5545_FORMAT . '\Z', $data['start_time']);
  $results[] = "DTEND:" . gmdate(RFC5545_FORMAT . '\Z', $data['end_time']);
  $results[] = "SUMMARY:" . ical_escape_text($data['name']);
  // Put the HTML version in an ALTREP, just in case there are any Calendars out there
  // that support it (although at the time of writing, Dec 2010, none are known)
  $results[] = "DESCRIPTION" .
               ((($use_html && !empty($html_description['cid'])) ? ";ALTREP=\"CID:" . $html_description['cid'] . "\"" : "") . ":" .
               ical_escape_text($text_description['content']));
  // This is another way of getting an HTML description, used by Outlook.  However it 
  // seems to be very limited
  if ($use_html)
  {
    $results[] = "X-ALT-DESC;FMTTYPE=text/html:" . ical_escape_text($html_description['content']);
  }
  $results[] = "LOCATION:" . ical_escape_text($data['area_name'] . " - " . $data['room_name']);
  $results[] = "SEQUENCE:" . $data['ical_sequence'];
  // If this is an individual member of a series then set the recurrence id
  if ($data['entry_type'] == ENTRY_RPT_CHANGED)
  {
    $results[] = "RECURRENCE-ID:" . $data['ical_recur_id'];
  }
  if ($confirmation_enabled)
  {
    $results[] = "STATUS:". (($data['status'] & STATUS_TENTATIVE) ? "TENTATIVE" : "CONFIRMED");
  }
  
  // Put the people on the "to" list as required participants and those on the cc
  // list as non particpants.   In theory the email client can then decide whether
  // to enter the booking automaticaly on the user's calendar - although at the
  // time of writing (Dec 2010) there don't seem to be any that do so!
  if (!empty($addresses))
  {
    $keys = array('to', 'cc');  // We won't do 'bcc' as they need to stay blind
    foreach ($keys as $key)
    {
      if (!empty($addresses[$key]))
      {
        $address_list = $addresses[$key];
        $address_array = explode(',', $address_list);
        foreach ($address_array as $address)
        {
          switch ($key)
          {
            case 'to':
              $role = "REQ-PARTICIPANT";
              break;
            default:
              $role = "NON-PARTICIPANT";
              break;
          }
          $results[] = "ATTENDEE;ROLE=$role:mailto:" . trim($address);
        }
      }
    }
  }

  $results[] = "END:VEVENT";
  
  $result = implode(ICAL_EOL, $results);  // No CRLF at end: that will be added later

  return $result;
}


// Creates an iCalendar object in RFC 5545 format
//    $method      string   the RFC 5545 METHOD (eg "REQUEST", "PUBLISH", "CANCEL")
//    $components  array    an array of iCalendar components, each a string  
function create_icalendar($method, $components)
{
  require_once "version.inc";

  $results = array();
  $results[] = "BEGIN:VCALENDAR";
  // Compulsory properties
  $results[] = "PRODID:-//MRBS//NONSGML " . get_mrbs_version() . " //EN";
  $results[] = "VERSION:2.0";
  // Optional properties
  $results[] = "CALSCALE:GREGORIAN";
  $results[] = "METHOD:$method";
  
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

?>