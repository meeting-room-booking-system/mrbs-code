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
  global $mail_settings, $auth, $tbl_users;
  
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
  else
  {
    $email = str_replace($mail_settings['username_suffix'], '', $user);
    $email .= $mail_settings['domain'];
  }
  return $email;
}

// get the list of email addresses that are allowed to approve
// provisional bookings for the room with id $room_id
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
      

// }}}
// {{{ notifyAdminOnBooking()

/**
 * Send email to administrator to notify a new/changed entry.
 *
 * @param bool    $new_entry    to know if this is a new entry or not
 * @param int     $new_id       used for create a link to the new entry
 * @return bool                 TRUE or PEAR error object if fails
 */
function notifyAdminOnBooking($new_entry , $new_id, $series, $action="book")
{
  global $url_base, $returl, $name, $description, $area_name;
  global $room_name, $room_id, $starttime, $duration, $dur_units, $end_date, $endtime;
  global $rep_enddate, $typel, $type, $status, $create_by, $rep_type, $enable_periods;
  global $rep_opt, $rep_num_weeks, $custom_fields, $standard_fields;
  global $mail_previous, $auth, $note;
  global $mail_settings, $weekstarts, $provisional_enabled;
  global $tbl_entry;
  
  // We will treat Accept, Reject and More_info mailings as new entries because the
  // data hasn't changed since the previous time.
  if (($action == "accept") || ($action == "more_info") || ($action == "remind"))
  {
    $new_entry = TRUE;
  }

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
  
  // if we're using provisional bookings and this user needs approval
  // for this room, then get the email addresses of the approvers
  if ($provisional_enabled && !auth_book_admin($user, $room_id))
  {
    $recipients[] = get_approvers_email($room_id);
  }
  
  ($mail_settings['admin_on_bookings']) ? $recipients[] = $mail_settings['recipients'] : '';
  
  if ($mail_settings['area_admin_on_bookings'])
  {
    // Look for list of area admins emails addresses
    if ($new_entry)
    {
      $email = get_area_admin_email($new_id, ($rep_type != REP_NONE));
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
      $email = get_room_admin_email($new_id, ($rep_type != REP_NONE));
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
    if (($action == "accept")  || ($action == "more_info"))
    {
      // Put the recipients on the cc line and the booker will go
      // on the to line
      $cc = array_merge($cc, $recipients);
      $recipients = array();
    }
    $booker = ($new_entry) ? $create_by : $mail_previous['createdby'];
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
  
  // set up the subject and body
  switch ($action)
  {
    case "accept":
      $subject = get_mail_vocab("mail_subject_accepted");
      $body = get_mail_vocab("mail_body_accepted") . "\n\n";
      break;
    case "more_info":
      $subject = get_mail_vocab("mail_subject_more_info");
      $body = get_mail_vocab("mail_body_more_info") . "\n\n";
      $body .= get_mail_vocab("info_requested") . ": ";
      $body .= convertToMailCharset($note) . "\n\n";
      break;
    case "remind":
      $subject = get_mail_vocab("mail_subject_reminder");
      $body = get_mail_vocab("mail_body_reminder") . "\n\n";
      break;
    default:
      $subject = get_mail_vocab("mail_subject_entry");
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
  // Displays/don't displays entry details
  if ($mail_settings['details'])
  {
    $body .= "\n" . get_mail_vocab("namebooker") . ": ";
    $body .= compareEntries(convertToMailCharset($name),
                            convertToMailCharset($mail_previous['namebooker']),
                            $new_entry)  . "\n";
        
    // Description:
    $body .= get_mail_vocab("description") . ": ";
    $body .= compareEntries(convertToMailCharset($description),
                            convertToMailCharset($mail_previous['description']),
                            $new_entry) . "\n";
                            
    if  ($provisional_enabled)
    {                        
      // Status:
      $body .= get_mail_vocab("status") . ": ";
      $body .= ($status == STATUS_CONFIRMED) ? get_mail_vocab("confirmed") : get_mail_vocab("provisional");
      $body .= "\n";
    }
                               
    // Room:
    $body .= get_mail_vocab("room") . ": " .
      compareEntries(convertToMailCharset($area_name),
                     convertToMailCharset($mail_previous['area_name']),
                     $new_entry);
    $body .= " - " . compareEntries(convertToMailCharset($room_name),
                                    convertToMailCharset($mail_previous['room_name']),
                                    $new_entry) . "\n";
        
    // Start time
    if ( $enable_periods )
    {
      list( $start_period, $start_date) =
        getMailPeriodDateString($starttime);
      $body .= get_mail_vocab("start_date") . ": ";
      $body .= compareEntries($start_date,
                              $mail_previous['start_date'],
                              $new_entry) . "\n";
    }
    else
    {
      $start_date = getMailTimeDateString($starttime);
      $body .= get_mail_vocab("start_date") . ": " .
        compareEntries($start_date,
                       $mail_previous['start_date'],
                       $new_entry) . "\n";
    }
        
    // Duration
    $body .= get_mail_vocab("duration") . ": " .
      compareEntries($duration,
                     $mail_previous['duration'],
                     $new_entry);
    $body .= " " . compareEntries(get_mail_vocab("$dur_units"),
                                  $mail_previous['dur_units'],
                                  $new_entry) . "\n";
        
    // End time
    if ( $enable_periods )
    {
      $myendtime = $endtime;
      $mod_time = -1;
      list($end_period, $end_date) =  getMailPeriodDateString($myendtime,
                                                              $mod_time);
      $body .= get_mail_vocab("end_date") . ": ";
      $body .= compareEntries($end_date,
                              $mail_previous['end_date'],
                              $new_entry) ."\n";
    }
    else
    {
      $myendtime = $endtime;
      $end_date = getMailTimeDateString($myendtime);
      $body .= get_mail_vocab("end_date") . ": " .
        compareEntries($end_date,
                       $mail_previous['end_date'],
                       $new_entry) . "\n";
    }
    
    // Type of booking
    $body .= get_mail_vocab("type") . ": ";
    if ($new_entry)
    {
      $body .= $typel[$type];
    }
    else
    {
      $temp = $mail_previous['type'];
      $body .= compareEntries($typel[$type],
                              $typel[$temp],
                              $new_entry);
    }
        
    // Created by
    $body .= "\n" . get_mail_vocab("createdby") . ": " .
      compareEntries($create_by,
                     $mail_previous['createdby'],
                     $new_entry) . "\n";
                     
    // Custom fields
    $fields = sql_field_info($tbl_entry);
    foreach ($fields as $field)
    {
      if (!in_array($field['name'], $standard_fields['entry']))
      {
        $key = $field['name'];
        $value = (isset($custom_fields[$key])) ? $custom_fields[$key] : '';
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
        $body .= compareEntries($value, $mail_previous[$key], $new_entry) . "\n";
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
      $body .= ": " . get_mail_vocab("rep_type_$rep_type");
    }
    else
    {
      $temp = $mail_previous['rep_type'];
      $body .=  ": " . compareEntries(get_mail_vocab("rep_type_$rep_type"),
                                      get_mail_vocab("rep_type_$temp"),
                                      $new_entry);
    }
        
    // Details if a series
    if ($rep_type != REP_NONE)
    {
      $opt = "";
      if (($rep_type == REP_WEEKLY) || ($rep_type == REP_N_WEEKLY))
      {
        // Display day names according to language and preferred weekday start.
        for ($i = 0; $i < 7; $i++)
        {
          $daynum = ($i + $weekstarts) % 7;
          if ($rep_opt[$daynum])
          {
            $opt .= day_name($daynum) . " ";
          }
        }
      }
      if ($rep_type == REP_N_WEEKLY)
      {
        $body .= "\n" . get_mail_vocab("rep_num_weeks");
        $body .=  ": " . compareEntries($rep_num_weeks,
                                        $mail_previous["rep_num_weeks"],
                                        $new_entry);
      }
      
      if($opt || $mail_previous["rep_opt"])
      {
        $body .= "\n" . get_mail_vocab("rep_rep_day");
        $body .=  ": " . compareEntries($opt,
                                        $mail_previous["rep_opt"],
                                        $new_entry);
      }

      $body .= "\n" . get_mail_vocab("rep_end_date");
      if ($new_entry)
      {
        $body .= ": " . mail_strftime('%A %d %B %Y',$rep_enddate);
      }
      else
      {
        $temp = mail_strftime('%A %d %B %Y',$rep_enddate);
        $body .=  ": " . 
          compareEntries($temp,
                         $mail_previous['rep_end_date'],
                         $new_entry) . "\n";
      }
    }
    $body .= "\n";
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
function notifyAdminOnDelete($mail_previous)
{
  global $typel, $enable_periods, $auth;
  global $mail_settings, $standard_fields, $tbl_entry;
  
  // Get any extra arguments
  $action = (func_num_args() > 1) ? func_get_arg(1) : "delete";
  $note   = (func_num_args() > 2) ? func_get_arg(2) : "";

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
  
  // Set the subject and body
  if ($action == "reject")
  {
    $subject = get_mail_vocab("mail_subject_rejected");
    $body = get_mail_vocab("mail_body_rej_entry") . "\n\n";
    $body .= get_mail_vocab("reason") . ': ';
    $body .= convertToMailCharset($note) . "\n\n";
  }
  else
  {
    $subject = get_mail_vocab("mail_subject_delete");
    $body = get_mail_vocab("mail_body_del_entry") . "\n\n";
    // Give the name of the person deleting the entry (might not
    // be the same as the creator)
    $body .= get_mail_vocab("deleted_by") . ': ';
    $body .= convertToMailCharset($user) . "\n";
  }
  
  // Displays deleted entry details
  $body .= "\n" . get_mail_vocab("namebooker") . ': ';
  $body .= convertToMailCharset($mail_previous['namebooker']) . "\n";
  $body .= get_mail_vocab("description") . ": ";
  $body .= convertToMailCharset($mail_previous['description']) . "\n";
  $body .= get_mail_vocab("room") . ": ";
  $body .= convertToMailCharset($mail_previous['area_name']);
  $body .= " - " . convertToMailCharset($mail_previous['room_name']) . "\n";
  $body .= get_mail_vocab("start_date") . ': ';
  if ( $enable_periods )
  {
    $body .= convertToMailCharset($mail_previous['start_date']) . "\n";
  }
  else
  {
    $body .= convertToMailCharset($mail_previous['start_date']) . "\n";
  }
  $body .= get_mail_vocab("duration") . ': ' . $mail_previous['duration'] . ' ';
  $body .= $mail_previous['dur_units'] . "\n";
  if ( $enable_periods )
  {
    $body .= get_mail_vocab("end_date") . ": ";
    $body .= convertToMailCharset($mail_previous['end_date']) ."\n";
  }
  else
  {
    $body .= get_mail_vocab("end_date") . ": " .
      convertToMailCharset($mail_previous['end_date']);
    $body .= "\n";
  }
  $body .= get_mail_vocab("type") . ": ";
  $body .=  (empty($typel[$mail_previous['type']])) ? "?" .
    $mail_previous['type'] . "?" : $typel[$mail_previous['type']];
  $body .= "\n" . get_mail_vocab("createdby") . ": ";
  $body .= convertToMailCharset($mail_previous['createdby']) . "\n";
  
  // Custom fields
  $fields = sql_field_info($tbl_entry);
  foreach ($fields as $field)
  {
    if (!in_array($field['name'], $standard_fields['entry']))
    {
      $key = $field['name'];
      // Convert any booleans or pseudo-booleans to text strings (in the mail language)
      if (($field['nature'] == 'boolean') || 
          (($field['nature'] == 'integer') && isset($field['length']) && ($field['length'] <= 2)) )
      {
        $mail_previous[$key] = ($mail_previous[$key]) ? get_mail_vocab("yes") : get_mail_vocab("no");
      }
      $body .= get_mail_field_name($tbl_entry, $key) . ": ";
      $body .= convertToMailCharset($mail_previous[$key]) . "\n";
    }
  }
    
  $body .= get_mail_vocab("lastupdate") . ": " . convertToMailCharset($mail_previous['updated']);
  $body .= "\n" . get_mail_vocab("rep_type");
  $temp = $mail_previous['rep_type'];
  $body .=  ": " . get_mail_vocab("rep_type_$temp");
  if ($mail_previous['rep_type'] != REP_NONE)
  {
    if ($mail_previous['rep_type'] == REP_N_WEEKLY)
    {
      $body .= "\n" . get_mail_vocab("rep_num_weeks");
      $body .=  ": " . $mail_previous["rep_num_weeks"];
    }
   
    if($mail_previous["rep_opt"])
    {
      $body .= "\n" . get_mail_vocab("rep_rep_day");
      $body .=  ": " . $mail_previous["rep_opt"];
    }

    $body .= "\n" . get_mail_vocab("rep_end_date");
    $body .=  ": " . $mail_previous['rep_end_date'] . "\n";
  }
  $body .= "\n";
  // End of mail details
  $result = sendMail($recipient_list, $subject, $body, get_mail_charset(), $from, $cc_list);
  return $result;
}

// }}}
// {{{ getPreviousEntryData()

/**
 * Gather all fields values for an entry. Used for emails to get previous
 * entry state.
 *
 * @param int     $id       entry id to get data
 * @param int     $series   1 if this is a series or 0
 * @return bool             TRUE or PEAR error object if fails
 */
function getPreviousEntryData($id, $series)
{
  global $tbl_area, $tbl_entry, $tbl_repeat, $tbl_room, $enable_periods, $weekstarts;
  global $standard_fields;
  //
  $sql = "
    SELECT  e.name,
            e.description,
            e.create_by,
            r.room_name,
            a.area_name,
            e.type,
            e.room_id,
            e.repeat_id, " .
            sql_syntax_timestamp_to_unix("e.timestamp") . " AS last_updated,
            (e.end_time - e.start_time) AS tbl_e_duration,
            e.start_time AS tbl_e_start_time,
            e.end_time AS tbl_e_end_time,
            a.area_admin_email,
            r.room_admin_email";
  // Get the custom fields
  $fields = sql_field_info($tbl_entry);
  foreach ($fields as $field)
  {
    if (!in_array($field['name'], $standard_fields['entry']))
    {
      $sql .= ", e." . $field['name'];
    }
  }
  
  // Here we could just use $tbl_repeat.start_time, and not use alias,
  // as the last column will take precedence using mysql_fetch_array,
  // but for portability purpose I will not use it.
  if (1 == $series)
  {
    $sql .= ", re.rep_type, re.rep_opt, re.rep_num_weeks,
               (re.end_time - re.start_time) AS tbl_r_duration,
               re.start_time AS tbl_r_start_time,
               re.end_time AS tbl_r_end_time,
               re.end_date AS tbl_r_end_date";
  }
  $sql .= "
    FROM $tbl_entry e, $tbl_room r, $tbl_area a ";
  (1 == $series) ? $sql .= ', ' . $tbl_repeat . ' re ' : '';
  $sql .= "
    WHERE e.room_id = r.id
    AND r.area_id = a.id
    AND e.id=$id";
  (1 == $series) ? $sql .= " AND e.repeat_id = re.id" : '';
  //
  $res = sql_query($sql);
  (! $res) ? fatal_error(0, sql_error()) : '';
  (sql_count($res) < 1) ? fatal_error(0, get_vocab("invalid_entry_id")) : '';
  $row = sql_row_keyed($res, 0);
  sql_free($res);
  
  // Store all needed values in $mail_previous array to pass to
  // notifyAdminOnDelete function (shorter than individual variables -:) )
  $mail_previous['namebooker']    = $row['name'];
  $mail_previous['description']   = $row['description'];
  $mail_previous['createdby']     = $row['create_by'];
  $mail_previous['room_name']     = $row['room_name'];
  $mail_previous['area_name']     = $row['area_name'];
  $mail_previous['type']          = $row['type'];
  $mail_previous['room_id']       = $row['room_id'];
  $mail_previous['repeat_id']     = $row['repeat_id'];
  $mail_previous['updated']       = getMailTimeDateString($row['last_updated']);
  $mail_previous['area_admin_email'] = $row['area_admin_email'];
  $mail_previous['room_admin_email'] = $row['room_admin_email'];
  // Add in the custom fields
  foreach ($fields as $field)
  {
    if (!in_array($field['name'], $standard_fields['entry']))
    {
      $mail_previous[$field['name']] = $row[$field['name']];
    }
  }
  
  // Now get the start time, end time and duration.   The way this is done
  // depends on (a) whether we're using periods and (b) whether this is a series.
  // If we use periods
  if ( $enable_periods )
  {
    // If we delete a series, start_time and end_time must
    // come from $tbl_repeat, not $tbl_entry.
    //
    // This is not a series
    if (1 != $series)
    {
      list( $mail_previous['start_period'], $mail_previous['start_date']) =
        getMailPeriodDateString($row['tbl_e_start_time']);
      list( $mail_previous['end_period'] , $mail_previous['end_date']) =
        getMailPeriodDateString($row['tbl_e_end_time'], -1);
      // need to make DST correct in opposite direction to entry creation
      // so that user see what he expects to see
      $mail_previous['duration'] = $row['tbl_e_duration'] -
        cross_dst($row['tbl_e_start_time'], $row['tbl_e_end_time']);
    }
    // This is a series
    else
    {
      list( $mail_previous['start_period'], $mail_previous['start_date']) =
        getMailPeriodDateString($row['tbl_r_start_time']);
      list( $mail_previous['end_period'] , $mail_previous['end_date']) =
        getMailPeriodDateString($row['tbl_r_end_time'], 0);   
      // need to make DST correct in opposite direction to entry creation
      // so that user see what he expects to see
      $mail_previous['duration'] = $row['tbl_r_duration'] -
        cross_dst($row['tbl_r_start_time'], $row['tbl_r_end_time']);    
    }
    // Don't translate the units at this stage, as they'll get translated into the
    // browser language rather than the mail language.   We'll translate them later.
    toPeriodString($mail_previous['start_period'],
                   $mail_previous['duration'], $mail_previous['dur_units'], FALSE);
  }
  // If we don't use periods
  else
  {
    // This is not a series
    if (1 != $series)
    {
      $mail_previous['start_date'] =
        getMailTimeDateString($row['tbl_e_start_time']);
      $mail_previous['end_date'] =
        getMailTimeDateString($row['tbl_e_end_time']);
      // need to make DST correct in opposite direction to entry creation
      // so that user see what he expects to see
      $mail_previous['duration'] = $row['tbl_e_duration'] -
        cross_dst($row['tbl_e_start_time'], $row['tbl_e_end_time']);
    }
    // This is a series
    else
    {
      $mail_previous['start_date'] =
        getMailTimeDateString($row['tbl_r_start_time']);
      $mail_previous['end_date'] =
        getMailTimeDateString($row['tbl_r_end_time']);
      // need to make DST correct in opposite direction to entry creation
      // so that user see what he expects to see
      $mail_previous['duration'] = $row['tbl_r_duration'] -
        cross_dst($row['tbl_r_start_time'], $row['tbl_r_end_time']);
    }
    // Don't translate the units at this stage, as they'll get translated into the
    // browser language rather than the mail language.   We'll translate them later.
    toTimeString($mail_previous['duration'], $mail_previous['dur_units'], FALSE);
  }
  // Now translate the duration units into the mail language
  $mail_previous['dur_units'] = get_mail_vocab($mail_previous['dur_units']);
  
  
  // Next, get the repeat information if it's a series (and if it's not
  // a series, we still need to set rep_type to show that it's not a series)
  if (1 != $series)
  {
    $mail_previous['rep_type'] = REP_NONE;
  }
  else
  {
    $mail_previous['rep_type'] = $row['rep_type'];
    
    // use getMailTimeDateString as all I want is the date
    $mail_previous['rep_end_date'] =
        getMailTimeDateString($row['tbl_r_end_date'], FALSE);
    $mail_previous['rep_opt'] = "";
    switch($row['rep_type'])
    {
      case 2:
      case 6:
        $rep_day[0] = $row['rep_opt'][0] != "0";
        $rep_day[1] = $row['rep_opt'][1] != "0";
        $rep_day[2] = $row['rep_opt'][2] != "0";
        $rep_day[3] = $row['rep_opt'][3] != "0";
        $rep_day[4] = $row['rep_opt'][4] != "0";
        $rep_day[5] = $row['rep_opt'][5] != "0";
        $rep_day[6] = $row['rep_opt'][6] != "0";     
        break; 
      default:
        $rep_day = array(0, 0, 0, 0, 0, 0, 0);
    }
    for ($i = 0; $i < 7; $i++)
    {
      $wday = ($i + $weekstarts) % 7;
      if ($rep_day[$wday])
        $mail_previous['rep_opt'] .= day_name($wday) . " ";
    }
    
    if ($row['rep_type'] == REP_N_WEEKLY)
    {
      $mail_previous['rep_num_weeks'] = $row['rep_num_weeks'];
    }
    else
    {
      $mail_previous['rep_num_weeks'] = "";
    }   
  }
  
  // return entry previous data as an array
  return $mail_previous;
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
 * Send emails using PEAR::Mail class.
 * How to use this class -> http://www.pear.php.net/package/Mail then link
 * "View documentation".
 * Currently implemented version: Mail 1.1.3 and its dependancies
 * Net_SMTP 1.2.6 and Net_Socket 1.0.2
 *
 * @param string  $recipients       comma separated list of recipients or array
 * @param string  $subject          email subject
 * @param string  $body             text message
 * @param string  $charset          character set used in body
 * @param string  $cc               Carbon Copy
 * @param string  $bcc              Blind Carbon Copy
 * @param string  $from             from field
 * @param string  $backend          'mail', 'smtp' or 'sendmail'
 * @param string  $sendmail_path    ie. "/usr/bin/sendmail"
 * @param string  $sendmail_args    ie. "-t -i"
 * @param string  $host             smtp server hostname
 * @param string  $port             smtp server port
 * @param string  $auth             smtp server authentication, TRUE/FALSE
 * @param string  $username         smtp server username
 * @param string  $password         smtp server password
 * @return bool                     TRUE or PEAR error object if fails
 */
function sendMail($recipients, $subject, $body, 
                  $charset = 'us-ascii', $from, $cc = NULL, $bcc = NULL)
{
  require_once "Mail.php";
  
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
  
  $params = array();  // to avoid an undefined variable message

  // Headers part
  $headers['From']         = $from;
  if ( $backend != 'mail' )
  {
    $headers['To']           = $recipients;
  }
  (NULL != $cc) ? $headers['Cc'] = $cc : '';
  (NULL != $bcc) ? $headers['Bcc'] = $bcc : '';
  $headers['Subject']      = $subject;
  $headers['MIME-Version'] = '1.0';
  $headers['Content-Type'] = 'text/plain; charset=' . $charset;

  // Parameters part
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

  // Call to the PEAR::Mail class
  $mail_object =& Mail::factory($backend, $params);
  $result = $mail_object->send($recipients, $headers, $body);

  if (is_object($result))
  {
    error_log("Error sending email: ".$result->getMessage());
  }
  return $result;
}

// }}}
?>
