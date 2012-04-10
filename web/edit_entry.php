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


require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";

// Generate a time or period selector starting with $first and ending with $last.
// $time is a full Unix timestamp and is the current value.  The selector returns
// the start time in seconds since the beginning of the day for the start of that slot.
// Note that these are nominal seconds and do not take account of any DST changes that
// may have happened earlier in the day.  (It's this way because we don't know what day
// it is as that's controlled by the date selector - and we can't assume that we have
// JavaScript enabled to go and read it)
//
// The $display_none parameter sets the display style of the <select> to "none"
// The $disabled parameter will disable the input and also generate a hidden input, provided
// that $display_none is FALSE.  (This prevents multiple inputs of the same name)
function genSlotSelector($area, $prefix, $first, $last, $time, $display_none=FALSE, $disabled=FALSE)
{
  global $periods;
  
  $html = '';
  // Get the settings for this area.   Note that the variables below are
  // local variables, not globals.
  $enable_periods = $area['enable_periods'];
  $resolution = ($enable_periods) ? 60 : $area['resolution'];
  // Check that $resolution is positive to avoid an infinite loop below.
  // (Shouldn't be possible, but just in case ...)
  if (empty($resolution) || ($resolution < 0))
  {
    fatal_error(FALSE, "Internal error - resolution is NULL or <= 0");
  }
  
  // Get the current hour and minute and convert it into nominal (ie ignoring any
  // DST effects) seconds since the start of the day
  $date = getdate($time);
  $current_t = (($date['hours'] * 60) + $date['minutes']) * 60;
  
  if ($enable_periods)
  {
    $base = 12*60*60;  // The start of the first period of the day
  }
  else
  {
    $format = hour_min_format();
  }
  $html .= "<select" .
           (($display_none) ? " style=\"display: none\"" : "") .
           // If $display_none or $disabled are set then we'll also disable the select so
           // that there is only one select passing through the variable to the handler
           (($display_none || $disabled) ? " disabled=\"disabled\"" : "") .
           // and if $disabled is set, give the element a class so that the JavaScript
           // knows to keep it disabled
           (($disabled) ? " class=\"keep_disabled\"" : "") .
           " id=\"${prefix}seconds${area['id']}\" name=\"${prefix}seconds\" onChange=\"adjustSlotSelectors(this.form)\">\n";
  for ($t = $first; $t <= $last; $t = $t + $resolution)
  {
    // The date used below is completely arbitrary.   All that matters is that it
    // is a day that does not contain a DST boundary.   (We need a real date so that
    // we can use strftime to get an hour and minute formatted according to the locale)
    $timestamp = $t + mktime(0, 0, 0, 1, 1, 2000);
    $slot_string = ($enable_periods) ? $periods[intval(($t-$base)/60)] : utf8_strftime($format, $timestamp);
    $html .= "<option value=\"$t\"";
    $html .= ($t == $current_t) ? " selected=\"selected\"" : "";
    $html .= ">$slot_string</option>\n";
  }
  $html .= "</select>\n";
  // Add in a hidden input if the select is disabled but displayed
  if ($disabled && !$display_none)
  {
    $html .= "<input type=\"hidden\" name=\"${prefix}seconds\" value=\"$current_t\">\n";
  }
  
  echo $html;
}


function create_field_entry_name($disabled=FALSE)
{
  global $name, $select_options, $maxlength, $is_mandatory_field;
  
  echo "<div id=\"div_name\">\n";
  $label_text = get_vocab("namebooker") . ":";
  if (!empty($select_options['entry.name']))
  {
    generate_select($label_text, 'name', $name, $select_options['entry.name'],
                    $is_mandatory_field['entry.name'], $disabled);
  }
  else
  {
    generate_input($label_text, 'name', $name, $disabled, $maxlength['entry.name']);
  }
  echo "</div>\n";
}


function create_field_entry_description($disabled=FALSE)
{
  global $description, $select_options, $is_mandatory_field;
  
  echo "<div id=\"div_description\">\n";
  $label_text = get_vocab("fulldescription");
  if (!empty($select_options['entry.description']))
  {
    generate_select($label_text, 'description', $description, $select_options['entry.description'],
                    $is_mandatory_field['entry.description'], $disabled);
  }
  else
  {
    generate_textarea($label_text, 'description', $description, $disabled);
  }
  echo "</div>\n";
}


