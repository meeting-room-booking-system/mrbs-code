<?php
// +---------------------------------------------------------------------------+
// | Meeting Room Booking System.                                              |
// +---------------------------------------------------------------------------+
// | Functions dedicated to emails handling.                                   |
// |---------------------------------------------------------------------------+
// | I keeped these functions in a separated file to avoid burden the main     |
// | function.inc files if emails are not used.                                |
// |                                                                           |
// | USE : This file should be included in all files where emails functions    |
// |        are likely to be used.                                             |
// +---------------------------------------------------------------------------+
// | @author    thierry_bo.                                                    |
// | @version   $Revision: 797 $.                                              |
// +---------------------------------------------------------------------------+
//
// $Id$

// {{{ convertToMailCharset()

/**
 * Convert already utf-8 encoded strings to charset defined for mails in
 * config.inc.php.
 *
 * @param string    $string   string to convert
 * @return string   $string   string converted to $mail_charset, or in
 *                            original UTF-8 if mail_charset isn't set.
 */
function convertToMailCharset($string)
{
  global $unicode_encoding, $mail_charset;
  //
  if ($unicode_encoding && isset($mail_charset) &&
      (strcasecmp($mail_charset, "utf-8") != 0))
  {
    return iconv("utf-8", $mail_charset, $string);
  }
  else
  {
    return $string;
  }
}


function get_mail_charset()
{
  global $unicode_encoding, $mail_charset, $mail_vocab;

  if (isset($mail_charset))
  {
    $charset = $mail_charset;
  }
  else
  {
    $charset = "utf-8";
    if (!$unicode_encoding)
    {
      $charset = $mail_vocab["charset"];
    }
  }
  return $charset;
}

function get_mail_vocab($token)
{
  global $mail_vocab;

  $string = get_vocab($token, $mail_vocab);

  $unescaped_string = str_replace('&nbsp;', ' ', $string);

  return convertToMailCharset($unescaped_string);
}

// Get localized (for email) field name for a user defined table column
// Looks for a tag of the format tablename.columnname (where tablename is
// stripped of the table prefix) and if can't find a string for that tag will
// return the columnname
function get_mail_field_name($table, $name)
{
  global $mail_vocab, $db_tbl_prefix;
  
  $tag = substr($table, strlen($db_tbl_prefix));  // strip the prefix off the table name
  $tag .= "." . $name;           // add on the fieldname
  // then if there's a string in the vocab array for $tag use that
  // otherwise just use the fieldname
  return (isset($mail_vocab[$tag])) ? get_mail_vocab($tag) : $name;
}
    
// }}}
// {{{ getMailPeriodDateString()

/**
 * Format a timestamp in non-unicode output (for emails).
 *
 * @param   timestamp   $t
 * @param   int         $mod_time
 * @return  array
 */
function getMailPeriodDateString($t, $mod_time=0)
{
  global $periods;
  //
  $time = getdate($t);
  $p_num = $time['minutes'] + $mod_time;
  ( $p_num < 0 ) ? $p_num = 0 : '';
  ( $p_num >= count($periods) - 1 ) ? $p_num = count($periods ) - 1 : '';
  // I have made the separator a ',' as a '-' leads to an ambiguous
  // display in report.php when showing end times.
  
  // As HTML entities and tags are allowed in period names, we need to replace/strip
  // them out before putting them in emails, which are sent as plain text
  $mailperiod = $periods[$p_num];
  $mailperiod = mrbs_entity_decode($mailperiod, ENT_COMPAT, get_mail_charset());
  $mailperiod = strip_tags($mailperiod);
  return array($p_num, $mailperiod . strftime(", %A %d %B %Y",$t));
}

// }}}
// {{{ getMailTimeDateString()

/**
 * Format a timestamp in non-unicode output (for emails).
 *
 * @param   timestamp   $t         timestamp to format
 * @param   boolean     $inc_time  include time in return string
 * @return  string                 formated string
 */
function getMailTimeDateString($t, $inc_time=TRUE)
{
  global $twentyfourhour_format;

  if ($inc_time)
  {
    if ($twentyfourhour_format)
    {
      return mail_strftime("%H:%M:%S - %A %d %B %Y",$t);
    }
    else
    {
      return mail_strftime("%I:%M:%S%p - %A %d %B %Y",$t);
    }
  }
  else
  {
    return mail_strftime("%A %d %B %Y",$t);
  }
}

function mail_strftime($format,$t)
{
  $string = utf8_strftime($format,$t);
  return convertToMailCharset($string);
}

