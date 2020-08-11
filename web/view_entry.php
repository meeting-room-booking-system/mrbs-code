<?php
namespace MRBS;

use MRBS\Form\FieldDiv;
use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldTextarea;


require "defaultincludes.inc";
require_once "mrbs_sql.inc";
require_once "functions_view.inc";


function generate_registrant_table($row)
{
  echo "<table id=\"registrants\">\n";
  echo "<tbody>\n";

  foreach ($row['registrants'] as $registrant)
  {
    echo '<tr>';
    $registrant_user = auth()->getUser($registrant['username']);
    $display_name = (isset($registrant_user)) ? $registrant_user->display_name : $registrant['username'];
    echo '<td>' . htmlspecialchars($display_name) . '</td>';
    $registrant_creator = auth()->getUser($registrant['create_by']);
    $display_name = (isset($registrant_creator)) ? $registrant_creator->display_name : $registrant['create_by'];
    echo '<td>' . htmlspecialchars($display_name) . '</td>';
    echo "</tr>\n";
  }

  echo "</tbody>\n";
  echo "</table>\n";
}


function generate_event_registration($row, $previous_page=null)
{
  global $auth, $server;

  if (empty($row['allow_registration']))
  {
    return;
  }

  $can_register_others = is_book_admin($row['room_id']);
  $n_registered = count($row['registrants']);


  echo '<h4>' . get_vocab('event_registration') . "</h4>\n";
  echo "<div id=\"registration\">\n";
  echo "<table class=\"list\">\n";
  echo "<tbody>\n";

  if (!empty($row['enable_registrant_limit']))
  {
    echo '<tr>';
    echo '<td>' . htmlspecialchars(get_vocab('registrant_limit')) . '</td>';
    echo '<td>' . htmlspecialchars($row['registrant_limit']) . '</td>';
    echo "</tr>\n";
  }

  echo '<tr>';
  echo '<td>' . htmlspecialchars(get_vocab('n_registered')) . '</td>';
  echo '<td>' . htmlspecialchars($n_registered) . '</td>';
  echo "</tr>\n";
  echo "</tbody>\n";
  echo "</table>\n";

  // Display the list of registrants, if the user is allowed to see it, which is if
  // they have write access for this booking.
  // Display it as a table because in the future we might want to (a) add more columns,
  // eg date and time of registration and (b) use DataTables to make the list searchable
  // and exportable.
  if (($n_registered > 0) &&
      ($auth['show_registrant_names'] || getWritable($row['create_by'], $row['room_id'])))
  {
    generate_registrant_table($row);
  }

  // Display registration information and buttons for this user
  $mrbs_user = session()->getCurrentUser();
  if (!$can_register_others && in_arrayi($mrbs_user->username,
                                         array_column($row['registrants'], 'username')))
  {
    echo '<p>' . htmlspecialchars(get_vocab('already_registered')) . "</p>\n";
    $button_value = get_vocab('cancel_registration');
    $button_action = 'cancel';
  }
  else
  {
    if (empty($row['enable_registrant_limit']) ||
      ($row['registrant_limit'] > $n_registered))
    {
      $button_value = get_vocab('register');
      $button_action = 'register';
    }
    else
    {
      echo '<p>' . htmlspecialchars(get_vocab('event_full')) . "</p>\n";
    }
  }

  if (isset($button_value))
  {
    $form = new Form();
    $form->setAttributes(array('action' => multisite('registration_handler.php'),
                               'class'  => 'standard',
                               'method' => 'post'));

    // Add the previous_page (ie the one we were on before view_entry) to the query string
    // so that it is preserved.
    $returl  = this_page();
    $query_string = isset($server['QUERY_STRING']) ? $server['QUERY_STRING'] : '';
    parse_str($query_string, $query_string_parts);
    if (isset($previous_page))
    {
      $query_string_parts['previous_page'] = $previous_page;
    }
    if (!empty($query_string_parts))
    {
      $returl .= '?' . http_build_query($query_string_parts, '', '&');
    }

    // Hidden inputs
    $form->addHiddenInputs(array(
        'action' => $button_action,
        'event_id' => $row['id'],
        'returl' => $returl
      ));

    if (($button_action != 'register') || !$can_register_others)
    {
      $form->addHiddenInput('username', $mrbs_user->username);
    }
    else
    {
      $fieldset = new ElementFieldset();
      $params = array(
        'value'     => $mrbs_user->username,
        'disabled'  => false,
        'required'  => true,
        'field'     => 'participants.username',
        'label'     => get_vocab('name'),
        'name'      => 'username',
      );
      $fieldset->addElement(get_user_field($params, true));
      $form->addElement($fieldset);
    }

    // Submit button
    $fieldset = new ElementFieldset();
    $field = new FieldDiv();
    $element = new ElementInputSubmit();
    $element->setAttribute('value', $button_value);
    $field->addControl($element);
    $fieldset->addElement($field);
    $form->addElement($fieldset);

    $form->render();

    echo "</div>\n";
  }
}