function create_field_entry_start_date($disabled=FALSE)
{
  global $start_time, $areas, $area_id, $periods, $default_duration_all_day, $id, $drag;
  global $periods, $is_admin;
  
  echo "<div id=\"div_start_date\">\n";
  echo "<label>" . get_vocab("start") . ":</label>\n";
  $date = getdate($start_time);
  gendateselector("start_", $date['mday'], $date['mon'], $date['year'], '', $disabled);
  // If we're using periods the booking model is slightly different:
  // you're allowed to specify the last period as your first period.
  // This is why we don't substract the resolution

  foreach ($areas as $a)
  {
    if ($a['enable_periods'])
    {
      $a['resolution'] = 60;
      $first = 12*60*60;
      // If we're using periods we just go to the beginning of the last slot
      $last = $first + ((count($periods) - 1) * $a['resolution']);
    }
    else
    {
      $first = (($a['morningstarts'] * 60) + $a['morningstarts_minutes']) * 60;
      $last = (($a['eveningends'] * 60) + $a['eveningends_minutes']) * 60;
      $last = $last + $a['resolution'];
    }
    $start_last = ($a['enable_periods']) ? $last : $last - $a['resolution'];
    $display_none = ($a['id'] != $area_id);
    genSlotSelector($a, "start_", $first, $start_last, $start_time, $display_none, $disabled);
    
    echo "<div class=\"group\">\n";
    echo "<div id=\"ad{$a['id']}\"".($display_none ? " style=\"display: none\" " : "") .">\n";
    // We don't show the all day checkbox if it's going to result in bookings that
    // contravene the policy - ie if max_duration is enabled and an all day booking
    // would be longer than the maximum duration allowed
    $show_all_day = $is_admin || !$a['max_duration_enabled'] ||
                    ( ($a['enable_periods'] && ($a['max_duration_periods'] >= count($periods))) ||
                        (!$a['enable_periods'] && ($a['max_duration_secs'] >= ($last - $first))) );
    echo "<input id=\"all_day{$a['id']}\" class=\"all_day checkbox\"" .
         // If this is an existing booking that we are editing or copying, then we do
         // not want the default duration applied
         (($default_duration_all_day && !isset($id) && !$drag) ? " checked=\"checked\"" : "") .
         " name=\"all_day\" type=\"checkbox\" value=\"yes\" onclick=\"OnAllDayClick(this)\"".
         ($show_all_day? "" : " style=\"display: none;\" ").
         // If $display_none or $disabled are set then we'll also disable the select so
         // that there is only one select passing through the variable to the handler
         (($display_none || $disabled) ? " disabled=\"disabled\"" : "") .
         // and if $disabled is set, give the element a class so that the JavaScript
         // knows to keep it disabled
         (($disabled) ? " class=\"keep_disabled\"" : "") .
         ">\n";
    if($show_all_day)
    {
      echo "<label for=\"all_day{$a['id']}\">" . get_vocab("all_day") . "</label>\n";
    }
    echo "</div>\n";
    echo "</div>\n";
  }
  echo "</div>\n";
}


function create_field_entry_end_date($disabled=FALSE)
{
  global $end_time, $areas, $area_id, $periods, $multiday_allowed;
  
  echo "<div id=\"div_end_date\">\n";
  echo "<label>" . get_vocab("end") . ":</label>\n";
  $date = getdate($end_time);
  // Don't show the end date selector if multiday is not allowed
  echo "<div" . (($multiday_allowed) ? '' : " style=\"visibility: hidden\"") . ">\n";
  gendateselector("end_", $date['mday'], $date['mon'], $date['year'], '', $disabled);
  echo "</div>\n";
  // If we're using periods the booking model is slightly different,
  // so subtract one period because the "end" period is actually the beginning
  // of the last period booked
  foreach ($areas as $a)
  {
    if ($a['enable_periods'])
    {
      $a['resolution'] = 60;
      $first = 12*60*60;
      // If we're using periods we just go to the beginning of the last slot
      $last = $first + ((count($periods) - 1) * $a['resolution']);
    }
    else
    {
      $first = (($a['morningstarts'] * 60) + $a['morningstarts_minutes']) * 60;
      $last = (($a['eveningends'] * 60) + $a['eveningends_minutes']) * 60;
      $last = $last + $a['resolution'];
    }
    $end_value = ($a['enable_periods']) ? $end_time - $a['resolution'] : $end_time;
    $display_none = ($a['id'] != $area_id);
    genSlotSelector($a, "end_", $first, $last, $end_value, $display_none, $disabled);
  }
  echo "<span id=\"end_time_error\" class=\"error\"></span>\n";
  echo "</div>\n";
}


