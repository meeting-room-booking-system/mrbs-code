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
// the form in the appropriate lang file(s) using the tag 'entry.[columnname]'.
// (Note that it is not necessary to add a 'repeat.[columnname]' tag.   The 
// entry tag is sufficient.)
//
// For example if you want to add a column recording the number of participants
// you could add a column to the entry and repeat tables called 'participants'
// of type int.  Then in the appropriate lang file(s) you would add the line
//
// vocab["entry.participants"] = "Participants";  // or appropriate translation
//
// If MRBS can't find an entry for the field in the lang file, then it will use
// the fieldname, eg 'coffee_machine'. 


require_once "defaultincludes.inc";
require_once "mrbs_sql.inc";

// Generate a time or period selector starting with $first and ending with $last.
// $time is a full Unix timestamp and is the current value.  The selector returns
// the start time in seconds since the beginning of the day for the start of that slot
// The $display parameter sets the display style of the <select>
function genslotselector($area, $prefix, $first, $last, $time, $display="block")
{
  global $twentyfourhour_format, $periods;
  
  $html = '';
  // Get the settings for this area.   Note that the variables below are
  // local variables, not globals.
  $enable_periods = $area['enable_periods'];
  $resolution = ($enable_periods) ? 60 : $area['resolution'];
  // If they've asked for "display: none" then we'll also disable the select so
  // hat there is only one select passing through the variable to the handler
  $disabled = (strtolower($display) == "none") ? " disabled=\"disabled\"" : "";
  
  $date = getdate($time);
  $time_zero = mktime(0, 0, 0, $date['mon'], $date['mday'], $date['year']);
  if ($enable_periods)
  {
    $base = 12*60*60;  // The start of the first period of the day
  }
  else
  {
    $format = ($twentyfourhour_format) ? "%R" : "%l:%M %P";
  }
  $html .= "<select style=\"display: $display\" id = \"${prefix}seconds${area['id']}\" name=\"${prefix}seconds\" onChange=\"adjustSlotSelectors(this.form)\"$disabled>\n";
  for ($t = $first; $t <= $last; $t = $t + $resolution)
  {
    $timestamp = $t + $time_zero;
    $slot_string = ($enable_periods) ? $periods[intval(($t-$base)/60)] : utf8_strftime($format, $timestamp);
    $html .= "<option value=\"$t\"";
    $html .= ($timestamp == $time) ? " selected=\"selected\"" : "";
    $html .= ">$slot_string</option>\n";
  }
  $html .= "</select>\n";
  echo $html;
}

// Get non-standard form variables
$hour = get_form_var('hour', 'int');
$minute = get_form_var('minute', 'int');
$period = get_form_var('period', 'int');
$id = get_form_var('id', 'int');
$copy = get_form_var('copy', 'int');
$edit_type = get_form_var('edit_type', 'string');
$returl = get_form_var('returl', 'string');

if (!isset($edit_type))
{
  $edit_type = "";
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
    fatal_error(1, sql_error());
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
        
      case 'name':
      case 'description':
      case 'type':
      case 'room_id':
      case 'entry_type':
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
  

  if($entry_type >= 1)
  {
    $sql = "SELECT rep_type, start_time, end_time, end_date, rep_opt, rep_num_weeks
              FROM $tbl_repeat 
             WHERE id=$rep_id
             LIMIT 1";
   
    $res = sql_query($sql);
    if (! $res)
    {
      fatal_error(1, sql_error());
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

      switch ($rep_type)
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
          // Get the repeat days as an array for use
          // when the input is disabled
          $rep_opt = $row['rep_opt'];

          if ($rep_type == REP_N_WEEKLY)
          {
            $rep_num_weeks = $row['rep_num_weeks'];
          }

          break;

        default:
          $rep_day = array(0, 0, 0, 0, 0, 0, 0);
      }
    }
  }
}
else
{
  // It is a new booking. The data comes from whichever button the user clicked
  $edit_type   = "series";
  $name        = "";
  $create_by   = $user;
  $description = "";
  $type        = "I";
  $room_id     = $room;
  $rep_id        = 0;
  $rep_type      = REP_NONE;
  $rep_end_day   = $day;
  $rep_end_month = $month;
  $rep_end_year  = $year;
  $rep_day       = array(0, 0, 0, 0, 0, 0, 0);
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

  if (!isset($default_duration))
  {
    $default_duration = (60 * 60);
  }
  $duration    = ($enable_periods ? 60 : $default_duration);
  $end_time = $start_time + $duration;
}

