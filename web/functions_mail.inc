<?php
namespace MRBS;

use PHPMailer\PHPMailer\PHPMailer;

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
//

global $mail_settings;

if ($mail_settings['icalendar'])
{
  require_once "functions_ical.inc";
}


// Determines whether an email might need to be sent
function need_to_send_mail()
{
  global $mail_settings;

  return ($mail_settings['admin_on_bookings'] or
          $mail_settings['area_admin_on_bookings'] or
          $mail_settings['room_admin_on_bookings'] or
          $mail_settings['booker'] or
          $mail_settings['book_admin_on_approval']);
}


function get_mail_locale()
{
  static $mail_locale;
  global $mail_settings;

  if (!isset($mail_locale))
  {
    if (isset($mail_settings['admin_lang']))
    {
      $mail_locale = System::getOSlocale($mail_settings['admin_lang']);
    }
    else
    {
      $mail_locale = FALSE;
    }
  }

  return $mail_locale;
}

// Get localized (for email) field name for a user defined table column
// Looks for a tag of the format tablename.columnname (where tablename is
// stripped of the table prefix) and if can't find a string for that tag will
// return the column name
function get_mail_field_name($table, $name)
{
  global $db_tbl_prefix;

  $tag = utf8_substr($table, utf8_strlen($db_tbl_prefix));  // strip the prefix off the table name
  $tag .= "." . $name;           // add on the fieldname
  // then if there's a string in the vocab array for $tag use that
  // otherwise just use the fieldname
  return get_mail_vocab($tag);
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

  $time = getdate($t);
  $p_num = $time['minutes'] + $mod_time;

  // Make sure we have a valid period [should this be necessary??]
  if ($p_num < 0)
  {
    $p_num = 0;
  }

  if ($p_num > count($periods) - 1)
  {
    $p_num = count($periods) - 1;
  }

  $mailperiod = $periods[$p_num];

  // The separator is a ',' as a '-' leads to an ambiguous display in report.php when showing end times.
  return array($p_num, $mailperiod . utf8_strftime(", " . $strftime_format['date'], $t, get_mail_locale()));
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
      return utf8_strftime($strftime_format['datetime24'], $t, get_mail_locale());
    }
    else
    {
      return utf8_strftime($strftime_format['datetime12'], $t, get_mail_locale());
    }
  }
  else
  {
    return utf8_strftime($strftime_format['date'], $t, get_mail_locale());
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
    if ($array[$i] !== '')
    {
      $mailer = new PHPMailer;
      // Use parseAddresses() to validate the address because it could contain a display name
      if (count($mailer->parseAddresses($array[$i])) == 0)
      {
        $message = 'Invalid email address "' . $array[$i] . '"';
        mail_debug($message);
        trigger_error($message, E_USER_NOTICE);
      }
      $trimmed_array[] = $array[$i];
    }
  }
  // remove duplicates
  $trimmed_array = array_unique($trimmed_array);
  // re-assemble the string
  $string = implode(',', $trimmed_array);
  return $string;
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
  $id_table = ($series) ? "rep" : "e";

  $sql = "SELECT a.area_admin_email ";
  $sql .= "FROM " . _tbl('room') . " r, " . _tbl('area') . " a, " . _tbl('entry') . " e ";
  // If this is a repeating entry...
  if ($id_table == 'rep')
  {
    // ...use the repeat table
    $sql .= ", " . _tbl('repeat') . " rep ";
  }
  $sql .= "WHERE ${id_table}.id=?
             AND r.id=${id_table}.room_id
             AND a.id=r.area_id
           LIMIT 1";
  $email = db()->query1($sql, array($id));

  return ($email == -1) ? '' : $email;
}


// Get the room_admin_email for an entry $id
// If $series is set this is an entry in the repeat table, otherwise the entry table
// Returns an empty string in the case of an error
function get_room_admin_email($id, $series=FALSE)
{
  $id_table = ($series) ? "rep" : "e";

  $sql = "SELECT r.room_admin_email ";
  $sql .= "FROM " . _tbl('room') . " r, " . _tbl('entry') . " e ";
  // If this is a repeating entry...
  if ($id_table == 'rep')
  {
    // ...use the repeat table
    $sql .= ", " . _tbl('repeat') . " rep ";
  }
  $sql .= "WHERE ${id_table}.id=?
             AND r.id=${id_table}.room_id
           LIMIT 1";
  $email = db()->query1($sql, array($id));

  return ($email == -1) ? '' : $email;
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
    $result .= ($as_html) ? "</td>\n" : "";
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
    $date_string = getMailDateString($date);
    $result .= ($as_html) ? htmlspecialchars($date_string) : $date_string;
    $result .= ($as_html) ? "</li>" : "\n";
  }
  $result .= ($as_html) ? "</ul>\n" : "";
  return $result;
}


