<?php
// $Id$

// If you want to add some extra columns to the entry and repeat tables to
// record extra details about bookings then you can do so and this page should
// automatically recognise them and handle them.    NOTE: if you add a column to
// the entry table you must add an identical column to the repeat table.
//
// At the moment support is limited to the following column types:
//
// MySQL        PostgreSQL            Form input type
// -----        ----------            ---------------
// bigint       bigint                text
// int          integer               text
// mediumint                          text
// smallint     smallint              checkbox
// tinyint                            checkbox
// text         text                  textarea
// tinytext                           textarea
//              character varying     textarea
// varchar(n)   character varying(n)  text/textarea, depending on the value of n
//              character             text
// char(n)      character(n)          text/textarea, depending on the value of n
//
// NOTE 1: For char(n) and varchar(n) fields, a text input will be presented if
// n is less than or equal to $text_input_max, otherwise a textarea box will be
// presented.
//
// NOTE 2: PostgreSQL booleans are not supported, due to difficulties in
// handling the fields in a database independent way (a PostgreSQL boolean
// will return a PHP boolean type when read by a PHP query, whereas a MySQL
// tinyint returns an int).   In order to have a boolean field in the room
// table you should use a smallint in PostgreSQL or a smallint or a tinyint
// in MySQL.
//
// You can put a description of the column that will be used as the label in
// the form in the $vocab_override variable in the config file using the tag
// 'entry.[columnname]'.   (Note that it is not necessary to add a
// 'repeat.[columnname]' tag.   The entry tag is sufficient.)
//
// For example if you want to add a column recording the number of participants
// you could add a column to the entry and repeat tables called 'participants'
// of type int.  Then in the appropriate lang file(s) you would add the line
//
// $vocab_override['en']['entry.participants'] = "Participants";  // or appropriate translation
//
// If MRBS can't find an entry for the field in the lang file or $vocab_override,
// then it will use the fieldname, eg 'coffee_machine'. 


require "defaultincludes.inc";
require_once "mrbs_sql.inc";

$fields = sql_field_info($tbl_entry);
$custom_fields = array();

// Fill $edit_entry_field_order with not yet specified entries.
$entry_fields = array('name', 'description', 'start_date', 'end_date', 'areas',
                      'rooms', 'type', 'confirmation_status', 'privacy_status');
                      
foreach ($entry_fields as $field)
{
  if (!in_array($field, $edit_entry_field_order))
  {
    $edit_entry_field_order[] = $field;
  }
}

$custom_fields_map = array();
foreach ($fields as $field)
{
  $key = $field['name'];
  if (!in_array($key, $standard_fields['entry']))
  {
    $custom_fields_map[$key] = $field;
    if (!in_array($key, $edit_entry_field_order))
    {
      $edit_entry_field_order[] = $key;
    }
  }
}


// Returns the booking date for a given time.   If the booking day spans midnight and
// $t is in the interval between midnight and the end of the day then the booking date
// is really the day before.
//
// If $is_end is set then this is the end time and so if the booking day happens to
// last exactly 24 hours, when there will be two possible answers, we want the later 
// one.
function getbookingdate($t, $is_end=FALSE)
{
  global $eveningends, $eveningends_minutes, $resolution;
  
  $date = getdate($t);
  
  $t_secs = (($date['hours'] * 60) + $date['minutes']) * 60;
  $e_secs = (((($eveningends * 60) + $eveningends_minutes) * 60) + $resolution) % SECONDS_PER_DAY;

  if (day_past_midnight())
  {
    if (($t_secs < $e_secs) ||
        (($t_secs == $e_secs) && $is_end))
    {
      $date = getdate(mktime($date['hours'], $date['minutes'], $date['seconds'],
                             $date['mon'], $date['mday'] -1, $date['year']));
      $date['hours'] += 24;
    }
  }
  
  return $date;
}


// Generate a time or period selector starting with $first and ending with $last.
// $time is a full Unix timestamp and is the current value.  The selector returns
// the start time in seconds since the beginning of the day for the start of that slot.
// Note that these are nominal seconds and do not take account of any DST changes that
// may have happened earlier in the day.  (It's this way because we don't know what day
// it is as that's controlled by the date selector - and we can't assume that we have
// JavaScript enabled to go and read it)
//
//    $display_none parameter     sets the display style of the <select> to "none"
//    $disabled parameter         disables the input and also generate a hidden input, provided
//                                that $display_none is FALSE.  (This prevents multiple inputs
//                                of the same name)
//    $is_start                   Boolean.  Whether this is the start selector.  Default FALSE
function genSlotSelector($area, $id, $name, $current_s, $display_none=FALSE, $disabled=FALSE, $is_start=FALSE)
{
  global $periods;

  $html = '';
  
  // Check that $resolution is positive to avoid an infinite loop below.
  // (Shouldn't be possible, but just in case ...)
  if (empty($area['resolution']) || ($area['resolution'] < 0))
  {
    fatal_error(FALSE, "Internal error - resolution is NULL or <= 0");
  }
  
  if ($area['enable_periods'])
  {
    $base = 12*SECONDS_PER_HOUR;  // The start of the first period of the day
  }
  else
  {
    $format = hour_min_format();
  }
  
  // Build the attributes
  $attributes = array();
  if ($disabled)
  {
    // If $disabled is set, give the element a class so that the JavaScript
    // knows to keep it disabled
    $attributes[] = 'class="keep_disabled"';
  }
  if ($display_none)
  {
    $attributes[] = 'style="display: none"';
  }
  
  // Build the options
  $options = array();
  // If we're using periods then the last slot is actually the start of the last period,
  // or if we're using times and this is the start selector, then we don't show the last
  // time
  if ($area['enable_periods'] || $is_start)
  {
    $last = $area['last'] - $area['resolution'];
  }
  else
  {
    $last = $area['last'];
  }
  for ($s = $area['first']; $s <= $last; $s += $area['resolution'])
  {
    $slot_string = ($area['enable_periods']) ? $periods[intval(($s-$base)/60)] : hour_min($s);
    $options[$s] = $slot_string;
  }

  // If $display_none or $disabled are set then we'll also disable the select so
  // that there is only one select passing through the variable to the handler
  $params = array('name'          => $name,
                  'id'            => $id,
                  'disabled'      => $disabled || $display_none,
                  'create_hidden' => $disabled && !$display_none,
                  'attributes'    => $attributes,
                  'value'         => $current_s,
                  'options'       => $options,
                  'force_assoc'   => TRUE);

  generate_select($params);
}


