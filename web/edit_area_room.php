<?php
// $Id$

require_once "defaultincludes.inc";

require_once "mrbs_sql.inc";

// Get form variables
$day = get_form_var('day', 'int');
$month = get_form_var('month', 'int');
$year = get_form_var('year', 'int');
$area = get_form_var('area', 'int');
$new_area = get_form_var ('new_area', 'int');
$old_area = get_form_var ('old_area', 'int');
$room = get_form_var('room', 'int');
$room_name = get_form_var('room_name', 'string');
$sort_key = get_form_var('sort_key', 'string');
$old_room_name = get_form_var('old_room_name', 'string');
$area_name = get_form_var('area_name', 'string');
$description = get_form_var('description', 'string');
$capacity = get_form_var('capacity', 'int');
$room_admin_email = get_form_var('room_admin_email', 'string');
$area_admin_email = get_form_var('area_admin_email', 'string');
$area_morningstarts = get_form_var('area_morningstarts', 'int');
$area_morningstarts_minutes = get_form_var('area_morningstarts_minutes', 'int');
$area_morning_ampm = get_form_var('area_morning_ampm', 'string');
$area_res_mins = get_form_var('area_res_mins', 'int');
$area_def_duration_mins = get_form_var('area_def_duration_mins', 'int');
$area_eveningends = get_form_var('area_eveningends', 'int');
$area_eveningends_minutes = get_form_var('area_eveningends_minutes', 'int');
$area_evening_ampm = get_form_var('area_evening_ampm', 'string');
$area_eveningends_t = get_form_var('area_eveningends_t', 'int');
$area_min_ba_enabled = get_form_var('area_min_ba_enabled', 'string');
$area_min_ba_value = get_form_var('area_min_ba_value', 'int');
$area_min_ba_units = get_form_var('area_min_ba_units', 'string');
$area_max_ba_enabled = get_form_var('area_max_ba_enabled', 'string');
$area_max_ba_value = get_form_var('area_max_ba_value', 'int');
$area_max_ba_units = get_form_var('area_max_ba_units', 'string');
$area_private_enabled = get_form_var('area_private_enabled', 'string');
$area_private_default = get_form_var('area_private_default', 'int');
$area_private_mandatory = get_form_var('area_private_mandatory', 'string');
$area_private_override = get_form_var('area_private_override', 'string');
$change_done = get_form_var('change_done', 'string');
$change_room = get_form_var('change_room', 'string');
$change_area = get_form_var('change_area', 'string');

// If we dont know the right date then make it up
if (!isset($day) or !isset($month) or !isset($year))
{
  $day   = date("d");
  $month = date("m");
  $year  = date("Y");
}

$required_level = (isset($max_level) ? $max_level : 2);
if (!getAuthorised($required_level))
{
  showAccessDenied($day, $month, $year, $area, "");
  exit();
}

// Done changing area or room information?
if (isset($change_done))
{
  if (!empty($room)) // Get the area the room is in
  {
    $area = mrbsGetRoomArea($room);
  }
  Header("Location: admin.php?day=$day&month=$month&year=$year&area=$area");
  exit();
}

// Intialise the validation booleans
$valid_email = TRUE;
$valid_resolution = TRUE;
$enough_slots = TRUE;
$valid_area = TRUE;
$valid_room_name = TRUE;


// PHASE 2 (ROOM) - UPDATE THE DATABASE
// ------------------------------------
if (isset($change_room) && !empty($room))
{
  // validate the email addresses
  $valid_email = validate_email_list($room_admin_email);
  
  if (FALSE != $valid_email)
  {
    if (empty($capacity))
    {
      $capacity = 0;
    }
    
    // Acquire a mutex to lock out others who might be deleting the new area
    if (!sql_mutex_lock("$tbl_area"))
    {
      fatal_error(TRUE, get_vocab("failed_to_acquire"));
    }
    // Check the new area still exists
    if (sql_query1("SELECT COUNT(*) FROM $tbl_area WHERE id=$new_area LIMIT 1") < 1)
    {
      $valid_area = FALSE;
    }
    // If so, check that the room name is not already used in the area
    // (only do this if you're changing the room name or the area - if you're
    // just editing the other details for an existing room we don't want to reject
    // the edit because the room already exists!)
    elseif ( (($new_area != $old_area) || ($room_name != $old_room_name))
            && sql_query1("SELECT COUNT(*) FROM $tbl_room WHERE room_name='" . addslashes($room_name) . "' AND area_id=$new_area LIMIT 1") > 0)
    {
      $valid_room_name = FALSE;
    }
    // If everything is still OK, update the databasae
    else
    {
      $sql = "UPDATE $tbl_room SET room_name='" . addslashes($room_name)
        . "', sort_key='" . addslashes($sort_key)
        . "', description='" . addslashes($description)
        . "', capacity=$capacity, area_id=$new_area, room_admin_email='"
        . addslashes($room_admin_email) . "' WHERE id=$room";
      if (sql_command($sql) < 0)
      {
        fatal_error(0, get_vocab("update_room_failed") . sql_error());
      }
      // if everything is OK, release the mutex and go back to
      // the admin page (for the new area)
      sql_mutex_unlock("$tbl_area");
      Header("Location: admin.php?day=$day&month=$month&year=$year&area=$new_area");
      exit();
    }
    
    // Release the mutex
    sql_mutex_unlock("$tbl_area");
  }
}

