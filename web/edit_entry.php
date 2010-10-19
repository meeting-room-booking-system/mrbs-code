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

global $twentyfourhour_format;

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
        $start_day   = strftime('%d', $row['start_time']);
        $start_month = strftime('%m', $row['start_time']);
        $start_year  = strftime('%Y', $row['start_time']);
        $start_hour  = strftime('%H', $row['start_time']);
        $start_min   = strftime('%M', $row['start_time']);
        break;
        
      case 'end_time':
        $duration = $row['end_time'] - $row['start_time'] - cross_dst($row['start_time'], $row['end_time']);
        break;
        
      default:
        $custom_fields[$column] = ($keep_private && $is_private_field["entry.$column"]) ? '' : $row[$column];
        break;
    }
  }
  

  if($entry_type >= 1)
  {
    $sql = "SELECT rep_type, start_time, end_date, rep_opt, rep_num_weeks
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
      // but don't overwrite the start time if we're not editing the series
      if ($edit_type == "series")
      {
        $start_day   = (int)strftime('%d', $row['start_time']);
        $start_month = (int)strftime('%m', $row['start_time']);
        $start_year  = (int)strftime('%Y', $row['start_time']);
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
  $start_day   = $day;
  $start_month = $month;
  $start_year  = $year;
  // Avoid notices for $hour and $minute if periods is enabled
  (isset($hour)) ? $start_hour = $hour : '';
  (isset($minute)) ? $start_min = $minute : '';
  if (!isset($default_duration))
  {
    $default_duration = (60 * 60);
  }
  $duration    = ($enable_periods ? 60 : $default_duration);
  $type        = "I";
  $room_id     = $room;
  unset($id);

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
}

// These next 4 if statements handle the situation where
// this page has been accessed directly and no arguments have
// been passed to it.
// If we have not been provided with a room_id
if (empty( $room_id ) )
{
  $sql = "select id from $tbl_room limit 1";
  $res = sql_query($sql);
  $row = sql_row_keyed($res, 0);
  $room_id = $row['id'];

}

// If we have not been provided with starting time
if ( empty( $start_hour ) && $morningstarts < 10 )
{
  $start_hour = "0$morningstarts";
}

if ( empty( $start_hour ) )
{
  $start_hour = "$morningstarts";
}

if ( empty( $start_min ) )
{
  $start_min = "00";
}

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

?>

<script type="text/javascript">
//<![CDATA[

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
  <?php if( ! $enable_periods ) { ?>

  h = parseInt(form.hour.value);
  m = parseInt(form.minute.value);

  if(h > 23 || m > 59)
  {
    alert ("<?php echo get_vocab("you_have_not_entered") . '\n' . get_vocab("valid_time_of_day") ?>");
    return false;
  }
  <?php } ?>

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
    for (i = 0; i < mandatory_fields.length; i++)
    {
      if (form[mandatory_fields[i]].value == '')
      {
        label = $("label[for="+mandatory_fields[i]+"]").html();
        label = label.replace(/:$/, '');
        alert('"' + label + '" ' +
              <?php echo '"'.
                         str_replace('"', '\\"',
                                     get_vocab("is_mandatory_field")
                                    ).
                         '"'; ?>);
        return false;
      }
    }
  <?php
   
  }
  ?>

  // Form submit can take some times, especially if mails are enabled and
  // there are more than one recipient. To avoid users doing weird things
  // like clicking more than one time on submit button, we hide it as soon
  // it is clicked.
  form.save_button.disabled="true";

  // would be nice to also check date to not allow Feb 31, etc...

  return true;
}

// set up some global variables for use by OnAllDayClick().   (It doesn't really
// matter about the initial values, but we might as well put in some sensible ones).
var old_duration = '<?php echo $duration;?>';
var old_dur_units = 0;  // This is the index number
var old_hour = '<?php if (!$twentyfourhour_format && ($start_hour > 12)){ echo ($start_hour - 12);} else { echo $start_hour;} ?>';
var old_minute = '<?php echo $start_min;?>';
var old_period = 0; // This is the index number

