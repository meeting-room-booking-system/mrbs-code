<?php

// $Id$

// Generates the JavaScript code to turn the input with id $id
// into an autocomplete box, with options contained in the
// array $options.
function generate_autocomplete($id, $options)
{
  global $autocomplete_length_breaks;
  
  $js = '';

  $n_options = count($options);
  if ($n_options > 0)
  {
    // Work out a suitable value for the autocomplete minLength
    // option, ie the number of characters that must be typed before
    // a list of options appears.   We want to avoid presenting a huge 
    // list of options.
    
    $min_length = 0;
    if (isset($autocomplete_length_breaks) && is_array($autocomplete_length_breaks))
    {
      foreach ($autocomplete_length_breaks as $break)
      {
        if ($n_options < $break)
        {
          break;
        }
        $min_length++;
      }
    }
    // Start forming the array literal
    // Escape the options
    for ($i=0; $i < $n_options; $i++)
    {
      $options[$i] = escape_js($options[$i]);
    }
    $options_string = "'" . implode("','", $options) . "'";
    // Build the JavaScript.   We don't support autocomplete in IE6 and below
    // because the browser doesn't render the autocomplete box properly - it
    // gets hidden behind other elements.   Although there are fixes for this,
    // it's not worth it ...
    $js .= "if (!lteIE6)\n";
    $js .= "{\n";
    $js .= "  $('#$id').autocomplete({\n";
    $js .= "    source: [$options_string],\n";
    $js .= "    minLength: $min_length\n";
    $js .= "  })";
    // If the minLength is 0, then the autocomplete widget doesn't do
    // quite what you might expect and you need to force it to display
    // the available options when it receives focus
    if ($min_length == 0)
    {
      $js .= ".focus(function() {\n";
      $js .= "    $(this).autocomplete('search', '');\n";
      $js .= "  })";
    }
    $js .= "  ;\n";
    $js .= "}\n";
  }

  return $js;
}

