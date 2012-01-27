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

global $mail_settings;
 
if ($mail_settings['icalendar'])
{
  require_once "functions_ical.inc";
}


function get_mail_charset()
{
  return 'utf-8';
}

function get_mail_vocab($token)
{
  global $mail_vocab;

  $string = get_vocab($token, $mail_vocab);

  return str_replace('&nbsp;', ' ', $string);
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
  global $periods, $strftime_format;
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
  return array($p_num, $mailperiod . strftime(", " . $strftime_format['date'], $t));
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
  global $twentyfourhour_format, $strftime_format;

  if ($inc_time)
  {
    if ($twentyfourhour_format)
    {
      return utf8_strftime($strftime_format['datetime24'], $t);
    }
    else
    {
      return utf8_strftime($strftime_format['datetime12'], $t);
    }
  }
  else
  {
    return utf8_strftime($strftime_format['date'], $t);
  }
}


function getMailDateString($time)
{
  global $enable_periods;
  
  if ($enable_periods)
  {
    list($entry_period, $entry_date) = getMailPeriodDateString($time);
  }
  else
  {
    $entry_date = getMailTimeDateString($time);
  }
  return $entry_date;
}


// Splits an email address of the form 'common_name <address>',
// '"common_name" <address>' or just 'address' into a common name and an address.
// Returns the result as an array index by 'common_name' and 'address'.
function parse_address($email_address)
{
  if (strpos($email_address, '<') === FALSE)
  {
    $cn = '';
    $addr = $email_address;
  }
  else
  {
    list($cn, $addr) = explode('<', $email_address, 2);
  
    $cn = trim($cn);
    $cn = trim($cn, '"');
    $cn = trim($cn);
  
    $addr = trim($addr);
    $addr = rtrim($addr, '>');
    $addr = trim($addr);
  }
  
  $result = array();
  $result['common_name'] = $cn;
  $result['address'] = $addr; 
  return $result;
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


// Create a row of a table in either plain text or HTML format.
// Plain text:  returns "$label: $new\n"
// HTML:        returns "<tr><td>$label: </td><td>$new</td></tr>\n"
// If $compare is TRUE then a third column is output with $old in parentheses
function create_body_table_row($label, $new, $old='', $compare=FALSE, $as_html=FALSE)
{
  $result  = ($as_html) ? "<tr>\n" : "";
  
  // The label
  $result .= ($as_html) ? "<td>" : "";
  $result .= ($as_html) ? htmlspecialchars("$label: ") : "$label: ";
  $result .= ($as_html) ? "</td>\n" : "";
  // The new value
  $result .= ($as_html) ? "<td>" : "";
  $result .= ($as_html) ? htmlspecialchars($new) : "$new";
  $result .= ($as_html) ? "</td>\n" : "";
  // The old value (if we're doing a comparison)
  if ($compare)
  {
    $result .= ($as_html) ? "<td>" : "";
    if ($new == $old)
    {
      $result .= ($as_html) ? "&nbsp;" : "";
    }
    else
    {
      // Put parentheses around the HTML version as well as the plain text
      // version in case the table is not rendered properly in HTML.  The
      // parentheses will make the old value stand out.
      $result .= ($as_html) ? htmlspecialchars(" ($old)") : " ($old)";
    }
    $result .= ($as_html) ? "<td>\n" : "";
  }
  
  $result .= ($as_html) ? "</tr>\n" : "\n";
  return $result;
}


// Generate a list of dates from an array of start times
//
//   $dates      an array of start times
//   $as_html    (boolean) whether the list should be HTML or plain text
function create_date_list($dates, $as_html)
{
  $result = ($as_html) ? "<ul>\n" : "";
  foreach ($dates as $date)
  {
    $result .= ($as_html) ? "<li>" : "";
    $result .= getMailDateString($date);
    $result .= ($as_html) ? "</li>" : "\n";
  }
  $result .= ($as_html) ? "</ul>\n" : "";
  return $result;
}


// Generate a list of repeat dates for a series
function create_repeat_list($data, $action, $as_html)
{
  global $max_rep_entrys;
  
  if ($data['rep_type'] == REP_NONE)
  {
    return;
  }

  // The introductory text
  $result = ($as_html) ? "<p>" : "\n\n";
  if (($action == "delete") || ($action == "reject"))
  {
    $result .= get_vocab("mail_body_repeats_deleted");
  }
  else
  {
    $result .= get_vocab("mail_body_repeats_booked");
  }
  $result .= ($as_html) ? "</p>\n" : "\n\n";
  
  $reps = mrbsGetRepeatEntryList($data['start_time'], 
                                 $data['end_date'],
                                 $data['rep_type'], 
                                 $data['rep_opt'],
                                 $data['rep_num_weeks'],
                                 $max_rep_entrys);
  
  // Remove any dates that could not be booked due to conflicts                               
  if (!empty($data['skip_list']))
  {
    $reps = array_diff($reps, $data['skip_list']);
  }
                                                         
  $result .= create_date_list($reps, $as_html);
  
  // Now add in the list of repeat bookings that could not be booked
  if (!empty($data['skip_list']))
  {
    // The introductory text
    $result .= ($as_html) ? "<p>" : "\n\n";
    $result .= get_vocab("mail_body_exceptions");
    $result .= ($as_html) ? "</p>\n" : "\n\n";
    // Now the list of conflicts
    $result .= create_date_list($data['skip_list'], $as_html);
  }
  
  return $result;
}


function create_body($data, $mail_previous, $compare, $series, $action, $as_html=FALSE, $note='')
{
  global $returl, $mrbs_company;
  global $enable_periods, $approval_enabled, $confirmation_enabled;
  global $mail_settings, $standard_fields, $url_base;
  global $tbl_entry;
  global $select_options;

  // If we haven't got a previous entry just give it one.   It won't get used,
  // but will prevent a series if undefined index notices.
  if (empty($mail_previous))
  {
    $mail_previous = $data;
  }

  // set up the body
  $body = "";
  if ($as_html)
  {
    $body .= DOCTYPE . "\n";
    $body .= "<html>\n";
    $body .= "<head>\n";
    $body .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . get_mail_charset() . "\">\n";
    $body .= "<title></title>\n";
    $body .= "<style type=\"text/css\">\n";
    $css_file = 'mrbs-mail.css';
    if (file_exists($css_file))
    {
      $fh = fopen($css_file, 'r');
      $css = fread($fh, filesize($css_file));
      $css = preg_replace('!/\*.*?\*/!s', '', $css);  // Remove comments
      $css = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $css);  // Remove blank lines
      $body .= $css;
    }
    $body .= "</style>\n";
    $body .= "</head>\n";
    $body .= "<body id=\"mrbs\">\n";
    $body .= "<div id=\"header\">" . $mrbs_company . " - " . get_mail_vocab("mrbs") . "</div>\n";
    $body .= "<div id=\"contents\">\n";
  }
  $body .= ($as_html) ? "<p>" : "";
  switch ($action)
  {
    case "approve":
      $body .= get_mail_vocab("mail_body_approved");
      break;
    case "more_info":
      $body .= get_mail_vocab("mail_body_more_info");
      $body .= ($as_html) ? "</p><p>" : "\n\n";
      $body .= get_mail_vocab("info_requested") . ": ";
      $body .= $note;
      break;
    case "remind":
      $body .= get_mail_vocab("mail_body_reminder");
      break;
    case "reject":
      $body .= get_mail_vocab("mail_body_rej_entry");
      $body .= ($as_html) ? "</p><p>" : "\n\n";
      $body .= get_mail_vocab("reason") . ': ';
      $body .= $note;
      break;
    case "delete":
      $body .= get_mail_vocab("mail_body_del_entry");
      $body .= ($as_html) ? "</p><p>" : "\n\n";
      // Give the name of the person deleting the entry (might not
      // be the same as the creator)
      $body .= get_mail_vocab("deleted_by") . ': ';
      $user = getUserName();
      $body .= $user;
      break;
    default:
      if ($compare)
      {
        $body .= get_mail_vocab("mail_body_changed_entry");
      }
      else
      {
        $body .= get_mail_vocab("mail_body_new_entry");
      }
      break;
  }

  // Create a link to the entry, unless we're deleting it of course,
  // because then there won't be one.
  if (($action != "delete") && ($action != "reject"))
  {
    $body .= ($as_html) ? "</p><p>" : "\n\n";
    $body .= ($as_html) ? "<a target=\"_blank\" href=\"" : "";
    // Set the link to view entry page
    if (isset($url_base) && ($url_base != ""))
    {
      $body .= "$url_base/view_entry.php?id=" . $data['id'];
    }
    else
    {
      ('' != $returl) ? $url = explode(basename($returl), $returl) : '';
      $body .= $url[0] . "view_entry.php?id=" . $data['id'];
    }
    if ($series)
    {
      $body .= "&series=1";
    }
    $body .= ($as_html) ? "\">" . $data['name'] . "</a>" : "";
  }
  $body .= ($as_html) ? "</p>\n" : "\n\n";
  
  $body .= ($as_html) ? "<table>\n" : "\n";
  if ($compare && $as_html)
  {
    $body .= "<thead>\n";
    $body .= "<tr>\n";
    $body .= "<th>&nbsp;</th>\n";
    $body .= "<th>" . get_vocab("new_value") . "</th>\n";
    $body .= "<th>(" . get_vocab("old_value") . ")</th>\n";
    $body .= "</tr>\n";
    $body .= "</thead>\n";
  }
    
  $body .= ($as_html) ? "<tbody>\n" : "";

  
  // Always display the brief description
  $body .= create_body_table_row (get_mail_vocab("namebooker"),
                                  $data['name'],
                                  $mail_previous['name'],
                                  $compare, $as_html);
  
  // Displays/don't displays entry details
  if ($mail_settings['details'])
  {
    // Description:
    $body .= create_body_table_row (get_mail_vocab("description"),
                                    $data['description'],
                                    $mail_previous['description'],
                                    $compare, $as_html);
                                                                                         
    if ($confirmation_enabled)
    {                        
      // Confirmation status:
      $new_status = ($data['status'] & STATUS_TENTATIVE) ? get_mail_vocab("tentative") : get_mail_vocab("confirmed");
      $old_status = ($mail_previous['status'] & STATUS_TENTATIVE) ? get_mail_vocab("tentative") : get_mail_vocab("confirmed");
      $body .= create_body_table_row (get_mail_vocab("confirmation_status"),
                                      $new_status,
                                      $old_status,
                                      $compare, $as_html);
    }
                            
    if ($approval_enabled)
    {                        
      // Approval status:
      $new_status = ($data['status'] & STATUS_AWAITING_APPROVAL) ? get_mail_vocab("awaiting_approval") : get_mail_vocab("approved");
      $old_status = ($mail_previous['status'] & STATUS_AWAITING_APPROVAL) ? get_mail_vocab("awaiting_approval") : get_mail_vocab("approved");
      $body .= create_body_table_row (get_mail_vocab("approval_status"),
                                      $new_status,
                                      $old_status,
                                      $compare, $as_html);
    }
                               
    // Room:
    $new_room = $data['area_name'] . " - " . $data['room_name'];
    $old_room = $mail_previous['area_name'] . " - " . $mail_previous['room_name'];
    $body .= create_body_table_row (get_mail_vocab("room"),
                                    $new_room,
                                    $old_room,
                                    $compare, $as_html);
        
    // Start time
    $body .= create_body_table_row (get_mail_vocab("start_date"),
                                    getMailDateString($data['start_time']),
                                    getMailDateString($mail_previous['start_time']),
                                    $compare, $as_html);
        
    // Duration
    $new_duration = $data['duration'] . " " . get_mail_vocab($data['dur_units']);
    $old_duration = $mail_previous['duration'] . " " . get_mail_vocab($mail_previous['dur_units']);
    $body .= create_body_table_row (get_mail_vocab("duration"),
                                    $new_duration,
                                    $old_duration,
                                    $compare, $as_html);
                                        
    // End time
    $this_endtime = $data['end_time'];
    $previous_endtime = ($compare) ? $mail_previous['end_time'] : 0;
    if ($enable_periods)
    {
      // If we are using periods then the end_time is the end of the last
      // period.   We therefore need to subtract 60 seconds from it so that
      // we get the name of that period, rather than the name of the next one.
      $this_endtime = $this_endtime - 60;
      $previous_endtime = $previous_endtime - 60;
    }
    $body .= create_body_table_row (get_mail_vocab("end_date"),
                                    getMailDateString($this_endtime),
                                    getMailDateString($previous_endtime),
                                    $compare, $as_html);
    
    // Type of booking
    $body .= create_body_table_row (get_mail_vocab("type"),
                                    get_type_vocab($data['type']),
                                    get_type_vocab($mail_previous['type']),
                                    $compare, $as_html);
                                         
    // Created by
    $body .= create_body_table_row (get_mail_vocab("createdby"),
                                    $data['create_by'],
                                    $mail_previous['create_by'],
                                    $compare, $as_html);
                     
    // Custom fields
    $fields = sql_field_info($tbl_entry);
    foreach ($fields as $field)
    {
      if (!in_array($field['name'], $standard_fields['entry']))
      {
        $key = $field['name'];
        $value = (isset($data[$key])) ? $data[$key] : '';
        // Convert any booleans or pseudo-booleans to text strings (in the mail language)
        if (($field['nature'] == 'boolean') || 
            (($field['nature'] == 'integer') && isset($field['length']) && ($field['length'] <= 2)) )
        {
          $value = ($value) ? get_mail_vocab("yes") : get_mail_vocab("no");
          if ($compare)
          {
            $mail_previous[$key] = ($mail_previous[$key]) ? get_mail_vocab("yes") : get_mail_vocab("no");
          }
        }
        // For any associative arrays we want the value rather than the key
        if (isset($select_options["entry.$key"]) &&
            is_assoc($select_options["entry.$key"]) && 
            array_key_exists($value, $select_options["entry.$key"]))
        {
          $value = $select_options["entry.$key"][$value];
          if ($compare &&
              array_key_exists($mail_previous[$key], $select_options["entry.$key"]))
          {
            $mail_previous[$key] = $select_options["entry.$key"][$mail_previous[$key]];
          }
        }
        $body .= create_body_table_row (get_mail_field_name($tbl_entry, $key),
                                        $value,
                                        ($compare) ? $mail_previous[$key] : '',
                                        $compare, $as_html);
      }
    }
    
    // Last updated
    $body .= create_body_table_row (get_mail_vocab("lastupdate"),
                                    getMailTimeDateString(time()),
                                    ($compare) ? getMailTimeDateString($mail_previous['last_updated']) : '',
                                    $compare, $as_html);
        
    // Repeat Type
    $body .= create_body_table_row (get_mail_vocab("rep_type"),
                                    get_mail_vocab("rep_type_" . $data['rep_type']),
                                    get_mail_vocab("rep_type_" . $mail_previous['rep_type']),
                                    $compare, $as_html);
                                       
    // Details if a series
    if ($data['rep_type'] != REP_NONE)
    {     
      // Repeat number of weeks
      if ($data['rep_type'] == REP_N_WEEKLY)
      {
      
        $body .= create_body_table_row (get_mail_vocab("rep_num_weeks"),
                                        $data['rep_num_weeks'],
                                        $mail_previous["rep_num_weeks"],
                                        $compare, $as_html);
      }
      
      // Repeat days
      if (($data['rep_type'] == REP_WEEKLY) || ($data['rep_type'] == REP_N_WEEKLY))
      {
      
        // Display day names according to language and preferred weekday start.
        $opt = get_rep_day_list($data['rep_opt']);
        $opt_previous = ($compare) ? get_rep_day_list($mail_previous['rep_opt']) : "";
        $body .= create_body_table_row (get_mail_vocab("rep_rep_day"),
                                        $opt,
                                        $opt_previous,
                                        $compare, $as_html);
      }
      
      // Repeat end date
      $body .= create_body_table_row (get_mail_vocab("rep_end_date"),
                                      getMailTimeDateString($data['end_date'], FALSE),
                                      getMailTimeDateString($mail_previous['end_date'], FALSE),
                                      $compare, $as_html);
                                                                   
    }
  }
  
  if ($as_html)
  {
    $body .= "</tbody>\n";
    $body .= "</table>\n";
  }
  
  // Add in a list of repeat dates.  Although we've given them the repeat charateristics
  // above, it's often helpful to have this expanded out into a list of actual dates to
  // avoid any confusion.    The repeat list also gives a list of dates that could not
  // be booked due to conflicts.
  if ($data['rep_type'] != REP_NONE)
  {
    $body .= create_repeat_list($data, $action, $as_html);
  }
  
  if ($as_html)
  {
    $body .= "</div>\n";
    $body .= "</body>\n";
    $body .= "</html>\n";
  }

  return $body;
}