function create_field_entry_areas($disabled=FALSE)
{
  global $areas, $area_id, $rooms;
  
  echo "<div id=\"div_areas\">\n";
  echo "</div>\n";
  // if there is more than one area then give the option
  // to choose areas.
  if (count($areas) > 1)
  { 
    ?> 
      <script type="text/javascript">
      //<![CDATA[
      
      var area = <?php echo $area_id ?>;
      
      function changeRooms( formObj )
      {
        areasObj = eval( "formObj.area" );

        area = areasObj[areasObj.selectedIndex].value;
        roomsObj = eval( "formObj.elements['rooms']" );

        // remove all entries
        roomsNum = roomsObj.length;
        for (i=(roomsNum-1); i >= 0; i--)
        {
          roomsObj.options[i] = null;
        }
        // add entries based on area selected
        switch (area){
          <?php
          foreach ($areas as $a)
          {
            print "case \"" . $a['id'] . "\":\n";
            // get rooms for this area
            $i = 0;
            foreach ($rooms as $r)
            {
              if ($r['area_id'] == $a['id'])
              {
                print "roomsObj.options[$i] = new Option(\"" . escape_js($r['room_name']) . "\"," . $r['id'] . ");\n";
                $i++;
              }
            }
            // select the first entry by default to ensure
            // that one room is selected to begin with
            if ($i > 0)  // but only do this if there is a room
            {
              print "roomsObj.options[0].selected = true;\n";
            }
            print "break;\n";
          }
          ?>
        } //switch
        
        <?php 
        // Replace the start and end selectors with those for the new area
        // (1) We set the display for the old elements to "none" and the new
        // elements to "block".   (2) We also need to disable the old selectors and
        // enable the new ones: they all have the same name, so we only want
        // one passed through with the form.  (3) We take a note of the currently
        // selected start and end values so that we can have a go at finding a
        // similar time/period in the new area. (4) We also take a note of the old
        // area id because we'll need that when trying to match up slots: it only
        // makes sense to match up slots if both old and new area used the same
        // mode (periods/times).
        
        // For the "all day" checkbox, the process is slightly different.  This
        // is because the checkboxes themselves are visible or not depending on
        // the time restrictions for that particular area. (1) We set the display 
        // for the old *container* element to "none" and the new elements to 
        // "block".  (2) We disable the old checkboxes and enable the new ones for
        // the same reasons as above.  (3) We copy the value of the old check box
        // to the new check box
        ?>
        var oldStartId = "start_seconds" + currentArea;
        var oldEndId = "end_seconds" + currentArea;
        var newStartId = "start_seconds" + area;
        var newEndId = "end_seconds" + area;
        var oldAllDayId = "ad" + currentArea;
        var newAllDayId = "ad" + area;
        var oldAreaStartValue = formObj[oldStartId].options[formObj[oldStartId].selectedIndex].value;
        var oldAreaEndValue = formObj[oldEndId].options[formObj[oldEndId].selectedIndex].value;
        $("#" + oldStartId).hide()
                           .attr('disabled', 'disabled');
        $("#" + oldEndId).hide()
                         .attr('disabled', 'disabled');
        $("#" + newStartId).show()
                           .removeAttr('disabled');
        $("#" + newEndId).show()
                         .removeAttr('disabled');
                         +        $("#" + oldAllDayId).hide();
        $("#" + newAllDayId).show();
        if($("#all_day" + currentArea).attr('checked') == 'checked')
        { 
          $("#all_day" + area).attr('checked', 'checked').removeAttr('disabled');
        }
        else
        {
          $("#all_day" + area).removeAttr('checked').removeAttr('disabled');
        }
        $("#all_day" + currentArea).removeAttr('disabled');
        var oldArea = currentArea;
        currentArea = area;
        prevStartValue = undefined;
        adjustSlotSelectors(formObj, oldArea, oldAreaStartValue, oldAreaEndValue);
      }
      
      // Create area selector, only if we have Javascript
      var div_areas = document.getElementById('div_areas');
      // First of all create a label and insert it into the <div>
      var area_label = document.createElement('label');
      var area_label_text = document.createTextNode('<?php echo get_vocab("area") ?>:');
      area_label.appendChild(area_label_text);
      area_label.setAttribute('for', 'area');
      div_areas.appendChild(area_label);
      // Now give it a select box
      var area_select = document.createElement('select');
      area_select.setAttribute('id', 'area');
      area_select.setAttribute('name', 'area');
      area_select.onchange = function(){changeRooms(this.form)}; // setAttribute doesn't work for onChange with IE6
      // populated with options
      var option;
      var option_text
      <?php
      // go through the areas and create the options
      foreach ($areas as $a)
      {
        ?>
        option = document.createElement('option');
        option.value = <?php echo $a['id'] ?>;
        option_text = document.createTextNode('<?php echo escape_js($a['area_name']) ?>');
        <?php
        if ($a['id'] == $area_id)
        {
          ?>
          option.selected = true;
          <?php
        }
        ?>
        option.appendChild(option_text);
        area_select.appendChild(option);
        <?php
      }
      ?>
      // insert the <select> which we've just assembled into the <div>
      div_areas.appendChild(area_select);
      
      <?php
      if ($disabled)
      {
        // If the field is disabled we need to disable the select box and
        // add in a hidden input containing the value
        ?>
        $('#area').attr('disabled', 'disabled');
        $('<input>').attr('type', 'hidden')
                    .attr('name', 'area')
                    .val('<?php echo $area_id ?>')
                    .appendTo('#div_areas');
        <?php
      }
      ?>
      
      //]]>
      </script>
      
      
      <?php
    } // if count($areas)
}