// Generates a single button.  Parameters in the array $params
//
//    Manadatory parameters
//      action    The form action attribute
//      value     The value of the button
//      inputs    An array of hidden form inputs
//
//    Optional parameters
//      button_attributes   An array of attributes to be used for the button.
function generate_button(array $params, array $button_attributes=array())
{
  // Note that until IE supports the form attribute on the button tag, we can't
  // use a <button> here and have to use the <input type="submit"> to create the
  // button.   This unfortunately means that styling options on the button are limited.

  $form = new Form();

  $attributes = array('action' => $params['action'],
                      'method' => 'post');

  $form->setAttributes($attributes);

  // Hidden inputs
  $form->addHiddenInputs($params['inputs']);

  // Submit button
  $element = new ElementInputSubmit();
  $element->setAttribute('value', $params['value'])
          ->setAttributes($button_attributes);
  $form->addElement($element);

  $form->render();
}


// Generates the Approve, Reject and More Info buttons
function generateApproveButtons($id, $series)
{
  global $returl;
  global $entry_info_time, $entry_info_user, $repeat_info_time, $repeat_info_user;
  global $multisite, $site;

  $info_time = ($series) ? $repeat_info_time : $entry_info_time;
  $info_user = ($series) ? $repeat_info_user : $entry_info_user;

  $this_page = this_page();
  if (empty($info_time))
  {
    $info_title = get_vocab("no_request_yet");
  }
  else
  {
    $info_title = get_vocab("last_request") . ' ' . time_date_string($info_time);
    if (!empty($info_user))
    {
      $info_title .= " " . get_vocab("by") . " $info_user";
    }
  }

  $query_string = "id=$id&series=" . (($series) ? 1 : 0);
  if ($multisite && isset($site) && ($site !== ''))
  {
    $query_string .= "&site=$site";
  }

  echo "<tr>\n";
  echo "<td>" . ($series ? get_vocab("series") : get_vocab("entry")) . "</td>\n";
  echo "<td>\n";

  // Approve
  $params = array('action' => multisite("approve_entry_handler.php?$query_string"),
                  'value'  => get_vocab('approve'),
                  'inputs' => array('action' => 'approve',
                                    'returl' => $returl)
                 );
  generate_button($params);

  // Reject
  $params = array('action' => multisite("$this_page?$query_string"),
                  'value'  => get_vocab('reject'),
                  'inputs' => array('action' => 'reject',
                                    'returl' => $returl)
                 );
  generate_button($params);

  // More info
  $params = array('action' => multisite("$this_page?$query_string"),
                  'value'  => get_vocab('more_info'),
                  'inputs' => array('action' => 'more_info',
                                    'returl' => $returl)
                 );
  generate_button($params, array('title' => $info_title));

  echo "</td>\n";
  echo "</tr>\n";
}

function generateOwnerButtons($id, $series)
{
  global $mrbs_username, $create_by, $awaiting_approval, $area;
  global $reminders_enabled, $last_reminded, $reminder_interval;
  global $multisite, $site;

  // Remind button if you're the owner AND there's a booking awaiting
  // approval AND sufficient time has passed since the last reminder
  // AND we want reminders in the first place
  if (($reminders_enabled) &&
      (strcasecmp($mrbs_username, $create_by) === 0) &&
      ($awaiting_approval) &&
      (working_time_diff(time(), $last_reminded) >= $reminder_interval))
  {
    echo "<tr>\n";
    echo "<td class=\"no_suffix\"></td>\n";
    echo "<td>\n";

    $this_page = this_page();
    $returl = "$this_page?id=$id&area=$area";
    $query_string = "id=$id&series=" . (($series) ? 1 : 0);
    if ($multisite && isset($site) && ($site !== ''))
    {
      $query_string .= "&site=$site";
      $returl .= "&site=$site";
    }

    $params = array('action' => multisite("approve_entry_handler.php?$query_string"),
                    'value'  => get_vocab('remind_admin'),
                    'inputs' => array('action' => 'remind',
                                      'returl' => "$this_page?id=$id&area=$area")
                   );
    generate_button($params);

    echo "</td>\n";
    echo "</tr>\n";
  }
}

