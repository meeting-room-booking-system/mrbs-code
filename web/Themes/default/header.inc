<?php

// $Id$

// Print the page header
function print_theme_header($day, $month, $year, $area, $room)
{
  global $mrbs_company, $mrbs_company_logo, $mrbs_company_url, $mrbs_company_more_info,
         $search_str, $locale_warning;
  global $tbl_entry, $tbl_room, $tbl_area;
  global $PHP_SELF, $view_week_number, $weekstarts;
  global $auth;
  global $default_language_tokens, $disable_automatic_language_changing, $override_locale;
  global $lang_map_windows, $langs;
  
  $page = basename($PHP_SELF, ".php" );
  $user = getUserName();
  $is_admin = (authGetUserLevel($user) >= 2);

  // If we dont know the right date then make it up 
  if (!$day)
  {
    $day   = date("d");
  }
  if (!$month)
  {
    $month = date("m");
  }
  if (!$year)
  {
    $year  = date("Y");
  }
  if (empty($search_str))
  {
    $search_str = "";
  }
  
  header("Content-Type: text/html; charset=" . get_charset());
  header("Pragma: no-cache");                          // HTTP 1.0
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
  echo DOCTYPE;
?>

<html>
  <head>
<?php
  require_once "style.inc";
?>
    <title><?php echo get_vocab("mrbs") ?></title>
    
    <?php
    // We are using the jQuery UI library, which has been custom built to include the following components:
    //   - Core
    //   - Widget
    //   - Mouse
    //   - Draggable
    //   - Datepicker
    // If you need any other components you will need to rebuild the custom UI library
    // See http://jqueryui.com/
    ?>
    
    <script type="text/javascript" src="jquery/jquery-1.4.2.min.js"></script>
    <script type="text/javascript" src="jquery/ui/jquery-ui-1.8.4.custom.min.js"></script>
    <script type="text/javascript" src="jquery/ui/jquery-ui-i18n.js"></script>
    <?php
    // The Portuguese (-pt) file below is a non-standard addition to the regional
    // variants.   The standard package only has a pt-BR variant at the moment.
    // Similarly the English (-en) and English-US (-en-US) files, which do not
    // exist explicitly.   The files have been kept separate to avoid problems with
    // merging new releases of UI
    ?>
    <script type="text/javascript" src="jquery/ui/jquery-ui-datepicker-en.js"></script>
    <script type="text/javascript" src="jquery/ui/jquery-ui-datepicker-en-US.js"></script>
    <script type="text/javascript" src="jquery/ui/jquery-ui-datepicker-pt.js"></script>
    
    <script type="text/javascript">

//<![CDATA[
<?php
// Set the default values for datepicker, including the default regional setting
?>
$(function() {
  <?php
  // We set the regional setting by setting locales in reverse order of priority.
  // If you try and set a datepicker locale that doesn't exist, then nothing is
  // changed and the regional setting stays as it was before.   The reverse order
  // of priority is:
  // - the MRBS default language
  // - locales taken from the browser in increasing order of browser preference,
  //   taking for each locale
  //     - the language part only (in case the xx-YY localisation does not exist)
  //     - the full locale
  // - then, if automatic language changing is disabled, 
  //      - the MRBS default language setting again
  //      - the language part of the override_locale
  //      - the full override_locale
  // This algorithm is designed to ensure that datepicker is set to the closest
  // available locale to that specified in the config file.   If automatic language
  // changing is disabled, we fall back to a browser specified locale if the locale
  // in the config file is not available in datepicker.
  
  $default_lang = locale_format($default_language_tokens, '-');
  
  echo "$.datepicker.setDefaults($.datepicker.regional['$default_lang']);\n";
  $datepicker_langs = $langs;
  asort($datepicker_langs, SORT_NUMERIC);
  foreach ($datepicker_langs as $lang => $qual)
  {
    // Get the locale in the format that datepicker likes: language lower case
    // and country upper case (xx-XX)
    $datepicker_locale = locale_format($lang, '-');
    // First we'll try and get the correct language and then we'll try and
    // overwrite that with the correct country variant
    if (strlen($datepicker_locale) > 2)
    {
      $datepicker_lang = substr($datepicker_locale, 0, 2);
      echo "$.datepicker.setDefaults($.datepicker.regional['$datepicker_lang']);\n";
    }
    echo "$.datepicker.setDefaults($.datepicker.regional['$datepicker_locale']);\n";
  }
  if ($disable_automatic_language_changing)
  {
    // They don't want us to use the browser language, so we'll set the datepicker
    // locale setting back to the default language (as a fall-back) and then we'll
    // try and set it to the override_locale
    echo "$.datepicker.setDefaults($.datepicker.regional['$default_lang']);\n";
    if (!empty($override_locale))
    {
      $server_os = get_server_os();
      if ($server_os == 'windows')
      {
        // If the server is running on Windows we'll have to try and translate the 
        // Windows style locale back into an xx-YY locale
        $datepicker_locale = array_search($override_locale, $lang_map_windows);
      }
      else
      {
        $datepicker_locale = $override_locale;
      }
      if (!empty($datepicker_locale))  // in case the array_search() returned FALSE
      {
        $datepicker_locale = locale_format($datepicker_locale, '-');
        $datepicker_locale = substr($datepicker_locale, 0, 5);  // strip off anything after the country (eg charset)
        $datepicker_lang = substr($datepicker_locale, 0, 2);
        // First we'll try and get the correct language and then we'll try and
        // overwrite that with the correct country variant
        echo "$.datepicker.setDefaults($.datepicker.regional['$datepicker_lang']);\n";
        echo "$.datepicker.setDefaults($.datepicker.regional['$datepicker_locale']);\n";
      }
    }
  }
  ?>
  $.datepicker.setDefaults({
    showOtherMonths: true,
    selectOtherMonths: true,
    changeMonth: true,
    changeYear: true,
    duration: 'fast',
    showWeek: <?php echo ($view_week_number) ? 'true' : 'false' ?>,
    firstDay: <?php echo $weekstarts ?>,
    altFormat: 'yy-mm-dd',
    onClose: function(dateText, inst) {datepicker_close(dateText, inst);}
  });
});


<?php
// Writes out the day, month and year values to the three hidden inputs
// created by the PHP function genDateSelector().    It gets the date values
// from the _alt input, which is the alternate field populated by datepicker
// and is populated by datepicker with a date in yy-mm-dd format.
//
// (datepicker can only have one alternate field, which is why we need to write
// to the three fields ourselves).
//
// Blur the datepicker input field on close, so that the datepicker will reappear
// if you select it.    (Not quite sure why you need this.  It only seems
// to be necessary when you are using Firefox and the datepicker is draggable).
//
// Then go and adjust the start and end time/period select options, because
// they are dependent on the start and end dates
//
// Finally, if formId is defined, submit the form
?>
function datepicker_close(dateText, inst, formId)
{
  var alt_id = inst.id + '_alt';
  var date = document.getElementById(alt_id).value.split('-');
  document.getElementById(alt_id + '_year').value  = date[0];
  document.getElementById(alt_id + '_month').value = date[1];
  document.getElementById(alt_id + '_day').value   = date[2];
  document.getElementById(inst.id).blur();
  <?php
  if ($page == 'edit_entry')
  {
    ?>
    adjustSlotSelectors(document.getElementById('main'));
    <?php
  }
  ?>
  if (formId)
  {
    var form = document.getElementById(formId);
    form.submit();
  }
}



<?php
if ($page == 'edit_area_room')
{
?>
  var enablePeriods;
  
  function toggleMode(form, speed)
  {
    if (speed === undefined)
    {
      speed = 'slow';
    }
    if (form.area_enable_periods[0].checked != enablePeriods)
    {
      enablePeriods = !enablePeriods;
      $('#time_settings').animate({
        opacity : 'toggle',
        height: 'toggle'
        }, speed);
    }
  }
  
  // disable the min and max book ahead values and units if the corresponding
  // checkbox isn't checkd;  and enable it it is.
  function check_book_ahead()
  {
    var form = document.getElementById('edit_area');
    
    if (form)
    {
      if (form.area_min_ba_enabled)
      {
        form.area_min_ba_value.disabled = !form.area_min_ba_enabled.checked;
        form.area_min_ba_units.disabled = !form.area_min_ba_enabled.checked;
      }
      if (form.area_max_ba_enabled)
      {
        form.area_max_ba_value.disabled = !form.area_max_ba_enabled.checked;
        form.area_max_ba_units.disabled = !form.area_max_ba_enabled.checked;
      }
    }
  }
<?php
} // edit_area_room


if ($page == 'edit_entry')
{
  // Declare some variables to hold details of the slot selectors for each area.
  // We are going to store the contents of the selectors on page load
  // (when they will be fully populated with options) so that we can
  // rebuild the arrays later
  // Also declare a variable to hold text strings with the current
  // locale translations for periods,minutes, hours, etc.
  // The nStartOptions and nEndOptions array are indexed by area id
  // The startOptions and endOptions are multi-dimensional arrays indexed as follows:
  // [area_id][option number][text|value]
  ?>
  var nStartOptions = new Array;  
  var nEndOptions = new Array;
  var startOptions = new Array();
  var endOptions = new Array();
  var vocab = new Array();
  var prevStartValue;

  function durFormat(r)
  {
    r = r.toFixed(2);
    r = parseFloat(r);
    r = r.toLocaleString();

    if ((r.indexOf('.') >= 0) || (r.indexOf(',') >= 0))
    {
      while (r.substr(r.length -1) == '0')
      {
        r = r.substr(0, r.length - 1);
      }

      if ((r.substr(r.length -1) == '.') || (r.substr(r.length -1) == ','))
      {
        r = r.substr(0, r.length - 1);
      }
    }
    
    return r;
  }
  
  <?php
  // Returns a string giving the duration having chosen sensible units,
  // translated into the user's language, and formatted the number, taking
  // into account the user's locale.    Note that when using periods one
  // is added to the duration because the model is slightly different
  //   - from   the start time (in seconds since the start of the day
  //   - to     the end time (in seconds since the start of the day)
  //   - days   the number of days difference
  ?>
  function getDuration(from, to, days)
  {
    var duration, durUnits;
    var text = '';
    var enablePeriods = areas[currentArea]['enable_periods'];

    durUnits = (enablePeriods) ? '<?php echo "periods" ?>' : '<?php echo "minutes" ?>';
    duration = to - from;
    duration = Math.floor((to - from) / 60);
    
    if (duration < 0)
    {
      days--;
      if (enablePeriods)
      {
        duration += nEndOptions[currentArea];  <?php // add a day's worth of periods ?>
      }
      else
      {
        duration += 24*60;  <?php // add 24 hours (duration is now in minutes)  ?>
      }
    }
      
    if (enablePeriods)
    {
      duration++;  <?php // a period is a period rather than a point ?>
    }
    else
    {
      if (duration >= 60)
      {
        durUnits = "hours";
        duration = durFormat(duration/60);
      }
    }
    
    if (days != 0)
    {
      text += days + ' ';
      text += (days == 1) ? vocab['days']['singular'] : vocab['days']['plural'];
      if (duration != 0)
      {
        text +=  ', ';
      }
    }

    if (duration != 0)
    {
      text += duration + ' ';
      text +=(duration == 1) ? vocab[durUnits]['singular'] : vocab[durUnits]['plural'];
    }
    return text;
  }
  
  <?php
  // Returns the number of days between the start and end dates
  ?>
  function getDateDifference(form)
  {
    var diff;

    <?php
    if (!$is_admin && $auth['only_admin_can_book_multiday'])
    {
      ?>
      diff = 0;
      <?php
    }
    else
    {
      ?>
      var startDay = parseInt(form.start_datepicker_alt_day.value, 10);
      var startMonth = parseInt(form.start_datepicker_alt_month.value, 10);
      var startYear = parseInt(form.start_datepicker_alt_year.value, 10);
      var startDate = new Date(startYear, startMonth - 1, startDay, 12);
      
      var endDay = parseInt(form.end_datepicker_alt_day.value, 10);
      var endMonth = parseInt(form.end_datepicker_alt_month.value, 10);
      var endYear = parseInt(form.end_datepicker_alt_year.value, 10);
      var endDate = new Date(endYear, endMonth - 1, endDay, 12);
        
      diff = (endDate - startDate)/(24 * 60 * 60 * 1000);
      diff = Math.round(diff);
      <?php
    }
    ?>

    return diff;
  }
  
  function adjustSlotSelectors(form, oldArea, oldAreaStartValue, oldAreaEndValue)
  {
    <?php
    // Adjust the start and end time slot select boxes.
    // (a) If the start time has changed then adjust the end time so
    //     that the duration is still the same, provided that the endtime
    //     does not go past the start of the booking day
    // (b) If the end time has changed then adjust the duration.
    // (c) Make sure that you can't have an end time before the start time.
    // (d) Tidy up the two select boxes so that they are the same width
    // (e) if oldArea etc. are set, then we've switched areas and we want
    //     to have a go at finding a time/period in the new area as close
    //     as possible to the one that was selected in the old area.
    ?>
    
    if (!form)
    {
      return;
    }

    var area = currentArea;
    var enablePeriods = areas[area]['enable_periods'];

    var isSelected, i, j, option, defaultDuration;
    var nbsp = '\u00A0';
    var errorText = '<?php echo get_vocab("start_after_end")?>';
    var text = errorText;
    
    var startSelect = form["start_seconds" + area];
    var endSelect = form["end_seconds" + area];
    var startIndex, startValue, endIndex, endValue;
    
    <?php 
    // If All Day is checked then just set the start and end values to the first
    // and last possible options.
    ?>
    if (form.all_day.checked)
    {
      startValue = startOptions[area][0]['value']
      endValue = endOptions[area][nEndOptions[area] - 1]['value'];
      <?php
      // If we've come here from another area then we need to make sure that the
      // start and end selectors are disabled.  (We won't change the old_end and old_start
      // values, because there's a chance the existing ones may still work - for example if
      // the user flicks from Area A to Area B and then back to Area A, or else if the time/
      // period slots in Area B match those in Area.)
      ?>
      if (oldArea != null)
      {
        startSelect.disabled = true;
        endSelect.disabled = true;
      }
    }
    <?php
    // Otherwise what we do depends on whether we've come here as a result
    // of the area being changed
    ?>
    else if ((oldArea != null) && (oldAreaStartValue != null) && (oldAreaStartValue != null))
    {
      <?php 
      // If we've changed areas and the modes are the same, we can try and match times/periods.
      // We will try and be conservative and find a start time that includes the previous start time
      // and an end time that includes the previous end time.   This means that by default the 
      // booking period will include the old boioking period (unless we've hit the start or
      // end of day).   But it does mean that as you switch between areas the booking period
      // tends to get bigger:  if you switch fromn Area 1 to Area 2 and then bavk again it's
      // possible that the booking period for Area 1 is longer than it was originally.
      ?>
      if (areas[oldArea]['enable_periods'] == areas[area]['enable_periods'])
      {
        <?php
        // Step back through the start options until we find one that is less than or equal to the previous value,
        // or else we've got to the first option
        ?>
        option = startOptions[area];
        for (i = nStartOptions[area] - 1; i >= 0; i--)
        {
          if ((i == 0) || (option[i]['value'] <= oldAreaStartValue))
          {
            startValue = option[i]['value'];
            break;
          }
        }
        <?php
        // And step forward through the end options until we find one that is greater than
        // or equal to the previous value, or else we've got to the last option
        ?>
        option = endOptions[area];
        for (i = 0; i < nEndOptions[area]; i++)
        {
          if ((i == nEndOptions[area] - 1) || (option[i]['value'] >= oldAreaEndValue))
          {
            endValue = option[i]['value'];
            break;
          }
        }     
      }
      <?php
      // The modes are different, so it doesn't make any sense to match up old and new
      // times/periods.   The best we can do is choose some sensible defaults, which
      // is to set the start to the first possible start, and the end to the start + the
      // default duration (or the last possible end value if that is less)
      ?>
      else
      {
        startValue = startOptions[area][0]['value'];
        if (enablePeriods)
        {
          endValue = startValue;
        }
        else
        {
          if ((areas[area]['default_duration'] == null) || (areas[area]['default_duration'] == 0))
          {
            defaultDuration = 60 * 60;
          }
          else
          {
            defaultDuration = areas[area]['default_duration'];
          }
          endValue = startValue + defaultDuration;
          endValue = Math.min(endValue, endOptions[area][nEndOptions[area] - 1]['value']);
        }
      }
    }
    <?php 
    // We haven't changed areas.  In this case get the currently selected start and
    // end values
    ?>
    else  
    {
      startIndex = startSelect.selectedIndex;
      startValue = parseInt(startSelect.options[startIndex].value);
      endIndex = endSelect.selectedIndex;
      endValue = parseInt(endSelect.options[endIndex].value);
      <?php
      // If the start value has changed then we adjust the endvalue
      // to keep the duration the same.  (If the end value has changed
      // then the duration will be changed when we recalculate durations below)
      ?>
      if (prevStartValue)
      {
        endValue = endValue + (startValue - prevStartValue);
        endValue = Math.min(endValue, endOptions[area][nEndOptions[area] - 1]['value']);
      }
    }

    prevStartValue = startValue; <?php // Update the previous start value ?>
    
    var dateDifference = getDateDifference(form);
    
    <?php
    // If All Day isn't checked then we need to work out whether the start
    // and end dates are valid.   If the end date is before the start date
    // then we disable all the time selectors (start, end and All Day) until
    // the dates are fixed.
    ?>
    if (!form.all_day.checked)
    {
      var newState = (dateDifference < 0);
      startSelect.disabled = newState;
      endSelect.disabled = newState;
      form.all_day.disabled = newState;
    }

    <?php // Destroy and rebuild the start select ?>
    while (startSelect.options.length > 0)
    {
      startSelect.remove(0);
    }

    for (i = 0; i < nStartOptions[area]; i++)
    {
      isSelected = (startOptions[area][i]['value'] == startValue);
      if (dateDifference >= 0)
      {
        text = startOptions[area][i]['text'];
      }
      startSelect.options[i] = new Option(text, startOptions[area][i]['value'], false, isSelected);
    }
    
    <?php // Destroy and rebuild the end select ?>
    while (endSelect.options.length > 0)
    {
      endSelect.remove(0);
    }

    j = 0;
    for (i = 0; i < nEndOptions[area]; i++)
    {
      if ((endOptions[area][i]['value'] > startValue) ||
          ((endOptions[area][i]['value'] == startValue) && enablePeriods) ||
          (dateDifference != 0))
      {
        isSelected = (endOptions[area][i]['value'] == endValue);
        if (dateDifference >= 0)
        {
          text = endOptions[area][i]['text'] + nbsp + nbsp + '(' +
                 getDuration(startValue, endOptions[area][i]['value'], dateDifference) + ')';
        }      
        endSelect.options[j] = new Option(text, endOptions[area][i]['value'], false, isSelected);
        j++;
      }
    }
    
    <?php 
    // Make the two select boxes the same width.   Note that we set
    // the widths of both select boxes, even though it would seem
    // that just setting the width of the smaller should be sufficient.
    // But if you don't set both of them then you end up with a few 
    // pixels difference.  In other words doing a get and then a set 
    // doesn't leave you where you started - not quite sure why.
    // The + 2 is a fudge factor to make sure that the option text isn't
    // truncated - not quite sure why it is necessary.
    // The width: auto is necessary to get the boxes to resize themselves
    // according to their new contents.
    ?>
    var startId = "#start_seconds" + area;
    var endId = "#end_seconds" + area;
    $(startId).css({width: "auto"});
    $(endId).css({width: "auto"});
    var startWidth = $(startId).width();
    var endWidth = $(endId).width();
    var maxWidth = Math.max(startWidth, endWidth) + 2;
    $(startId).width(maxWidth);
    $(endId).width(maxWidth);
  } <?php // function adjustSlotSelectors() ?>
  
<?php
}


if ($page == 'pending')
{
?>
  // test whether array contains element
  function contains(array, element)
  {
    for (var i=0; i<array.length; i++)
    {
      if (array[i] == element)
      {
        return true;
      }
    }
    return false;
  }
  
  // Remove duplicates from an array
  function unique(array)
  {
    temp = new Array();
    for (var i=0; i<array.length; i++)
    {
      if (!contains(temp, array[i]))
      {
        temp.push(array[i]);
      }
    }
    return temp;
  }
  
  // Toggle a sub-table in the pending_list table to make it maximised/minimised
  function toggle_table(id)
  { 
    var table = document.getElementById(id);
    if (table.className == 'minimised')
    {
      table.className = 'maximised';
      table.firstChild.firstChild.firstChild.innerHTML = '-';
    }
    else
    {
      table.className = 'minimised';
      table.firstChild.firstChild.firstChild.innerHTML = '+';
    }
  }

  
  // Put a + control in the first cell of all the series headers on the pending.php page
  // and minimise the tables (except for those tables listed in the cookie)
  function activate_sub_tables()
  {
    // Go through each sub-table and minimise it
    var tables = document.getElementsByTagName('table');
    for (var i = 0; (element = tables[i]) != null; i++)
    {
      if (tables[i].className.indexOf('maximised') >= 0)
      {
        tables[i].className = 'minimised';
        tables[i].firstChild.firstChild.firstChild.innerHTML = '+'; 
      } 
    }
     
    // now make the table visible (if it's there at all - which it won't be when you 
    // log off from pending.php
    if (document.getElementById('pending_list'))
    {
      document.getElementById('pending_list').style.visibility = 'visible';
    }
  }
  
  
  
<?php
} // pending
?>

// actions to be taken on page load
function init()
{
  <?php // ALL PAGES ?>
  // if there's a logon box, set the username input field in focus
  var logonForm = document.getElementById('logon');
  if (logonForm && logonForm.NewUserName)
  {
    logonForm.NewUserName.focus();
  }

  <?php
  // EDIT_AREA_ROOM.PHP
  if ($page == 'edit_area_room')
  {
    // We need to hide the sections of the form relating to times
    // slots if the form is loaded with periods enabled.   We hide
    // the times sections instantly by setting speed = 0;
    // [This method works if there are no periods-specific settings.
    // When we get those we will have to do something different]
  ?>
    var form = document.getElementById('edit_area');
    if (form)
    {
      enablePeriods = false;
      if (form.area_enable_periods[0].checked)
      {
        toggleMode(form, 0);
      }
      check_book_ahead();
    }
    
  <?php
  }
  
  // EDIT_ENTRY.PHP
  // (1) put the booking name field in focus (but only for new bookings,
  // ie when the field is empty:  if it's a new booking you have to
  // complete that field, but if it's an existing booking you might
  // want to edit any field)
  // (2) Adjust the slot selectors
  if ($page == 'edit_entry')
  {
  ?>
  var form = document.getElementById('main');
  if (form)
  { 
    if (form.name && (form.name.value.length == 0))
    {
      form.name.focus();
    }
    <?php
    // Get the current vocab (in the appropriate language) for periods,
    // minutes, hours
    ?>
    vocab['periods'] = new Array();
    vocab['periods']['singular'] = '<?php echo get_vocab("period_lc") ?>';
    vocab['periods']['plural'] = '<?php echo get_vocab("periods") ?>';
    vocab['minutes'] = new Array();
    vocab['minutes']['singular'] = '<?php echo get_vocab("minute_lc") ?>';
    vocab['minutes']['plural'] = '<?php echo get_vocab("minutes") ?>';
    vocab['hours'] = new Array();
    vocab['hours']['singular'] = '<?php echo get_vocab("hour_lc") ?>';
    vocab['hours']['plural'] = '<?php echo get_vocab("hours") ?>';
    vocab['days'] = new Array();
    vocab['days']['singular'] = '<?php echo get_vocab("day_lc") ?>';
    vocab['days']['plural'] = '<?php echo get_vocab("days") ?>';
    <?php
    // Get the details of the start and end slot selectors now since
    // they are fully populated with options.  We can then use the details
    // to rebuild the selectors later on
    ?>
    var i, j, area, startSelect, endSelect;
    for (i in areas)
    {
      area = i;
      startSelect = form["start_seconds" + area];
      endSelect = form["end_seconds" + area];
      
      startOptions[area] = new Array();
      nStartOptions[area] = startSelect.options.length;
      for (j=0; j < nStartOptions[area]; j++)
      {
        startOptions[area][j] = new Array();
        startOptions[area][j]['text'] = startSelect.options[j].text;
        startOptions[area][j]['value'] = parseInt(startSelect.options[j].value);
      }
      
      endOptions[area] = new Array();
      nEndOptions[area] = endSelect.options.length;
      for (j=0; j < nEndOptions[area]; j++)
      {
        endOptions[area][j] = new Array();
        endOptions[area][j]['text'] = endSelect.options[j].text;
        endOptions[area][j]['value'] = parseInt(endSelect.options[j].value);
      }
    }
  
    adjustSlotSelectors(form);
    
    <?php
    // If this is an All Day booking then check the All Day box and disable the 
    // start and end time boxes
    ?>
    startSelect = form["start_seconds" + currentArea];
    endSelect = form["end_seconds" + currentArea];
    if (!form.all_day.disabled && 
        (parseInt(startSelect.options[startSelect.selectedIndex].value) == startOptions[currentArea][0]['value']) &&
        (parseInt(endSelect.options[endSelect.selectedIndex].value) == endOptions[currentArea][nEndOptions[currentArea] - 1]['value']))
    {
      form.all_day.checked = true;
      startSelect.disabled = true;
      endSelect.disabled = true;
      old_start = startSelect.options[startSelect.selectedIndex].value;
      old_end = endSelect.options[endSelect.selectedIndex].value;
    }
  }

  <?php
  }
  
  // PENDING.PHP
  if ($page == 'pending')
  {
    ?>
    activate_sub_tables();
    <?php
  }

  // SEARCH.PHP
  // put the search string field in focus
  if ($page == 'search')
  {
  ?>
  var form = document.getElementById('search_form');
  if (form && form.search_str)
  {
    form.search_str.focus();
  }
  <?php
  }
  ?>
}


//]]>
    </script>
  </head>
  <?php
  // Put the filename in as a class to aid styling.   
  // (Use a class rather than id to avoid specificity problems)
  echo "<body class=\"$page\" onLoad =\"init()\">\n";
  ?>
    <div class="screenonly">

<?php // show a warning if this is using a low version of php
  if (substr(phpversion(), 0, 1) == 3)
  {
    echo "<h1>" . get_vocab("not_php3") . "</h1>\n";
  }
  if (!empty($locale_warning))
  {
    echo "[Warning: ".$locale_warning."]";
  }
?>

  <table id="banner">
    <tr>
      <td id="company">
        <div>
          <?php
          echo "<div id=\"logo\">\n";
          if (!empty($mrbs_company_url))
          {
            echo "<a href=\"$mrbs_company_url\">\n";
          }
          if (empty($mrbs_company_logo))
          {
            echo "<span>$mrbs_company</span>\n";
          }
          else
          {
            $logo_size = getimagesize($mrbs_company_logo);
            echo "<img src=\"$mrbs_company_logo\" " . $logo_size[3] . " alt=\"logo\">\n";
          }
          if (!empty($mrbs_company_url))
          {
            echo "</a>\n";
          }
          echo "</div>\n";
          if (!empty($mrbs_company_more_info))
          {
            echo "<div id=\"more_info\">$mrbs_company_more_info</div>\n";
          }
          ?>
          <div id="mrbs">
            <a href="index.php"><?php echo get_vocab("mrbs") ?></a>
          </div>
        </div>
      </td>
      <td>
        <form action="day.php" method="get" id="Form1">
          <div>
            <?php
            // Give the form id as the optional fifth parameter because we want
            // the form to be automatically submitted when the datepicker is closed
            genDateSelector("", $day, $month, $year, "Form1");
            if (!empty($area))
            {
              echo "<input type=\"hidden\" name=\"area\" value=\"$area\">\n";
            }
            if (!empty($room))
            {
              echo "<input type=\"hidden\" name=\"room\" value=\"$room\">\n";
            }
            // Although the datepicker will automatically go to the new date when
            // the date is changed, we still need a submit button because there
            // are occasions when you want to go to the date without changing it -
            // for example when you've been on a Search or Report page
            echo "<input type=\"submit\" value=\"" . get_vocab("goto") . "\">\n";
            ?>
           </div>
        </form>
        <?php
        // Provide a link to the list of bookings awaiting approval
        // (if there are any areas where we require bookings to be approved)


        $approval_somewhere = some_area('approval_enabled');
        if ($approval_somewhere && (authGetUserLevel($user) >= 1))
        {
          $sql_approval_enabled = some_area_predicate('approval_enabled');
          // Find out how many bookings are awaiting approval
          // (but only for areas where approval is required)
          $sql = "SELECT COUNT(*)
                    FROM $tbl_entry E, $tbl_room R, $tbl_area A
                   WHERE (status&" . STATUS_AWAITING_APPROVAL . " != 0)
                     AND E.room_id = R.id
                     AND R.area_id = A.id
                     AND $sql_approval_enabled";
          if (!$is_admin)
          {
            // Ordinary users can only see their own
            $sql .= " AND create_by='" . addslashes($user) . "'";
          }
          $n_outstanding = sql_query1($sql);
          echo "<div id=\"n_outstanding\"" .
               (($n_outstanding > 0) ? " class=\"outstanding\"" : '') .
               ">\n";
          echo "<a href=\"pending.php?day=$day&amp;month=$month&amp;year=$year&amp;area=$area" . 
               ((!empty($room)) ? "&amp;room=$room" : "") . 
               "\">$n_outstanding " . get_vocab("outstanding") . "</a>\n";
          echo "</div>\n";
        }
        ?>
      </td>
      <?php
      $query_str = "day=$day&amp;month=$month&amp;year=$year";
      if (!empty($area))
      {
        $query_str .= "&amp;area=$area";
      }
      if (!empty($room))
      {
        $query_str .= "&amp;room=$room";
      }
      
      echo "<td>\n";
      echo "<a href=\"help.php?$query_str\">" . get_vocab("help") . "</a>\n";
      echo "</td>\n";
      
      echo "<td>\n";
      echo "<a href=\"admin.php?$query_str\">" . get_vocab("rooms") . "</a>\n";
      echo "</td>\n";
      
      echo "<td>\n";
      echo "<a href=\"report.php?$query_str\">" . get_vocab("report") . "</a>\n";
      echo "</td>\n";
      ?>
      
      <td>
        <form id="header_search" method="get" action="search.php">
          <div>
            <a href="search.php?advanced=1"><?php echo get_vocab("search") ?>:</a>
            <input type="text"   name="search_str" value="<?php echo $search_str ?>">
            <input type="hidden" name="day"        value="<?php echo $day        ?>">
            <input type="hidden" name="month"      value="<?php echo $month      ?>">
            <input type="hidden" name="year"       value="<?php echo $year       ?>">
            <?php
            if (!empty($area))
            {
              echo "<input type=\"hidden\" name=\"area\" value=\"$area\">\n";
            }
            if (!empty($room))
            {
              echo "<input type=\"hidden\" name=\"room\" value=\"$room\">\n";
            }
            ?>
          </div>
        </form>
      </td>
      <?php
      // For session protocols that define their own logon box...
      if (function_exists('PrintLogonBox'))
      {
        echo "<td>\n";
        echo "<div id=\"logon_box\">\n";
        PrintLogonBox(); 
        echo "</div>\n";
        echo "</td>\n";
      }
      ?>
    </tr>
  </table>
</div>
<?php

} // end of print_theme_header()
?>