// Generate the All Day checkbox for an area
function genAllDay($a, $id, $name, $display_none=FALSE, $disabled=FALSE)
{
  global $default_duration_all_day;
  
  echo "<div class=\"group\"" . (($display_none || !$a['show_all_day']) ? ' style="display: none"' : '') .">\n";
  
  $class = array();
  $class[] = 'all_day';
  if ($disabled)
  {
    // and if $disabled is set, give the element a class so that the JavaScript
    // knows to keep it disabled
    $class[] = 'keep_disabled';
  }
  // (1) If $display_none or $disabled are set then we'll also disable the select so
  //     that there is only one select passing through the variable to the handler.
  // (2) If this is an existing booking that we are editing or copying, then we do
  //     not want the default duration applied
  $params = array('name'        => $name,
                  'id'          => $id,
                  'label'       => get_vocab("all_day"),
                  'label_after' => TRUE,
                  'attributes'  => 'data-show=' . (($a['show_all_day']) ? '1' : '0'),
                  'value'       => ($default_duration_all_day && !isset($id) && !$drag),
                  'disabled'    => $display_none || $disabled,
                  'class'       => $class);
                    
  generate_checkbox($params);
  
  echo "</div>\n";
}


function create_field_entry_name($disabled=FALSE)
{
  global $name, $maxlength, $is_mandatory_field;
  
  echo "<div id=\"div_name\">\n";
  
  // 'mandatory' is there to prevent null input (pattern doesn't seem to be triggered until
  // there is something there).
  $params = array('label'      => get_vocab("namebooker") . ":",
                  'name'       => 'name',
                  'field'      => 'entry.name',
                  'value'      => $name,
                  'type'       => 'text',
                  'pattern'    => REGEX_TEXT_POS,
                  'disabled'   => $disabled,
                  'mandatory'  => TRUE,
                  'maxlength'  => $maxlength['entry.name']);
                  
  generate_input($params);

  echo "</div>\n";
}


function create_field_entry_description($disabled=FALSE)
{
  global $description, $select_options, $datalist_options, $is_mandatory_field;
  
  echo "<div id=\"div_description\">\n";
  
  $params = array('label'       => get_vocab("fulldescription"),
                  'name'        => 'description',
                  'value'       => $description,
                  'disabled'    => $disabled,
                  'mandatory'   => isset($is_mandatory_field['entry.description']) && $is_mandatory_field['entry.description']);
  
  if (isset($select_options['entry.description']) ||
      isset($datalist_options['entry.description']) )
  {
    $params['field'] = 'entry.description';
    generate_input($params);
  }
  else
  {
    $params['attributes'] = array('rows="8"', 'cols="40"');
    generate_textarea($params);
  }
  echo "</div>\n";
}


function create_field_entry_start_date($disabled=FALSE)
{
  global $start_time, $areas, $area_id, $periods, $id, $drag;
  
  $date = getbookingdate($start_time);
  $current_s = (($date['hours'] * 60) + $date['minutes']) * 60;

  echo "<div id=\"div_start_date\">\n";
  echo "<label>" . get_vocab("start") . ":</label>\n";
  echo "<div>\n"; // Needed so that the structure is the same as for the end date to help the JavaScript
  gendateselector("start_", $date['mday'], $date['mon'], $date['year'], '', $disabled);
  echo "</div>\n";

  // Generate the live slot selector and all day checkbox
  genSlotSelector($areas[$area_id], 'start_seconds', 'start_seconds', $current_s, FALSE, $disabled, TRUE);
  genAllDay($areas[$area_id], 'all_day', 'all_day', FALSE, $disabled);
  
  // Generate the templates for each area
  foreach ($areas as $a)
  {
    genSlotSelector($a, 'start_seconds' . $a['id'], 'start_seconds', $current_s, TRUE, TRUE, TRUE);
    genAllDay($a, 'all_day' . $a['id'], 'all_day', TRUE, TRUE);
  }
  echo "</div>\n";
}


function create_field_entry_end_date($disabled=FALSE)
{
  global $end_time, $areas, $area_id, $periods, $multiday_allowed;
  
  $date = getbookingdate($end_time, TRUE);
  $current_s = (($date['hours'] * 60) + $date['minutes']) * 60;
  
  echo "<div id=\"div_end_date\">\n";
  echo "<label>" . get_vocab("end") . ":</label>\n";
  // Don't show the end date selector if multiday is not allowed
  echo "<div" . (($multiday_allowed) ? '' : " style=\"visibility: hidden\"") . ">\n";
  gendateselector("end_", $date['mday'], $date['mon'], $date['year'], '', $disabled);
  echo "</div>\n";
  
  // Generate the live slot selector
  // If we're using periods the booking model is slightly different,
  // so subtract one period because the "end" period is actually the beginning
  // of the last period booked
  $a = $areas[$area_id];
  $this_current_s = ($a['enable_periods']) ? $current_s - $a['resolution'] : $current_s;
  genSlotSelector($areas[$area_id], 'end_seconds', 'end_seconds', $this_current_s, FALSE, $disabled);
 
  // Generate the templates
  foreach ($areas as $a)
  {
    $this_current_s = ($a['enable_periods']) ? $current_s - $a['resolution'] : $current_s;
    genSlotSelector($a, 'end_seconds' . $a['id'], 'end_seconds', $this_current_s, TRUE, TRUE);
  }
  
  echo "<span id=\"end_time_error\" class=\"error\"></span>\n";
  echo "</div>\n";
}