// Generate a list of repeat dates for a series
//
// $reps is an array of start_times that have been created/modified/deleted.
function create_repeat_list($data, $action, $as_html, $reps)
{
  if (($data['rep_type'] == REP_NONE) ||
       in_array($action, array('more_info', 'remind')))
  {
    return '';
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

  $rep_details = array();
  foreach (array('rep_type', 'rep_opt', 'rep_interval', 'month_absolute', 'month_relative') as $key)
  {
    if (isset($data[$key]))
    {
      $rep_details[$key] = $data[$key];
    }
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


// $start_times is an array of start_times that have been created/modified/deleted.
// If not specified the function works them out for itself from the repeat data
function create_body($data, $mail_previous, $compare, $series, $action, $start_times, $as_html=false, $note='')
{
  global $mrbs_company;
  global $enable_periods, $approval_enabled, $confirmation_enabled;
  global $mail_settings, $standard_fields;
  global $select_options, $booking_types;

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
    $body .= '<html lang="' . convert_to_BCP47($mail_settings['admin_lang']) . "\">\n";
    $body .= "<head>\n";
    $body .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . get_mail_charset() . "\">\n";
    $body .= "<title>$mrbs_company</title>\n";
    $body .= "<style type=\"text/css\">\n";
    $css_file = 'css/mrbs-mail.css.php';
    if (is_file($css_file) && is_readable($css_file))
    {
      ob_start();
      include $css_file;
      $css = ob_get_clean();
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

  $user = getUserName();
  $user_escaped = ($as_html) ? htmlspecialchars($user) : $user;

  switch ($action)
  {
    case "approve":
      $body .= get_mail_vocab("mail_body_approved", $user_escaped);
      break;
    case "more_info":
      $body .= get_mail_vocab("mail_body_more_info", $user_escaped);
      $body .= ($as_html) ? "</p><p>" : "\n\n";
      $body .= get_mail_vocab("info_requested") . ": ";
      $body .= $note;
      break;
    case "remind":
      $body .= get_mail_vocab("mail_body_reminder");
      break;
    case "reject":
      $body .= get_mail_vocab("mail_body_rej_entry", $user_escaped);
      $body .= ($as_html) ? "</p><p>" : "\n\n";
      $body .= get_mail_vocab("reason") . ': ';
      $body .= $note;
      break;
    case "delete":
      $body .= get_mail_vocab("mail_body_del_entry", $user_escaped);
      break;
    default:
      if ($compare)
      {
        $body .= get_mail_vocab("mail_body_changed_entry", $user_escaped);
      }
      else
      {
        $body .= get_mail_vocab("mail_body_new_entry", $user_escaped);
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
    $body .= url_base() . multisite('view_entry.php?id=' . $data['id']);
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
      $new_status = ($data['tentative']) ? get_mail_vocab("tentative") : get_mail_vocab("confirmed");
      $old_status = ($mail_previous['tentative']) ? get_mail_vocab("tentative") : get_mail_vocab("confirmed");
      $body .= create_body_table_row (get_mail_vocab("confirmation_status"),
                                      $new_status,
                                      $old_status,
                                      $compare, $as_html);
    }

    if ($approval_enabled)
    {
      // Approval status:
      $new_status = ($data['awaiting_approval']) ? get_mail_vocab("awaiting_approval") : get_mail_vocab("approved");
      $old_status = ($mail_previous['awaiting_approval']) ? get_mail_vocab("awaiting_approval") : get_mail_vocab("approved");
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
    if (isset($booking_types) && (count($booking_types) > 1))
    {
      $body .= create_body_table_row (get_mail_vocab("type"),
                                      get_type_vocab($data['type']),
                                      get_type_vocab($mail_previous['type']),
                                      $compare, $as_html);
    }

    // Created by
    $body .= create_body_table_row (get_mail_vocab("createdby"),
                                    $data['create_by'],
                                    $mail_previous['create_by'],
                                    $compare, $as_html);

    // Custom fields
    $fields = db()->field_info(_tbl('entry'));
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
        $body .= create_body_table_row (get_mail_field_name(_tbl('entry'), $key),
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

      if ($data['rep_type'] == REP_WEEKLY)
      {
        // Repeat days
        // Display day names according to language and preferred weekday start.
        $opt = get_rep_day_list($data['rep_opt']);
        $opt_previous = ($compare) ? get_rep_day_list($mail_previous['rep_opt']) : "";
        $body .= create_body_table_row (get_mail_vocab("rep_rep_day"),
                                        $opt,
                                        $opt_previous,
                                        $compare, $as_html);
      }

      if ($data['rep_type'] == REP_MONTHLY)
      {

        if (isset($data['month_absolute']))
        {
          $new = $data['month_absolute'];
        }
        elseif (isset($data['month_relative']))
        {
          // Note: this does not internationalise very well and could do with revisiting.
          // It follows the select box order in edit_entry, which is the more difficult one
          // to sort out.  It assumes all languages have the same order as English
          // eg "the second Wednesday" which is probably not true.
          list($ord, $dow) = byday_split($data['month_relative']);
          $new = get_vocab("ord_" . $ord) . " " . day_name(RFC_5545_day_to_ord($dow));
        }
        else
        {
          trigger_error("Unknown monthly repeat type, E_USER_NOTICE");
        }
        if (isset($mail_previous['month_absolute']))
        {
          $old = $mail_previous['month_absolute'];
        }
        elseif (isset($mail_previous['month_relative']))
        {
          // Note: this does not internationalise very well and could do with revisiting.
          // It follows the select box order in edit_entry, which is the more difficult one
          // to sort out.  It assumes all languages have the same order as English
          // eg "the second Wednesday" which is probably not true.
          list($ord, $dow) = byday_split($mail_previous['month_relative']);
          $old = get_vocab("ord_" . $ord) . " " . day_name(RFC_5545_day_to_ord($dow));
        }
        else
        {
          trigger_error("Unknown monthly repeat type, E_USER_NOTICE");
        }
        $body .= create_body_table_row (get_mail_vocab("repeat_on"),
                                        $new,
                                        $old,
                                        $compare, $as_html);
      }

      // Repeat interval
      $new = $data['rep_interval'] . ' ' .
             get_rep_interval_units_mail($data['rep_type'], $data['rep_interval']);

      if (isset($mail_previous['rep_interval']))
      {
        $old = $mail_previous['rep_interval'] . ' ' .
               get_rep_interval_units_mail($mail_previous['rep_type'], $mail_previous['rep_interval']);
      }
      else
      {
        $old = '';
      }

      $body .= create_body_table_row (get_mail_vocab("rep_interval"),
                                      $new,
                                      $old,
                                      $compare, $as_html);

      // Repeat end date
      $end_previous = ($mail_previous['rep_type'] == REP_NONE) ? '' : getMailTimeDateString($mail_previous['end_date'], FALSE);
      $body .= create_body_table_row (get_mail_vocab("rep_end_date"),
                                      getMailTimeDateString($data['end_date'], FALSE),
                                      $end_previous,
                                      $compare, $as_html);

    }
  }

  if ($as_html)
  {
    $body .= "</tbody>\n";
    $body .= "</table>\n";
  }

  // Add in a list of repeat dates.  Although we've given them the repeat characteristics
  // above, it's often helpful to have this expanded out into a list of actual dates to
  // avoid any confusion.    The repeat list also gives a list of dates that could not
  // be booked due to conflicts.
  if ($data['rep_type'] != REP_NONE)
  {
    $body .= create_repeat_list($data, $action, $as_html, $start_times);
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
  if (isset($user) &&
      in_array($action, array('more_info', 'reject', 'remind')) &&
      !$mail_settings['use_from_for_all_mail'])
  {
    $from = authGetUserEmail($user);
  }

  if (empty($from))
  {
    $from = (isset($mail_settings['from'])) ? $mail_settings['from'] : null;
  }

  // if we're requiring bookings to be approved and this user needs approval
  // for this room, then get the email addresses of the approvers
  if (!in_array($action, array('delete', 'reject')) &&
      $approval_enabled &&
      !is_book_admin($data['room_id']))
  {
    $to[] = get_approvers_email($data['room_id']);
  }

  if ($mail_settings['admin_on_bookings'])
  {
    $to[] = $mail_settings['recipients'];
  }

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
    $booker_email = authGetUserEmail($booker);
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
 * @param   array $start_times   an array of start times that have been made
 * @param string  $action        the booking action (eg "delete", "more_info", etc.)
 * @param string  $note          a note that is used with "more_info"
 */
function notifyAdminOnBooking($data, $mail_previous, $new_entry, $series, $start_times, $action="book", $note='')
{
  global $mail_settings, $enable_periods;
  global $mrbs_company;

  mail_debug('Preparing email for new or changed booking ...');

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
    mail_debug('Email abandoned: no addresses.');
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
        $subject = get_mail_vocab("mail_subject_approved", $mrbs_company);
        break;
      case "more_info":
        $subject = get_mail_vocab("mail_subject_more_info", $mrbs_company);
        break;
      case "remind":
        $subject = get_mail_vocab("mail_subject_reminder", $mrbs_company);
        break;
      default:
        if ($new_entry)
        {
          $subject = get_mail_vocab("mail_subject_new_entry", $mrbs_company);
        }
        else
        {
          $subject = get_mail_vocab("mail_subject_changed_entry", $mrbs_company);
        }
        break;
    }
  }

  // Create the text body
  $compare = !$new_entry;
  $text_body = array();
  $text_body['content'] = create_body($data, $mail_previous, $compare, $series, $action, $start_times, false, $note);

  // Create the HTML body
  $html_body = array();
  if ($mail_settings['html'])
  {
    $html_body['content'] = create_body($data, $mail_previous, $compare, $series, $action, $start_times, true, $note);
    $html_body['cid'] = generate_global_uid("html");
  }

  // Create the iCalendar if required
  $attachment = array();
  if ($mail_settings['icalendar'] && !$enable_periods)
  {
    $attachment['method']   = "REQUEST";
    $ical_components = array();
    $ical_components[] = create_ical_event($attachment['method'], $data, $addresses, $series);
    $attachment['content']  = create_icalendar($attachment['method'], $ical_components);
    $attachment['name']     = $mail_settings['ics_filename'] . ".ics";
  }

  MailQueue::add(
      $addresses,
      $subject,
      $text_body,
      $html_body,
      $attachment,
      get_mail_charset()
    );
}

// }}}
// {{{ notifyAdminOnDelete()

/**
 * Send email to administrator to notify a new/changed entry.
 *
 * @param   array   $data      contains deleted entry data for email body
 * @param   array   $start_times an array of start times that have been deleted
 * @param   bool    $series    whether this is a series or not
 * @param   string  $action    the booking action (eg 'delete', 'more_info', etc.)
 * @param   string  $note      a note that is used with 'reject'
 * @return  bool    TRUE or PEAR error object if fails
 */
function notifyAdminOnDelete($data, $start_times, $series=false, $action='delete', $note='')
{
  global $mail_settings, $enable_periods;
  global $mrbs_company;

  if ($mail_settings['debug'])
  {
    mail_debug('Preparing email for deleted booking');
  }

  // As we are going to cancel this booking we need to increment the iCalendar
  // sequence number
  $data['ical_sequence']++;

  // Set up the addresses (from, to and cc)
  $addresses = create_addresses($data, $action);
  if ($addresses === FALSE)
  {
    mail_debug('Email abandoned: no addresses.');
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
    $subject = get_mail_vocab("mail_subject_rejected", $mrbs_company);
  }
  else
  {
    $subject = get_mail_vocab("mail_subject_delete", $mrbs_company);
  }

  // Create the text body
  $text_body = array();
  $text_body['content'] = create_body($data, null, false, $series, $action, $start_times, false, $note);

  // Create the HTML body
  $html_body = array();
  if ($mail_settings['html'])
  {
    $html_body['content'] = create_body($data, null, false, $series, $action, $start_times, true, $note);
    $html_body['cid'] = generate_global_uid("html");
  }

  // Set up the attachment
  $attachment = array();
  if ($mail_settings['icalendar'] && !$enable_periods)
  {
    $attachment['method']   = "CANCEL";
    $ical_components = array();
    $ical_components[] = create_ical_event($attachment['method'], $data, $addresses, $series);
    $attachment['content']  = create_icalendar($attachment['method'], $ical_components);
    $attachment['name']     = $mail_settings['ics_filename'] . ".ics";
  }

  MailQueue::add(
      $addresses,
      $subject,
      $text_body,
      $html_body,
      $attachment,
      get_mail_charset()
    );
}


function debug_output($message)
{
  global $mail_settings;

  if (isset($mail_settings['debug_output']) &&
      ($mail_settings['debug_output'] == 'browser'))
  {
    echo htmlspecialchars($message) . "<br>\n";
    // flush in case they have output_buffering configured on
    if (ob_get_length() !== FALSE)
    {
      ob_flush();
    }
    flush();
  }
  else  // anything else goes to the error log
  {
    error_log($message);
  }
}


function mail_debug($message)
{
  global $mail_settings;

  if ($mail_settings['debug'])
  {
    debug_output('[DEBUG] ' . $message);
  }
}