function generateTextArea($form_action, $id, $series, $action_type, $returl, $submit_value, $caption, $value='')
{
  echo "<tr><td id=\"caption\" colspan=\"2\">$caption</td></tr>\n";
  echo "<tr>\n";
  echo "<td id=\"note\" class=\"no_suffix\" colspan=\"2\">\n";

  $form = new Form();

  $attributes = array('action' => $form_action,
                      'method' => 'post');

  $form->setAttributes($attributes);

  // Hidden inputs
  $hidden_inputs = array('id'     => $id,
                         'series' => $series,
                         'returl' => $returl,
                         'action' => $action_type);
  $form->addHiddenInputs($hidden_inputs);

  // Visible fields
  $fieldset = new ElementFieldset();
  $fieldset->addLegend('');

  $field = new FieldTextarea();
  $field->setControlAttribute('name', 'note')
        ->setControlText($value);

  $fieldset->addElement($field);

  // The submit button
  $field = new FieldInputSubmit();
  $field->setControlAttribute('value', $submit_value);
  $fieldset->addElement($field);

  $form->addElement($fieldset);

  $form->render();

  echo "</td>\n";
  echo "<tr>\n";
}


// Get non-standard form variables
//
// If $series is TRUE, it means that the $id is the id of an
// entry in the repeat table.  Otherwise it's from the entry table.
$id = get_form_var('id', 'int');
$series = get_form_var('series', 'int');
$action = get_form_var('action', 'string');
$returl = get_form_var('returl', 'string');
$error = get_form_var('error', 'string');
$previous_page = get_form_var('previous_page', 'string');

if (!isset($previous_page) && isset($server['HTTP_REFERER']))
{
  $previous_page = $server['HTTP_REFERER'];
}

// Need to tell all the links where to go back to after an edit or delete
if (!isset($returl))
{
  // We need $_SERVER['HTTP_REFERER'] to contain an actual page, and not be a directory, ie end in '/'
  if (isset($server['HTTP_REFERER']) && (substr($server['HTTP_REFERER'], -1) != '/'))
  {
    $parsed_url = parse_url($server['HTTP_REFERER']);
    $returl = basename($parsed_url['path']);
  }
  // If we haven't got a referer (eg we've come here from an email) then construct
  // a sensible place to go to afterwards
  else
  {
    $returl = 'index.php';
  }

  // Add on the query string
  if (isset($parsed_url) && isset($parsed_url['query']))
  {
    $returl .= '?' . $parsed_url['query'];
  }
  else
  {
    $vars = array('view'  => $default_view,
                  'year'  => $year,
                  'month' => $month,
                  'day'   => $day,
                  'area'  => $area,
                  'room'  => $room);

    $returl .= '?' . http_build_query($vars, '', '&');
  }
}

// Check the CSRF token if we're going to do something
if (isset($action))
{
  Form::checkToken();
}

// Check the user is authorised for this page
checkAuthorised(this_page());

$mrbs_user = session()->getCurrentUser();
$mrbs_username = (isset($mrbs_user)) ? $mrbs_user->username : null;

// You're only allowed to make repeat bookings if you're an admin
// or else if $auth['only_admin_can_book_repeat'] is not set
$repeats_allowed = is_book_admin() || empty($auth['only_admin_can_book_repeat']);

$row = get_booking_info($id, $series);

$room = $row['room_id'];
$area = $row['area_id'];

// Get the area settings for the entry's area.   In particular we want
// to know how to display private/public bookings in this area.
get_area_settings($row['area_id']);

// Work out whether the room or area is disabled
$room_disabled = $row['room_disabled'] || $row['area_disabled'];
// Get the approval status
$awaiting_approval = $row['awaiting_approval'];
// Work out whether this event should be kept private
$private = $row['private'];
// Get the creator
$create_by = $row['create_by'];
$writeable = getWritable($row['create_by'], $row['room_id']);
$keep_private = (is_private_event($private) && !$writeable);