function create_field_entry_areas($disabled=FALSE)
{
  global $areas, $area_id, $rooms;
  
  // if there is more than one area then give the option
  // to choose areas.
  if (count($areas) > 1)
  {
    // We will set the display to none and then turn it on in the JavaScript.  That's
    // because if there's no JavaScript we don't want to display it because we won't
    // have any means of changing the rooms if the area is changed.
    echo "<div id=\"div_areas\" style=\"display: none\">\n";
    $options = array();
    // go through the areas and create the options
    foreach ($areas as $a)
    {
      $options[$a['id']] = $a['area_name'];
    }
    
    $params = array('label'       => get_vocab("area") . ":",
                    'name'        => 'area',
                    'options'     => $options,
                    'force_assoc' => TRUE,
                    'value'       => $area_id,
                    'disabled'    => $disabled);
                      
    generate_select($params);
    echo "</div>\n";
  } // if count($areas)
}


function create_field_entry_rooms($disabled=FALSE)
{
  global $multiroom_allowed, $room_id, $area_id, $selected_rooms, $areas;
  global $tbl_room, $tbl_area;
  
  // $selected_rooms will be populated if we've come from a drag selection
  if (empty($selected_rooms))
  {
    $selected_rooms = array($room_id);
  }
  
  // Get the details of all the enabled rooms
  $all_rooms = array();
  $sql = "SELECT R.id, R.room_name, R.area_id
            FROM $tbl_room R, $tbl_area A
           WHERE R.area_id = A.id
             AND R.disabled=0
             AND A.disabled=0
        ORDER BY R.area_id, R.sort_key";
  $res = sql_query($sql);
  if ($res === FALSE)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(FALSE, get_vocab("fatal_db_error"));
  }
  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    $all_rooms[$row['area_id']][$row['id']] = $row['room_name'];
  }

  echo "<div id=\"div_rooms\">\n";
  echo "<label for=\"rooms\">" . get_vocab("rooms") . ":</label>\n";
  echo "<div class=\"group\">\n";
  
  // First of all generate the rooms for this area
  $params = array('name'        => 'rooms[]',
                  'id'          => 'rooms',
                  'options'     => $all_rooms[$area_id],
                  'force_assoc' => TRUE,
                  'value'       => $selected_rooms,
                  'multiple'    => $multiroom_allowed,
                  'mandatory'   => TRUE,
                  'disabled'    => $disabled,
                  'attributes'  => array('size="5"'));
  generate_select($params);
  
  // Then generate templates for all the rooms
  $params['disabled']      = TRUE;
  $params['create_hidden'] = FALSE;
  foreach ($all_rooms as $a => $rooms)
  {
    $attributes = array();
    $attributes[] = 'style="display: none"';
    // Put in some data about the area for use by the JavaScript
    $attributes[] = 'data-enable_periods='       . (($areas[$a]['enable_periods']) ? 1 : 0);
    $attributes[] = 'data-default_duration='     . ((isset($areas[$a]['default_duration']) && ($areas[$a]['default_duration'] != 0)) ? $areas[$a]['default_duration'] : SECONDS_PER_HOUR);
    $attributes[] = 'data-max_duration_enabled=' . (($areas[$a]['max_duration_enabled']) ? 1 : 0);
    $attributes[] = 'data-max_duration_secs='    . $areas[$a]['max_duration_secs'];
    $attributes[] = 'data-max_duration_periods=' . $areas[$a]['max_duration_periods'];
    $attributes[] = 'data-max_duration_qty='     . $areas[$a]['max_duration_qty'];
    $attributes[] = 'data-max_duration_units="'  . htmlspecialchars($areas[$a]['max_duration_units']) . '"';
    $attributes[] = 'data-timezone="'            . htmlspecialchars($areas[$a]['timezone']) . '"';
    
    $room_ids = array_keys($rooms);
    $params['id']         = 'rooms' . $a;
    $params['options']    = $rooms;
    $params['value']      = $room_ids[0];
    $params['attributes'] = $attributes;
    generate_select($params);
  }
  

  // No point telling them how to select multiple rooms if the input
  // is disabled
  if ($multiroom_allowed && !$disabled)
  {
    echo "<span>" . get_vocab("ctrl_click") . "</span>\n";
  }
  echo "</div>\n";

  echo "</div>\n";
}


function create_field_entry_type($disabled=FALSE)
{
  global $booking_types, $type;
  
  echo "<div id=\"div_type\">\n";
  
  $params = array('label'       => get_vocab("type") . ":",
                  'name'        => 'type',
                  'disabled'    => $disabled,
                  'options'     => array(),
                  'force_assoc' => TRUE,  // in case the type keys happen to be digits
                  'value'       => $type);
                  
  foreach ($booking_types as $key)
  {
    $params['options'][$key] = get_type_vocab($key);
  }
  
  generate_select($params);
  
  echo "</div>\n";
}


function create_field_entry_confirmation_status($disabled=FALSE)
{
  global $confirmation_enabled, $confirmed;
  
  // Confirmation status
  if ($confirmation_enabled)
  {
    echo "<div id=\"div_confirmation_status\">\n";
    
    $buttons[0] = get_vocab("tentative");
    $buttons[1] = get_vocab("confirmed");
    
    $params = array('label'    => get_vocab("confirmation_status") . ":",
                    'name'     => 'confirmed',
                    'value'    => ($confirmed) ? 1 : 0,
                    'options'  => $buttons,
                    'disabled' => $disabled);
                    
    generate_radio_group($params);

    echo "</div>\n";
  }
}