// Print the page header
function print_theme_header($day, $month, $year, $area, $room)
{
  global $mrbs_company, $mrbs_company_logo, $mrbs_company_url, $mrbs_company_more_info,
         $search_str, $locale_warning;
  global $tbl_entry, $tbl_room, $tbl_area;
  global $PHP_SELF, $HTTP_HOST, $QUERY_STRING;
  global $view_week_number, $weekstarts;
  global $auth, $max_level;
  global $default_language_tokens, $disable_automatic_language_changing, $override_locale;
  global $lang_map_windows, $langs, $server_os;
  global $default_duration_all_day;
  global $select_options;
  global $ajax_refresh_rate;
  
  $page = basename($PHP_SELF, ".php");
  $user = getUserName();
  $is_admin = (authGetUserLevel($user) >= $max_level);

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
  if (!isset($search_str))
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
    //   - Position
    //   - Draggable
    //   - Resizable
    //   - Autocomplete
    //   - Dialog
    //   - Tabs
    //   - Datepicker
    //   - Sunny theme
    // If you need any other components you will need to rebuild the custom UI library
    // See http://jqueryui.com/
    ?>
    
    <script type="text/javascript" src="jquery/jquery-1.6.4.min.js"></script>
    <script type="text/javascript" src="jquery/ui/jquery-ui-1.8.16.custom.min.js"></script>
    <script type="text/javascript" src="jquery/ui/jquery-ui-i18n.js"></script>
    <?php
    // The English (-en) and English-US (-en-US) files are non-standard additions,
    // which do not exist explicitly.   Although datepicker defaults to US English, it
    // helps when choosing the regional settings to have explicit settings.  A ticket
    // (#6682) has been raised to request their inclusion.    The files have been kept
    // separate to avoid problems with merging new releases of UI.
    //
    ?>
    <script type="text/javascript" src="jquery/ui/jquery-ui-datepicker-en.js"></script>
    <script type="text/javascript" src="jquery/ui/jquery-ui-datepicker-en-US.js"></script>
     
    <?php // Work out if we're using IE6 or below ?>
    <script type="text/javascript">
      //<![CDATA[
      var lteIE6 = false;
      //]]>
    </script>
    <!--[if lte IE 6]>
    <script type="text/javascript">
      //<![CDATA[
      var lteIE6 = true;
      //]]>
    </script>
    <![endif]-->
    
    <?php // Work out if we're using IE7 or below ?>
    <script type="text/javascript">
      //<![CDATA[
      var lteIE7 = false;
      //]]>
    </script>
    <!--[if lte IE 7]>
    <script type="text/javascript">
      //<![CDATA[
      var lteIE7 = true;
      //]]>
    </script>
    <![endif]-->
      
    <?php
    // dataTables initialisation
    if (in_array($page, array('admin', 'edit_users', 'pending', 'report', 'search')))
    {
      // Include the JavaScript for those pages that use dataTables
      ?>
      <script type="text/javascript" src="jquery/datatables/js/jquery.dataTables.min.js"></script>
      <script type="text/javascript" src="jquery/datatables/js/ColReorder.min.js"></script>
      <script type="text/javascript" src="jquery/datatables/js/ColVis.min.js"></script>
      <script type="text/javascript" src="jquery/datatables/js/FixedColumns.min.js"></script>
      <script type="text/javascript" src="jquery/datatables/js/plugins.js"></script>
      
      <script type="text/javascript">
        //<![CDATA[
        
        <?php
        // Turn the table with id 'id' into a DataTable, using specificOptions
        // which are merged with the default options.   If the browser is IE6 or less
        // we don't bother making a dataTable:  it can be done, but it's not worth it.
        //
        // [We've created a specific function to make a data table rather than just 
        // using the dataTable() method directly, because if we're using options such as
        // FixedColumns we have to set up the FixedColumns using a call back function
        // after initialisation is complete, because the setting of the language text
        // strings, which happens asynchronously, needs to happen first.    When we're
        // setting up the call back function we need to know the name of the dataTable
        // object.   Having the makeDataTable() function allows us to keep the lines of
        // code that use the object name together in one place.   It also allows us 
        // to keep the browser conditional code in one place.]
        ?>
        function makeDataTable(id, specificOptions)
        {
          if (lteIE6)
          {
            $('.js div.datatable_container').css('visibility', 'visible');
            return false;
          }
          else
          {
            <?php // Set up the default options ?>
            var defaultOptions = {};
            <?php
            // Set the language file to be used
            if ($lang_file = get_datatable_lang())
            {
              ?>
              defaultOptions.oLanguage = {"sUrl": "<?php echo $lang_file ?>"};
              <?php
            }
            ?>
            defaultOptions.bDeferRender = true;
            defaultOptions.bPaginate = true;
            defaultOptions.bProcessing = true;
            defaultOptions.bScrollCollapse = true;
            defaultOptions.bStateSave = true;
            <?php
            // If we're using an Ajax data source then don't offer 'R' (column reordering).
            // This is a problem at the moment in DataTables because if you reorder a column
            // DataTables doesn't know that the Ajax data is still in the original order.
            // May be fixed in a future release of DataTables
            ?>
            defaultOptions.sDom = (specificOptions.sAjaxSource) ? 'C<"clear">lfrtip' : 'RC<"clear">lfrtip';
            defaultOptions.sScrollX = "100%";
            defaultOptions.sPaginationType = "full_numbers";
            defaultOptions.oColVis = {sSize: "css",
                                      buttonText: '<?php echo get_vocab("show_hide_columns") ?>',
                                      bRestore: true,
                                      sRestore: '<?php echo get_vocab("restore_original") ?>'};
            <?php
            // Merge the specific options with the default options.  We do a deep
            // merge.
            ?>
            var mergedOptions = $.extend(true, {}, defaultOptions, specificOptions);
            var oTable = $(id).dataTable(mergedOptions);
            <?php // Need to redraw the datatable when the browser window is resized ?>
            $(window).bind('resize', function () {
                oTable.fnDraw();
              });
            return oTable;
          }
        }
        
        //]]>
      </script>
      <?php
    }
    
    ?>
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
    if (function_exists('json_encode'))
    {
      // If we're doing Ajax checking of the form then we have to check
      // for conflicts the form when the datepicker is closed
      ?>
      checkConflicts();
      <?php
    }
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
    var periodsChecked = form.area_enable_periods[0].checked;
    if (periodsChecked != enablePeriods)
    {
      enablePeriods = !enablePeriods;
      $('#time_settings').animate({
        opacity : 'toggle',
        height: 'toggle'
        }, speed);
    }
    <?php // Show or Hide the note about periods as appropriate ?>
    if (periodsChecked)
    {
      $('#book_ahead_periods_note').show(speed);
    }
    else
    {
      $('#book_ahead_periods_note').hide(speed);
    }
  }
  
  // disable the min and max book ahead values and units if the corresponding
  // checkbox isn't checked;  and enable it it is.
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

  // Add Ajax capabilities (but only if we can return the result as a JSON object)
  if (function_exists('json_encode'))
  {
    
    // Get the value of the field in the form
    ?>
    function getFormValue(form, field)
    {
      <?php
      // We need to exclude the disabled elements, because otherwise jQuery
      // will pick them all up
      ?>
      var value;
      var formInput = form.find('[name="' + field + '"]');
      <?php // Scalar parameters (two types - checkboxes and the rest) ?>
      if (field.indexOf('[]') == -1)
      {
        if (formInput.filter(':checkbox').length > 0)
        {
          value = formInput.is(':checked') ? '1' : '';
        }
        else if (formInput.filter(':radio').length > 0)
        {
          value = formInput.filter(':checked').val();
        }
        else
        {
          value = formInput.val();
        }
      }
      <?php // Array parameters (two types - checkboxes and the rest) ?>
      else
      {
        value = [];
        if (formInput.filter(':checkbox').length > 0)
        {
          formInput.each(function(index) {
              if ($(this).is(':checked'))
              {
                value.push($(this).val());
              }
            });
        }
        else
        {
          value = formInput.val();
        }
      }
      return value;
    }

    <?php
    // function to check whether the proposed booking would (a) conflict with any other bookings
    // and (b) conforms to the booking policies.   Makes an Ajax call to edit_entry_handler but does
    // not actually make the booking.
    ?>
    function checkConflicts()
    {
      <?php
      // We set a small timeout on checking the booking in order to allow time for
      // the click handler on the Submit buttons to set the data in the form.  We then
      // test the data and if it is set we don't validate the booking because we're going off
      // somewhere else.  [This isn't an ideal way of doing this.   The problem is that
      // the change event for a text input can be fired when the user clicks the submit
      // button - but how can you tell that it was the clicking of the submit button that
      // caused the change event?]
      ?>
      var timeout = 200; <?php // ms ?>
      window.setTimeout(function() {
        var params = {'ajax': 1}; <?php // This is an Ajax request ?>
        var form = $('form#main');
        if (form.data('submit'))
        {
          return;
        }
      
        // Load the params object with the values of all the form fields that are not
        // disabled and are not submit buttons of one kind or another
        form.find('[name]').not(':disabled, [type="submit"], [type="button"], [type="image"]').each(function() {
            var fieldName = $(this).attr('name');
            if (params[fieldName] === undefined)
            {
              params[fieldName] = getFormValue(form, fieldName);
            }
          });
        
        <?php
        // For some reason I don't understand, posting an empty array will
        // give you a PHP array of ('') at the other end.    So to avoid
        // that problem, delete the property if the array (really an object) is empty
        ?>
        $.each(params, function(i, val) {
            if ((typeof(val) == 'object') && (val.length == 0))
            {
              delete params[i];
            }
          });
        
        $.post('edit_entry_handler.php', params, function(result) {
            var conflictDiv = $('#conflict_check');
            var scheduleDetails = $('#schedule_details');
            var policyDetails = $('#policy_details');
            var checkMark = "\u2714";
            var cross = "\u2718";
            var titleText, detailsHTML;
            var patternSpan = /<span[\s\S]*span>/gi;
            var patternTags = /<\S[^><]*>/g;
            if (result.conflicts.length == 0)
            {
              conflictDiv.text(checkMark).attr('class', 'good').attr;
              titleText = '<?php echo mrbs_entity_decode(get_vocab("no_conflicts")) ?>';
              detailsHTML = titleText;
            }
            else
            {
              conflictDiv.text(cross).attr('class', 'bad');
              detailsHTML = "<p>";
              titleText = '<?php echo mrbs_entity_decode(get_vocab("conflict")) ?>' + ":  \n\n";
              detailsHTML += titleText + "<\/p><ul>";
              for (var i=0; i<result.conflicts.length; i++)
              {
                detailsHTML += "<li>" + result.conflicts[i] + "<\/li>";
                titleText += '(' + (i+1).toString() + ') ';
                <?php // strip out the <span> and its contents and then all other tags ?>
                titleText += result.conflicts[i].replace(patternSpan, '').replace(patternTags, '') + "  \n";
              }
              detailsHTML += "<\/ul>";
            }
            conflictDiv.attr('title', titleText);
            scheduleDetails.html(detailsHTML);
            var policyDiv = $('#policy_check');
            if (result.rules_broken.length == 0)
            {
              policyDiv.text(checkMark).attr('class', 'good');
              titleText = '<?php echo mrbs_entity_decode(get_vocab("no_rules_broken")) ?>';
              detailsHTML = titleText
            }
            else
            {
              policyDiv.text(cross).attr('class', 'bad');
              detailsHTML = "<p>";
              titleText = '<?php echo mrbs_entity_decode(get_vocab("rules_broken")) ?>' + ":  \n\n";
              detailsHTML += titleText + "<\/p><ul>";
              for (var i=0; i<result.rules_broken.length; i++)
              {
                detailsHTML += "<li>" + result.rules_broken[i] + "<\/li>";
                titleText += '(' + (i+1).toString() + ') ' + result.rules_broken[i] + "  \n";
              }
              detailsHTML += "<\/ul>";
            }
            policyDiv.attr('title', titleText);
            policyDetails.html(detailsHTML);
          }, 'json');
      }, timeout);  <?php // setTimeout() ?>
    }
    <?php
  }

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
    
    var startId = "start_seconds" + area;
    var startSelect = form[startId];
    var startKeepDisabled = ($('#' + startId).attr('class') == 'keep_disabled');
    var endId = "end_seconds" + area;
    var endSelect = form[endId];
    var endKeepDisabled = ($('#' + endId).attr('class') == 'keep_disabled');
    var startIndex, startValue, endIndex, endValue;
    
    <?php 
    // If All Day is checked then just set the start and end values to the first
    // and last possible options.
    ?>
    if (form.all_day && form.all_day.checked)
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
      // booking period will include the old booking period (unless we've hit the start or
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
    if (!form.all_day || !form.all_day.checked)
    {
      var newState = (dateDifference < 0);
      startSelect.disabled = newState || startKeepDisabled;
      endSelect.disabled = newState || endKeepDisabled;
      if (form.all_day)
      {
        form.all_day.disabled = newState;
      }
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
}  // edit_entry