// Work out when the last reminder was sent
$last_reminded = (empty($row['reminded'])) ? $row['last_updated'] : $row['reminded'];


if ($series == 1)
{
  $repeat_id = $id;  // Save the repeat_id
  // I also need to set $id to the value of a single entry as it is a
  // single entry from a series that is used by del_entry.php and
  // edit_entry.php
  // So I will look for the first entry in the series where the entry is
  // as per the original series settings
  $sql = "SELECT id
          FROM " . _tbl('entry') . "
          WHERE repeat_id=? AND entry_type=" . ENTRY_RPT_ORIGINAL . "
          ORDER BY start_time
          LIMIT 1";
  $id = db()->query1($sql, array($id));
  if ($id < 1)
  {
    // if all entries in series have been modified then
    // as a fallback position just select the first entry
    // in the series
    // hopefully this code will never be reached as
    // this page will display the start time of the series
    // but edit_entry.php will display the start time of the entry
    $sql = "SELECT id
            FROM " . _tbl('entry') . "
            WHERE repeat_id=?
            ORDER BY start_time
            LIMIT 1";
    $id = db()->query1($sql, array($id));
  }
  $repeat_info_time = $row['repeat_info_time'];
  $repeat_info_user = $row['repeat_info_user'];
  $repeat_info_text = $row['repeat_info_text'];
}
else
{
  $repeat_id = $row['repeat_id'];

  $entry_info_time = $row['entry_info_time'];
  $entry_info_user = $row['entry_info_user'];
  $entry_info_text = $row['entry_info_text'];
}


// PHASE 2 - EXPORTING ICALENDAR FILES
// -------------------------------------

if (isset($action) && ($action == "export"))
{
  if ($keep_private  || $enable_periods)
  {
    // should never normally be able to get here, but if we have then
    // go somewhere safe.
    location_header('index.php');
  }
  else
  {
    // Construct the SQL query
    $sql_params = array();
    $sql = "SELECT E.*, "
         .  db()->syntax_timestamp_to_unix("E.timestamp") . " AS last_updated, "
         . "A.area_name, R.room_name, "
         . "A.approval_enabled, A.confirmation_enabled";
    if ($series)
    {
      // If it's a series we want the repeat information
      $sql .= ", T.rep_type, T.end_date, T.rep_opt, T.rep_interval, T.month_absolute, T.month_relative";
    }
    $sql .= " FROM " . _tbl('area') . " A, " . _tbl('room') . " R, " . _tbl('entry') . " E";
    if ($series)
    {
      $sql .= ", " . _tbl('repeat') . " T"
            . " WHERE E.repeat_id=?"
            . " AND E.repeat_id=T.id";
      $sql_params[] = $repeat_id;
    }
    else
    {
      $sql .= " WHERE E.id=?";
      $sql_params[] = $id;
    }

    $sql .= " AND E.room_id=R.id
              AND R.area_id=A.id";

    if ($series)
    {
      $sql .= " ORDER BY E.ical_recur_id";
    }
    $res = db()->query($sql, $sql_params);

    // Export the calendar
    require_once "functions_ical.inc";

    $content_type = "application/ics;  charset=" . get_charset(). "; name=\"" . $mail_settings['ics_filename'] . ".ics\"";
    $content_disposition = "attachment; filename=\"" . $mail_settings['ics_filename'] . ".ics\"";
    http_headers(array("Content-Type: $content_type",
                       "Content-Disposition: $content_disposition"));

    export_icalendar($res, $keep_private);
    exit;
  }
}

// PHASE 1 - VIEW THE ENTRY
// ------------------------

$context = array(
    'view'      => $view,
    'view_all'  => $view_all,
    'year'      => $year,
    'month'     => $month,
    'day'       => $day,
    'area'      => $area,
    'room'      => isset($room) ? $room : null
  );

print_header($context);

if (empty($series))
{
  $series = 0;
}
else
{
  $series = 1;
}

// Now that we know all the data we start drawing it

echo "<h3" . (($keep_private && $is_private_field['entry.name']) ? " class=\"private\"" : "") . ">\n";
echo ($keep_private && $is_private_field['entry.name']) ? "[" . get_vocab("unavailable") . "]" : htmlspecialchars($row['name']);
if (is_private_event($private) && $writeable)
{
  echo ' ('.get_vocab("unavailable").')';
}
echo "</h3>\n";