// Executed when the user clicks on the all_day checkbox.
function OnAllDayClick(allday)
{
  var form = document.forms["main"];
  if (form.all_day.checked) // If checking the box...
  {
    // save the old values, disable the inputs and, to avoid user confusion,
    // show the start time as the beginning of the day and the duration as one day
    <?php 
    if ($enable_periods )
    {
      ?>
      old_period = form.period.selectedIndex;
      form.period.value = 0;
      form.period.disabled = true;
      <?php
    }
    else
    { 
      ?>
      old_hour = form.hour.value;
      form.hour.value = '<?php echo $morningstarts; ?>';
      old_minute = form.minute.value;
      form.minute.value = '<?php printf("%02d", $morningstarts_minutes); ?>';
      form.hour.disabled = true;
      form.minute.disabled = true;
      <?php 
    } 
    ?>
    
    old_duration = form.duration.value;
    form.duration.value = '1';  
    old_dur_units = form.dur_units.selectedIndex;
    form.dur_units.value = 'days';  
    form.duration.disabled = true;
    form.dur_units.disabled = true;
  }
  else  // restore the old values and re-enable the inputs
  {
    <?php 
    if ($enable_periods)
    {
      ?>
      form.period.selectedIndex = old_period;
      form.period.disabled = false;
      <?php
    }
    else
    { 
      ?>
      form.hour.value = old_hour;
      form.minute.value = old_minute;
      form.hour.disabled = false;
      form.minute.disabled = false;
      <?php 
    } 
    ?>
    form.duration.value = old_duration;
    form.dur_units.selectedIndex = old_dur_units;  
    form.duration.disabled = false;
    form.dur_units.disabled = false;
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
    ?>

    <div id="div_date">
      <?php
      echo "<label for=\"start_datepicker\">" . get_vocab("date") . ":</label>\n";
      gendateselector("start_", $start_day, $start_month, $start_year);
      ?>
    </div>

    <?php 
    if(! $enable_periods ) 
    { 
      echo "<div class=\"div_time\">\n";
      echo "<label>" . get_vocab("time") . ":</label>\n";
      echo "<input type=\"text\" class=\"time_hour\" name=\"hour\" value=\"";
      if ($twentyfourhour_format)
      {
        echo $start_hour;
      }
      elseif ($start_hour > 12)
      {
        echo ($start_hour - 12);
      } 
      elseif ($start_hour == 0)
      {
        echo "12";
      }
      else
      {
        echo $start_hour;
      } 
      echo "\" maxlength=\"2\">\n";
      echo "<span>:</span>\n";
      echo "<input type=\"text\" class=\"time_minute\" name=\"minute\" value=\"" . $start_min . "\" maxlength=\"2\">\n";
      if (!$twentyfourhour_format)
      {
        echo "<div class=\"group ampm\">\n";
        $checked = ($start_hour < 12) ? "checked=\"checked\"" : "";
        echo "      <label><input name=\"ampm\" type=\"radio\" value=\"am\" $checked>" . utf8_strftime("%p",mktime(1,0,0,1,1,2000)) . "</label>\n";
        $checked = ($start_hour >= 12) ? "checked=\"checked\"" : "";
        echo "      <label><input name=\"ampm\" type=\"radio\" value=\"pm\" $checked>". utf8_strftime("%p",mktime(13,0,0,1,1,2000)) . "</label>\n";
        echo "</div>\n";
      }
      echo "</div>\n";
    }
    
    else
    {
      ?>
      <div id="div_period">
        <label for="period" ><?php echo get_vocab("period")?>:</label>
        <select id="period" name="period">
          <?php
          foreach ($periods as $p_num => $p_val)
          {
            echo "<option value=\"$p_num\"";
            if( ( isset( $period ) && $period == $p_num ) || $p_num == $start_min)
            {
              echo " selected=\"selected\"";
            }
            echo ">$p_val</option>\n";
          }
          ?>
        </select>
      </div>

    <?php
    }
    ?>
    <div id="div_duration">
      <label for="duration"><?php echo get_vocab("duration");?>:</label>
      <div class="group">
        <input id="duration" name="duration" value="<?php echo $duration;?>">
        <select id="dur_units" name="dur_units">
          <?php
          if( $enable_periods )
          {
            $units = array("periods", "days");
          }
          else
          {
            $units = array("minutes", "hours", "days", "weeks", "years");
          }

          while (list(,$unit) = each($units))
          {
            echo "        <option value=\"$unit\"";
            if ($dur_units == get_vocab($unit))
            {
              echo " selected=\"selected\"";
            }
            echo ">".get_vocab($unit)."</option>\n";
          }
          ?>
        </select>
        <div id="ad">
          <input id="all_day" class="checkbox" name="all_day" type="checkbox" value="yes" onclick="OnAllDayClick(this)">
          <label for="all_day"><?php echo get_vocab("all_day"); ?></label>
        </div>
      </div>
    </div>
    
    <div id="div_areas">
    </div>

    <?php
    // Determine the area id of the room in question first
    $area_id = mrbsGetRoomArea($room_id);
    // determine if there is more than one area
    $sql = "select id from $tbl_area";
    $res = sql_query($sql);
    $num_areas = sql_count($res);
    // if there is more than one area then give the option
    // to choose areas.
    if( $num_areas > 1 )
    {
    
    ?>
    
      <script type="text/javascript">
      //<![CDATA[
      
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
          // get the area id for case statement
          $sql = "select id, area_name from $tbl_area order by area_name";
          $res = sql_query($sql);
          if ($res)
          {
            for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
            {
              print "      case \"".$row['id']."\":\n";
              // get rooms for this area
              $sql2 = "select id, room_name from $tbl_room where area_id='".$row['id']."' order by sort_key";
              $res2 = sql_query($sql2);
              if ($res2)
              {
                for ($j = 0; ($row2 = sql_row_keyed($res2, $j)); $j++)
                {
                  $clean_room_name = str_replace('\\', '\\\\', $row2['room_name']);  // escape backslash
                  $clean_room_name = str_replace('"', '\\"', $clean_room_name);      // escape double quotes
                  $clean_room_name = str_replace('/', '\\/', $clean_room_name);      // prevent '/' being parsed as markup (eg </p>)
                  print "        roomsObj.options[$j] = new Option(\"".$clean_room_name."\",".$row2['id'] .");\n";
                }
                // select the first entry by default to ensure
                // that one room is selected to begin with
                if ($j > 0)  // but only do this if there is a room
                {
                  print "        roomsObj.options[0].selected = true;\n";
                }
                print "        break;\n";
              }
            }
          }
          ?>
        } //switch
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
      // get list of areas
      $sql = "select id, area_name from $tbl_area order by area_name";
      $res = sql_query($sql);
      if ($res)
      {
        for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
        {
          ?>
          option = document.createElement('option');
          option.value = <?php echo $row['id'] ?>;
          option_text = document.createTextNode('<?php echo $row['area_name'] ?>');
          <?php
          if ($row['id'] == $area_id)
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
      }
      ?>
      // insert the <select> which we've just assembled into the <div>
      div_areas.appendChild(area_select);
      
      //]]>
      </script>
      
      
      <?php
    } // if $num_areas
    ?>
    
    
    <div id="div_rooms">
    <label for="rooms"><?php echo get_vocab("rooms") ?>:</label>
    <div class="group">
      <select id="rooms" name="rooms[]" multiple="multiple" size="5">
        <?php 
        // select the rooms in the area determined above
        $sql = "select id, room_name from $tbl_room where area_id=$area_id order by sort_key";
        $res = sql_query($sql);
        if ($res)
        {
          for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
          {
            $selected = "";
            if ($row['id'] == $room_id)
            {
              $selected = "selected=\"selected\"";
            }
            echo "              <option $selected value=\"" . $row['id'] . "\">" . htmlspecialchars($row['room_name']) . "</option>\n";
            // store room names for emails
            $room_names[$i] = $row['room_name'];
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
            echo "        <option value=\"$c\"" . ($type == $c ? " selected=\"selected\"" : "") . ">$typel[$c]</option>\n";
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