// create_addresses($data, $action)
//
// Returns an array indexed by 'from', 'to' and 'cc' with each element
// consisting of a comma separated list of email addresses, or else
// FALSE if there are no 'to' or 'cc' addresses
//
// Parameters:
//   $data     an array containing all the data concerning this booking
//   $action   the action that has caused this email to be sent
//
function create_addresses($data, $action)
{
  global $approval_enabled, $mail_settings;
  
  $to = array();
  $cc = array();
  
  $cc[] = $mail_settings['cc'];
  
  // set the from address
  $user = getUserName();
  if (isset($user) && in_array($action, array("more_info", "reject", "remind")))
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
  if (!in_array($action, array("delete", "reject")) &&
      $approval_enabled && 
      !auth_book_admin($user, $data['room_id']))
  {
    $to[] = get_approvers_email($data['room_id']);
  }
  
  ($mail_settings['admin_on_bookings']) ? $to[] = $mail_settings['recipients'] : '';
  
  if ($mail_settings['area_admin_on_bookings'])
  {
    // Look for list of area admins email addresses
    if (empty($data['area_admin_email']))
    {
      $email = get_area_admin_email($data['id'], ($data['rep_type'] != REP_NONE));
      if (!empty($email))
      {
        $to[] = $email;
      }
    }
    else
    {
      $to[] = $data['area_admin_email'];
    }
  }
  
  if ($mail_settings['room_admin_on_bookings'])
  {
    // Look for list of room admins email addresses
    if (empty($data['room_admin_email']))
    {
      $email = get_room_admin_email($data['id'], ($data['rep_type'] != REP_NONE));
      if (!empty($email))
      {
        $to[] = $email;
      }
    }
    else
    {
      $to[] = $data['room_admin_email'];
    }
  }
  
  if ($mail_settings['booker'])
  {
    if (in_array($action, array("approve", "more_info", "reject")))
    {
      // Put the addresses on the cc line and the booker will go
      // on the to line
      $cc = array_merge($cc, $to);
      $to = array();
    }
    $booker = $data['create_by'];
    $booker_email = get_email_address($booker);
    if (!empty($booker_email))
    {
      $to[] = $booker_email;
    }
  }
  // In case $to and $cc are empty, no need to go further
  if (empty($to) && empty($cc))
  {
    return FALSE;
  }
  
  $addresses = array();
  $addresses['from'] = $from;
  $addresses['to']   = get_address_list($to);
  $addresses['cc'] = get_address_list($cc);
  return $addresses;
}