$start_hour  = strftime('%H', $start_time);
$start_min   = strftime('%M', $start_time);

// These next 4 if statements handle the situation where
// this page has been accessed directly and no arguments have
// been passed to it.
// If we have not been provided with a room_id
if (empty( $room_id ) )
{
  $sql = "SELECT id FROM $tbl_room LIMIT 1";
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

// Get the details of all the rooms
$rooms = array();
$sql = "SELECT id, room_name, area_id
          FROM $tbl_room
      ORDER BY area_id, sort_key";
$res = sql_query($sql);
if ($res)
{
  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    $rooms[$row['id']] = $row;
  }
}
    
// Get the details of all the areas
$areas = array();
$sql = "SELECT id, area_name, resolution, default_duration, enable_periods,
               morningstarts, morningstarts_minutes, eveningends , eveningends_minutes
          FROM $tbl_area
      ORDER BY area_name";
$res = sql_query($sql);
if ($res)
{
  for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
  {
    $areas[$row['id']] = $row;
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
    if ($key == "area_name")
    {
      // Enclose strings in quotes
      $value = "'$value'";
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
function validate(form)
{
  <?php
  // First of all check that a name (brief description) has been entered.
  // Only do this if the name is being entered via an INPUT box.   If it's
  // being entered via a SELECT box there's no need to do this because there's
  // bound to be a value and the test below will fail on some browsers (eg IE)
  ?>
  if (form.name.tagName.toLowerCase() == 'input')
  {
    // null strings and spaces only strings not allowed
    if(/(^$)|(^\s+$)/.test(form.name.value))
    {
      alert ( "<?php echo get_vocab("you_have_not_entered") . '\n' . get_vocab("brief_description") ?>");
      return false;
    }
  }
  
  <?php
  // Check that the start date is not after the end date
  ?>
  var dateDiff = getDateDifference(form);
  if (dateDiff < 0)
  {
    alert('<?php echo get_vocab("start_after_end_long")?>');
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
    alert("<?php echo get_vocab("you_have_not_entered") . '\n' . get_vocab("useful_n-weekly_value") ?>");
    return false;
  }
  

  // check that a room(s) has been selected
  // this is needed as edit_entry_handler does not check that a room(s)
  // has been chosen
  if (form.elements['rooms'].selectedIndex == -1 )
  {
    alert("<?php echo get_vocab("you_have_not_selected") . '\n' . get_vocab("valid_room") ?>");
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
             if ($("#"+value).val() == '')
             {
               label = $("label[for="+value+"]").html();
               label = label.replace(/:$/, '');
               alert('"' + label + '" ' +
                 <?php echo '"'.
                         str_replace('"', '\\"',
                                     get_vocab("is_mandatory_field")
                                    ).
                         '"'; ?>);
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
  form.save_button.disabled="true";

  // would be nice to also check date to not allow Feb 31, etc...

  return true;
}

// set up some global variables for use by OnAllDayClick(). 
var old_start, old_end;

// Executed when the user clicks on the all_day checkbox.
function OnAllDayClick(allday)
{
  var form = document.forms["main"];
  if (form)
  {
    var startSelect = form["start_seconds" + currentArea];
    var endSelect = form["end_seconds" + currentArea];
    var i;
    if (form.all_day.checked) // If checking the box...
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


<form class="form_general" id="main" action="edit_entry_handler.php" method="post" onsubmit="return validate(this)">
  <fieldset>
  <legend><?php echo get_vocab($token); ?></legend>

    <?php  
    echo "<div id=\"div_name\">\n";
    $label_text = get_vocab("namebooker") . ":";
    if (count($select_options['entry.name']) > 0)
    {
      generate_select($label_text, 'name', $name, $select_options['entry.name']);  
    }
    else
    {
      generate_input($label_text, 'name', $name, FALSE, $maxlength['entry.name']);
    }
    echo "</div>\n";
    
    echo "<div id=\"div_description\">\n";
    $label_text = get_vocab("fulldescription");
    if (count($select_options['entry.description']) > 0)
    {
      generate_select($label_text, 'description', $description, $select_options['entry.description']);
    }
    else
    {
      generate_textarea($label_text, 'description', $description);
    }
    echo "</div>\n";


    echo "<div id=\"div_start_date\">\n";
    echo "<label for=\"start_datepicker\">" . get_vocab("start") . ":</label>\n";
    $date = getdate($start_time);
    gendateselector("start_", $date['mday'], $date['mon'], $date['year']);
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
      $display = ($a['id'] == $area_id) ? "block" : "none";
      genslotselector($a, "start_", $first, $start_last, $start_time, $display);
    }

    ?>
    <div class="group">
      <div id="ad">
        <input id="all_day" class="checkbox" name="all_day" type="checkbox" value="yes" onclick="OnAllDayClick(this)">
        <label for="all_day"><?php echo get_vocab("all_day"); ?></label>
      </div>
    </div>
    <?php
    echo "</div>\n";
    
    echo "<div id=\"div_end_date\">\n";
    echo "<label for=\"start_datepicker\">" . get_vocab("end") . ":</label>\n";
    $date = getdate($end_time);
    // Don't show the end date selector if multiday is not allowed
    echo "<div" . (($multiday_allowed) ? '' : " style=\"visibility: hidden\"") . ">\n";
    gendateselector("end_", $date['mday'], $date['mon'], $date['year']);
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
      $display = ($a['id'] == $area_id) ? "block" : "none";
      genslotselector($a, "end_", $first, $last, $end_value, $display);
    }
    echo "</div>\n";
    
    ?>  
    <div id="div_areas">
    </div>

    <?php   
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
                $clean_room_name = str_replace('\\', '\\\\', $r['room_name']);  // escape backslash
                $clean_room_name = str_replace('"', '\\"', $clean_room_name);      // escape double quotes
                $clean_room_name = str_replace('/', '\\/', $clean_room_name);      // prevent '/' being parsed as markup (eg </p>)
                print "roomsObj.options[$i] = new Option(\"" . $clean_room_name . "\"," . $r['id'] . ");\n";
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
        ?>
        var oldStartId = "start_seconds" + currentArea;
        var oldEndId = "end_seconds" + currentArea;
        var newStartId = "start_seconds" + area;
        var newEndId = "end_seconds" + area;
        var oldAreaStartValue = formObj[oldStartId].options[formObj[oldStartId].selectedIndex].value;
        var oldAreaEndValue = formObj[oldEndId].options[formObj[oldEndId].selectedIndex].value;
        $("#" + oldStartId).css({display: "none"});
        $("#" + oldStartId).attr('disabled', 'disabled');
        $("#" + oldEndId).css({display: "none"});
        $("#" + oldEndId).attr('disabled', 'disabled');
        $("#" + newStartId).css({display: "block"});
        $("#" + newStartId).removeAttr('disabled');
        $("#" + newEndId).css({display: "block"});
        $("#" + newEndId).removeAttr('disabled');
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
        option_text = document.createTextNode('<?php echo $a['area_name'] ?>');
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
      
      //]]>
      </script>
      
      
      <?php
    } // if count($areas)
    ?>
    
    
    <div id="div_rooms">
    <label for="rooms"><?php echo get_vocab("rooms") ?>:</label>
    <div class="group">
      <select id="rooms" name="rooms[]" multiple="multiple" size="5">
        <?php 
        foreach ($rooms as $r)
        {
          if ($r['area_id'] == $area_id)
          {
            $selected = ($r['id'] == $room_id) ? "selected=\"selected\"" : "";
            echo "<option $selected value=\"" . $r['id'] . "\">" . htmlspecialchars($r['room_name']) . "</option>\n";
            // store room names for emails
            $room_names[$i] = $r['room_name'];
          }
        }
        ?>
      </select>
      <span><?php echo get_vocab("ctrl_click") ?></span>
      </div>
    </div>
    <div id="div_type">
      <label for="type"><?php echo get_vocab("type")?>:</label>
      <select id="type" name="type">
        <?php
        for ($c = "A"; $c <= "Z"; $c++)
        {
          if (!empty($typel[$c]))
          { 
            echo "<option value=\"$c\"" . ($type == $c ? " selected=\"selected\"" : "") . ">$typel[$c]</option>\n";
          }
        }
        ?>
      </select>
    </div>
    
    <?php
    // Status
    if ($private_enabled || $confirmation_enabled) 
    { 
      echo "<div id=\"div_status\">\n";
      echo "<label>" . get_vocab("status") . ":</label>\n";
      echo "<div class=\"group\">\n";
      
      // Privacy status
      if ($private_enabled)
      {    
        echo "<input id=\"private\" class=\"checkbox\" name=\"private\" type=\"checkbox\" value=\"yes\"";
        if ($private) 
        {
          echo " checked=\"checked\"";
        }
        if ($private_mandatory) 
        {
          echo " disabled=\"true\"";
        }
        echo ">\n";
        echo "<label for=\"private\">" . get_vocab("private") . "</label>\n";
      }
      
      // Confirmation status
      if ($confirmation_enabled)
      {
        echo "<input id=\"confirmed\" class=\"checkbox\" name=\"confirmed\" type=\"checkbox\" value=\"yes\"";
        if ($confirmed) 
        {
          echo " checked=\"checked\"";
        }
        echo ">\n";
        echo "<label for=\"confirmed\">" . get_vocab("confirmed") . "</label>\n";
      }

      echo "</div>\n";
      echo "</div>\n";
    }

    
    // CUSTOM FIELDS

    foreach ($fields as $field)
    {
      $key = $field['name'];
      if (!in_array($key, $standard_fields['entry']))
      {
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
                ">\n";
        }
        // Output a select box if they want one
        elseif (count($select_options["entry.$key"]) > 0)
        {
          $mandatory = (array_key_exists("entry.$key", $is_mandatory_field) &&
                        $is_mandatory_field["entry.$key"]) ? true : false;
          generate_select($label_text, $var_name, $value,
                          $select_options["entry.$key"], $mandatory);
        }
        // Output a textarea if it's a character string longer than the limit for a
        // text input
        elseif (($field['nature'] == 'character') && isset($field['length']) && ($field['length'] > $text_input_max))
        {
          generate_textarea($label_text, $var_name, $value);   
        }
        // Otherwise output a text input
        else
        {
          generate_input($label_text, $var_name, $value);
        }
        echo "</div>\n";
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
        echo "<label for=\"rep_end_datepicker\">" . get_vocab("rep_end_date") . ":</label>\n";
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
            echo "      <label><input class=\"checkbox\" name=\"rep_day[$wday]\" type=\"checkbox\"";
            if ($rep_day[$wday])
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
    <input type="hidden" name="create_by" value="<?php echo $create_by?>">
    <input type="hidden" name="rep_id" value="<?php echo $rep_id?>">
    <input type="hidden" name="edit_type" value="<?php echo $edit_type?>">
    <?php 
    if(isset($id) && !isset($copy))
    {
      echo "<input type=\"hidden\" name=\"id\" value=\"$id\">\n";
    }

    // The Submit button
    echo "<div id=\"edit_entry_submit\">\n";
    echo "<input class=\"submit\" type=\"submit\" name=\"save_button\" value=\"" . get_vocab("save") . "\">\n";
    echo "</div>\n";
    ?>
  </fieldset>
</form>

<?php require_once "trailer.inc" ?>