// PHASE 2 (AREA) - UPDATE THE DATABASE
// ------------------------------------

if (isset($change_area) && !empty($area))
{ 
  // validate email addresses
  $valid_email = validate_email_list($area_admin_email);
  
  // Tidy up the input from the form
  if (isset($area_eveningends_t))
  {
    // if we've been given a time in minutes rather than hours and minutes, convert it
    // (this will happen if JavaScript is enabled)
    $area_eveningends_minutes = $area_eveningends_t % 60;
    $area_eveningends = ($area_eveningends_t - $area_eveningends_minutes)/60;
  }

  if (!empty($area_morning_ampm))
  {
    if (($area_morning_ampm == "pm") && ($area_morningstarts < 12))
    {
      $area_morningstarts += 12;
    }
    if (($area_morning_ampm == "am") && ($area_morningstarts > 11))
    {
      $area_morningstarts -= 12;
    }
  }

  if (!empty($area_evening_ampm))
  {
    if (($area_evening_ampm == "pm") && ($area_eveningends < 12))
    {
      $area_eveningends += 12;
    }
    if (($area_evening_ampm == "am") && ($area_eveningends > 11))
    {
      $area_eveningends -= 12;
    }
  }
  
  // Convert the book ahead times into seconds
  fromTimeString($area_min_ba_value, $area_min_ba_units);
  fromTimeString($area_max_ba_value, $area_max_ba_units);
  
  // Convert booleans into 0/1 (necessary for PostgreSQL)
  $area_min_ba_enabled = (!empty($area_min_ba_enabled)) ? 1 : 0;
  $area_max_ba_enabled = (!empty($area_max_ba_enabled)) ? 1 : 0;
  $area_private_enabled = (!empty($area_private_enabled)) ? 1 : 0;
  $area_private_mandatory = (!empty($area_private_mandatory)) ? 1 : 0;
    
  if (!$enable_periods)
  { 
    // Avoid divide by zero errors
    if ($area_res_mins == 0)
    {
      $valid_resolution = FALSE;
    }
    else
    {
      // Check morningstarts, eveningends, and resolution for consistency
      $start_first_slot = ($area_morningstarts*60) + $area_morningstarts_minutes;   // minutes
      $start_last_slot  = ($area_eveningends*60) + $area_eveningends_minutes;       // minutes
      $start_difference = ($start_last_slot - $start_first_slot);         // minutes
      if (($start_difference < 0) or ($start_difference%$area_res_mins != 0))
      {
        $valid_resolution = FALSE;
      }
      
      // Check that the number of slots we now have is no greater than $max_slots
      // defined in the config file - otherwise we won't generate enough CSS classes
      $n_slots = ($start_difference/$area_res_mins) + 1;
      if ($n_slots > $max_slots)
      {
        $enough_slots = FALSE;
      }
    }
  }
    
  // If everything is OK, update the database
  if ((FALSE != $valid_email) && (FALSE != $valid_resolution) && (FALSE != $enough_slots))
  {
    $sql = "UPDATE $tbl_area SET area_name='" . addslashes($area_name)
      . "', area_admin_email='" . addslashes($area_admin_email) . "'";
    if (!$enable_periods)
    {
      // only update the min and max book_ahead_secs fields if the form values
      // are set;  they might be NULL because they've been disabled by JavaScript
      $sql .= ", resolution=" . $area_res_mins * 60
            . ", default_duration=" . $area_def_duration_mins * 60
            . ", morningstarts=" . $area_morningstarts
            . ", morningstarts_minutes=" . $area_morningstarts_minutes
            . ", eveningends=" . $area_eveningends
            . ", eveningends_minutes=" . $area_eveningends_minutes
            . ", min_book_ahead_enabled=" . $area_min_ba_enabled
            . (isset($area_min_ba_value) ? ", min_book_ahead_secs=" . $area_min_ba_value : "")
            . ", max_book_ahead_enabled=" . $area_max_ba_enabled
            . (isset($area_max_ba_value) ? ", max_book_ahead_secs=" . $area_max_ba_value : "");
    }
    $sql .= ", private_enabled=" . $area_private_enabled
          . ", private_default=" . $area_private_default
          . ", private_mandatory=" . $area_private_mandatory
          . ", private_override='" . $area_private_override . "'";
            
    $sql .= " WHERE id=$area";
    if (sql_command($sql) < 0)
    {
      fatal_error(0, get_vocab("update_area_failed") . sql_error());
    }
    // If the database update worked OK, go back to the admin page
    Header("Location: admin.php?day=$day&month=$month&year=$year&area=$area");
    exit();
  }
}