// }}}
// {{{ notifyAdminOnBooking()

/**
 * Send email to administrator to notify a new/changed entry.
 *
 * @param array   $data          contains the data for this entry
 * @param array   $mail_previous contains the data for the previous entry, or is an empty array
 * @param bool    $new_entry     whether this is a new entry or not
 * @param bool    $series        whether this is a series or not
 * @param string  $action        the booking action (eg "delete", "more_info", etc.)
 * @param string  $note          a note that is used with "more_info"
 * @return bool                  TRUE or PEAR error object if fails
 */
function notifyAdminOnBooking($data, $mail_previous, $new_entry, $series, $action="book", $note='')
{
  global $mail_settings, $enable_periods;
  
  if ($mail_settings['debug'])
  {
    echo "[DEBUG] Preparing email for new or changed booking ... <br>";
  }

  // Add some values to the $data array before we go and create the addresses
  if (!$new_entry)
  {
    $data['area_admin_email'] = (!empty($mail_previous['area_admin_email'])) ? $mail_previous['area_admin_email'] : NULL;
    $data['room_admin_email'] = (!empty($mail_previous['room_admin_email'])) ? $mail_previous['room_admin_email'] : NULL;
  }
  
  // Set up the addresses (from, to and cc)
  $addresses = create_addresses($data, $action);
  if ($addresses === FALSE)
  {
    if ($mail_settings['debug'])
    {
      echo "[DEBUG] Email abandoned:  no addresses.<br>";
    }
    return;
  }
  
  // Set up the subject
  //
  // If we're sending iCalendar notifications, then it seems that some calendar
  // applications use the email subject as the booking title instead of the iCal
  // SUMMARY field.   As far as I can see this is wrong, but as a circumvention
  // we'll put the booking title in the email subject line.   (See also
  // SF Tracker id 3297799)
  if ($mail_settings['icalendar'] && !$enable_periods)
  {
    $subject = $data['name'];
  }
  else
  {
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
  }

  // Create the text body
  $compare = !$new_entry;
  $text_body = array();
  $text_body['content'] = create_body($data, $mail_previous, $compare, $series, $action, FALSE, $note);
  
  // Create the HTML body
  $html_body = array();
  if ($mail_settings['html'])
  {
    $html_body['content'] = create_body($data, $mail_previous, $compare, $series, $action, TRUE, $note);
    $html_body['cid'] = generate_global_uid("html");
  }
  
  // Create the iCalendar if required
  $attachment = array();
  if ($mail_settings['icalendar'] && !$enable_periods)
  {
    $attachment['method']   = "REQUEST";
    $ical_components = array();
    $ical_components[] = create_ical_event($attachment['method'], $data, $text_body, $html_body, $addresses, $series);
    $attachment['content']  = create_icalendar($attachment['method'], $ical_components);
    $attachment['name']     = $mail_settings['ics_filename'] . ".ics";
  }

  $result = sendMail($addresses,
                     $subject,
                     $text_body,
                     $html_body,
                     $attachment,
                     get_mail_charset());
  return $result;
}