?>


$(window).load(function() {

  <?php // ALL PAGES ?>
  // if there's a logon box, set the username input field in focus
  var logonForm = document.getElementById('logon');
  if (logonForm && logonForm.NewUserName)
  {
    logonForm.NewUserName.focus();
  }
  
  <?php
  // Add in a hidden input to the header search form so that we can tell if we are using DataTables
  // (which will be if JavaScript is enabled and we're not running IE6 or below).   We
  // need to know this because when we're using an Ajax data source we don't want to send
  // the HTML version of the table data.
  ?>
  if (!lteIE6)
  {
    $('<input>').attr({
        type: 'hidden',
        name: 'datatable',
        value: '1'
      }).appendTo('#header_search');
  }
  

  <?php
  // ADMIN.PHP
  if ($page == 'admin')
  {
    // Turn the list of rooms into a dataTable
    ?>
    var tableOptions = new Object();
    <?php
    // If we're an ordinary user we only see the Name column.   If we're
    // an admin we also see the Delete, Edit and Disabled columns.
    // [For some weird reason, setting nFixedColumns as 4 causes a JavaScript
    // error in IE7 and IE8 when you reload the page]
    ?>
    var nFixedColumns = <?php echo ($is_admin) ? 3 : 1 ?>;
    <?php 
    // Fix the left hand columns.  This has to be done when 
    // initialisation is complete as the language files are loaded
    // asynchronously
    ?>
    tableOptions.fnInitComplete = function(){
        new FixedColumns(roomsTable, {"iLeftColumns": nFixedColumns,
                                      "iLeftWidth": 30,
                                      "sLeftWidth": "relative"});
        $('.js div.datatable_container').css('visibility', 'visible');
      };
    <?php
    // Remove the fixed columns from the column visibility
    ?>
    tableOptions.oColVis = {aiExclude: []};
    for (var i=0; i<nFixedColumns; i++)
    {
      tableOptions.oColVis.aiExclude.push(i);
    }
    <?php
    // and stop those columns being reordered
    ?>
    tableOptions.oColReorder = {iFixedColumns: nFixedColumns};
    
    var roomsTable = makeDataTable('#rooms_table', tableOptions);
    <?php
  }  
    
  // EDIT_AREA_ROOM.PHP
  if ($page == 'edit_area_room')
  {
    // We need to hide the sections of the form relating to times
    // slots if the form is loaded with periods enabled.   We hide
    // the times sections instantly by setting speed = 0;
    // Also show or hide the periods note as appropriate
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
        $('#book_ahead_periods_note').show();
      }
      else
      {
        $('#book_ahead_periods_note').hide();
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
  // (3) Add some Ajax capabilities to the form (if we can) so that when
  //  a booking parameter is changed MRBS checks to see whether there would
  //  be any conflicts
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
    if (form.all_day &&
        !form.all_day.disabled && 
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
  // Check the All Day box if that's the default and then call the function
  // that handles changes.  (Note - the call to OnAllDayClick() comes inside
  // an each() in case there isn't an all_day checkbox, which there won't be
  // if we're logging in)
  if ($default_duration_all_day)
  {
    ?>
    $('#all_day').attr('checked', true).each(function() {
        OnAllDayClick(true);
      });
    <?php
  }
  
  // Set up handlers if anything like a submit button is pressed.   If it's the Save
  // button we need to validate the form first.  In addition, for all the buttons, if
  // we are going to submit something we set some data in the form.   This allows
  // the function that checks for a valid booking to see if the change was triggered
  // by a Submit button being pressed, and if so, not to send an Ajax request.
  ?>
  $('form#main').find('[type="submit"], [type="button"], [type="image"]').click(function() {
    var trigger = $(this).attr('name');
    var thisForm = $(this).parents('form').eq(0);
    thisForm.data('submit', trigger);
    if (trigger == 'save_button')
    {
      if (validate('main'))
      {
        thisForm.submit();
      }
      thisForm.removeData('submit');
      return false;
    }
    return true;
  });
      
  <?php
  // Add Ajax capabilities (but only if we can return the result as a JSON object)
  if (function_exists('json_encode'))
  {
    // Add a change event handler to each of the form fields - except for those that
    // are disabled and anything that might be a submit button - so that when they change
    // the validity of the booking is re-checked.   (This probably causes more checking
    // than is really necessary, eg when the brief description is changed, but on the other
    // hand it (a) removes the need to know the names of the fields you want and (b) keeps
    // the data available for policy checking as complete as possible just in case somebody
    // decides to set a policy based on for example the brief description, for some reason).
    //
    // Use a click event for checkboxes as it seems that in some browsers the event fires
    // before the value is changed.
    //
    // Note that we also need to add change event handlers to the start and end
    // datepicker input fields, but we have to do that in datepicker_close()
    ?>
    var formFields = $('form#main [name]').not(':disabled, [type="submit"], [type="button"], [type="image"]');
    formFields.filter(':checkbox')
              .click(function() {
                  checkConflicts();
                });
    formFields.not(':checkbox')
              .change(function(event) { 
                  checkConflicts();
                });
     
    checkConflicts();

    $('#conflict_check, #policy_check').click(function() {
        var tabId;
        <?php 
        // Work out which tab should be selected
        // (Slightly long-winded using a switch, but there may be more tabs in future)
        ?>
        switch ($(this).attr('id'))
        {
          case 'policy_check':
            tabId = 'policy_details';
            break;
          case 'conflict_check':
          default:
            tabId = 'schedule_details';
            break;
        }
        <?php
        // If we've already created the dialog and tabs, then all we have
        // to do is select the tab corresponding to the div that was clicked
        ?>
        if (arguments.callee.alreadyExists)
        {
          $('#check_tabs').tabs("select", tabId);
          return;
        }
        <?php
        // We want to create a set of tabs that appear inside a dialog box,
        // with the whole structure being draggable.   Thanks to dbroox at
        // http://forum.jquery.com/topic/combining-ui-dialog-and-tabs for the solution.
        ?>
        $('#check_tabs').tabs();
        $('#check_results').dialog({'width':400, 'height':200, 
                                    'minWidth':300, 'minHeight':150, 
                                    'draggable':true });
        <?php //steal the close button ?>
        $('#ui-tab-dialog-close').append($('a.ui-dialog-titlebar-close'));
        <?php //move the tabs out of the content and make them draggable ?>
        $('.ui-dialog').addClass('ui-tabs')
                       .prepend($('#details_tabs'))
                       .draggable('option', 'handle', '#details_tabs');
        <?php //switch the titlebar class ?>
        $('.ui-dialog-titlebar').remove();
        $('#details_tabs').addClass('ui-dialog-titlebar');
        
        arguments.callee.alreadyExists=true;
      });
    
    <?php
    // Finally set a timer so that conflicts are periodically checked for,
    // in case someone else books that slot before you press Save.
    // (Note the config variable is in seconds, but the setInterval() function
    // uses milliseconds)
    if (!empty($ajax_refresh_rate))
    {
      ?>
      window.setInterval(function() {
        checkConflicts();
      }, <?php echo $ajax_refresh_rate * 1000 ?>);
      <?php
    }


  } // if (function_exists('json_encode'))
  ?>

  <?php
  }  // EDIT_ENTRY
  
  // EDIT_USERS.PHP
  if ($page == 'edit_users')
  {
    // Turn the list of users into a dataTable
    ?>
    var tableOptions = new Object();
    <?php
    // Set the initial order of columns (we want it to go
    // "Action - Name - Rights" and not "Action - Rights - Name"
    ?>
    var nColumns = $('#users_table tr:first th').length;
    tableOptions.oColReorder = {aiOrder: [0, 2, 1]};
    for (var i=3; i < nColumns; i++)
    {
      tableOptions.oColReorder.aiOrder.push(i);
    }
    <?php
    // The Rights column has a span with title for sorting
    ?>
    tableOptions.aoColumnDefs = [{"sType": "title-numeric", "aTargets": [1]}]; 
    <?php 
    // Fix the left hand two columns.  This has to be done when 
    // initialisation is complete as the language files are loaded
    // asynchronously
    ?>
    tableOptions.fnInitComplete = function(){
        new FixedColumns(usersTable, {"iLeftColumns": 2,
                                      "iLeftWidth": 30,
                                      "sLeftWidth": "relative"});
        $('.js div.datatable_container').css('visibility', 'visible');
      };
    <?php
    // Remove the first two columns from the column visibility
    // list because these two are fixed
    ?>
    tableOptions.oColVis = {aiExclude: [0,1]};
    <?php
    // and stop those first two columns being reordered
    ?>
    tableOptions.oColReorder.iFixedColumns = 2;
    
    var usersTable = makeDataTable('#users_table', tableOptions);

    <?php
  }
  
  // PENDING.PHP
  if ($page == 'pending')
  {
    // Turn the table into a datatable, with subtables that appear/disappear when
    // the control is clicked, with the subtables also being datatables.  Note though
    // that the main and sub-datatables are independent and we only display the main search
    // box which just applies to the main table rows.  (I suppose it would be possible to do
    // something clever with the main search box and get it to search the subtables as well)
    //
    // The datatables with subtables don't seem to work properly in IE7, so don't
    // bother with them if we're using IE7
    ?>
    if (lteIE7)
    {
      $('.js div.datatable_container').css('visibility', 'visible');
    }
    else
    {
      var maintable = $('#pending_table');
      <?php
      // Add a '-' control to the subtables and make them close on clicking it
      ?>
      maintable.find('table.sub th.control')
               .text('-')
               .live('click', function (event) {
                    var nTr = $(this).parents('.table_container').parent().prev();
                    var serial = $(this).parent().parent().parent().attr('id').replace('subtable_', '');
                    $('#subtable_' + serial + '_wrapper').slideUp( function () {
                        pendingTable.fnClose(nTr.get(0));
                        nTr.show();
                      });
                  });
      <?php
      // Detach all the subtables from the DOM (detach keeps a copy) so that they
      // don't appear, but so that we've got the data when we want to "open" a row
      ?>
      var subtables = maintable.find('tr.sub_table').detach();
      <?php
      // Set up the column definitions, fixing the widths of the first and last columns
      // Get the width of the last column by finding the width of the largest content
      // (assuming all the content is wrapped in the first child)
      ?>
      var maxActionWidth = 0;
      $('th:last-child, td:last-child').each(function() {
          var actionWidth = $(this).children().eq(0).outerWidth(true);
          maxActionWidth = Math.max(maxActionWidth, actionWidth);
        });
      maxActionWidth += 16; <?php // to allow for padding in the <td> ?>
      var colDefsMain = [{"sWidth": "1.2em", "aTargets": [0] },
                         {"sWidth": maxActionWidth + "px", "aTargets": [6] },
                         {"sType": "title-numeric", "aTargets": [5]} ];
      <?php
      // Set up a click event that "opens" the table row and inserts the subtable
      ?>
      maintable.find('td.control')
               .text('+')
               .live('click', function (event) {
                    var nTr = $(this).parent();
                    var serial = nTr.attr('id').replace('row_', '');
                    var subtableId = 'subtable_' + serial;
                    var subtable = subtables.find('#' + subtableId).parent().clone();                                
                    var columns = [];          
                    <?php
                    // We want the columns in the main and sub tables to align.  So
                    // find the widths of the main table columns and use those values
                    // to set the widths of the subable columns.   [This doesn't work
                    // 100% - I'm not sure why - but I have left the code in]
                    ?>
                    maintable.find('tr').eq(0).find('th').each(function(i){
                        var def = new Object();
                        switch (i)
                        {
                          case 0: <?php // expand control ?>
                            def.bSortable = false;
                            break;
                          case 5: <?php // start-time ?>
                            def.sType = "title-numeric";
                            break;
                        }
                        def.sWidth = ($(this).innerWidth()) + "px";
                        columns.push(def);
                      });

                    nTr.hide();
                    pendingTable.fnOpen(nTr.get(0), subtable.get(0), 'table_container');

                    $('#' + subtableId).dataTable({"bAutoWidth": false,
                                                   "sDom": 't',
                                                   "aoColumns": columns});

                    $('#subtable_' + serial + '_wrapper').hide().slideDown();
                  });
                  
      <?php // Turn the table into a datatable ?>
      var tableOptions = new Object();
      tableOptions.aoColumnDefs = colDefsMain;
      tableOptions.sScrollXInner = "100%";
      <?php
      // For some reason I don't understand, fnOpen() doesn't seem to work when
      // using FixedColumns.   We also have to turn off bStateSave.  I have raised
      // this on the dataTables forum.  In the meantime we comment out the FixedColumns.
      ?>
      tableOptions.bStateSave = false;
      <?php
      // Fix the left hand column.  This has to be done when 
      // initialisation is complete as the language files are loaded
      // asynchronously
      ?>
      tableOptions.fnInitComplete = function(){
          /*
          new FixedColumns(pendingTable, {"iLeftColumns": 1,
                                          "iLeftWidth": 30,
                                          "sLeftWidth": "fixed"});
          */
          $('.js div.datatable_container').css('visibility', 'visible');
        };
      <?php
      // Remove the first column from the column visibility
      // list because it is fixed
      ?>
      tableOptions.oColVis = {aiExclude: [0]};
      <?php
      // and stop the first column being reordered
      ?>
      tableOptions.oColReorder = {"iFixedColumns": 1};
      var pendingTable = makeDataTable('#pending_table', tableOptions);
    }  // if (!lteie6)
    <?php
  }

  // REPORT.PHP
  if ($page == 'report')
  {
    // Make the area match input on the report page into an auto-complete input
    $options = sql_query_array("SELECT area_name FROM $tbl_area ORDER BY area_name");
    if ($options !== FALSE)
    {
      echo generate_autocomplete('areamatch', $options);
    }

    // Make the room match input on the report page into an auto-complete input
    // (We need DISTINCT because it's possible to have two rooms of the same name
    // in different areas)
    $options = sql_query_array("SELECT DISTINCT room_name FROM $tbl_room ORDER BY room_name");
    if ($options !== FALSE)
    {
      echo generate_autocomplete('roommatch', $options);
    }
    
    // Make any custom fields for the entry table that have an array of options
    // into auto-complete inputs
    foreach ($select_options as $field => $options)
    {
      if (strpos($field, 'entry.') == 0)
      {
        echo generate_autocomplete('match_' . substr($field, strlen('entry.')), $options);
      }
    }
    
    // Turn the list of users into a dataTable
    ?>
    var tableOptions = new Object();
    <?php
    // Use an Ajax source if we're able to - gives much better
    // performance for large tables
    if (function_exists('json_encode'))
    {
      $ajax_url = "http://" . $HTTP_HOST . $PHP_SELF . "?" . $QUERY_STRING . "&ajax=1&phase=2";
      ?>
      tableOptions.sAjaxSource = "<?php echo $ajax_url ?>";
      <?php
    }
    // Add in a hidden input to the form so that we can tell if we are using DataTables
    // (which will be if JavaScript is enabled and we're not running IE6 or below).   We
    // need to know this because when we're using an Ajax data source we don't want to send
    // the HTML version of the table data.
    ?>
    if (!lteIE6)
    {
      $('<input>').attr({
          type: 'hidden',
          name: 'datatable',
          value: '1'
        }).appendTo('#report_form');
    }
    <?php 
    // Stop the first column ("id") from being searchable.   For some reason
    // using bVisible here does not work, so we will use CSS instead.
    // Define the type of the start time, end time, duration and last updated columns
    // (they have the Unix timestamp in the title of a span for sorting)
    ?>
    tableOptions.aoColumnDefs = [{"bSearchable": false, "bVisible": false, "aTargets": [ 0 ]},
                                 {"sType": "title-numeric", "aTargets": [2, 3, 4, -1]}]; 

    <?php
    // Fix the left hand two columns.  This has to be done when initialisation is 
    // complete as the language files are loaded asynchronously
    ?>
    tableOptions.fnInitComplete = function(){
        var oFC = new FixedColumns(reportTable, {"iLeftColumns": 2,
                                                 "iLeftWidth": 30,
                                                 "sLeftWidth": "relative"});
        $('.js div.datatable_container').css('visibility', 'visible');
        <?php 
        // Also add a "Delete entries button", provided that (a) the user is an
        // admin and (b) the configuration allows it
        if ($is_admin && $auth['show_bulk_delete'])
        {
          ?>
          $('<button id="delete_button"><?php echo get_vocab("delete_entries") ?><\/button>')
                .click(function() {
                    var aData = reportTable.fnGetFilteredData();
                    var nEntries = aData.length;
                    if (confirm("<?php echo escape_js(get_vocab('delete_entries_warning')) ?>" +
                                nEntries.toLocaleString()))
                    {
                      <?php
                      // We're going to split the POST requests into batches because
                      // if a single POST request is too large we could get a 406
                      // error.    The POST requests are fired off asynchronously
                      // so we need to count them all back before we know that we've
                      // finished.  The results will be held in the results array.
                      ?>
                      var batchSize = <?php echo DEL_ENTRY_AJAX_BATCH_SIZE ?>;
                      var batches = [];
                      var batch = [];
                      for (var i=0; i<nEntries; i++)
                      {
                        batch.push(aData[i][0]);
                        if (batch.length >= batchSize)
                        {
                          batches.push(batch);
                          batch = [];
                        }
                      }
                      if (batch.length > 0)
                      {
                        batches.push(batch);
                      }
                      <?php // Dispatch the batches (if any) ?>
                      var nBatches = batches.length;
                      if (nBatches > 0)
                      {
                        var results = [];
                        $('#report_table_processing').css('visibility', 'visible');
                        for (var j=0; j<nBatches; j++)
                        {
                          $.post('del_entry_ajax.php',
                                 {ids: batches[j]},
                                 function(result) {
                                    results.push(result);
                                    <?php // Check whether everything has finished ?>
                                    if (results.length >= nBatches)
                                    {
                                      $('#report_table_processing').css('visibility', 'hidden');
                                      <?php
                                      // If all's gone well the result will contain the number of entries deleted
                                      ?>
                                      var nDeleted = 0;
                                      var isInt = /^\s*\d+\s*$/;
                                      for (var i=0; i<results.length; i++)
                                      {
                                        if (!isInt.test(results[i]))
                                        {
                                          alert("<?php echo escape_js(get_vocab('delete_entries_failed')) ?>");
                                          break;
                                        }
                                        nDeleted += parseInt(results[i]);
                                      }
                                      <?php 
                                      // Reload the page to get the new dataset.   If we're using
                                      // an Ajax data source (for true Ajax data sources, not server
                                      // side processing) and there's no summary table we can be
                                      // slightly more elegant and just reload the Ajax data source.
                                      ?>
                                      var oSettings = reportTable.fnSettings();
                                      if (oSettings.sAjaxSource && 
                                          !oSettings.bServerSide &&
                                          ($('#div_summary').length == 0))
                                      {
                                        reportTable.fnReloadAjax();
                                        <?php
                                        // We also need to update the count of the number of entries.  We
                                        // can't just get the length of the data, because the new data is
                                        // loaded asynchronously and won't be there yet.  So we calculate it
                                        // by subtracting the number of entries deleted from theee previous count.
                                        ?>
                                        var span = $('#n_entries');
                                        span.text(parseInt(span.text()) - nDeleted);
                                      }
                                      else
                                      {
                                        window.location.reload();
                                      }
                                    }
                                  });
                        }
                      }
                    }
                  })
                .insertAfter('#report_table_paginate');
          <?php
        }
        ?>
      };

    <?php
    // Remove the first three columns from the column visibility
    // list because these two are fixed
    ?>
    tableOptions.oColVis = {aiExclude: [0,1]};
    <?php
    // and stop those first two columns being reordered
    ?>
    tableOptions.oColReorder = {iFixedColumns: 2};

    var reportTable = makeDataTable('#report_table', tableOptions);

    <?php
  }

  // SEARCH.PHP
  if ($page == 'search')
  {
    // put the search string field in focus
    ?>
    var searchForm = $('#search_form');
    searchForm.find('#search_str').focus();
    
    <?php
    // Turn the list of users into a dataTable, provided that we can use
    // an Ajax source.  Otherwise they just get the old style search page
    // with "Next" and "Prev" buttons to get new pages from the server.
    // [We could of course use dataTables with server side processing, but
    // that's a lot of work.  A better option would probably be to write one's
    // own json_encode function for PHP versions that don't have it]
    if (function_exists('json_encode'))
    {
      // Add in a hidden input to the search form so that we can tell if we are using DataTables
      // (which will be if JavaScript is enabled and we're not running IE6 or below).   We
      // need to know this because when we're using an Ajax data source we don't want to send
      // the HTML version of the table data.
      ?>
      if (!lteIE6)
      {
        $('<input>').attr({
            type: 'hidden',
            name: 'datatable',
            value: '1'
          }).appendTo(searchForm);
      }
      
      var tableOptions = new Object();
      <?php
      // Use an Ajax source - gives much better performance for large tables

      $ajax_url = "http://" . $HTTP_HOST . $PHP_SELF . "?" . $QUERY_STRING . "&ajax=1";
      ?>
      tableOptions.sAjaxSource = "<?php echo $ajax_url ?>";
      tableOptions.aoColumnDefs = [{"sType": "title-numeric", "aTargets": [2]}]; 
      <?php
      // Fix the left hand column.  This has to be done when initialisation
      // is complete as the language files are loaded asynchronously
      ?>
      tableOptions.fnInitComplete = function(){
          var oFC = new FixedColumns(searchTable, {"iLeftColumns": 1,
                                                   "iLeftWidth": 25,
                                                   "sLeftWidth": "relative"});
          $('.js div.datatable_container').css('visibility', 'visible');
        };
      <?php
      // Remove the first column from the column visibility
      // list because it is fixed
      ?>
      tableOptions.oColVis = {aiExclude: [0]};
      <?php
      // and stop the first column being reordered
      ?>
      tableOptions.oColReorder = {"iFixedColumns": 1};
      
      var searchTable = makeDataTable('#search_results', tableOptions);

      <?php
    }  //  if (function_exists('json_encode'))
  }  // if ($page == 'search')
  ?>

});


//]]>
    </script>
  </head>
  <?php
  // Put the filename in as a class to aid styling.   
  // (Use a class rather than id to avoid specificity problems)
  echo "<body class=\"".htmlspecialchars($page)."\">\n";
   
    // Add a class of "js" so that we know if we're using JavaScript or not
  ?>
    <script type="text/javascript">
      //<![CDATA[
      $('body').addClass('js');
      //]]>
    </script> 

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
        // (if there are any enabled areas where we require bookings to be approved)

        $approval_somewhere = some_area('approval_enabled', TRUE);
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
                     AND R.disabled = 0
                     AND A.disabled = 0
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
            <input type="text"   name="search_str" value="<?php echo htmlspecialchars($search_str) ?>">
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