// PHASE 1 - GET THE USER INPUT
// ----------------------------

print_header($day, $month, $year, isset($area) ? $area : "", isset($room) ? $room : "");

?>

<h2><?php echo get_vocab("editroomarea") ?></h2>

<?php

// THE ROOM FORM
if (!empty($room))
{
  $res = sql_query("SELECT * FROM $tbl_room WHERE id=$room");
  if (! $res)
  {
    fatal_error(0, get_vocab("error_room") . $room . get_vocab("not_found"));
  }
  $row = sql_row_keyed($res, 0);
  sql_free($res);
  
  ?>
  <form class="form_general" id="edit_room" action="edit_area_room.php" method="post">
    <fieldset class="admin">
    <legend><?php echo get_vocab("editroom") ?></legend>
  
      <fieldset>
      <legend></legend>
        <span class="error">
           <?php 
           // It's impossible to have more than one of these error messages, so no need to worry
           // about paragraphs or line breaks.
           echo ((FALSE == $valid_email) ? get_vocab('invalid_email') : "");
           echo ((FALSE == $valid_area) ? get_vocab('invalid_area') : "");
           echo ((FALSE == $valid_room_name) ? get_vocab('invalid_room_name') : "");
           ?>
        </span>
      </fieldset>
    
      <input type="hidden" name="room" value="<?php echo $row["id"]?>">
    
      <?php
      $res = sql_query("SELECT id, area_name FROM $tbl_area");
      if (!$res)
      {
        fatal_error(FALSE, "Fatal error: " . sql_error);  // should not happen
      }
      if (sql_count($res) == 0)
      {
        fatal_error(FALSE, get_vocab('noareas'));  // should not happen
      }
      ?>
      <div>
      <label for="new_area"><?php echo get_vocab("area") ?>:</label>
      <select id="new_area" name="new_area">
        <?php  
        for ($i = 0; ($row_area = sql_row_keyed($res, $i)); $i++)
        {
          echo "<option value=\"" . $row_area['id'] . "\"";
          if ($row_area['id'] == $row['area_id'])
          {
            echo " selected=\"selected\"";
          }
          echo ">" . $row_area['area_name'] . "</option>\n";
        }  
        ?>
      </select>
      <input type="hidden" name="old_area" value="<?php echo $row['area_id'] ?>">
      </div>
    
      <div>
      <label for="room_name"><?php echo get_vocab("name") ?>:</label>
      <input type="text" id="room_name" name="room_name" value="<?php echo htmlspecialchars($row["room_name"]); ?>">
      <input type="hidden" name="old_room_name" value="<?php echo htmlspecialchars($row["room_name"]); ?>">
      </div>
    
      <div>
      <?php
      echo "<label for=\"sort_key\" title=\"" . get_vocab("sort_key_note") . "\">" . get_vocab("sort_key") . ":</label>\n";
      ?>
      <input type="text" id="sort_key" name="sort_key" value="<?php echo htmlspecialchars($row["sort_key"]); ?>">
      </div>
    
      <div>
      <label for="description"><?php echo get_vocab("description") ?>:</label>
      <input type="text" id="description" name="description" value="<?php echo htmlspecialchars($row["description"]); ?>"> 
      </div>
    
      <div>
      <label for="capacity"><?php echo get_vocab("capacity") ?>:</label>
      <input type="text" id="capacity" name="capacity" value="<?php echo $row["capacity"]; ?>">
      </div>
    
      <div>
      <label for="room_admin_email"><?php echo get_vocab("room_admin_email") ?>:</label>
      <input type="text" id="room_admin_email" name="room_admin_email" maxlength="75" value="<?php echo htmlspecialchars($row["room_admin_email"]); ?>">
      </div>
    
      <fieldset class="submit_buttons">
      <legend></legend>
        <div id="edit_area_room_submit_back">
          <input class="submit" type="submit" name="change_done" value="<?php echo get_vocab("backadmin") ?>">
        </div>
        <div id="edit_area_room_submit_save">
          <input class="submit" type="submit" name="change_room" value="<?php echo get_vocab("change") ?>">
        </div>
      </fieldset>
    
    </fieldset>
  </form>

  <?php
}