// }}}
// {{{ notifyAdminOnDelete()

/**
 * Send email to administrator to notify a new/changed entry.
 *
 * @param   array   $data      contains deleted entry data for email body
 * @param   bool    $series    whether this is a series or not
 * @param   string  $action    the booking action (eg "delete", "more_info", etc.)
 * @param   string  $note      a note that is used with "reject"
 * @return  bool    TRUE or PEAR error object if fails
 */
function notifyAdminOnDelete($data, $series=FALSE, $action="delete", $note="")
{
  global $mail_settings, $enable_periods;
  
  if ($mail_settings['debug'])
  {
    echo "[DEBUG] Preparing email for deleted booking ... <br>";
  }
  
  // As we are going to cancel this booking we need to increment the iCalendar
  // sequence number
  $data['ical_sequence']++;

  // Set up the addresses (from, to and cc)
  $addresses = create_addresses($data, $action);
  if ($addresses === FALSE)
  {
    if ($mail_settings['debug'])
    {
      echo "[DEBUG] Email abandoned:  no addresses.<br>";
    }
    return;
  }
  
  // Set the subject
  //
  // If we're sending iCalendar notifications, then it seems that some calendar
  // applications use the email subject as the booking title instead of the iCal
  // SUMMARY field.   As far as I can see this is wrong, but as a circumvention
  // we'll put the booking title in the email subject line.   (See also
  // SF Tracker id 3297799)
  if ($mail_settings['icalendar'] && !$enable_periods)
  {
    $subject = $data['name'];
  }
  elseif ($action == "reject")
  {
    $subject = get_mail_vocab("mail_subject_rejected");
  }
  else
  {
    $subject = get_mail_vocab("mail_subject_delete");
  }
  
  // Create the text body
  $text_body = array();
  $text_body['content'] = create_body($data, NULL, FALSE, $series, $action, FALSE, $note);
  
  // Create the HTML body
  $html_body = array();
  if ($mail_settings['html'])
  {
    $html_body['content'] = create_body($data, NULL, FALSE, $series, $action, TRUE, $note);
    $html_body['cid'] = generate_global_uid("html");
  }
  
  // Set up the attachment
  $attachment = array();
  if ($mail_settings['icalendar'] && !$enable_periods)
  {
    $attachment['method']   = "CANCEL";
    $ical_components = array();
    $ical_components[] = create_ical_event($attachment['method'], $data, $text_body, $html_body, $addresses, $series);
    $attachment['content']  = create_icalendar($attachment['method'], $ical_components);
    $attachment['name']     = $mail_settings['ics_filename'] . ".ics";
  }

  $result = sendMail($addresses,
                     $subject,
                     $text_body,
                     $html_body,
                     $attachment,
                     get_mail_charset());

  return $result;
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
 * @param array   $addresses        an array of addresses, each being a comma
 *                                  separated list of email addreses.  Indexed by
 *                                    'from'
 *                                    'to'
 *                                    'cc'
 *                                    'bcc'
 * @param string  $subject          email subject
 * @param array   $text_body        text part of body, an array consisting of
 *                                    'content'  the content itself
 *                                    'cid'      the content id
 * @param array   $text_html        HTML part of body, an array consisting of
 *                                    'content'  the content itself
 *                                    'cid'      the content id
 * @param array   $attachment       file to attach.   An array consisting of
 *                                    'content' the file or data to attach
 *                                    'method'  the iCalendar METHOD
 *                                    'name'    the name to give it
 * @param string  $charset          character set used in body
 * @return bool                     TRUE or PEAR error object if fails
 */
function sendMail($addresses, $subject, $text_body, $html_body, $attachment, $charset = 'us-ascii')
{
  require_once "Mail.php";
  require_once "Mail/mime.php";
  require_once "Mail/mimePart.php";

  global $mail_settings, $sendmail_settings, $smtp_settings, $enable_periods;
  
  // Don't do aything if mail has been disabled.   Useful for testing MRBS without
  // sending emails to those who don't want them
  if ($mail_settings['disabled'])
  {
    return;
  }
  
  if ($mail_settings['debug'])
  {
    echo "[DEBUG] Preparing to send email ... <br>";
  }
  
  $eol = "\n";  // EOL sequence to use in mail headers.  Need "\n" for mail backend
  
  // for cases where the mail server refuses
  // to send emails with cc or bcc set, put the cc
  // addresses on the to line
  if (!empty($addresses['cc']) && $mail_settings['treat_cc_as_to'])
  {
    $recipients_array = array_merge(explode(',', $addresses['to']),
                                    explode(',', $addresses['cc']));
    $addresses['to'] = get_address_list($recipients_array);
    $addresses['cc'] = NULL;
  }
  if (empty($addresses['from']))
  {
    $addresses['from'] = $mail_settings['from'];
  }
  
  // Need to put all the addresses into $recipients
  $recipients = $addresses['to'];
  $recipients .= (!empty($addresses['cc'])) ? "," . $addresses['cc'] : "";
  $recipients .= (!empty($addresses['bcc'])) ? "," . $addresses['bcc'] : "";
  
  // Set up configuration settings
  $backend = $mail_settings['admin_backend'];
  
  // Parameters part
  $params = array();  // to avoid an undefined variable message
  if ($backend == 'sendmail')
  {
    $params['sendmail_path'] = $sendmail_settings['path'];
    $params['sendmail_args'] = $sendmail_settings['args'];
  }
  if ($backend == 'smtp')
  {
    $params['host']     = $smtp_settings['host'];
    $params['port']     = $smtp_settings['port'];
    $params['auth']     = $smtp_settings['auth'];
    $params['username'] = $smtp_settings['username'];
    $params['password'] = $smtp_settings['password'];
  }
  
  // Headers part (extra headers - the standard headers will already be there)
  $headers = array();
  $headers['From'] = $addresses['from'];
  if ($backend != 'mail')
  {
    $headers['To'] = $addresses['to'];
  }
  (!empty($addresses['cc'])) ? $headers['Cc'] = $addresses['cc'] : '';
  (!empty($addresses['bcc'])) ? $headers['Bcc'] = $addresses['bcc'] : '';
  // If the subject contains any non-ASCII characters...
  if (!preg_match('/^[[:ascii:]]*$/', $subject))
  {
    // ...communicate the charset and encode it correctly
    $subject = "=?".get_mail_charset()."?B?".base64_encode($subject)."?=";
  }
  $headers['Subject'] = $subject;
  $headers['Mime-Version'] = '1.0';
  
  // Build the email.   We're going to use the "alternative" subtype which means
  // that we order the sub parts according to how faithful they are to the original,
  // putting the least faithful first, ie the ordinary plain text version.   The
  // email client then uses the most faithful version that it can handle.
  // 
  // If we are also adding the iCalendar information then we enclose this alternative
  // mime subtype in an outer mime type which is mixed.    This is necessary so that
  // the widest variety of calendar applications can access the calendar information.
  // So depending on whether we are sending iCalendar information we will have a Mime
  // structure that looks like this:
  //
  //    With iCalendar info                 Without iCalendar info
  //    -------------------                 ----------------------
  //
  //    multipart/mixed                     mutlipart/alternative
  //      multipart/alternative               text/plain
  //        text/plain                        text/html
  //        text/html
  //        text/calendar
  //      application/ics
  
  // First of all build the inner mime type, ie the multipart/alternative type.
  // If we're not sending iCalendar information this will become the outer,
  // otherwise we'll then enclose it in an outer mime type.
  $mime_params = array();
  $mime_params['eol'] = $eol;
  $mime_params['content_type'] = "multipart/alternative";
  $mime_inner = new Mail_mimePart('', $mime_params);
  
  // Add the text part
  $mime_params['content_type'] = "text/plain";
  $mime_params['encoding']     = "8bit";
  $mime_params['charset']      = $charset;
  $mime_inner->addSubPart($text_body['content'], $mime_params);
  
  // Add the HTML mail
  if (!empty($html_body))
  {
    $mime_params['content_type'] = "text/html";
    $mime_params['cid'] = $html_body['cid'];
    $mime_inner->addSubPart($html_body['content'], $mime_params);
    unset($mime_params['cid']);
  }
  
  if (!$mail_settings['icalendar'] || $enable_periods)
  {
    // If we're not sending iCalendar information we've now got everything,
    // so we'll make the "inner" section the complete mime.
    $mime = $mime_inner;
  }
  else
  {
    // Otherwise we need to carry on and add the text version of the iCalendar
    $mime_params['content_type'] = "text/calendar; method=" . $attachment['method'];
    // The encoding needs to be base64, because Postfix will otherwise not preserve
    // CRLF sequences and these are mandatory terminators in the iCalendar file
    $mime_params['encoding'] = "base64";
    $mime_inner->addSubPart($attachment['content'], $mime_params);

    // and then enclose the inner section in a multipart/mixed outer section.
    // First create the outer section
    $mime_params = array();
    $mime_params['eol'] = $eol;
    $mime_params['content_type'] = "multipart/mixed";
    $mime = new Mail_mimePart('', $mime_params);

    // Now add the inner section as the first sub part
    $mime_inner = $mime_inner->encode();
    $mime_params = array();
    $mime_params['eol'] = $eol;
    $mime_params['encoding'] = "8bit";
    $mime_params['content_type'] = $mime_inner['headers']['Content-Type'];
    $mime->addSubPart($mime_inner['body'], $mime_params);
    
    // And add the attachment as the second sub part
    $mime_params['content_type'] = "application/ics";
    $mime_params['encoding']     = "base64";
    $mime_params['disposition']  = "attachment";
    $mime_params['dfilename']    = $attachment['name'];
    $mime->addSubPart($attachment['content'], $mime_params);
  }

  // Encode the result
  $mime = $mime->encode();
  // Add in the extra headers
  $mime['headers'] = array_merge($headers, $mime['headers']);

  // Debug info if required
  if ($mail_settings['debug'])
  {
    echo "[DEBUG] Using backend '$backend'<br>";
    echo "[DEBUG] Recipients: '" . htmlspecialchars($recipients) . "'<br>";
  }
  
  // Call to the PEAR::Mail class
  $mail_object =& Mail::factory($backend, $params);
  $result = $mail_object->send($recipients, $mime['headers'], $mime['body']);

  if (is_object($result))
  {
    error_log("Error sending email: ".$result->getMessage());
  }
  
  // Debug info if required
  if ($mail_settings['debug'])
  {
    if (is_object($result))
    {
      echo "[DEBUG] Failed to send email: " . htmlspecialchars($result->getMessage()) . "<br>";
    }
    else
    {
      echo "[DEBUG] Email sent successfully<br>";
    }
  }
  
  return $result;
}

// }}}
?>