function create_field_entry_rooms($disabled=FALSE)
{
  global $rooms, $multiroom_allowed, $room_id, $area_id, $selected_rooms;

  echo "<div id=\"div_rooms\">\n";
  echo "<label for=\"rooms\">" . get_vocab("rooms") . ":</label>\n";
  echo "<div class=\"group\">\n";
  echo "<select id=\"rooms\" name=\"rooms[]\"" .
    (($multiroom_allowed) ? " multiple=\"multiple\"" : "") .
    (($disabled) ? " disabled=\"disabled\"" : "") .
    " size=\"5\">\n";
  // $selected_rooms will be populated if we've come from a drag selection
  if (empty($selected_rooms))
  {
    $selected_rooms = array($room_id);
  }
  foreach ($rooms as $r)
  {
    if ($r['area_id'] == $area_id)
    {
      $is_selected = in_array($r['id'], $selected_rooms);
      $selected = ($is_selected) ? "selected=\"selected\"" : "";
      echo "<option $selected value=\"" . $r['id'] . "\">" . htmlspecialchars($r['room_name']) . "</option>\n";
    }
  }
  echo "</select>\n";
  // No point telling them how to select multiple rooms if the input
  // is disabled
  if ($multiroom_allowed && !$disabled)
  {
    echo "<span>" . get_vocab("ctrl_click") . "</span>\n";
  }
  echo "</div>\n";
  if ($disabled)
  {
    foreach ($selected_rooms as $selected_room)
    {
      echo "<input type=\"hidden\" name=\"rooms[]\" value=\"$selected_room\">\n";
    }
  }
  echo "</div>\n";
}


function create_field_entry_type($disabled=FALSE)
{
  global $booking_types, $type;
  
  echo "<div id=\"div_type\">\n";
  echo "<label for=\"type\">" . get_vocab("type") . ":</label>\n";
  echo "<select id=\"type\" name=\"type\"" .
       (($disabled) ? " disabled=\"disabled\"" : "") .
       ">\n";
  foreach ($booking_types as $key)
  {
    echo "<option value=\"$key\"" . (($type == $key) ? " selected=\"selected\"" : "") . ">".get_type_vocab($key)."</option>\n";
  }
  echo "</select>\n";
  if ($disabled)
  {
    echo "<input type=\"hidden\" name=\"type\" value=\"$type\">\n";
  }
  echo "</div>\n";
}


function create_field_entry_confirmation_status($disabled=FALSE)
{
  global $confirmation_enabled, $confirmed;
  
  // Confirmation status
  if ($confirmation_enabled)
  {
    echo "<div id=\"div_confirmation_status\">\n";
    echo "<label>" . get_vocab("confirmation_status") . ":</label>\n";
    echo "<div class=\"group\">\n";
    echo "<label><input class=\"radio\" name=\"confirmed\" type=\"radio\" value=\"1\"" .
      (($confirmed) ? " checked=\"checked\"" : "") .
      (($disabled) ? " disabled=\"disabled\"" : "") .
      ">" . get_vocab("confirmed") . "</label>\n";
    echo "<label><input class=\"radio\" name=\"confirmed\" type=\"radio\" value=\"0\"" .
      (($confirmed) ? "" : " checked=\"checked\"") .
      (($disabled) ? " disabled=\"disabled\"" : "") .
      ">" . get_vocab("tentative") . "</label>\n";
    echo "</div>\n";
    if ($disabled)
    {
      echo "<input type=\"hidden\" name=\"confirmed\" value=\"" .
           (($confirmed) ? "1" : "0") .
           "\">\n";
    }
    echo "</div>\n";
  }
}