// THE AREA FORM
if (!empty($area))
{
  $res = sql_query("SELECT * FROM $tbl_area WHERE id=$area LIMIT 1");
  if (! $res)
  {
    fatal_error(0, get_vocab("error_area") . $area . get_vocab("not_found"));
  }
  $row = sql_row_keyed($res, 0);
  sql_free($res);
  // Get the settings for this area, from the database if they are there, otherwise from
  // the config file.    A little bit inefficient repeating the SQL query
  // we've just done, but it makes the code simpler and this page is not used very often.
  get_area_settings($area);
  ?>

  <form class="form_general" id="edit_area" action="edit_area_room.php" method="post">
    <fieldset class="admin">
    <legend><?php echo get_vocab("editarea") ?></legend>
  
      <fieldset>
      <legend></legend>
        <?php
        if (FALSE == $valid_email)
        {
          echo "<p class=\"error\">" .get_vocab('invalid_email') . "</p>\n";
        }
        if (FALSE == $valid_resolution)
        {
          echo "<p class=\"error\">" .get_vocab('invalid_resolution') . "</p>\n";
        }
        if (FALSE == $enough_slots)
        {
          echo "<p class=\"error\">" .get_vocab('too_many_slots') . "</p>\n";
        }
        ?>
      </fieldset>
  
      <fieldset>
      <legend><?php echo get_vocab("general_settings")?></legend>
        <input type="hidden" name="area" value="<?php echo $row["id"]?>">
    
        <div>
        <label for="area_name"><?php echo get_vocab("name") ?>:</label>
        <input type="text" id="area_name" name="area_name" value="<?php echo htmlspecialchars($row["area_name"]); ?>">
        </div>
    
        <div>
        <label for="area_admin_email"><?php echo get_vocab("area_admin_email") ?>:</label>
        <input type="text" id="area_admin_email" name="area_admin_email" maxlength="75" value="<?php echo htmlspecialchars($row["area_admin_email"]); ?>">
        </div>
      </fieldset>
    
      <?php
      if (!$enable_periods)
      {
      ?>
        <script type="text/javascript">
        //<![CDATA[
      
          function getTimeString(time, twentyfourhour_format)
          {
             // Converts a time (in minutes since midnight) into a string
             // of the form hh:mm if twentyfourhour_format is true,
             // otherwise of the form hh:mm am/pm.
           
             // This function doesn't do a great job of replicating the PHP
             // internationalised format, but is probably sufficient for a 
             // rarely used admin page.
           
             var minutes = time % 60;
             time -= minutes;
             var hour = time/60;
             if (!twentyfourhour_format)
             {
               var ap = "AM";
               if (hour > 11) {ap = "PM";}
               if (hour > 12) {hour = hour - 12;}
               if (hour == 0) {hour = 12;}
             }
             if (hour < 10) {hour   = "0" + hour;}
             if (minutes < 10) {minutes = "0" + minutes;}
             var timeString = hour + ':' + minutes;
             if (!twentyfourhour_format)
             {
               timeString += ap;
             }
             return timeString;
          } // function getTimeString()

        
          function writeSelect(morningstarts, morningstarts_minutes, eveningends, eveningends_minutes, res_mins)
          {
            // generates the HTML for the drop-down for the last slot time and
            // puts it in the element with id 'last_slot'
            if (res_mins == 0) return;  // avoid endless loops
          
            var first_slot = (morningstarts * 60) + morningstarts_minutes;
            var last_slot = (eveningends * 60) + eveningends_minutes;
            var last_possible = (24 * 60) - res_mins;
            var html = '<label for="area_eveningends_t"><?php echo get_vocab("area_last_slot_start")?>:<\/label>\n';
            html += '<select id="area_eveningends_t" name="area_eveningends_t">\n';
            for (var t=first_slot; t <= last_possible; t += res_mins)
            {
              html += '<option value="' + t + '"';
              if (t == last_slot)
              {
                html += ' selected="selected"';
              }
              html += ">" + getTimeString(t, <?php echo ($twentyfourhour_format ? "true" : "false") ?>) + "<\/option>\n";
            }
            html += "<\/select>\n";
            document.getElementById('last_slot').innerHTML = html;
          }  // function writeSelect
        
      
          function changeSelect(formObj)
          {
            // re-generates the dropdown given changed form values
            var res_mins = parseInt(formObj.area_res_mins.value);
            if (res_mins == 0) return;  // avoid endless loops and divide by zero errors
            var morningstarts = parseInt(formObj.area_morningstarts.value);
            var morningstarts_minutes = parseInt(formObj.area_morningstarts_minutes.value);
            var eveningends_t = parseInt(formObj.area_eveningends_t.value);
            var morningstarts_t = (morningstarts * 60) + morningstarts_minutes;
            var ampm = "am";
            if (formObj.area_morning_ampm && formObj.area_morning_ampm[1].checked)
            {
              ampm = "pm";
            }        
            if (<?php echo (!$twentyfourhour_format ? "true" : "false") ?>)
            {
              if ((ampm == "pm") && (morningstarts < 12))
              {
                morningstarts += 12;
              }
              if ((ampm == "am") && (morningstarts>11))
              {
                morningstarts -= 12;
              }
            }
            // Find valid values for eveningends
            var remainder = (eveningends_t - morningstarts_t) % res_mins;
            // round up to the nearest slot boundary
            if (remainder != 0)
            {
              eveningends_t += res_mins - remainder;
            }
            // and then step back to make sure that the end of the slot isn't past midnight (and the beginning isn't before the morning start)
            while ((eveningends_t + res_mins > 1440) && (eveningends_t > morningstarts_t + res_mins))  // 1440 minutes in a day
            {
              eveningends_t -= res_mins;
            }
            // convert into hours and minutes
            var eveningends_minutes = eveningends_t % 60;
            var eveningends = (eveningends_t - eveningends_minutes) / 60;
            writeSelect (morningstarts, morningstarts_minutes, eveningends, eveningends_minutes, res_mins);
          } // function changeSelect
        
        //]]>
        </script>
      
        <fieldset>
        <legend><?php echo get_vocab("time_settings")?></legend>
        <div class="div_time">
          <label><?php echo get_vocab("area_first_slot_start")?>:</label>
          <?php
          echo "<input class=\"time_hour\" type=\"text\" id=\"area_morningstarts\" name=\"area_morningstarts\" value=\"";
          if ($twentyfourhour_format)
          {
            printf("%02d", $morningstarts);
          }
          elseif ($morningstarts > 12)
          {
            echo ($morningstarts - 12);
          } 
          elseif ($morningstarts == 0)
          {
            echo "12";
          }
          else
          {
            echo $morningstarts;
          } 
          echo "\" maxlength=\"2\" onChange=\"changeSelect(this.form)\">\n";
          ?>
          <span>:</span>
          <input class="time_minute" type="text" id="area_morningstarts_minutes" name="area_morningstarts_minutes" value="<?php printf("%02d", $morningstarts_minutes) ?>" maxlength="2" onChange="changeSelect(this.form)">
          <?php
          if (!$twentyfourhour_format)
          {
            echo "<div class=\"group ampm\">\n";
            $checked = ($morningstarts < 12) ? "checked=\"checked\"" : "";
            echo "      <label><input name=\"area_morning_ampm\" type=\"radio\" value=\"am\" onClick=\"changeSelect(this.form)\" $checked>" . utf8_strftime("%p",mktime(1,0,0,1,1,2000)) . "</label>\n";
            $checked = ($morningstarts >= 12) ? "checked=\"checked\"" : "";
            echo "      <label><input name=\"area_morning_ampm\" type=\"radio\" value=\"pm\" onClick=\"changeSelect(this.form)\" $checked>". utf8_strftime("%p",mktime(13,0,0,1,1,2000)) . "</label>\n";
            echo "</div>\n";
          }
          ?>
        </div>
      
        <div class="div_dur_mins">
        <label for="area_res_mins"><?php echo get_vocab("area_res_mins") ?>:</label>
        <input type="text" id="area_res_mins" name="area_res_mins" value="<?php echo $resolution/60 ?>" onChange="changeSelect(this.form)">
        </div>
      
        <div class="div_dur_mins">
        <label for="area_def_duration_mins"><?php echo get_vocab("area_def_duration_mins") ?>:</label>
        <input type="text" id="area_def_duration_mins" name="area_def_duration_mins" value="<?php echo $default_duration/60 ?>">
        </div>
        <?php
        echo "<div id=\"last_slot\">\n";
        // The contents of this div will be overwritten by JavaScript if enabled.    The JavaScript version is a drop-down
        // select input with options limited to those times for the last slot start that are valid.   The options are
        // dynamically regenerated if the start of the first slot or the resolution change.    The code below is
        // therefore an alternative for non-JavaScript browsers.
        echo "<div class=\"div_time\">\n";
          echo "<label>" . get_vocab("area_last_slot_start") . ":</label>\n";
          echo "<input class=\"time_hour\" type=\"text\" id=\"area_eveningends\" name=\"area_eveningends\" value=\"";
          if ($twentyfourhour_format)
          {
            printf("%02d", $eveningends);
          }
          elseif ($eveningends > 12)
          {
            echo ($eveningends - 12);
          } 
          elseif ($eveningends == 0)
          {
            echo "12";
          }
          else
          {
            echo $eveningends;
          } 
          echo "\" maxlength=\"2\" onChange=\"changeSelect(this.form)\">\n";

          echo "<span>:</span>\n";
          echo "<input class=\"time_minute\" type=\"text\" id=\"area_eveningends_minutes\" name=\"area_eveningends_minutes\" value=\""; 
          printf("%02d", $eveningends_minutes);
          echo "\" maxlength=\"2\" onChange=\"changeSelect(this.form)\">\n";
          if (!$twentyfourhour_format)
          {
            echo "<div class=\"group ampm\">\n";
            $checked = ($eveningends < 12) ? "checked=\"checked\"" : "";
            echo "      <label><input name=\"area_evening_ampm\" type=\"radio\" value=\"am\" onClick=\"changeSelect(this.form)\" $checked>" . utf8_strftime("%p",mktime(1,0,0,1,1,2000)) . "</label>\n";
            $checked = ($eveningends >= 12) ? "checked=\"checked\"" : "";
            echo "      <label><input name=\"area_evening_ampm\" type=\"radio\" value=\"pm\" onClick=\"changeSelect(this.form)\" $checked>". utf8_strftime("%p",mktime(13,0,0,1,1,2000)) . "</label>\n";
            echo "</div>\n";
          }
        echo "</div>\n";  
        echo "</div>\n";  // last_slot
        ?>
      
        <script type="text/javascript">
        //<![CDATA[
          writeSelect(<?php echo "$morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes, $resolution/60" ?>);
        //]]>
        </script>
        </fieldset>
        
        <?php
        // Booking policies
        $min_ba_value = $min_book_ahead_secs;
        toTimeString($min_ba_value, $min_ba_units);
        $max_ba_value = $max_book_ahead_secs;
        toTimeString($max_ba_value, $max_ba_units);
        echo "<fieldset id=\"booking_policies\">\n";
        echo "<legend>" . get_vocab("booking_policies") . "</legend>\n";
        // Minimum book ahead
        echo "<div>\n";
        echo "<label for=\"area_min_book_ahead\">" . get_vocab("min_book_ahead") . ":</label>\n";
        echo "<input class=\"checkbox\" type=\"checkbox\" id=\"area_min_ba_enabled\" name=\"area_min_ba_enabled\"" .
             (($min_book_ahead_enabled) ? " checked=\"checked\"" : "") .
             " onChange=\"check_book_ahead()\">\n";
        echo "<input class=\"text\" type=\"text\" name=\"area_min_ba_value\" value=\"$min_ba_value\">";
        echo "<select id=\"area_min_ba_units\" name=\"area_min_ba_units\">\n";
        $units = array("seconds", "minutes", "hours", "days", "weeks");
        foreach ($units as $unit)
        {
          echo "<option value=\"$unit\"" .
               (($min_ba_units == get_vocab($unit)) ? " selected=\"selected\"" : "") .
               ">" . get_vocab($unit) . "</option>\n";
        }
        echo "</select>\n";
        echo "</div>\n";
        // Maximum book ahead
        echo "<div>\n";
        echo "<label for=\"area_max_book_ahead\">" . get_vocab("max_book_ahead") . ":</label>\n";
        echo "<input class=\"checkbox\" type=\"checkbox\" id=\"area_max_ba_enabled\" name=\"area_max_ba_enabled\"" .
             (($max_book_ahead_enabled) ? " checked=\"checked\"" : "") .
             " onChange=\"check_book_ahead()\">\n";
        echo "<input class=\"text\" type=\"text\" name=\"area_max_ba_value\" value=\"$max_ba_value\">";
        echo "<select id=\"area_max_ba_units\" name=\"area_max_ba_units\">\n";
        $units = array("seconds", "minutes", "hours", "days", "weeks");
        foreach ($units as $unit)
        {
          echo "<option value=\"$unit\"" .
               (($max_ba_units == get_vocab($unit)) ? " selected=\"selected\"" : "") .
               ">" . get_vocab($unit) . "</option>\n";
        }
        echo "</select>\n";
        echo "</div>\n";
        echo "</fieldset>\n";
      } // end if (!$enable_periods)
    
      ?>
        
      <fieldset>
      <legend><?php echo get_vocab("private_settings")?></legend>
        <div>
          <label for="area_private_enabled"><?php echo get_vocab("allow_private")?>:</label>
          <?php $checked = ($private_enabled) ? " checked=\"checked\"" : "" ?>
          <input class="checkbox" type="checkbox"<?php echo $checked ?> id="area_private_enabled" name="area_private_enabled">
        </div>
        <div>
          <label for="area_private_mandatory"><?php echo get_vocab("force_private")?>:</label>
          <?php $checked = ($private_mandatory) ? " checked=\"checked\"" : "" ?>
          <input class="checkbox" type="checkbox"<?php echo $checked ?> id="area_private_mandatory" name="area_private_mandatory">
        </div>
        <label>
          <?php echo get_vocab("default_settings")?>:
        </label>
        <div class="group">
          <div>
            <label>
              <?php $checked = ($private_default) ? " checked=\"checked\"" : "" ?>
              <input class="radio" type="radio" name="area_private_default" value="1"<?php echo $checked ?>>
              <?php echo get_vocab("default_private")?>
            </label>
          </div>
          <div>
            <label>
              <?php $checked = ($private_default) ? "" : " checked=\"checked\"" ?>
              <input class="radio" type="radio" name="area_private_default" value="0"<?php echo $checked ?>>
              <?php echo get_vocab("default_public")?>
            </label>
          </div>
        </div>
      </fieldset>
    
      <fieldset>
      <legend><?php echo get_vocab("private_display")?></legend>
        <label>
          <?php echo get_vocab("private_display_label")?>
          <span id="private_display_caution">
            <?php echo get_vocab("private_display_caution")?>
          </span>
        </label>
        <div class="group" id="private_override" >
          <div>
            <label>
              <?php $checked = ($private_override == "none") ? " checked=\"checked\"" : "" ?>
              <input class="radio" type="radio" name="area_private_override" value="none"<?php echo $checked ?>>
              <?php echo get_vocab("treat_respect")?>
            </label>
          </div>
          <div>
            <label>
              <?php $checked = ($private_override == "private") ? " checked=\"checked\"" : "" ?>
              <input class="radio" type="radio" name="area_private_override" value="private"<?php echo $checked ?>>
              <?php echo get_vocab("treat_private")?>
            </label>
          </div>
          <div>
            <label>
              <?php $checked = ($private_override == "public") ? " checked=\"checked\"" : "" ?>
              <input class="radio" type="radio" name="area_private_override" value="public"<?php echo $checked ?>>
              <?php echo get_vocab("treat_public")?>
            </label>
          </div>
        </div>
      </fieldset>
    
      <fieldset class="submit_buttons">
      <legend></legend>
        <div id="edit_area_room_submit_back">
          <input class="submit" type="submit" name="change_done" value="<?php echo get_vocab("backadmin") ?>">
        </div>
        <div id="edit_area_room_submit_save">
          <input class="submit" type="submit" name="change_area" value="<?php echo get_vocab("change") ?>">
        </div>
      </fieldset>
    
    </fieldset>
  </form>
  <?php
}

require_once "trailer.inc" ?>