generate_event_registration($row, $previous_page);

echo '<h4>' . get_vocab('event_details') . "</h4>\n";

echo "<table id=\"entry\" class=\"list\">\n";

// Output any error messages
if (!empty($error))
{
  echo "<tr><td>&nbsp;</td><td class=\"error\">" . get_vocab($error) . "</td></tr>\n";
}

echo create_details_body($row, true, $keep_private, $room_disabled);

// If bookings require approval, and the room is enabled, put the buttons
// to do with managing the bookings in the footer
if ($approval_enabled && !$room_disabled && $awaiting_approval)
{
  echo "<tfoot id=\"approve_buttons\">\n";
  // PHASE 2 - REJECT
  if (isset($action) && ($action == "reject"))
  {
    // del_entry expects the id of a member of a series
    // when deleting a series and not the repeat_id
    generateTextArea(multisite('del_entry.php'), $id, $series,
                     "reject", $returl,
                     get_vocab("reject"),
                     get_vocab("reject_reason"));
  }
  // PHASE 2 - MORE INFO
  elseif (isset($action) && ($action == "more_info"))
  {
    // but approve_entry_handler expects the id to be a repeat_id
    // if $series is true (ie behaves like the rest of MRBS).
    // Sometime this difference in behaviour should be rationalised
    // because it is very confusing!
    $target_id = ($series) ? $repeat_id : $id;
    $info_time = ($series) ? $repeat_info_time : $entry_info_time;
    $info_user = ($series) ? $repeat_info_user : $entry_info_user;
    $info_text = ($series) ? $repeat_info_text : $entry_info_text;

    if (empty($info_time))
    {
      $value = '';
    }
    else
    {
      $value = get_vocab("sent_at") . time_date_string($info_time);
      if (!empty($info_user))
      {
        $value .= "\n" . get_vocab("by") . " $info_user";
      }
      $value .= "\n----\n";
      $value .= $info_text;
    }
    generateTextArea(multisite('approve_entry_handler.php'), $target_id, $series,
                     "more_info", $returl,
                     get_vocab("send"),
                     get_vocab("request_more_info"),
                     $value);
  }
  // PHASE 1 - first time through this page
  else
  {
    // Buttons for those who are allowed to approve this booking
    if (is_book_admin($row['room_id']))
    {
      if (!$series)
      {
        generateApproveButtons($id, false);
      }
      if (!empty($repeat_id) || $series)
      {
        generateApproveButtons($repeat_id, true);
      }
    }
    // Buttons for the owner of this booking
    elseif (strcasecmp($mrbs_username, $create_by) === 0)
    {
      generateOwnerButtons($id, $series);
    }
    // Others don't get any buttons
    else
    {
      // But valid HTML requires that there's something inside the <tfoot></tfoot>
      echo "<tr><td></td><td></td></tr>\n";
    }
  }
  echo "</tfoot>\n";
}
echo "</table>\n";

echo "<div id=\"view_entry_nav\">\n";