// get_address_list($array)
//
// Takes an array of email addresses and returns a comma separated
// list of addresses with duplicates removed.
function get_address_list($array)
{
  // Turn the array into a comma separated string
  $string = implode(',', $array);
  // Now turn it back into an array.   This is necessary because
  // some of the elements of the original array may themselves have
  // been comma separated strings
  $array = explode(',', $string);
  // remove any leading and trailing whitespace and any empty strings
  $trimmed_array = array();
  for ($i=0; $i < count($array); $i++)
  {
    $array[$i] = trim($array[$i]);
    if ($array[$i] != '')
    {
      $trimmed_array[] = $array[$i];
    }
  }
  // remove duplicates
  $trimmed_array = array_unique($trimmed_array);
  // re-assemble the string
  $string = implode(',', $trimmed_array);
  return $string;
}

// Take a string of email addresses separated by commas or newlines
// and return a comma separated list with duplicates removed.
function clean_address_list($string)
{
  $string = str_replace(array("\r\n", "\n", "\r"), ',', $string);
  $array = explode(',', $string);
  $string = get_address_list($array);
  return $string;
}

// get the email address of a user
// returns an empty string in the event of an error
function get_email_address($user)
{
  global $mail_settings, $auth, $tbl_users, $ldap_get_user_email;
  
  if ('db' == $auth['type'])
  {
    $email = sql_query1("SELECT email 
                         FROM $tbl_users 
                         WHERE name='" . addslashes($user) . "'
                         LIMIT 1");
    if ($email == -1)
    {
      $email = "";
    }
  }
  else if (('ldap' == $auth['type']) && $ldap_get_user_email)
  {
    $email = authLdapGetEmail($user);
  }
  else
  {
    $email = str_replace($mail_settings['username_suffix'], '', $user);
    $email .= $mail_settings['domain'];
  }
  return $email;
}

// get the list of email addresses that are allowed to approve bookings
// for the room with id $room_id
// (At the moment this is just the admin email address, but this could
// be extended.)
function get_approvers_email($room_id)
{
  global $mail_settings;
  
  return $mail_settings['recipients'];
}


// Get the area_admin_email for an entry $id
// If $series is set this is an entry in the repeat table, otherwise the entry table
// Returns an empty string in the case of an error
function get_area_admin_email($id, $series=FALSE)
{
  global $tbl_room, $tbl_area, $tbl_entry, $tbl_repeat;
  
  $id_table = ($series) ? "rep" : "e";
  
  $sql = "SELECT a.area_admin_email ";
  $sql .= "FROM $tbl_room r, $tbl_area a, $tbl_entry e ";
  // If this is a repeating entry...
  if ($id_table == 'rep')
  {
    // ...use the repeat table
    $sql .= ", $tbl_repeat rep ";
  }
  $sql .= "WHERE ${id_table}.id=$id 
             AND r.id=${id_table}.room_id
             AND a.id=r.area_id
           LIMIT 1";
  $email = sql_query1($sql);
  if ($email == -1)
  {
    $email = "";
  }
  return $email;
}


// Get the room_admin_email for an entry $id
// If $series is set this is an entry in the repeat table, otherwise the entry table
// Returns an empty string in the case of an error
function get_room_admin_email($id, $series=FALSE)
{
  global $tbl_room, $tbl_entry, $tbl_repeat;
  
  $id_table = ($series) ? "rep" : "e";
  
  $sql = "SELECT r.room_admin_email ";
  $sql .= "FROM $tbl_room r, $tbl_entry e ";
  // If this is a repeating entry...
  if ($id_table == 'rep')
  {
    // ...use the repeat table
    $sql .= ", $tbl_repeat rep ";
  }
  $sql .= "WHERE ${id_table}.id=$id 
             AND r.id=${id_table}.room_id
           LIMIT 1";
  $email = sql_query1($sql);
  if ($email == -1)
  {
    $email = "";
  }
  return $email;
}


function create_text_body($data, $new_entry, $new_id, $series, $action)
{
  global $mail_previous, $note, $returl;
  global $enable_periods, $approval_enabled, $confirmation_enabled;
  global $typel, $mail_settings, $standard_fields, $weekstarts, $url_base;
  global $tbl_entry;
  
  // set up the body
  switch ($action)
  {
    case "approve":
      $body = get_mail_vocab("mail_body_approved") . "\n\n";
      break;
    case "more_info":
      $body = get_mail_vocab("mail_body_more_info") . "\n\n";
      $body .= get_mail_vocab("info_requested") . ": ";
      $body .= convertToMailCharset($note) . "\n\n";
      break;
    case "remind":
      $body = get_mail_vocab("mail_body_reminder") . "\n\n";
      break;
    case "reject":
      $body = get_mail_vocab("mail_body_rej_entry") . "\n\n";
      $body .= get_mail_vocab("reason") . ': ';
      $body .= convertToMailCharset($note) . "\n\n";
      break;
    case "delete":
      $body = get_mail_vocab("mail_body_del_entry") . "\n\n";
      // Give the name of the person deleting the entry (might not
      // be the same as the creator)
      $body .= get_mail_vocab("deleted_by") . ': ';
      $user = getUserName();
      $body .= convertToMailCharset($user) . "\n";
      break;
    default:
      if ($new_entry)
      {
        $body = get_mail_vocab("mail_body_new_entry") . "\n\n";
      }
      else
      {
        $body = get_mail_vocab("mail_body_changed_entry") . "\n\n";
      }
      break;
  }

  // Create a link to the entry, unless we're deleting it of course,
  // because then there won't be one.
  if (($action != "delete") && ($action != "reject"))
  {
    // Set the link to view entry page
    if (isset($url_base) && ($url_base != ""))
    {
      $body .= "$url_base/view_entry.php?id=$new_id";
    }
    else
    {
      ('' != $returl) ? $url = explode(basename($returl), $returl) : '';
      $body .= $url[0] . "view_entry.php?id=$new_id";
    }
    if ($series)
    {
      $body .= "&series=1";
    }
    $body .= "\n";
  }
  
  // Always display the brief description
  $body .= "\n" . get_mail_vocab("namebooker") . ": ";
  $body .= compareEntries(convertToMailCharset($data['name']),
                          convertToMailCharset($mail_previous['namebooker']),
                          $new_entry)  . "\n";
  
  // Displays/don't displays entry details
  if ($mail_settings['details'])
  {
    // Description:
    $body .= get_mail_vocab("description") . ": ";
    $body .= compareEntries(convertToMailCharset($data['description']),
                            convertToMailCharset($mail_previous['description']),
                            $new_entry) . "\n";
                                                        
    if ($confirmation_enabled)
    {                        
      // Confirmation status:
      $body .= get_mail_vocab("confirmation_status") . ": ";
      $old_status = ($mail_previous['status'] & STATUS_TENTATIVE) ? get_mail_vocab("tentative") : get_mail_vocab("confirmed");
      $new_status = ($data['status'] & STATUS_TENTATIVE) ? get_mail_vocab("tentative") : get_mail_vocab("confirmed");
      $body .= compareEntries($new_status, $old_status, $new_entry) . "\n";
    }
                            
    if ($approval_enabled)
    {                        
      // Approval status:
      $body .= get_mail_vocab("approval_status") . ": ";
      $old_status = ($mail_previous['status'] & STATUS_AWAITING_APPROVAL) ? get_mail_vocab("awaiting_approval") : get_mail_vocab("approved");
      $new_status = ($data['status'] & STATUS_AWAITING_APPROVAL) ? get_mail_vocab("awaiting_approval") : get_mail_vocab("approved");
      $body .= compareEntries($new_status, $old_status, $new_entry) . "\n";
    }
                               
    // Room:
    $body .= get_mail_vocab("room") . ": " .
      compareEntries(convertToMailCharset($data['area_name']),
                     convertToMailCharset($mail_previous['area_name']),
                     $new_entry);
    $body .= " - " . compareEntries(convertToMailCharset($data['room_name']),
                                    convertToMailCharset($mail_previous['room_name']),
                                    $new_entry) . "\n";
        
    // Start time
    if ($enable_periods)
    {
      list( $start_period, $entry_start_date) = getMailPeriodDateString($data['start_time']);
    }
    else
    {
      $entry_start_date = getMailTimeDateString($data['start_time']);
    }
    $body .= get_mail_vocab("start_date") . ": ";
    $body .= compareEntries($entry_start_date,
                            $mail_previous['entry_start_date'],
                            $new_entry) . "\n";
        
    // Duration
    $body .= get_mail_vocab("duration") . ": " .
      compareEntries($data['duration'],
                     $mail_previous['duration'],
                     $new_entry);
    $body .= " " . compareEntries(get_mail_vocab($data['dur_units']),
                                  $mail_previous['dur_units'],
                                  $new_entry) . "\n";
        
    // End time
    $myendtime = $data['end_time'];
    if ( $enable_periods )
    {
      list($end_period, $entry_end_date) =  getMailPeriodDateString($myendtime, -1);
    }
    else
    {
      $entry_end_date = getMailTimeDateString($myendtime);
    }
    $body .= get_mail_vocab("end_date") . ": ";
    $body .= compareEntries($entry_end_date,
                            $mail_previous['entry_end_date'],
                            $new_entry) ."\n";
    
    // Type of booking
    $body .= get_mail_vocab("type") . ": ";
    if ($new_entry)
    {
      $body .= $typel[$data['type']];
    }
    else
    {
      $temp = $mail_previous['type'];
      $body .= compareEntries($typel[$data['type']],
                              $typel[$temp],
                              $new_entry);
    }
        
    // Created by
    $body .= "\n" . get_mail_vocab("createdby") . ": " .
      compareEntries(convertToMailCharset($data['create_by']),
                     convertToMailCharset($mail_previous['createdby']),
                     $new_entry) . "\n";
                     
    // Custom fields
    $fields = sql_field_info($tbl_entry);
    foreach ($fields as $field)
    {
      if (!in_array($field['name'], $standard_fields['entry']))
      {
        $key = $field['name'];
        $value = (isset($data['custom_fields'][$key])) ? $data['custom_fields'][$key] : '';
        // Convert any booleans or pseudo-booleans to text strings (in the mail language)
        if (($field['nature'] == 'boolean') || 
            (($field['nature'] == 'integer') && isset($field['length']) && ($field['length'] <= 2)) )
        {
          $value = ($value) ? get_mail_vocab("yes") : get_mail_vocab("no");
          if (!$new_entry)
          {
            $mail_previous[$key] = ($mail_previous[$key]) ? get_mail_vocab("yes") : get_mail_vocab("no");
          }
        }
        $body .= get_mail_field_name($tbl_entry, $key) . ": ";
        $body .= compareEntries(convertToMailCharset($value), 
                                convertToMailCharset($mail_previous[$key]),
                                $new_entry) . "\n";
      }
    }
    
    // Last updated
    $body .= get_mail_vocab("lastupdate") . ": " .
      compareEntries(getMailTimeDateString(time()),
                     $mail_previous['updated'],
                     $new_entry);
        
    // Repeat Type
    $body .= "\n" . get_mail_vocab("rep_type");
    if ($new_entry)
    {
      $body .= ": " . get_mail_vocab("rep_type_" . $data['rep_type']);
    }
    else
    {
      $temp = $mail_previous['rep_type'];
      $body .=  ": " . compareEntries(get_mail_vocab("rep_type_" . $data['rep_type']),
                                      get_mail_vocab("rep_type_$temp"),
                                      $new_entry);
    }
        
    // Details if a series
    if ($data['rep_type'] != REP_NONE)
    {
      $opt = "";
      if (($data['rep_type'] == REP_WEEKLY) || ($data['rep_type'] == REP_N_WEEKLY))
      {
        // Display day names according to language and preferred weekday start.
        for ($i = 0; $i < 7; $i++)
        {
          $daynum = ($i + $weekstarts) % 7;
          if ($data['rep_opt'][$daynum])
          {
            $opt .= day_name($daynum) . " ";
          }
        }
      }
      if ($data['rep_type'] == REP_N_WEEKLY)
      {
        $body .= "\n" . get_mail_vocab("rep_num_weeks");
        $body .=  ": " . compareEntries($data['rep_num_weeks'],
                                        $mail_previous["rep_num_weeks"],
                                        $new_entry);
      }
      
      if($opt || $mail_previous["rep_opt_list"])
      {
        $body .= "\n" . get_mail_vocab("rep_rep_day");
        $body .=  ": " . compareEntries($opt,
                                        $mail_previous["rep_opt_list"],
                                        $new_entry);
      }

      $body .= "\n" . get_mail_vocab("rep_end_date");
      if ($new_entry)
      {
        $body .= ": " . getMailTimeDateString($data['end_date'], FALSE);
      }
      else
      {
        $temp = getMailTimeDateString($data['end_date'], FALSE);
        $body .=  ": " . 
          compareEntries($temp,
                         getMailTimeDateString($mail_previous['end_date'], FALSE),
                         $new_entry) . "\n";
      }
    }
    $body .= "\n";
  }
  return $body;
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


// Creates an iCalendar object in RFC 5545 format
function create_icalendar($method, $data, $description, $series=FALSE)
{
  require_once "version.inc";
  
  global $timezone, $confirmation_enabled;
  
  $eol = "\r\n";
  
  $results = array();
  $results[] = "BEGIN:VCALENDAR";
  // Compulsory properties
  $results[] = "PRODID:-//MRBS//NONSGML " . get_mrbs_version() . " //EN";
  $results[] = "VERSION:2.0";
  // Optional properties
  $results[] = "CALSCALE:GREGORIAN";
  $results[] = "METHOD:$method";
  
  // The event
  $results[] = "BEGIN:VEVENT";
  $results[] = "UID:" . $data['ical_uid'];  // compulsory
  $results[] = "DTSTAMP:" . gmdate(RFC5545_FORMAT . '\Z');  // compulsory
  if ($series)
  {
    $results[] = "RRULE:" . create_rrule($data);
  }
  $results[] = "DTSTART;TZID=$timezone:" . date(RFC5545_FORMAT, $data['start_time']);
  $results[] = "DTEND;TZID=$timezone:" . date(RFC5545_FORMAT, $data['end_time']);
  $results[] = "SUMMARY:" . ical_escape_text($data['name']);
  $results[] = "DESCRIPTION:" . ical_escape_text($description);
  $results[] = "LOCATION:" . ical_escape_text($data['area_name'] . " - " . $data['room_name']);
  $results[] = "SEQUENCE:" . $data['ical_sequence'];
  // If this is an individual member of a series then set the recurrence id
  if ($data['entry_type'] == 2)
  {
    $results[] = "RECURRENCE-ID:" . gmdate(RFC5545_FORMAT . '\Z', $data['start_time']);
  }
  if ($confirmation_enabled)
  {
    $results[] = "STATUS:". (($data['status'] & STATUS_TENTATIVE) ? "TENTATIVE" : "CONFIRMED");
  }
  $results[] = "END:VEVENT";
  
  $results[] = "END:VCALENDAR";
  
  $result = implode($eol, $results);
  $result .= $eol;  // Has to end with a CRLF

  return $result;
}
      

// }}}
// {{{ notifyAdminOnBooking()

/**
 * Send email to administrator to notify a new/changed entry.
 *
 * @param bool    $new_entry    to know if this is a new entry or not
 * @param int     $new_id       used for create a link to the new entry
 * @return bool                 TRUE or PEAR error object if fails
 */
function notifyAdminOnBooking($new_entry, $new_id, $series, $action="book")
{
  global $data, $mail_previous;
  global $auth, $mail_settings, $approval_enabled;
  
  $recipients = array();
  $cc = array();
  $cc[] = $mail_settings['cc'];
  
  // set the from address
  $user = getUserName();
  if (isset($user) && (($action == "remind")  || ($action == "more_info")))
  {
    $from = get_email_address($user);
    if (empty($from))
    {
      // there was an error:  use a sensible default
      $from = $mail_settings['from'];
    }
  }
  else
  {
    $from = $mail_settings['from'];
  }
  
  // if we're requiring bookings to be approved and this user needs approval
  // for this room, then get the email addresses of the approvers
  if ($approval_enabled && !auth_book_admin($user, $data['room_id']))
  {
    $recipients[] = get_approvers_email($data['room_id']);
  }
  
  ($mail_settings['admin_on_bookings']) ? $recipients[] = $mail_settings['recipients'] : '';
  
  if ($mail_settings['area_admin_on_bookings'])
  {
    // Look for list of area admins emails addresses
    if ($new_entry)
    {
      $email = get_area_admin_email($new_id, ($data['rep_type'] != REP_NONE));
      if (!empty($email))
      {
        $recipients[] = $email;
      }
    }
    elseif (!empty($mail_previous['area_admin_email']))
    {
      // if this is an edited entry, we already have area_admin_email,
      // avoiding a database hit.
      $recipients[] = $mail_previous['area_admin_email'];
    }
  }
  
  if ($mail_settings['room_admin_on_bookings'])
  {
    // Look for list of room admins email addresses
    if ($new_entry)
    {
      $email = get_room_admin_email($new_id, ($data['rep_type'] != REP_NONE));
      if (!empty($email))
      {
        $recipients[] = $email;
      }
    }
    elseif (!empty($mail_previous['room_admin_email']))
    {
      // if this is an edited entry, we already have room_admin_email,
      // avoiding a database hit.
      $recipients[] = $mail_previous['room_admin_email'];
    }
  }
  
  if ($mail_settings['booker'])
  {
    if (($action == "approve")  || ($action == "more_info"))
    {
      // Put the recipients on the cc line and the booker will go
      // on the to line
      $cc = array_merge($cc, $recipients);
      $recipients = array();
    }
    $booker = ($new_entry) ? $data['create_by'] : $mail_previous['createdby'];
    $booker_email = get_email_address($booker);
    if (!empty($booker_email))
    {
      $recipients[] = $booker_email;
    }
  }
  // In case $recipients is empty, no need to go further
  if (empty($recipients))
  {
    return FALSE;
  }
  $recipient_list = get_address_list($recipients);
  $cc_list = get_address_list($cc);
  
  // set up the subject
  switch ($action)
  {
    case "approve":
      $subject = get_mail_vocab("mail_subject_approved");
      break;
    case "more_info":
      $subject = get_mail_vocab("mail_subject_more_info");
      break;
    case "remind":
      $subject = get_mail_vocab("mail_subject_reminder");
      break;
    default:
      if ($new_entry)
      {
        $subject = get_mail_vocab("mail_subject_new_entry");
      }
      else
      {
        $subject = get_mail_vocab("mail_subject_changed_entry");
      }
      break;
  }

  // Create the text body
  $body = create_text_body($data, $new_entry, $new_id, $series, $action);
  
  // Create the iCalendar if required
  $attachment = array();
  if ($mail_settings['icalendar'])
  {
    $attachment['method']   = "REQUEST";
    $attachment['content']  = create_icalendar($attachment['method'], $data, $body, $series);
    $attachment['name']     = "meeting.ics";
  }

  // If the subject contains any non-ASCII characters...
  if (!preg_match('/^[[:ascii:]]*$/', $subject))
  {
    // ...communicate the charset and encode it correctly
    $subject = "=?".get_mail_charset()."?B?".base64_encode($subject)."?=";
  }
  $result = sendMail($recipient_list,
                     $subject,
                     $body,
                     NULL,
                     $attachment,
                     get_mail_charset(),
                     $from,
                     $cc_list);
  return $result;
}

// }}}
// {{{ notifyAdminOnDelete()

/**
 * Send email to administrator to notify a new/changed entry.
 *
 * @param   array   $mail_previous  contains deleted entry data forr email body
 * @return  bool    TRUE or PEAR error object if fails
 */
function notifyAdminOnDelete($mail_previous, $series=FALSE, $action="delete", $note="")
{
  global $typel, $enable_periods, $auth;
  global $mail_settings, $standard_fields, $tbl_entry;
  global $approval_enabled, $confirmation_enabled;
  
  $recipients = array();
  $cc = array();
  $cc[] = $mail_settings['cc'];
  
  // set the from address
  $user = getUserName();
  if (isset($user) && ($action == "reject"))
  {
    $from = get_email_address($user);
    if (empty($from))
    {
      // there was an error:  use a sensible default
      $from = $mail_settings['from'];
    }
  }
  else
  {
    $from = $mail_settings['from'];
  }
  
  ($mail_settings['admin_on_bookings']) ? $recipients[] = $mail_settings['recipients'] : '';
  if ($mail_settings['area_admin_on_bookings']  && !empty($mail_previous['area_admin_email']))
  {
    $recipients[] = $mail_previous['area_admin_email'];
  }
  if ($mail_settings['room_admin_on_bookings']  && !empty($mail_previous['room_admin_email']))
  {
    $recipients[] = $mail_previous['room_admin_email'];
  }
  if ($mail_settings['booker'])
  {
    if ($action == "reject")
    {
      // Put the recipients on the cc line and the booker will go
      // on the to line
      $cc = array_merge($cc, $recipients);
      $recipients = array();
    }
    $booker_email = get_email_address($mail_previous['createdby']);
    if (!empty($booker_email))
    {
      $recipients[] = $booker_email;
    }
  }
  // In case mail is allowed but someone forgot to supply email addresses...
  if (empty($recipients))
  {
    return FALSE;
  }
  $recipient_list = get_address_list($recipients);
  $cc_list = get_address_list($cc);
  
  // Set the subject
  if ($action == "reject")
  {
    $subject = get_mail_vocab("mail_subject_rejected");
  }
  else
  {
    $subject = get_mail_vocab("mail_subject_delete");
  }
  
  // Create the text body
  $data = $mail_previous;
  $body = create_text_body($data, TRUE, 0, $series, $action);
  
  // Set up the attachment
  $attachment = array();
  if ($mail_settings['icalendar'])
  {
    $attachment['method']   = "CANCEL";
    $attachment['content']  = create_icalendar($attachment['method'], $mail_previous, $body, $series);
    $attachment['name']     = "meeting.ics";
  }
  
  // End of mail details
  $result = sendMail($recipient_list, $subject, $body, NULL, $attachment, get_mail_charset(), $from, $cc_list);
  return $result;
}

// }}}
// {{{ getPreviousEntryData()

/**
 * Gather all fields values for an entry. Used for emails to get previous
 * entry state.
 *
 * @param int     $id       entry id to get data
 * @param int     $series   TRUE if this is the id of an entry in the repeat table
                            FALSE if this is the id of an entry in the entry table
 * @return bool             TRUE or PEAR error object if fails
 */
function getPreviousEntryData($id, $series)
{
  global $enable_periods, $weekstarts;
  
  // Get the data for the booking
  $data = mrbsGetBookingInfo($id, $series);
  
  // Now process some special fields
  $data['id']         = $id;
  $data['namebooker'] = $data['name'];
  $data['createdby']  = $data['create_by'];
  $data['updated']    = getMailTimeDateString($data['last_updated']);

  // Now get the start time, end time and duration.
  if ($enable_periods)
  {
    list( $data['start_period'], $data['entry_start_date']) = getMailPeriodDateString($data['start_time']);
    list( $data['end_period'] , $data['entry_end_date'])    = getMailPeriodDateString($data['end_time'], -1);
    // need to make DST correct in opposite direction to entry creation
    // so that user see what he expects to see
    $data['duration'] -= cross_dst($data['start_time'], $data['end_time']);
    // Don't translate the units at this stage, as they'll get translated into the
    // browser language rather than the mail language.   We'll translate them later.
    toPeriodString($data['start_period'], $data['duration'], $data['dur_units'], FALSE);
  }
  else
  {
    $data['entry_start_date'] = getMailTimeDateString($data['start_time']);
    $data['entry_end_date']   = getMailTimeDateString($data['end_time']);
    // need to make DST correct in opposite direction to entry creation
    // so that user see what he expects to see
    $data['duration'] -= cross_dst($data['start_time'], $data['end_time']);
    // Don't translate the units at this stage, as they'll get translated into the
    // browser language rather than the mail language.   We'll translate them later.
    toTimeString($data['duration'], $data['dur_units'], FALSE);
  }
  // Now translate the duration units into the mail language
  $data['dur_units'] = get_mail_vocab($data['dur_units']);
  
  // Next, process any repeat information
  if ($data['rep_type'] != REP_NONE)
  { 
    // get the names of the repeat days 
    switch($data['rep_type'])
    {
      case 2:
      case 6:
        $rep_day[0] = $data['rep_opt'][0] != "0";
        $rep_day[1] = $data['rep_opt'][1] != "0";
        $rep_day[2] = $data['rep_opt'][2] != "0";
        $rep_day[3] = $data['rep_opt'][3] != "0";
        $rep_day[4] = $data['rep_opt'][4] != "0";
        $rep_day[5] = $data['rep_opt'][5] != "0";
        $rep_day[6] = $data['rep_opt'][6] != "0";     
        break; 
      default:
        $rep_day = array(0, 0, 0, 0, 0, 0, 0);
    }
    $data['rep_opt_list'] = "";
    for ($i = 0; $i < 7; $i++)
    {
      $wday = ($i + $weekstarts) % 7;
      if ($rep_day[$wday])
        $data['rep_opt_list'] .= day_name($wday) . " ";
    }
    
    // Sanitise the rep_num_weeks
    if ($data['rep_type'] != REP_N_WEEKLY)
    {
      $data['rep_num_weeks'] = "";
    }
  }

  // return entry previous data as an array
  return $data;
}

// }}}
// {{{ compareEntries()

/**
 * Compare entries fields to show in emails.
 *
 * @param string  $new_value       new field value
 * @param string  $previous_value  previous field value
 * @return string                  new value if no difference, new value and
 *                                 previous value in brackets otherwise
 */
function compareEntries($new_value, $previous_value, $new_entry)
{
  $suffix = "";
  if ($new_entry)
  {
    return $new_value;
  }
  if ($new_value != $previous_value)
  {
    $suffix = " ($previous_value)";
  }
  return($new_value . $suffix);
}

// }}}
// {{{ sendMail()

/**
 * Send emails using PEAR::Mail and PEAR::Mail_mime classes.
 * How to use these classes:
 *   -> http://www.pear.php.net/package/Mail 
 *   -> http://www.pear.php.net/package/Mail_Mime
 * then link "View documentation".
 * Currently implemented version: Mail 1.1.3 and its dependancies
 * Net_SMTP 1.2.6 and Net_Socket 1.0.2
 * Mail_Mime 1.8.0
 *
 * @param string  $recipients       comma separated list of recipients or array
 * @param string  $subject          email subject
 * @param string  $text_body        text part of body
 * @param string  $text_html        HTML part of body
 * @param string  $attachment       file to attach.   An array consisting of
 *                                    'content' the file or data to attach
 *                                    'method'  the iCalendar METHOD
 *                                    'name'    the name to give it
 * @param string  $charset          character set used in body
 * @param string  $cc               Carbon Copy
 * @param string  $bcc              Blind Carbon Copy
 * @param string  $from             from field
 * @return bool                     TRUE or PEAR error object if fails
 */
function sendMail($recipients, $subject, $text_body, $html_body, $attachment,
                  $charset = 'us-ascii', $from, $cc = NULL, $bcc = NULL)
{
  require_once "Mail.php";
  require_once "Mail/mime.php";
  require_once "Mail/mimePart.php";
  
  global $mail_settings, $sendmail_settings, $smtp_settings;
  
  // for cases where the mail server refuses
  // to send emails with cc or bcc set, put the cc
  // addresses on the to line
  if (isset($cc) && $mail_settings['treat_cc_as_to'])
  {
    $recipients_array = array_merge(explode(',', $recipients),
                                    explode(',', $cc));
    $recipients = get_address_list($recipients_array);
    $cc = NULL;
  }
  
  // Set up configuration settings
  if (empty($from))
  {
    $from = $mail_settings['from'];
  }
  $backend = $mail_settings['admin_backend'];
  $sendmail_path = $sendmail_settings['path'];
  $sendmail_args = $sendmail_settings['args'];
  $host = $smtp_settings['host'];
  $port = $smtp_settings['port'];
  $auth = $smtp_settings['auth'];
  $username = $smtp_settings['username'];
  $password = $smtp_settings['password'];
  
  // Parameters part
  $params = array();  // to avoid an undefined variable message
  if ( $backend == 'sendmail' )
  {
    $params['sendmail_path'] = $sendmail_path;
    $params['sendmail_args'] = $sendmail_args;
  }
  if ( $backend == "smtp" )
  {
    $params['host']          = $host;
    $params['port']          = $port;
    $params['auth']          = $auth;
    $params['username']      = $username;
    $params['password']      = $password;
  }
  
  // Headers part (extra headers - the standard headers will already be there)
  $headers = array();
  $headers['From'] = $from;
  if ($backend != 'mail')
  {
    $headers['To'] = $recipients;
  }
  (NULL != $cc) ? $headers['Cc'] = $cc : '';
  (NULL != $bcc) ? $headers['Bcc'] = $bcc : '';
  $headers['Subject'] = $subject;
  $headers['Mime-Version'] = '1.0';
  
  // Build the email.   We're going to use the "alternative" subtype which means
  // that we order the sub parts according to how faithful they are to the original,
  // putting the least faithful first, ie the ordinary plain text version.   The
  // email client then uses the most faithful version that it can handle.
  $mime_params = array();
  $mime_params['content_type'] = "multipart/alternative";
  $mime = new Mail_mimePart('', $mime_params);
  
  // Add the text part
  $mime_params['content_type'] = "text/plain";
  $mime_params['encoding']     = "7bit";
  $mime_params['charset']      = $charset;
  $text = $mime->addSubPart($text_body, $mime_params);
  
  // Add the HTML mail
  if (!empty($html_body))
  {
    // do something
  }
  
  if ($mail_settings['icalendar'])
  {
    // Add the text version of the iCalendar
    $mime_params['content_type'] = "text/calendar; method=" . $attachment['method'];
    $text = $mime->addSubPart($attachment['content'], $mime_params);
    // And add it as a file as well
    $mime_params['content_type'] = "application/ics";
    $mime_params['encoding']     = "base64";
    $mime_params['disposition']  = "attachment";
    $mime_params['dfilename']    = $attachment['name'];
    unset($mime_params['charset']);
    $text = $mime->addSubPart($attachment['content'], $mime_params);
  }

  // Encode the result
  $mime = $mime->encode();
  // Add in the extra headers
  $mime['headers'] = array_merge($mime['headers'], $headers);

  // Call to the PEAR::Mail class
  $mail_object =& Mail::factory($backend, $params);
  $result = $mail_object->send($recipients, $mime['headers'], $mime['body']);

  if (is_object($result))
  {
    error_log("Error sending email: ".$result->getMessage());
  }
  return $result;
}

// }}}
?>