function create_field_entry_privacy_status($disabled=FALSE)
{
  global $private_enabled, $private, $private_mandatory;
  
  // Privacy status
  if ($private_enabled)
  {
    echo "<div id=\"div_privacy_status\">\n";
    
    $buttons[0] = get_vocab("public");
    $buttons[1] = get_vocab("private");
    
    $params = array('label'    => get_vocab("privacy_status") . ":",
                    'name'     => 'private',
                    'value'    => ($private) ? 1 : 0,
                    'options'  => $buttons,
                    'disabled' => $private_mandatory || $disabled);
                    
    generate_radio_group($params);

    echo "</div>\n";
  }
}


function create_field_entry_custom_field($field, $key, $disabled=FALSE)
{
  global $custom_fields, $tbl_entry;
  global $is_mandatory_field, $text_input_max;
  
  echo "<div>\n";
  $params = array('label'     => get_loc_field_name($tbl_entry, $key) . ":",
                  'name'      => VAR_PREFIX . $key,
                  'value'     => $custom_fields[$key],
                  'disabled'  => $disabled,
                  'mandatory' => isset($is_mandatory_field["entry.$key"]) && $is_mandatory_field["entry.$key"]);
  // Output a checkbox if it's a boolean or integer <= 2 bytes (which we will
  // assume are intended to be booleans)
  if (($field['nature'] == 'boolean') || 
    (($field['nature'] == 'integer') && isset($field['length']) && ($field['length'] <= 2)) )
  {
    generate_checkbox($params);
  }
  // Output a textarea if it's a character string longer than the limit for a
  // text input
  elseif (($field['nature'] == 'character') && isset($field['length']) && ($field['length'] > $text_input_max))
  {
    // HTML5 does not allow a pattern attribute for the textarea element
    $params['attributes'] = array('rows="8"', 'cols="40"');
    generate_textarea($params);   
  }
  // Otherwise output an input
  else
  {
    $is_integer_field = ($field['nature'] == 'integer') && ($field['length'] > 2);
    if ($is_integer_field)
    {
      $params['type'] = 'number';
      $params['step'] = '1';
    }
    else
    {
      $params['type'] = 'text';
      if ($params['mandatory'])
      {
        // 'required' is not sufficient for strings, because we also want to make sure
        // that the string contains at least one non-whitespace character
        $params['pattern'] = REGEX_TEXT_POS;
      }
    }
    $params['field'] = "entry.$key";
    generate_input($params);
  }
  echo "</div>\n";
}


// Get non-standard form variables
$hour = get_form_var('hour', 'int');
$minute = get_form_var('minute', 'int');
$period = get_form_var('period', 'int');
$id = get_form_var('id', 'int');
$copy = get_form_var('copy', 'int');
$edit_type = get_form_var('edit_type', 'string', '');
$returl = get_form_var('returl', 'string');
// The following variables are used when coming via a JavaScript drag select
$drag = get_form_var('drag', 'int');
$start_seconds = get_form_var('start_seconds', 'int');
$end_seconds = get_form_var('end_seconds', 'int');
$selected_rooms = get_form_var('rooms', 'array');
$start_date = get_form_var('start_date', 'string');
$end_date = get_form_var('end_date', 'string');


// Check the user is authorised for this page
checkAuthorised();

// Also need to know whether they have admin rights
$user = getUserName();
$is_admin = (authGetUserLevel($user) >= 2);
// You're only allowed to make repeat bookings if you're an admin
// or else if $auth['only_admin_can_book_repeat'] is not set
$repeats_allowed = $is_admin || empty($auth['only_admin_can_book_repeat']);
// Similarly for multi-day
$multiday_allowed = $is_admin || empty($auth['only_admin_can_book_multiday']);
// Similarly for multiple room selection
$multiroom_allowed = $is_admin || empty($auth['only_admin_can_select_multiroom']);



if (isset($start_seconds))
{
  $minutes = intval($start_seconds/60);
  if ($enable_periods)
  {
    $period = $minutes - (12*60);
  }
  else
  {
    $hour = intval($minutes/60);
    $minute = $minutes%60;
  }
}

if (isset($start_date))
{
  list($year, $month, $day) = explode('-', $start_date);
  if (isset($end_date) && ($start_date != $end_date) && $repeats_allowed)
  {
    $rep_type = REP_DAILY;
    list($rep_end_year, $rep_end_month, $rep_end_day) = explode('-', $end_date);
  }
}



// We might be going through edit_entry more than once, for example if we have to log on on the way.  We
// still need to preserve the original calling page so that once we've completed edit_entry_handler we can
// go back to the page we started at (rather than going to the default view).  If this is the first time 
// through, then $HTTP_REFERER holds the original caller.    If this is the second time through we will have 
// stored it in $returl.
if (!isset($returl))
{
  $returl = isset($HTTP_REFERER) ? $HTTP_REFERER : "";
}
    


// This page will either add or modify a booking

// We need to know:
//  Name of booker
//  Description of meeting
//  Date (option select box for day, month, year)
//  Time
//  Duration
//  Internal/External

// Firstly we need to know if this is a new booking or modifying an old one
// and if it's a modification we need to get all the old data from the db.
// If we had $id passed in then it's a modification.