function create_field_entry_privacy_status($disabled=FALSE)
{
  global $private_enabled, $private, $private_mandatory;
  
  // Privacy status
  if ($private_enabled)
  {
    // No need to pass through a hidden variable if disabled because the handler will sort it out
    echo "<div id=\"div_privacy_status\">\n";
    echo "<label>" . get_vocab("privacy_status") . ":</label>\n";
    echo "<div class=\"group\">\n";
    echo "<label><input class=\"radio\" name=\"private\" type=\"radio\" value=\"0\"" .
      (($private) ? "" : " checked=\"checked\"") .
      (($private_mandatory || $disabled) ? " disabled=\"disabled\"" : "") .
      ">" . get_vocab("public") . "</label>\n";
    echo "<label><input class=\"radio\" name=\"private\" type=\"radio\" value=\"1\"" .
      (($private) ? " checked=\"checked\"" : "") .
      (($private_mandatory || $disabled) ? " disabled=\"disabled\"" : "") .
      ">" . get_vocab("private") . "</label>\n";
    echo "</div>\n";
    if ($disabled)
    {
      echo "<input type=\"hidden\" name=\"private\" value=\"" .
           (($private) ? "1" : "0") .
           "\">\n";
    }
    echo "</div>\n";
  }
}


function create_field_entry_custom_field($field, $key, $disabled=FALSE)
{
  global $custom_fields, $tbl_entry, $select_options;
  global $is_mandatory_field, $text_input_max;

  $var_name = VAR_PREFIX . $key;
  $value = $custom_fields[$key];
  $label_text = get_loc_field_name($tbl_entry, $key) . ":";
  echo "<div>\n";
  // Output a checkbox if it's a boolean or integer <= 2 bytes (which we will
  // assume are intended to be booleans)
  if (($field['nature'] == 'boolean') || 
    (($field['nature'] == 'integer') && isset($field['length']) && ($field['length'] <= 2)) )
  {
    echo "<label for=\"$var_name\">$label_text</label>\n";
    echo "<input type=\"checkbox\" class=\"checkbox\" " .
      "id=\"$var_name\" name=\"$var_name\" value=\"1\" " .
      ((!empty($value)) ? " checked=\"checked\"" : "") .
      (($disabled) ? " disabled=\"disabled\"" : "") .
      ">\n";
  }
  // Output a select box if they want one
  elseif (!empty($select_options["entry.$key"]))
  {
    $mandatory = (array_key_exists("entry.$key", $is_mandatory_field) &&
      $is_mandatory_field["entry.$key"]) ? true : false;
    generate_select($label_text, $var_name, $value,
      $select_options["entry.$key"], $mandatory, $disabled);
  }
  // Output a textarea if it's a character string longer than the limit for a
  // text input
  elseif (($field['nature'] == 'character') && isset($field['length']) && ($field['length'] > $text_input_max))
  {
    generate_textarea($label_text, $var_name, $value, $disabled);   
  }
  // Otherwise output a text input
  else
  {
    generate_input($label_text, $var_name, $value, $disabled);
  }
  if ($disabled)
  {
    echo "<input type=\"hidden\" name=\"$var_name\" value=\"$value\">\n";
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

$fields = sql_field_info($tbl_entry);
$custom_fields = array();

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
  if (isset($copy) && ($create_by != $row['create_by'])) 
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
        $$column = ($keep_private && $is_private_field["entry.$column"]) ? '' : $row[$column];
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
        $custom_fields[$column] = ($keep_private && $is_private_field["entry.$column"]) ? '' : $row[$column];
        break;
    }
  }
  

  if(($entry_type == ENTRY_RPT_ORIGINAL) || ($entry_type == ENTRY_RPT_CHANGED))
  {
    $sql = "SELECT rep_type, start_time, end_time, end_date, rep_opt, rep_num_weeks
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

    // If it's a repeating entry get the repeat details
    if (isset($rep_type) && ($rep_type != REP_NONE))
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

      $rep_day = array();
      switch ($rep_type)
      {
        case REP_WEEKLY:
        case REP_N_WEEKLY:
          for ($i=0; $i<7; $i++)
          {
            if ($row['rep_opt'][$i])
            {
              $rep_day[] = $i;
            }
          }
          // Get the repeat days as an array for use
          // when the input is disabled
          $rep_opt = $row['rep_opt'];

          if ($rep_type == REP_N_WEEKLY)
          {
            $rep_num_weeks = $row['rep_num_weeks'];
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
  $rep_id        = 0;
  if (!isset($rep_type))  // We might have set it through a drag selection
  {
    $rep_type      = REP_NONE;
    $rep_end_day   = $day;
    $rep_end_month = $month;
    $rep_end_year  = $year;
  }
  $rep_day       = array();
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
      $default_duration = (60 * 60);
    }
    $duration    = ($enable_periods ? 60 : $default_duration);
    $end_time = $start_time + $duration;
    // The end time can't be past the end of the booking day
    $pm7 = mktime($eveningends, $eveningends_minutes, 0, $month, $day, $year);
    $end_time = min($end_time, $pm7 + $resolution);
  }
}

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
$sql = "SELECT id, area_name, resolution, default_duration, enable_periods,
               morningstarts, morningstarts_minutes, eveningends , eveningends_minutes
          FROM $tbl_area
         WHERE disabled=0
      ORDER BY area_name";