// Only show the links for Edit and Delete if the room is enabled.    We're
// allowed to view and copy existing bookings in disabled rooms, but not to
// modify or delete them.
if (!$room_disabled)
{
  // Only show the Edit and Delete buttons if the user is allowed to use them
  if (getWritable($create_by, $room))
  {
    // Check whether the entry is deletable (and therefore editable).  Also get the
    // reason.  We only get the first reason because it's easier just to display one
    // reason in a tooltip, rather than a complete list.  [Note: if the entry is
    // deletable but the series is not, the series button will not be disabled.  This
    // is something that needs to be fixed in the future.]
    $violations = mrbsCheckPolicy($row, false, false, true);

    if (empty($violations['errors']))
    {
      $button_attributes = array();
    }
    else
    {
      $button_attributes = array('disabled' => true,
                                 'title'    => $violations['errors'][0]);
    }

    // Edit and Edit Series
    echo "<div>\n";
    if (!$series)
    {
      echo "<div>\n";
      $params = array('action' => multisite('edit_entry.php'),
                      'value'  => get_vocab('editentry'),
                      'inputs' => array('id' => $id,
                      'returl' => $returl)
      );
      generate_button($params, $button_attributes);
      echo "</div>\n";
    }
    if ((!empty($repeat_id) || $series) && $repeats_allowed)
    {
      echo "<div>\n";
      $params = array('action'    => multisite("edit_entry.php?day=$day&month=$month&year=$year"),
                      'value'     => get_vocab('editseries'),
                      'inputs'    => array('id' => $id,
                      'edit_type' => 'series',
                      'returl'    => $returl)
      );

      if (empty($button_attributes['disabled']) &&
          isset($repeat_id) &&
          series_has_registrants($repeat_id))
      {
        $button_attributes['onclick'] = "return confirm('" . escape_js(get_vocab("confirm_edit_series")) . "');";
      }
      generate_button($params, $button_attributes);
      echo "</div>\n";
    }
    echo "</div>\n";

    // Delete and Delete Series
    echo "<div>\n";

    // For the delete buttons, either the button is disabled and we show the reason why, or else
    // we add a click event to confirm the deletion
    if (empty($button_attributes['disabled']))
    {
      $button_attributes['onclick'] = "return confirm('" . escape_js(get_vocab("confirmdel")) . "');";
    }
    else
    {
      unset($button_attributes['onclick']);
    }
    if (!$series)
    {
      echo "<div>\n";
      $params = array('action' => multisite('del_entry.php'),
                      'value'  => get_vocab('deleteentry'),
                      'inputs' => array('id' => $id,
                      'series' => 0,
                      'returl' => $returl)
      );

      generate_button($params, $button_attributes);
      echo "</div>\n";
    }
    if ((!empty($repeat_id) || $series) && $repeats_allowed)
    {
      echo "<div>\n";
      $params = array('action' => multisite("del_entry.php?day=$day&month=$month&year=$year"),
                      'value'  => get_vocab('deleteseries'),
                      'inputs' => array('id' => $id,
                      'series' => 1,
                      'returl' => $returl)
      );

      generate_button($params, $button_attributes);
      echo "</div>\n";
    }
    echo "</div>\n";
  }
}

// Copy and Copy Series
echo "<div>\n";
if (!$series)
{
  echo "<div>\n";
  $params = array('action' => multisite('edit_entry.php'),
                  'value'  => get_vocab('copyentry'),
                  'inputs' => array('id' => $id,
                                    'copy' => 1,
                                    'returl' => $returl)
                 );
  generate_button($params);
  echo "</div>\n";
}
if ((!empty($repeat_id) || $series) && $repeats_allowed)
{
  echo "<div>\n";
  $params = array('action' => multisite("edit_entry.php?day=$day&month=$month&year=$year"),
                  'value'  => get_vocab('copyseries'),
                  'inputs' => array('id' => $id,
                                    'edit_type' => 'series',
                                    'copy' => 1,
                                    'returl' => $returl)
                 );
  generate_button($params);
  echo "</div>\n";
}
echo "</div>\n";

// Export and Export Series
if (!$keep_private && !$enable_periods)
{
  // The iCalendar information has the full booking details in it, so we will not allow
  // it to be exported if it is private and the user is not authorised to see it.
  // iCalendar information doesn't work with periods at the moment (no periods to times mapping)
  echo "<div>\n";
  if (!$series)
  {
    echo "<div>\n";
    $params = array('action' => multisite('view_entry.php'),
                    'value'  => get_vocab('exportentry'),
                    'inputs' => array('id' => $id,
                                      'action' => 'export',
                                      'returl' => $returl)
                   );
    generate_button($params);
    echo "</div>\n";
  }
  if (!empty($repeat_id) || $series)
  {
    echo "<div>\n";
    $params = array('action' => multisite("view_entry.php?day=$day&month=$month&year=$year"),
                    'value'  => get_vocab('exportseries'),
                    'inputs' => array('id' => $repeat_id,
                                      'action' => 'export',
                                      'series' => 1,
                                      'returl' => $returl)
                   );
    generate_button($params);
    echo "</div>\n";
  }
  echo "</div>\n";
}
echo "</div>\n";

if (isset($previous_page)) //remove the link if displayed from an email
{
  echo "<div id=\"returl\">\n";
  echo '<a href="' . htmlspecialchars($previous_page) . '">' . get_vocab('returnprev') . "</a>\n";
  echo "</div>\n";
}


print_footer();