if (isset($id))
{
  $sql = "SELECT *
            FROM $tbl_entry
           WHERE id=$id
           LIMIT 1";
   
  $res = sql_query($sql);
  if (! $res)
  {
    trigger_error(sql_error(), E_USER_WARNING);
    fatal_error(TRUE, get_vocab("fatal_db_error"));
  }
  if (sql_count($res) != 1)
  {
    fatal_error(1, get_vocab("entryid") . $id . get_vocab("not_found"));
  }

  $row = sql_row_keyed($res, 0);
  sql_free($res);
  
  // We've possibly got a new room and area, so we need to update the settings
  // for this area.
  $area = get_area($row['room_id']);
  get_area_settings($area);
  
  $private = $row['status'] & STATUS_PRIVATE;
  if ($private_mandatory) 
  {
    $private = $private_default;
  }
  // Need to clear some data if entry is private and user
  // does not have permission to edit/view details
  if (isset($copy) && ($user != $row['create_by'])) 
  {
    // Entry being copied by different user
    // If they don't have rights to view details, clear them
    $privatewriteable = getWritable($row['create_by'], $user, $row['room_id']);
    $keep_private = (is_private_event($private) && !$privatewriteable);
  }
  else
  {
    $keep_private = FALSE;
  }
  
  // default settings
  $rep_day = array();
  $rep_type = REP_NONE;
  $rep_num_weeks = 1;
  
  foreach ($row as $column => $value)
  {
    switch ($column)
    {
      // Don't bother with these columns
      case 'id':
      case 'timestamp':
      case 'reminded':
      case 'info_time':
      case 'info_user':
      case 'info_text':
        break;
      
      // These columns cannot be made private  
      case 'room_id':
        // We need to preserve the original room_id for existing bookings and pass
        // it through to edit_entry_handler.    We need this because we need to know
        // in edit_entry_handler which room contains the original booking.   It's
        // possible in this form to select multiple rooms, or even change the room.
        // We will need to know which booking is the "original booking" because the 
        // original booking will keep the same ical_uid and have the ical_sequence
        // incremented, whereas new bookings will have a new ical_uid and start with 
        // an ical_sequence of 0.    (If there is more than one room when we get to
        // edit_entry_handler and the original room isn't among them, then we will 
        // just have to make an arbitrary choice as to which is the room containing
        // the original booking.)
        // NOTE:  We do not set the original_room_id if we are copying an entry,
        // because when we are copying we are effectively making a new entry and
        // so we want edit_entry_handler to assign a new UID, etc.
        if (!$copy)
        {
          $original_room_id = $row['room_id'];
        }
      case 'ical_uid':
      case 'ical_sequence':
      case 'ical_recur_id':
      case 'entry_type':
        $$column = $row[$column];
        break;
      
      // These columns can be made private [not sure about 'type' though - haven't
      // checked whether it makes sense/works to make the 'type' column private]
      case 'name':
      case 'description':
      case 'type':
        $$column = ($keep_private && isset($is_private_field["entry.$column"]) && $is_private_field["entry.$column"]) ? '' : $row[$column];
        break;
        
      case 'status':
        // No need to do the privacy status as we've already done that.
        // Just do the confirmation status
        $confirmed = !($row['status'] & STATUS_TENTATIVE);
        break;
      
      case 'repeat_id':
        $rep_id      = $row['repeat_id'];
        break;
        
      case 'create_by':
        // If we're copying an existing entry then we need to change the create_by (they could be
        // different if it's an admin doing the copying)
        $create_by   = (isset($copy)) ? $user : $row['create_by'];
        break;
        
      case 'start_time':
        $start_time = $row['start_time'];
        break;
        
      case 'end_time':
        $end_time = $row['end_time'];
        $duration = $row['end_time'] - $row['start_time'] - cross_dst($row['start_time'], $row['end_time']);
        break;
        
      default:
        $custom_fields[$column] = ($keep_private && isset($is_private_field["entry.$column"]) && $is_private_field["entry.$column"]) ? '' : $row[$column];
        break;
    }
  }
  

  if(($entry_type == ENTRY_RPT_ORIGINAL) || ($entry_type == ENTRY_RPT_CHANGED))
  {
    $sql = "SELECT rep_type, start_time, end_time, end_date, rep_opt, rep_num_weeks,
                   month_absolute, month_relative
              FROM $tbl_repeat 
             WHERE id=$rep_id
             LIMIT 1";
   
    $res = sql_query($sql);
    if (! $res)
    {
      trigger_error(sql_error(), E_USER_WARNING);
      fatal_error(TRUE, get_vocab("fatal_db_error"));
    }
    if (sql_count($res) != 1)
    {
      fatal_error(1,
                  get_vocab("repeat_id") . $rep_id . get_vocab("not_found"));
    }

    $row = sql_row_keyed($res, 0);
    sql_free($res);
   
    $rep_type = $row['rep_type'];

    if (!isset($rep_type))
    {
      $rep_type == REP_NONE;
    }
    
    // If it's a repeating entry get the repeat details
    if ($rep_type != REP_NONE)
    {
      // If we're editing the series we want the start_time and end_time to be the
      // start and of the first entry of the series, not the start of this entry
      if ($edit_type == "series")
      {
        $start_time = $row['start_time'];
        $end_time = $row['end_time'];
      }
      
      $rep_end_day   = (int)strftime('%d', $row['end_date']);
      $rep_end_month = (int)strftime('%m', $row['end_date']);
      $rep_end_year  = (int)strftime('%Y', $row['end_date']);
      // Get the end date in string format as well, for use when
      // the input is disabled
      $rep_end_date = utf8_strftime('%A %d %B %Y',$row['end_date']);
      
      switch ($rep_type)
      {
        case REP_WEEKLY:
          for ($i=0; $i<7; $i++)
          {
            if ($row['rep_opt'][$i])
            {
              $rep_day[] = $i;
            }
          }
          $rep_num_weeks = $row['rep_num_weeks'];
          break;
        case REP_MONTHLY:
          if (isset($row['month_absolute']))
          {
            $month_type = REP_MONTH_ABSOLUTE;
            $month_absolute = $row['month_absolute'];
          }
          elseif (isset($row['month_relative']))
          {
            $month_type = REP_MONTH_RELATIVE;
            $month_relative = $row['month_relative'];
          }
          else
          {
            trigger_error("Invalid monthly repeat", E_USER_WARNING);
          }
          break;
        default:
          break;
      }
    }
  }
}
else
{
  // It is a new booking. The data comes from whichever button the user clicked
  $edit_type     = "series";
  $name          = "";
  $create_by     = $user;
  $description   = $default_description;
  $type          = $default_type;
  $room_id       = $room;
  $private       = $private_default;
  $confirmed     = $confirmed_default;

  // now initialise the custom fields
  foreach ($fields as $field)
  {
    if (!in_array($field['name'], $standard_fields['entry']))
    {
      $custom_fields[$field['name']] = '';
    }
  }

  // Get the hour and minute, converting a period to its MRBS time
  // Set some sensible defaults
  if ($enable_periods)
  {
    if (isset($period))
    {
      $hour = 12 + intval($period/60);
      $minute = $period % 60;
    }
    else
    {
      $hour = 0;
      $minute = 0;
    }
  }
  else
  {
    if (!isset($hour) || !isset($minute))
    {
      $hour = $morningstarts;
      $minute = $morningstarts_minutes;
    }
  }

  $start_time = mktime($hour, $minute, 0, $month, $day, $year);

  if (isset($end_seconds))
  {
    $end_minutes = intval($end_seconds/60);
    $end_hour = intval($end_minutes/60);
    $end_minute = $end_minutes%60;
    $end_time = mktime($end_hour, $end_minute, 0, $month, $day, $year);
    $duration = $end_time - $start_time - cross_dst($start_time, $end_time);
  }
  else
  {
    if (!isset($default_duration))
    {
      $default_duration = SECONDS_PER_HOUR;
    }
    $duration    = ($enable_periods ? 60 : $default_duration);
    $end_time = $start_time + $duration;
    // The end time can't be past the end of the booking day
    $pm7 = get_start_last_slot($month, $day, $year);
    $end_time = min($end_time, $pm7 + $resolution);
  }
  
  $rep_id        = 0;
  if (!isset($rep_type))  // We might have set it through a drag selection
  {
    $rep_type      = REP_NONE;
    $rep_end_day   = $day;
    $rep_end_month = $month;
    $rep_end_year  = $year;
  }
  $rep_day       = array(date('w', $start_time));
  $rep_num_weeks = 1;
  $month_type = REP_MONTH_ABSOLUTE;
}