$res = sql_query($sql);
if ($res)
{
  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    $areas[$row['id']] = $row;
    // The following config settings aren't yet per-area, but we'll treat them as if
    // they are to make it easier to change them to per-area settings in the future.
    $areas[$row['id']]['max_duration_enabled'] = $max_duration_enabled;
    $areas[$row['id']]['max_duration_secs']    = $max_duration_secs;
    $areas[$row['id']]['max_duration_periods'] = $max_duration_periods;
    // Clean up the settings, getting rid of any nulls and casting boolean fields into bools
    $areas[$row['id']] = clean_area_row($areas[$row['id']]);
    // Generate some derived settings
    $areas[$row['id']]['max_duration_qty'] = $areas[$row['id']]['max_duration_secs'];
    toTimeString($areas[$row['id']]['max_duration_qty'], $areas[$row['id']]['max_duration_units']);
  }
}

?>

<script type="text/javascript">
//<![CDATA[

var currentArea = <?php echo $area_id ?>;
var areas = new Array();
<?php
// give JavaScript a copy of the PHP array $areas
foreach ($areas as $area)
{
  echo "areas[${area['id']}] = new Array();\n";
  foreach ($area as $key => $value)
  {
    if (in_array($key, array('area_name', 'max_duration_units')))
    {
      // Enclose strings in quotes
      $value = "'" . escape_js($value) . "'";
    }
    elseif (in_array($key, $boolean_fields['area']))
    {
      // Convert booleans
      $value = ($value) ? 'true' : 'false';
    }
    echo "areas[${area['id']}]['$key'] = $value;\n";
  }
}
?>

// do a little form verifying
function validate(form_id)
{
  <?php
  // First of all check that a name (brief description) has been entered.
  // Only do this if the name is being entered via an INPUT box.   If it's
  // being entered via a SELECT box there's no need to do this because there's
  // bound to be a value and the test below will fail on some browsers (eg IE)
  ?>
  var form = document.getElementById(form_id);
  if (form.name.tagName.toLowerCase() == 'input')
  {
    // null strings and spaces only strings not allowed
    if(/(^$)|(^\s+$)/.test(form.name.value))
    {
      alert("<?php echo escape_js(get_vocab('you_have_not_entered')) . '\n' . escape_js(get_vocab('brief_description')) ?>");
      return false;
    }
  }
  
  <?php
  // Check that the start date is not after the end date
  ?>
  var dateDiff = getDateDifference(form);
  if (dateDiff < 0)
  {
    alert("<?php echo escape_js(get_vocab('start_after_end_long'))?>");
    return false;
  }

  // check form element exist before trying to access it
  if (form.id )
  {
    i1 = parseInt(form.id.value);
  }
  else
  {
    i1 = 0;
  }

  i2 = parseInt(form.rep_id.value);
  if (form.rep_num_weeks)
  {
     n = parseInt(form.rep_num_weeks.value);
  }
  if ((!i1 || (i1 && i2)) &&
      form.rep_type &&
      (form.rep_type.value != <?php echo REP_NONE ?>) && 
      form.rep_type[<?php echo REP_N_WEEKLY ?>].checked && 
      (!n || n < 2))
  {
    alert("<?php echo escape_js(get_vocab('you_have_not_entered')) . '\n' . escape_js(get_vocab('useful_n-weekly_value')) ?>");
    return false;
  }
  

  // check that a room(s) has been selected
  // this is needed as edit_entry_handler does not check that a room(s)
  // has been chosen
  if (form.elements['rooms'].selectedIndex == -1 )
  {
    alert("<?php echo escape_js(get_vocab('you_have_not_selected')) . '\n' . escape_js(get_vocab('valid_room')) ?>");
    return false;
  }
  
  <?php
  if (count($is_mandatory_field))
  {
    $m_fields = array();
    foreach ($is_mandatory_field as $field => $value)
    {
      if ($value)
      {
        $field = preg_replace('/^entry\./', 'f_', $field);
        $m_fields[] = "'".str_replace("'", "\\'", $field)."'";
      }
    }
    echo "var mandatory_fields = [".implode(', ', $m_fields)."];\n";
  ?>

    var return_val = true;

    $.each(mandatory_fields,
           function(index, value)
           {
             var field = $("#"+value);
             <?php
             // If it's a checkbox then it needs to be checked.    If it's
             // an ordinary field then it must have some content.
             ?>
             if ( ((field.attr('type') !== undefined) && 
                   (field.attr('type').toLowerCase() == 'checkbox') && 
                   !field.attr('checked')) ||
                  (field.val() == '') )
             {
               label = $("label[for="+value+"]").html();
               label = label.replace(/:$/, '');
               alert('"' + label + '" ' +
                 <?php echo '"' . escape_js(get_vocab('is_mandatory_field')) . '"'; ?>);
               return_val = false;
             }
           });
    if (!return_val)
    {
      return return_val;
    }
  <?php
   
  }

  // Form submit can take some times, especially if mails are enabled and
  // there are more than one recipient. To avoid users doing weird things
  // like clicking more than one time on submit button, we hide it as soon
  // it is clicked.
  ?>
  form.save_button.disabled = true;

  // would be nice to also check date to not allow Feb 31, etc...

  return true;
}

// set up some global variables for use by OnAllDayClick(). 
var old_start, old_end;

// Executed when the user clicks on the all_day checkbox.
function OnAllDayClick(el)
{
  var form = document.forms["main"];
  if (form)
  {
    var startSelect = form["start_seconds" + currentArea];
    var endSelect = form["end_seconds" + currentArea];
    var allDay = form["all_day" + currentArea];
    var i;
    if (allDay.checked) // If checking the box...
    {
      <?php
      // Save the old values, disable the inputs and, to avoid user confusion,
      // show the start and end times as the beginning and end of the booking
      // (Note that we save the value rather than the index because the number
      // of options in the select box will change)
      ?>
      old_start = startSelect.options[startSelect.selectedIndex].value;
      startSelect.selectedIndex = 0;
      startSelect.disabled = true;
    
      old_end = endSelect.options[endSelect.selectedIndex].value;
      endSelect.selectedIndex = endSelect.options.length - 1;
      endSelect.disabled = true;
    }
    else  <?php // restore the old values and re-enable the inputs ?>
    {
      startSelect.disabled = false;
      for (i=0; i<startSelect.options.length; i++)
      {
        if (startSelect.options[i].value == old_start)
        {
          startSelect.options.selectedIndex = i;
          break;
        }
      }     
      endSelect.disabled = false;
      for (i=0; i<endSelect.options.length; i++)
      {
        if (endSelect.options[i].value == old_end)
        {
          endSelect.options.selectedIndex = i;
          break;
        }
      } 
      prevStartValue = undefined;  <?php // because we don't want adjustSlotSelectors() to change the end time ?>
    }
    adjustSlotSelectors(form); <?php // need to get the duration right ?>
  }
}
//]]>
</script>

<?php

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

// Fill $edit_entry_field_order with not yet specified entries.
$entry_fields = array('name', 'description', 'start_date', 'end_date', 'areas',
  'rooms', 'type', 'confirmation_status', 'privacy_status');
foreach( $entry_fields as $field )
{
  if( ! in_array( $field, $edit_entry_field_order ) )
    $edit_entry_field_order[] = $field;
}

// CUSTOM FIELDS
$custom_fields_map = array();
foreach ($fields as $field)
{
  $key = $field['name'];
  if (!in_array($key, $standard_fields['entry']))
  {
    $custom_fields_map[$key] = $field;
    if( ! in_array( $key, $edit_entry_field_order ) )
      $edit_entry_field_order[] = $key;
  }
}