if (!isset($month_relative))
{
  $month_relative = date_byday($start_time);
}
if (!isset($month_absolute))
{
  $month_absolute = date('j', $start_time);
}
list($month_relative_ord, $month_relative_day) = byday_split($month_relative);

$start_hour  = strftime('%H', $start_time);
$start_min   = strftime('%M', $start_time);

// These next 4 if statements handle the situation where
// this page has been accessed directly and no arguments have
// been passed to it.
// If we have not been provided with a room_id
if (empty( $room_id ) )
{
  $sql = "SELECT id FROM $tbl_room WHERE disabled=0 LIMIT 1";
  $res = sql_query($sql);
  $row = sql_row_keyed($res, 0);
  $room_id = $row['id'];
}

// Determine the area id of the room in question first
$area_id = mrbsGetRoomArea($room_id);


// Remove "Undefined variable" notice
if (!isset($rep_num_weeks))
{
  $rep_num_weeks = "";
}

$enable_periods ? toPeriodString($start_min, $duration, $dur_units) : toTimeString($duration, $dur_units);

//now that we know all the data to fill the form with we start drawing it

if (!getWritable($create_by, $user, $room_id))
{
  showAccessDenied($day, $month, $year, $area, isset($room) ? $room : "");
  exit;
}

print_header($day, $month, $year, $area, isset($room) ? $room : "");

// Get the details of all the enabled rooms
$rooms = array();
$sql = "SELECT R.id, R.room_name, R.area_id
          FROM $tbl_room R, $tbl_area A
         WHERE R.area_id = A.id
           AND R.disabled=0
           AND A.disabled=0
      ORDER BY R.area_id, R.sort_key";
$res = sql_query($sql);
if ($res)
{
  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    $rooms[$row['id']] = $row;
  }
}
    
// Get the details of all the enabled areas
$areas = array();
$sql = "SELECT id, area_name, resolution, default_duration, enable_periods, timezone,
               morningstarts, morningstarts_minutes, eveningends , eveningends_minutes
          FROM $tbl_area
         WHERE disabled=0
      ORDER BY area_name";
$res = sql_query($sql);
if ($res)
{
  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    // Make sure we've got the correct resolution when using periods (it's
    // probably OK anyway, but just in case)
    if ($row['enable_periods'])
    {
      $row['resolution'] = 60;
    }
    // The following config settings aren't yet per-area, but we'll treat them as if
    // they are to make it easier to change them to per-area settings in the future.
    $row['max_duration_enabled'] = $max_duration_enabled;
    $row['max_duration_secs']    = $max_duration_secs;
    $row['max_duration_periods'] = $max_duration_periods;
    // Generate some derived settings
    $row['max_duration_qty']     = $row['max_duration_secs'];
    toTimeString($row['max_duration_qty'], $row['max_duration_units']);
    // Get the start and end of the booking day
    if ($row['enable_periods'])
    {
      $first = 12*SECONDS_PER_HOUR;
      // If we're using periods we just go to the end of the last slot
      $last = $first + (count($periods) * $row['resolution']);
    }
    else
    {
      $first = (($row['morningstarts'] * 60) + $row['morningstarts_minutes']) * 60;
      $last = ((($row['eveningends'] * 60) + $row['eveningends_minutes']) * 60) + $row['resolution'];
      // If the end of the day is the same as or before the start time, then it's really on the next day
      if ($first >= $last)
      {
        $last += SECONDS_PER_DAY;
      }
    }
    $row['first'] = $first;
    $row['last'] = $last;
    // We don't show the all day checkbox if it's going to result in bookings that
    // contravene the policy - ie if max_duration is enabled and an all day booking
    // would be longer than the maximum duration allowed.
    $row['show_all_day'] = $is_admin || 
                           !$row['max_duration_enabled'] ||
                           ( ($row['enable_periods'] && ($row['max_duration_periods'] >= count($periods))) ||
                             (!$row['enable_periods'] && ($row['max_duration_secs'] >= ($last - $first))) );
    
    // Clean up the settings, getting rid of any nulls and casting boolean fields into bools
    $row = clean_area_row($row);
    
    // Now assign the row to the area      
    $areas[$row['id']] = $row;
  }
}