foreach( $edit_entry_field_order as $key )
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
    
    // REPEAT BOOKING INPUTS
    if (($edit_type == "series") && $repeats_allowed)
    {
      // If repeats are allowed and the edit_type is a series (which means
      // that either you're editing an existing series or else you're making
      // a new booking) then print the repeat inputs
      echo "<fieldset id=\"rep_info\">\n";
      echo "<legend></legend>\n";
      ?>
      <div id="rep_type">
        <label><?php echo get_vocab("rep_type")?>:</label>
        <div class="group">
          <?php
          for ($i = 0; isset($vocab["rep_type_$i"]); $i++)
          {
            echo "      <label><input class=\"radio\" name=\"rep_type\" type=\"radio\" value=\"" . $i . "\"";
            if ($i == $rep_type)
            {
              echo " checked=\"checked\"";
            }
            echo ">" . get_vocab("rep_type_$i") . "</label>\n";
          }
          ?>
        </div>
      </div>

      <div id="rep_end_date">
        <?php
        echo "<label>" . get_vocab("rep_end_date") . ":</label>\n";
        genDateSelector("rep_end_", $rep_end_day, $rep_end_month, $rep_end_year);
        ?>
      </div>
      
      <div id="rep_day">
        <label><?php echo get_vocab("rep_rep_day")?>:<br><?php echo get_vocab("rep_for_weekly")?></label>
        <div class="group">
          <?php
          // Display day name checkboxes according to language and preferred weekday start.
          for ($i = 0; $i < 7; $i++)
          {
            $wday = ($i + $weekstarts) % 7;
            echo "      <label><input class=\"checkbox\" name=\"rep_day[]\" value=\"$wday\" type=\"checkbox\"";
            if (in_array($wday, $rep_day))
            {
              echo " checked=\"checked\"";
            }
            echo ">" . day_name($wday) . "</label>\n";
          }
          ?>
        </div>
      </div>
     
      <?php
      echo "<div>\n";
      $label_text = get_vocab("rep_num_weeks") . ":<br>" . get_vocab("rep_for_nweekly");
      generate_input($label_text, 'rep_num_weeks', $rep_num_weeks);
      echo "</div>\n";
      // Checkbox for skipping past conflicts
      echo "<div>\n";
      echo "<label for=\"skip\">" . get_vocab("skip_conflicts") . ":</label>\n";
      echo "<input type=\"checkbox\" class=\"checkbox\" " .
                "id=\"skip\" name=\"skip\" value=\"1\" " .
                ((!empty($skip_default)) ? " checked=\"checked\"" : "") .
                ">\n";
      echo "</div>\n";

      echo "</fieldset>\n";
    }
    elseif (isset($id))
    {
      // otherwise, if it's an existing booking, show the repeat information
      // and pass it through to the handler but do not let the user edit it
      // (because they're either not allowed to, or else they've chosen to edit
      // an individual entry rather than a series).
      // (NOTE: when repeat bookings are restricted to admins, an ordinary user
      // would not normally be able to get to the stage of trying to edit a series.
      // But we have to cater for the possibility because it could happen if (a) the
      // series was created before the policy was introduced or (b) the user has
      // been demoted since the series was created).
      $key = "rep_type_" . (isset($rep_type) ? $rep_type : REP_NONE);
      echo "<fieldset id=\"rep_info\">\n";
      echo "<legend></legend>\n";
      echo "<div>\n";
      echo "<label>" . get_vocab("rep_type") . ":</label>\n";
      echo "<select disabled=\"disabled\">\n";
      echo "<option>" . get_vocab($key) . "</option>\n";
      echo "</select>\n";
      echo "<input type=\"hidden\" name=\"rep_type\" value=\"" . REP_NONE . "\">\n";
      echo "</div>\n";
      if (isset($rep_type) && ($rep_type != REP_NONE))
      {
        $opt = "";
        if (($rep_type == REP_WEEKLY) || ($rep_type == REP_N_WEEKLY))
        {
          // Display day names according to language and preferred weekday start.
          for ($i = 0; $i < 7; $i++)
          {
            $wday = ($i + $weekstarts) % 7;
            if ($rep_opt[$wday])
            {
              $opt .= day_name($wday) . " ";
            }
          }
        }
        if($opt)
        {
          echo "  <div><label>".get_vocab("rep_rep_day").":</label><input type=\"text\" value=\"$opt\" disabled=\"disabled\"></div>\n";
        }
        echo "  <div><label>".get_vocab("rep_end_date").":</label><input type=\"text\" value=\"$rep_end_date\" disabled=\"disabled\"></div>\n";
        if ($rep_type == REP_N_WEEKLY)
        {
          echo "<div>\n";
          echo "<label for=\"rep_num_weeks\">" . get_vocab("rep_num_weeks") . ":<br>" . get_vocab("rep_for_nweekly") . "</label>\n";
          echo "<input type=\"text\" id=\"rep_num_weeks\" name=\"rep_num_weeks\" value=\"$rep_num_weeks\" disabled=\"disabled\">\n";
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
    echo "<input class=\"submit\" type=\"submit\" name=\"back_button\" value=\"" . get_vocab("back") . "\">\n";
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