if (isset($id) && !isset($copy))
{
  if ($edit_type == "series")
  {
    $token = "editseries";
  }
  else
  {
    $token = "editentry";
  }
}
else
{
  if (isset($copy))
  {
    if ($edit_type == "series")
    {
      $token = "copyseries";
    }
    else
    {
      $token = "copyentry";
    }
  }
  else
  {
    $token = "addentry";
  }
}
?>


<form class="form_general" id="main" action="edit_entry_handler.php" method="post">
  <fieldset>
  <legend><?php echo get_vocab($token); ?></legend>

<?php

foreach ($edit_entry_field_order as $key)
{
  switch( $key )
  {
  case 'name':
    create_field_entry_name();
    break;

  case 'description':
    create_field_entry_description();
    break;

  case 'start_date':
    create_field_entry_start_date();
    break;

  case 'end_date':
    create_field_entry_end_date();
    break;

  case 'areas':
    create_field_entry_areas();
    break;

  case 'rooms':
    create_field_entry_rooms();
    break;

  case 'type':
    create_field_entry_type();
    break;

  case 'confirmation_status':
    create_field_entry_confirmation_status();
    break;

  case 'privacy_status':
    create_field_entry_privacy_status();
    break;

  default:
    create_field_entry_custom_field($custom_fields_map[$key], $key);
    break;
  }
}


// Show the repeat fields if (a) it's a new booking and repeats are allowed,
// or else if it's an existing booking and it's a series.  (It's not particularly obvious but
// if edit_type is "series" then it means that either you're editing an existing
// series or else you're making a new booking.  This should be tidied up sometime!)
if (($edit_type == "series") && $repeats_allowed)
{
  // If repeats aren't allowed or this is not a series then disable
  // the repeat fields - they're for information only
  // (NOTE: when repeat bookings are restricted to admins, an ordinary user
  // would not normally be able to get to the stage of trying to edit a series.
  // But we have to cater for the possibility because it could happen if (a) the
  // series was created before the policy was introduced or (b) the user has
  // been demoted since the series was created).
  $disabled = ($edit_type != "series") || !$repeats_allowed;
  
  echo "<fieldset id=\"rep_info\">\n";
  echo "<legend></legend>\n";
      
  // Repeat type
  echo "<div id=\"rep_type\">\n";
  $params = array('label'         => get_vocab("rep_type") . ":",
                  'name'          => 'rep_type',
                  'value'         => $rep_type,
                  'disabled'      => $disabled,
                  'options'       => array());
  foreach (array(REP_NONE, REP_DAILY, REP_WEEKLY, REP_MONTHLY, REP_YEARLY) as $i)
  {
    $params['options'][$i] = get_vocab("rep_type_$i");
  }
  generate_radio_group($params);
  echo "</div>\n";
  
  // No point in showing anything more if the repeat fields are disabled
  // and the repeat type is None
  if (!$disabled || ($rep_type != REP_NONE))
  {
    // And no point in showing the weekly repeat details if the repeat
    // fields are disabled and the repeat type is not a weekly repeat
    if (!$disabled || ($rep_type == REP_WEEKLY))
    {
      echo "<fieldset class= \"rep_type_details js_none\" id=\"rep_weekly\">\n";
      echo "<legend></legend>\n";
      // Repeat day
      echo "<div id=\"rep_day\">\n";
      $params = array('label'    => get_vocab("rep_rep_day") . ":",
                      'name'     => 'rep_day[]',
                      'value'    => $rep_day,
                      'disabled' => $disabled,
                      'options'  => array());
      for ($i = 0; $i < 7; $i++)
      {
        // Display day name checkboxes according to language and preferred weekday start.
        $wday = ($i + $weekstarts) % 7;
        // We need to ensure the index is a string to force the array to be associative
        $params['options'][$wday] = day_name($wday, $strftime_format['dayname_edit']);
      }
      $params['force_assoc'] = TRUE;
      generate_checkbox_group($params);
      echo "</div>\n";

      // Repeat frequency
      echo "<div>\n";
      $params = array('label'      => get_vocab("rep_num_weeks") . ":",
                      'name'       => 'rep_num_weeks',
                      'type'       => 'number',
                      'step'       => '1',
                      'min'        => REP_NUM_WEEKS_MIN,
                      'value'      => $rep_num_weeks,
                      'suffix'     => get_vocab("weeks"),
                      'disabled'   => $disabled);
      generate_input($params);
    
      echo "</div>\n";
      echo "</fieldset>\n";
    }
    
    // And no point in showing the monthly repeat details if the repeat
    // fields are disabled and the repeat type is not a monthly repeat
    if (!$disabled || ($rep_type == REP_MONTHLY))
    {
      echo "<fieldset class= \"rep_type_details js_none\" id=\"rep_monthly\">\n";
      echo "<legend></legend>\n";
      
      // MONTH ABSOLUTE (eg Day 15 of every month)
      echo "<fieldset>\n";
      echo "<legend></legend>\n";
      $params = array('name'     => 'month_type',
                      'options'  => array(REP_MONTH_ABSOLUTE => get_vocab("month_absolute")),
                      'value'    => $month_type,
                      'disabled' => $disabled);
      generate_radio($params);
      
      // We could in the future allow -1 to -31, meaning "the nth last day of
      // the month", but for the moment we'll keep it simple
      $options = array();
      for ($i=1; $i<=31; $i++)
      {
        $options[] = $i;
      }
      $params = array('name'       => 'month_absolute',
                      'value'      => $month_absolute,
                      'options'    => $options,
                      'disabled'   => $disabled);
      generate_select($params);
      echo "</fieldset>\n";
      
      // MONTH RELATIVE (eg the second Thursday of every month)
      echo "<fieldset>\n";
      echo "<legend></legend>\n";
      $params = array('name'     => 'month_type',
                      'options'  => array(REP_MONTH_RELATIVE => get_vocab("month_relative")),
                      'value'    => $month_type,
                      'disabled' => $disabled);
      generate_radio($params);
      
      // Note: the select box order does not internationalise very well and could
      // do with revisiting.   It assumes all languages have the same order as English
      // eg "the second Wednesday" which is probably not true.
      $options = array();
      foreach (array('1', '2', '3', '4', '5', '-1', '-2', '-3', '-4', '-5') as $i)
      {
        $options[$i] = get_vocab("ord_" . $i);
      }
      $params = array('name'        => 'month_relative_ord',
                      'value'       => $month_relative_ord,
                      'disabled'    => $disabled,
                      'options'     => $options,
                      'force_assoc' => TRUE);
      generate_select($params);
      
      $options = array();
      for ($i=0; $i<7; $i++)
      {
        $i_offset = ($i + $weekstarts)%7;
        $options[$RFC_5545_days[$i_offset]] = day_name($i_offset);
      }
      $params = array('name'     => 'month_relative_day',
                      'value'    => $month_relative_day,
                      'disabled' => $disabled,
                      'options'  => $options);
      generate_select($params);
      echo "</fieldset>\n";
      
      echo "</fieldset>\n";
    }
    
    // Repeat end date
    echo "<div id=\"rep_end_date\">\n";
    echo "<label>" . get_vocab("rep_end_date") . ":</label>\n";
    genDateSelector("rep_end_", $rep_end_day, $rep_end_month, $rep_end_year, '', $disabled);
    echo "</div>\n";
    
    // Checkbox for skipping past conflicts
    if (!$disabled)
    {
      echo "<div>\n";
      $params = array('label' => get_vocab("skip_conflicts") . ":",
                      'name' => 'skip',
                      'value' => !empty($skip_default));
      generate_checkbox($params);
      echo "</div>\n";
    }
  }

  echo "</fieldset>\n";
}
    
    ?>
    <input type="hidden" name="returl" value="<?php echo htmlspecialchars($returl) ?>">
    <input type="hidden" name="create_by" value="<?php echo htmlspecialchars($create_by)?>">
    <input type="hidden" name="rep_id" value="<?php echo $rep_id?>">
    <input type="hidden" name="edit_type" value="<?php echo $edit_type?>">
    <?php
    // The original_room_id will only be set if this was an existing booking.
    // If it is an existing booking then edit_entry_handler needs to know the
    // original room id and the ical_uid and the ical_sequence, because it will
    // have to keep the ical_uid and increment the ical_sequence for the room that
    // contained the original booking.  If it's a new booking it will generate a new
    // ical_uid and start the ical_sequence at 0.
    if (isset($original_room_id))
    {
      echo "<input type=\"hidden\" name=\"original_room_id\" ".
        "value=\"$original_room_id\">\n";
      echo "<input type=\"hidden\" name=\"ical_uid\" value=\"".
        htmlspecialchars($ical_uid)."\">\n";
      echo "<input type=\"hidden\" name=\"ical_sequence\" value=\"".
        htmlspecialchars($ical_sequence)."\">\n";
      echo "<input type=\"hidden\" name=\"ical_recur_id\" value=\"".
        htmlspecialchars($ical_recur_id)."\">\n";
    }
    if(isset($id) && !isset($copy))
    {
      echo "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
    }

    // Buttons
    echo "<fieldset class=\"submit_buttons\">\n";
    echo "<legend></legend>\n";
    // The Back button
    echo "<div id=\"edit_entry_submit_back\">\n";
    echo "<input class=\"submit\" type=\"submit\" name=\"back_button\" value=\"" . get_vocab("back") . "\" formnovalidate>\n";
    echo "</div>\n";
    
    // The Submit button
    echo "<div id=\"edit_entry_submit_save\">\n";
    echo "<input class=\"submit default_action\" type=\"submit\" name=\"save_button\" value=\"" .
      get_vocab("save") . "\">\n";
    echo "</div>\n";
    
    // divs to hold the results of the Ajax checking of the booking
    echo "<div id=\"conflict_check\">\n";
    echo "</div>\n";
    
    echo "<div id=\"policy_check\">\n";
    echo "</div>\n";
    
    echo "</fieldset>";
    
    // and a div to hold the dialog box which gives more details.    The dialog
    // box contains a set of tabs.   And because we want the tabs to act as the
    // dialog box we add an extra tab where we're going to put the dialog close
    // button and then we hide the dialog itself
    echo "<div id=\"check_results\" style=\"display: none\">\n";
    echo "<div id=\"check_tabs\">\n";
    echo "<ul id=\"details_tabs\">\n";
    echo "<li><a href=\"#schedule_details\">" . get_vocab("schedule") . "</a></li>\n";
    echo "<li><a href=\"#policy_details\">" . get_vocab("policy") . "</a></li>\n";
    echo "<li id=\"ui-tab-dialog-close\"></li>\n";
    echo "</ul>\n";
    echo "<div id=\"schedule_details\"></div>\n";
    echo "<div id=\"policy_details\"></div>\n";
    echo "</div>\n";
    echo "</div>\n";
    ?>
  </fieldset>
</form>

<?php output_trailer() ?>
