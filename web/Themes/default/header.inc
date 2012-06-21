<?php

// $Id$

// Generates the JavaScript code to turn the input with id $id
// into an autocomplete box, with options contained in the
// array $options.  $options can be a simple or an associative array.
function generate_autocomplete($id, $options)
{
  global $autocomplete_length_breaks;

  $js = '';

  // Turn the array into a simple, numerically indexed, array
  $options = array_values($options);
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
  global $view_week_number, $weekstarts, $times_along_top, $periods, $enable_periods;
  global $auth, $max_level;
  global $default_language_tokens, $disable_automatic_language_changing, $override_locale;
  global $lang_map_windows, $langs, $server_os;
  global $select_options;
  global $ajax_refresh_rate;
  global $main_table_cell_border_width, $main_cell_height;
  global $timetohighlight;
  
  $page = basename($PHP_SELF, ".php");
  $user = getUserName();
  $is_admin = (authGetUserLevel($user) >= $max_level);
  
  // Need to set the timezone before we can use date()
  get_area_settings($area);

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
  <meta charset="<?php echo get_charset(); ?>">
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
    
    <?php // Work out if we're using IE8 or below ?>
    <script type="text/javascript">
      //<![CDATA[
      var lteIE8 = false;
      //]]>
    </script>
    <!--[if lte IE 8]>
    <script type="text/javascript">
      //<![CDATA[
      var lteIE8 = true;
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
      <?php
      // We use a customised version of ColVis which fixes a problem to do with the width of the
      // ColVis collection div meaning that when you have long column names the collection
      // buttons spill over onto multiple lines.   This problem has been reported in the
      // DataTables forum and hopefully the fix will appear in a future release (or else an
      // alternative solution suggested).  In the meantime we use the mrbs version of ColVis,
      // which is kept separate so that we can identify the differences.
      /*
      <script type="text/javascript" src="jquery/datatables/js/ColVis.min.js"></script>
      */
      ?>
      <script type="text/javascript" src="jquery/datatables/js/ColVis.mrbs.min.js"></script>
      <?php
      // We also have a customised version of FixedColumns which fixes a problem to do with the
      // resizing of windows in IE7/8.   The problem has been reported.
      /*
      <script type="text/javascript" src="jquery/datatables/js/FixedColumns.min.js"></script>
      */
      ?>
      <script type="text/javascript" src="jquery/datatables/js/FixedColumns.mrbs.js"></script>
      <script type="text/javascript" src="jquery/datatables/js/plugins.js"></script>
      
      <script type="text/javascript">
        //<![CDATA[
        
        <?php
        // Try and get a sensible value for the fixed column width, which is the
        // smaller of the actual column width and either a fixed width or a
        // proportion of the overall table width.
        // 
        // col is an object with two properties:  'iWidth' and 'sWidth', which work in
        // the same way as the DataTables properties
        ?>
        function getFixedColWidth(table, col)
        {
          var tableWidth = table.outerWidth();
          var leftWidth = table.find('th:first-child').outerWidth();
          var maxWidthPx = (col.sWidth == "relative") ? tableWidth*col.iWidth/100 : col.iWidth;
          return Math.min(leftWidth, maxWidthPx);
        }
        
        <?php
        // Turn the table with id 'id' into a DataTable, using specificOptions
        // which are merged with the default options.   If the browser is IE6 or less
        // we don't bother making a dataTable:  it can be done, but it's not worth it.
        //
        // leftCol and rightCol are two objects which if defined or not null will fix the left and/or
        // right most columns.  They have properties 'iWidth' and 'sWidth' defining the
        // maximum width of the fixed column.   If sWidth = "fixed" then the iWidth is
        // a pixel value.  If it is "relative" then a percentage.
        //
        // If you want to do anything else as part of fnInitComplete then you'll need
        // to define fnInitComplete in specificOptions
        ?>
        var windowResizeHandler;
        
        function makeDataTable(id, specificOptions, leftCol, rightCol)
        {
          var winWidth = $(window).width();
          var winHeight = $(window).height();
          
          windowResizeHandler = function()
          {
            <?php
            // IE8 and below will trigger $(window).resize not just when the window
            // is resizedbut also when an element in the window is resized.   We 
            // therefore need to check that this is a genuine window resize event
            // otherwise we end up in an infinite loop
            ?>
            var winNewWidth = $(window).width();
            var winNewHeight = $(window).height();
            if ((winNewWidth == winWidth) && (winNewHeight == winHeight))
            {
              return;
            }
            winWidth = winNewWidth;
            winHeight = winNewHeight;
            <?php
            // This is a genuine resize event.   Unbind the handler to stop any
            // more resize events while we are dealing with this one
            ?>
            $(window).unbind('resize', windowResizeHandler);
            <?php
            // Need to re-create the datatable when the browser window is resized.  We
            // can't just do a fnDraw() because that does not redraw the Fixed Columns
            // properly.
            
            // We set a timeout to make the resizing a bit smoother, as otherwise it's
            // fairly CPU intensive
            ?>
            window.setTimeout(function() {
                <?php
                // If we're using an Ajax data source then we don't want to have to make
                // an Ajax call and wait for the data every time we resize.   So retrieve
                // the data from the table and pass it directly to the new table.
                ?>
                if (mergedOptions.sAjaxSource)
                {
                  mergedOptions.aaData = oTable.fnGetData();
                  mergedOptions.sAjaxSource = null;
                }
                <?php
                // Save the language strings, because we don't need to make another Ajax
                // to fetch the language strings again when we resize
                ?>
                $.extend(true, mergedOptions.oLanguage, oTable.fnSettings().oLanguage);
                
                oTable.fnDestroy();
                oTable = table.dataTable(mergedOptions);
              }, 200);
            
          }
          
          if (lteIE6)
          {
            $('.js div.datatable_container').css('visibility', 'visible');
            return false;
          }
          else
          {
            var table = $(id);
            if (table.length == 0)
            {
              return false;
            }
            <?php
            // Remove the <colgroup>.  This is only needed to assist in the formatting
            // of the non-datatable version of the table.   When we have a datatable,
            // the datatable sorts out its own formatting.
            ?>
            table.find('colgroup').remove();
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
            defaultOptions.iDisplayLength = 25;
            defaultOptions.sDom = 'C<"clear">lfrtip';
            defaultOptions.sScrollX = "100%";
            defaultOptions.sPaginationType = "full_numbers";
            defaultOptions.oColReorder = {};
            defaultOptions.oColVis = {sSize: "auto",
                                      buttonText: '<?php echo escape_js(get_vocab("show_hide_columns")) ?>',
                                      bRestore: true,
                                      sRestore: '<?php echo escape_js(get_vocab("restore_original")) ?>'};

            defaultOptions.fnInitComplete = function(){
                if (((leftCol !== undefined) && (leftCol !== null)) ||
                    ((rightCol !== undefined) && (rightCol !== null)) )
                {
                  <?php 
                  // Fix the left and/or right columns.  This has to be done when 
                  // initialisation is complete as the language files are loaded
                  // asynchronously
                  ?>
                  var options = {};
                  if ((leftCol !== undefined) && (leftCol !== null))
                  {
                    options.iLeftColumns = 1;
                    options.sLeftWidth = "fixed";
                    options.iLeftWidth = getFixedColWidth(table, leftCol);
                  }
                  if ((rightCol !== undefined) && (rightCol !== null))
                  {
                    options.iRightColumns = 1;
                    options.sRightWidth = "fixed";
                    options.iRightWidth = getFixedColWidth(table, rightCol);
                  }
                  var oFC = new FixedColumns(oTable, options);
                  <?php
                  // Not quite sure why we have to adjust the column sizing here,
                  // but if we don't then the table isn't quite the right width 
                  // when first drawn
                  ?>
                  oTable.fnAdjustColumnSizing();
                }
                $('.js div.datatable_container').css('visibility', 'visible');
                <?php // Rebind the handler ?>
                $(window).bind('resize', windowResizeHandler);
              };
              
            <?php
            // If we've fixed the left or right hand columns, then (a) remove it
            // from the column visibility list because it is fixed and (b) stop it
            // being reordered
            ?>
            var colVisExcludeCols = []
            if ((leftCol !== undefined) && (leftCol !== null))
            { 
              colVisExcludeCols.push(0);
              defaultOptions.oColReorder = {iFixedColumns: 1};
            }
            if ((rightCol !== undefined) && (rightCol !== null))
            { 
              nCols = table.find('tr:first-child th').length;
              colVisExcludeCols.push(nCols - 1);
              <?php
              // Actually we stop them all from being reordered because at the moment
              // dataTables only has a way of stopping the leftmost n columns from
              // being reordered.  May be fixed in a future release
              ?>
              defaultOptions.oColReorder = {iFixedColumns: nCols};
            }
            defaultOptions.oColVis.aiExclude = colVisExcludeCols;
            <?php
            // Merge the specific options with the default options.  We do a deep
            // merge.
            ?>
            var mergedOptions = $.extend(true, {}, defaultOptions, specificOptions);
            
            var oTable = table.dataTable(mergedOptions);
            <?php
            // If we're using an Ajax data source then don't offer column reordering.
            // This is a problem at the moment in DataTables because if you reorder a column
            // DataTables doesn't know that the Ajax data is still in the original order.
            // May be fixed in a future release of DataTables
            ?>
            if (!specificOptions.sAjaxSource)
            {
              <?php
              /*
              // In fact we don't use column reordering at all, because (a) it doesn't
              // work with an Ajax source (b) there's no way of fixing the right hand column
              // (c) iFixedColumns doesn't seem to work properly and (d) it's confusing
              // for the user having reordering enabled sometimes and sometimes not.  Better
              // to wait for a future release of DataTables when these issues have been
              // fixed.  In the meantime the line of code we need is there below so we can see
              // how it is done, but commented out.
              
              var oCR = new ColReorder(oTable, mergedOptions);
              
              */
              ?>
            }

            $(window).bind('resize', windowResizeHandler);
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

function getErrorList(errors)
{
  var result = {html: '', text: ''};
  var patternSpan = /<span[\s\S]*span>/gi;
  var patternTags = /<\S[^><]*>/g;
  result.html += "<ul>";
  for (var i=0; i<errors.length; i++)
  {
    result.html += "<li>" + errors[i] + "<\/li>";
    result.text += '(' + (i+1).toString() + ') ';
    <?php // strip out the <span> and its contents and then all other tags ?>
    result.text += errors[i].replace(patternSpan, '').replace(patternTags, '') + "  \n";
  }
  result.html += "<\/ul>";
  return result;
}

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
  
<?php
} // edit_area_room


if ($page == 'edit_entry')
{

  // Add Ajax capabilities (but only if we can return the result as a JSON object)
  if (function_exists('json_encode'))
  {
    
    // Get the value of the field in the form
    ?>
    function getFormValue(formInput)
    {
      var value;
      <?php 
      // Scalar parameters (three types - checkboxes, radio buttons and the rest)
      ?>
      if (formInput.attr('name').indexOf('[]') == -1)
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
      <?php
      // Array parameters (two types - checkboxes and the rest, which could be
      // <select> elements or else multiple ordinary inputs with a *[] name
      ?>
      else
      {
        value = [];
        formInput.each(function(index) {
            if ((formInput.filter(':checkbox').length == 0) || $(this).is(':checked'))
            {
              var thisValue = $(this).val();
              if ($.isArray(thisValue))
              {
                $.merge(value, thisValue);
              }
              else
              {
                value.push($(this).val());
              }
            }
          });
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
      
        <?php
        // Load the params object with the values of all the form fields that are not
        // disabled and are not submit buttons of one kind or another
        ?>
        var relevantFields = form.find('[name]').not(':disabled, [type="submit"], [type="button"], [type="image"]');
        relevantFields.each(function() {
            <?php
            // Go through each of the fields and if we haven't got the value for a name
            // then go and get it.  (Remember that arrays can give more than one field
            // with the same name
            ?>
            var fieldName = $(this).attr('name');
            if (params[fieldName] === undefined)
            {
              params[fieldName] = getFormValue(relevantFields.filter('[name=' + fieldName.replace('[', '\\[').replace(']', '\\]') + ']'))
            }
          });
        
        <?php
        // For some reason I don't understand, posting an empty array will
        // give you a PHP array of ('') at the other end.    So to avoid
        // that problem, delete the property if the array (really an object) is empty
        ?>
        $.each(params, function(i, val) {
            if ((typeof(val) == 'object') && ((val === null) || (val.length == 0)))
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
            if (result.conflicts.length == 0)
            {
              conflictDiv.text(checkMark).attr('class', 'good').attr;
              titleText = '<?php echo escape_js(mrbs_entity_decode(get_vocab("no_conflicts"))) ?>';
              detailsHTML = titleText;
            }
            else
            {
              conflictDiv.text(cross).attr('class', 'bad');
              detailsHTML = "<p>";
              titleText = '<?php echo escape_js(mrbs_entity_decode(get_vocab("conflict"))) ?>' + ":  \n\n";
              detailsHTML += titleText + "<\/p>";
              var conflictsList = getErrorList(result.conflicts);
              detailsHTML += conflictsList.html;
              titleText += conflictsList.text;
            }
            conflictDiv.attr('title', titleText);
            scheduleDetails.html(detailsHTML);
            var policyDiv = $('#policy_check');
            if (result.rules_broken.length == 0)
            {
              policyDiv.text(checkMark).attr('class', 'good');
              titleText = '<?php echo escape_js(mrbs_entity_decode(get_vocab("no_rules_broken"))) ?>';
              detailsHTML = titleText;
            }
            else
            {
              policyDiv.text(cross).attr('class', 'bad');
              detailsHTML = "<p>";
              titleText = '<?php echo escape_js(mrbs_entity_decode(get_vocab("rules_broken"))) ?>' + ":  \n\n";
              detailsHTML += titleText + "<\/p>";
              var rulesList = getErrorList(result.rules_broken);
              detailsHTML += rulesList.html;
              titleText += rulesList.text;
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
    var maxDurationEnabled = areas[area]['max_duration_enabled'];
    var maxDurationSecs = areas[area]['max_duration_secs'];
    var maxDurationPeriods = areas[area]['max_duration_periods'];
    var maxDurationQty = areas[area]['max_duration_qty'];
    var maxDurationUnits = areas[area]['max_duration_units'];

    var isSelected, i, j, option, duration, defaultDuration, maxDuration;
    var nbsp = '\u00A0';
    var errorText = '<?php echo escape_js(get_vocab("start_after_end"))?>';
    var text = errorText;
    
    var startId = "start_seconds" + area;
    var startSelect = form[startId];
    var startKeepDisabled = ($('#' + startId).attr('class') == 'keep_disabled');
    var endId = "end_seconds" + area;
    var endSelect = form[endId];
    var endKeepDisabled = ($('#' + endId).attr('class') == 'keep_disabled');
    var allDayId = "all_day" + area;
    var allDay = form[allDayId];
    var allDayKeepDisabled = $('#' + allDayId).hasClass('keep_disabled');
    var startIndex, startValue, endIndex, endValue;
    
    <?php 
    // If All Day is checked then just set the start and end values to the first
    // and last possible options.
    ?>
    if (allDay && allDay.checked)
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
          if ((i == nEndOptions[area] - 1) ||
              (option[i]['value'] >= oldAreaEndValue))
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
    if (!allDay || !allDay.checked)
    {
      var newState = (dateDifference < 0);
      startSelect.disabled = newState || startKeepDisabled;
      endSelect.disabled = newState || endKeepDisabled;
      if (allDay)
      {
        allDay.disabled = newState || allDayKeepDisabled;
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

    $('#end_time_error').text('');  <?php  // Clear the error message ?>
    j = 0;
    for (i = 0; i < nEndOptions[area]; i++)
    {
      <?php
      // Limit the end slots to the maximum duration if that is enabled, if the
      // user is not an admin
      if (!$is_admin)
      {
        ?>
        if (maxDurationEnabled)
        {
          <?php
          // Calculate the duration in periods or seconds
          ?>
          duration = endOptions[area][i]['value'] - startValue;
          if (enablePeriods)
          {
            duration = duration/60 + 1;  <?php // because of the way periods work ?>
            duration += dateDifference * <?php echo count($periods) ?>;
          }
          else
          {
            duration += dateDifference * 60 * 60 *24;
          }
          maxDuration = (enablePeriods) ? maxDurationPeriods : maxDurationSecs;
          if (duration > maxDuration)
          {
            if (i == 0)
            {
              endSelect.options[j] = new Option(nbsp, endOptions[area][i]['value'], false, isSelected);
              var errorMessage = '<?php echo escape_js(get_vocab("max_booking_duration")) ?>' + nbsp;
              if (enablePeriods)
              {
                errorMessage += maxDurationPeriods + nbsp;
                errorMessage += (maxDurationPeriods > 1) ? '<?php echo escape_js(get_vocab("periods")) ?>' : '<?php escape_js(get_vocab("period_lc")) ?>';
              }
              else
              {
                errorMessage += maxDurationQty + nbsp + maxDurationUnits;
              }
              $('#end_time_error').text(errorMessage);
            }
            else
            {
              break;
            }
          }
        }
        <?php
      }
      ?>
      
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
  // There are some forms that have multiple submit buttons, eg a "Back" and "Save"
  // buttons.   In these cases we want hitting the Enter key in a text input field
  // to result in a "Save" rather than "Back".    So in these cases we have assigned
  // a class of 'default_action' to the one that we want to be executed when we hit
  // Enter.   (Note that it is a class rather than an id just in case we have two or
  // more such forms on a page.   However we should ensure that there is only one
  // button with this class per form.)
  ?>
  $('form input.default_action').each(function() {
      var defaultSubmitButton = $(this);
      $(this).parents('form').find('input').keypress(function(event) {
          if (event.which == 13)  // the Enter key
          {
            defaultSubmitButton.click();
            return false;
          }
          else
          {
            return true;
          }
        });
    });

  <?php
  // ADMIN.PHP
  if ($page == 'admin')
  {
    // Turn the list of rooms into a dataTable
    // If we're an admin, then fix the right hand column
    // (but not if we're running IE8 or below because for some reason I can't
    // get a fixed right hand column to work there.  It should do though, as it
    // works on the DataTables examples page)
    if ($is_admin)
    {
      ?>
      var rightCol = (lteIE8) ? null: {sWidth: "fixed", iWidth: 40};
      <?php
    }
    else
    {
      ?>
      var rightCol = null;
      <?php
    }
    ?>
    var roomsTable = makeDataTable('#rooms_table',
                                   {},
                                   {sWidth: "relative", iWidth: 33},
                                   rightCol);
    <?php
  }  
  
  // DAY.PHP, WEEK.PHP
  if (in_array($page, array('day', 'week')))
  {
    ?>
    var table = $('table.dwm_main');
    
    <?php // Turn all the multiple booking slots into the minimized state ?>
    table.find('td.multiple_booking')
        .removeClass('maximized')
        .addClass('minimized');
    <?php // Enable toggling on the +/- control ?>
    table.find('div.multiple_control')
        .click(function() {
            var cell = $(this).closest('td');
            if (cell.hasClass('maximized'))
            {
              cell.removeClass('maximized')
              cell.addClass('minimized');
            }
            else
            {
              cell.removeClass('minimized')
              cell.addClass('maximized');
            }
          });
                                          
     
    <?php
    // Resizable bookings (needs json_encode()).   These work by creating a div which 
    // is a clone of the real booking div and making it resizable.   We can't make the
    // real div resizable because it is bound by the table cell walls.   So we give
    // the clone an absolute position and a positive z-index.    We work out what
    // new booking the user is requesting by comparing the coordinates of the clone
    // with the table grid.   We also put the booking parameters (eg room id) as HTML5
    // data attributes in the cells of the header row and the column labels, so that we
    // can then get a set of parameters to send to edit_entry_handler as an Ajax request.
    // The result is a JSON object containg a success/failure boolean and the new table
    // HTML if successful or the reasons for failure if not.
    if (function_exists('json_encode'))
    {
      // 
      // We don't allow resizable bookings for IE8 and below.   In theory they should
      // be OK, but there seems to be a problem getting the resizing working properly.
      // (It looks as though it's probably something to do with the way .offset()
      // works in IE8 and below:  it seems to be giving some strange figures for the 
      // table coordinates.)
      ?>
      if (!lteIE8)
      {
        <?php
        // function to reverse a collection of jQuery objects
        ?>
        $.fn.reverse = [].reverse;
        
        <?php
        // Get the sides of the rectangle represented by the jQuery object jqObject
        // We round down the size of the rectangle to avoid any spurious overlaps
        // caused by rounding errors
        ?>
        function getSides(jqObject)
        {
          var sides = {};
          sides.n = Math.ceil(jqObject.offset().top);
          sides.w = Math.ceil(jqObject.offset().left);
          sides.s = Math.floor(sides.n + jqObject.outerHeight());
          sides.e = Math.floor(sides.w + jqObject.outerWidth());
          return sides;
        }
        
        
        <?php // Checks to see whether two rectangles occupy the same space ?>
        function rectanglesIdentical(r1, r2)
        {
          var tolerance = 2;  <?php //px ?>
          return ((Math.abs(r1.n - r2.n) < tolerance) &&
                  (Math.abs(r1.s - r2.s) < tolerance) &&
                  (Math.abs(r1.e - r2.e) < tolerance) &&
                  (Math.abs(r1.w - r2.w) < tolerance));
        }
            
                              
        <?php // Checks whether two rectangles overlap ?>         
        function rectanglesOverlap(r1, r2)
        {
          <?php
          // We check whether two rectangles overlap by checking whether any of the
          // sides of one rectangle intersect the sides of the other.   In the condition
          // below, we are checking on the first line to see if either of the vertical
          // sides of r1 intersects either of the horizontal sides of r2.  The second line
          // checks for intersection of the horizontal sides r1 with the vertical sides of r2.
          ?>
          if ( (( ((r1.w > r2.w) && (r1.w < r2.e)) || ((r1.e > r2.w) && (r1.e < r2.e)) ) && (r1.n < r2.s) && (r1.s > r2.n)) ||
               (( ((r1.n > r2.n) && (r1.n < r2.s)) || ((r1.s > r2.n) && (r1.s < r2.s)) ) && (r1.w < r2.e) && (r1.e > r2.w)) )
          {
            return true;
          }
          <?php // they also overlap if r1 is inside r2 ?>
          if ((r1.w >= r2.w) && (r1.n >= r2.n) && (r1.e <= r2.e) && (r1.s <= r2.s))
          {
            return true;
          }
          <?php // or r2 is inside r1 ?>
          if ((r2.w >= r1.w) && (r2.n >= r1.n) && (r2.e <= r1.e) && (r2.s <= r1.s))
          {
            return true;
          }
          return false;
        }
            
            
        <?php
        // Check whether the rectangle (with sides n,s,e,w) overlaps any
        // of the booked slots in the table.
        ?>
        function overlapsBooked(rectangle)
        {
          <?php
          // Check each of the booked cells in turn to see if it overlaps
          // the rectangle.  If it does return true immediately.
          ?>
          for (var i=0; i<bookedMap.length; i++)
          {
            if (rectanglesOverlap(rectangle, bookedMap[i]))
            {
              return true;
            }
          }
          return false;
        }
      
        <?php
        // Get the name of the data attribute in this jQuery object.
        ?>
        function getDataName(jqObject)
        {
          possibleNames = ['room', 'date', 'seconds'];
          for (var i=0; i<possibleNames.length; i++)
          {
            if (jqObject.data(possibleNames[i]) !== undefined)
            {
              return possibleNames[i];
            }
          }
          return false;
        }
        
        
        function redrawClones()
        {
          table.find('div.clone').each(function() {
              var clone = $(this);
              var original = clone.prev();
              clone.width(original.outerWidth())
                   .height(original.outerHeight())
            });
        }
        
        function getTableData(table, tableData)
        {
          <?php 
          // Build an object holding all the data we need about the table, which is
          // the coordinates of the cell boundaries and the names and values of the
          // data attributes.    The object has two properties, x and y, which in turn
          // are objects containing the data for the x and y axes.  Each of these
          // objects has a key property which holds the name of the data attribute and a
          // data object, which is an array of objects holding the coordinate and data
          // value at each cell boundary.
          //
          // Note that jQuery.offset() measures to the top left hand corner of the content
          // and does not take into account padding.   So we need to make sure that the padding-top
          // and padding-left is the same for all elements that we are going to measure so
          // that we can compare them properly.   It is simplest to use zero and put any
          // padding required on the contained element.
          ?>
          var rtl = ((table.css('direction') !== undefined) &&
                     table.css('direction').toLowerCase() == 'rtl');
          tableData.x = {};
          tableData.x.data = [];
          <?php // We need :visible because there might be hidden days // ?>
          var columns = table.find('thead tr:first-child th:visible').not('.first_last');
          <?php
          // If the table has direction rtl, as it may do if you're using a RTL language
          // such as Hebrew, then the columns will have been presented in the order right
          // to left and we'll need to reverse the columns.
          ?>
          if (rtl)
          {
            columns.reverse();
          }
          columns.each(function() {
              if (tableData.x.key === undefined)
              {
                tableData.x.key = getDataName($(this));
              }
              tableData.x.data.push({coord: $(this).offset().left,
                                     value: $(this).data(tableData.x.key)});
            });
          <?php 
          // and also get the right hand edge (and also the left hand edge if the
          // direction is RTL, as in Hebrew).  If we're dealing with seconds
          // we need to know what the end time of the slot would be
          ?>
          if (rtl)
          {
            columns.filter(':first').each(function() {
                var value = null;
                if (tableData.x.key == 'seconds')
                {
                  value = tableData.x.data[0].value;
                  value += tableData.x.data[0].value - tableData.x.data[1].value;
                }
                var edge = $(this).offset().left;
                tableData.x.data.unshift({coord: edge, value: value});
              });
          }

          columns.filter(':last').each(function() {
              var value = null;
              if (tableData.x.key == 'seconds')
              {
                value = tableData.x.data[tableData.x.data.length - 1].value;
                value += tableData.x.data[1].value - tableData.x.data[0].value;
              }
              var edge = $(this).offset().left + $(this).outerWidth();
              tableData.x.data.push({coord: edge, value: value});
            });

    
          tableData.y = {};
          tableData.y.data = [];
          var rows = table.find('tbody td:first-child').not('.multiple_booking td');
          rows.each(function() {
              if (tableData.y.key === undefined)
              {
                tableData.y.key = getDataName($(this));
              }
              tableData.y.data.push({coord: $(this).offset().top,
                                     value: $(this).data(tableData.y.key)});
            });
          <?php // and also get the bottom edge ?>
          rows.filter(':last').each(function() {
              var value = null;
              if (tableData.y.key == 'seconds')
              {
                value = tableData.y.data[tableData.y.data.length - 1].value + 
                        tableData.y.data[1].value - tableData.y.data[0].value;
              }
              tableData.y.data.push({coord: $(this).offset().top + $(this).outerHeight(),
                                     value: value});
            });
        }
        
        
        <?php
        // Tests whether the point p with coordinates x and y is outside the table
        ?>
        function outsideTable(p)
        {
          return ((p.x < tableData.x.data[0].coord) ||
                  (p.y < tableData.y.data[0].coord) ||
                  (p.x > tableData.x.data[tableData.x.data.length - 1].coord) ||
                  (p.y > tableData.y.data[tableData.y.data.length - 1].coord) );
        }
        
        <?php
        // Given 'div', snap the side specified (can be 'left', 'right', 'top' or 'bottom') to 
        // the nearest grid line, if the side is within the snapping range.
        //
        // If force is true, then the side is snapped regardless of where it is.
        //
        // We also contain the resize within the set of bookable cells
        //
        // We have to provide our own snapToGrid function instead of using the grid
        // option in the jQuery UI resize widget because our table may not have uniform
        // row heights and column widths - so we can't specify a grid in terms of a simple
        // array as required by the resize widget.
        ?>
        function snapToGrid(div, side, force)
        {
          var snapGap = (force) ? 100000: 20; <?php // px ?>
          var tolerance = 2; <?php //px ?>
          var isLR = (side=='left') || (side=='right');
 
          data = (isLR) ? tableData.x.data : tableData.y.data;

          for (var i=0; i<(data.length -1); i++)
          {
            var topLeft = data[i].coord + <?php echo $main_table_cell_border_width ?>;
            var bottomRight = data[i+1].coord;
            var divTop = div.offset().top;
            var divLeft = div.offset().left;
            var divWidth = div.outerWidth();
            var divHeight = div.outerHeight();
            switch (side)
            {
              case 'top':
                thisCoord = divTop;
                break;
              case 'bottom':
                thisCoord = divTop + divHeight;
                break;
              case 'left':
                thisCoord = divLeft;
                break;
              case 'right':
                thisCoord = divLeft + divWidth;
                break;
            }
            var gapTopLeft = thisCoord - topLeft;
            var gapBottomRight = bottomRight - thisCoord;
            
            if (((gapTopLeft>0) && (gapBottomRight>0)) ||
                <?php // containment tests ?>
                ((i==0) && (gapTopLeft<0)) ||
                ((i==(data.length-2)) && (gapBottomRight<0)) )
            {
              var gap = bottomRight - topLeft;
              
              if ((gapTopLeft <= gap/2) && (gapTopLeft < snapGap))
              {
                switch (side)
                {
                  case 'left':
                    div.offset({top: divTop, left: topLeft});
                    div.width(divWidth + gapTopLeft);
                    break;
                  case 'right':
                    <?php
                    // Don't let the width become zero.   (We don't need to do
                    // this for height because that's protected by a min-height
                    // rule.   Unfortunately we can't rely on uniform column widths
                    // so we can't use a min-width rule.
                    ?>
                    if ((divWidth - gapTopLeft) < tolerance)
                    {
                      div.width(divWidth + gapBottomRight);
                    }
                    else
                    {
                      div.width(divWidth - gapTopLeft);
                    }
                    break;
                  case 'top':
                    div.offset({top: topLeft, left: divLeft});
                    div.height(divHeight + gapTopLeft);
                    break;
                  case 'bottom':
                    div.height(divHeight - gapTopLeft);
                    break;
                }
                return;
              }
              else if ((gapBottomRight <= gap/2) && (gapBottomRight < snapGap))
              {
                switch (side)
                {
                  case 'left':
                    <?php // Don't let the width become zero.  ?>
                    if ((divWidth - gapBottomRight) < tolerance)
                    {
                      div.offset({top: div.Top, left: topLeft});
                      div.width(divWidth + gapTopLeft);
                    }
                    else
                    {
                      div.offset({top: divTop, left: bottomRight});
                      div.width(divWidth - gapBottomRight);
                    }
                    break;
                  case 'right':
                    div.width(divWidth + gapBottomRight);
                    break;
                  case 'top':
                    div.offset({top: bottomRight, left: divLeft});
                    div.height(divHeight - gapBottomRight);
                    break;
                  case 'bottom':
                    div.height(divHeight + gapBottomRight);
                    break;
                }
                return;
              }
            }
          }  <?php // for ?>
        }  <?php // snapToGrid() ?>
              

        <?php
        // Return the parameters for the booking represented by div
        // The result is an object with property of the data name (eg
        // 'seconds', 'time', 'room') and each property is an array of
        // the values for that booking (for example an array of room ids)
        ?>
        function getBookingParams(div)
        { 
          var rtl = (table.css('direction').toLowerCase() == 'rtl');
          var params = {};
          var data;
          var tolerance = 2; <?php //px ?>
          var cell = {x: {}, y: {}}
          cell.x.start = div.offset().left;
          cell.y.start = div.offset().top;
          cell.x.end = cell.x.start + div.outerWidth();
          cell.y.end = cell.y.start + div.outerHeight();
          for (var axis in cell)
          {
            data = tableData[axis].data;
            if (params[tableData[axis].key] === undefined)
            {
              params[tableData[axis].key] = [];
            }
            if (rtl && (axis=='x'))
            {
              for (var i = data.length - 1; i >= 0; i--)
              {
                if ((data[i].coord + tolerance) < cell[axis].start)
                {
                  <?php
                  // 'seconds' behaves slightly differently to the other parameters:
                  // we need to know the end time for the new slot.    Also it's possible
                  // for us to have a zero div, eg when selecting a new booking, and if
                  // so we need to make sure there's something returned
                  ?>
                  if ((tableData[axis].key == 'seconds') ||
                      (params[tableData[axis].key].length == 0))
                  {
                    params[tableData[axis].key].push(data[i].value);
                  }
                  break;
                }
                if ((data[i].coord + tolerance) < cell[axis].end)
                {
                  params[tableData[axis].key].push(data[i].value);
                }
              }
            }
            else
            {
              for (var i=0; i<data.length; i++)
              {
                if ((data[i].coord + tolerance) > cell[axis].end)
                {
                  <?php
                  // 'seconds' behaves slightly differently to the other parameters:
                  // we need to know the end time for the new slot.    Also it's possible
                  // for us to have a zero div, eg when selecting a new booking, and if
                  // so we need to make sure there's something returned
                  ?>
                  if ((tableData[axis].key == 'seconds') ||
                      (params[tableData[axis].key].length == 0))
                  {
                    params[tableData[axis].key].push(data[i].value);
                  }
                  break;
                }
                if ((data[i].coord + tolerance) > cell[axis].start)
                {
                  params[tableData[axis].key].push(data[i].value);
                }
              }
            }
          }
          return params;
        }
        
        
        function getRowNumber(y)
        {
          for (var i=0; i<tableData.y.data.length - 1; i++)
          {
            if (y >= tableData.y.data[i].coord && y < tableData.y.data[i+1].coord)
            {
              return i;
            }
          }
          return null;
        }
              
        <?php
        // function to highlight the row labels in the table that are level
        // with div
        ?>
        var highlightRowLabels = function (div)
        {
          if (highlightRowLabels.rows === undefined)
          {
            <?php // Cache the row label cells in an array ?>
            highlightRowLabels.rows = [];
            table.find('tbody tr').each(function() {
                highlightRowLabels.rows.push($(this).find('td.row_labels'));
              });
          }
          var divStartRow = getRowNumber(div.offset().top);
          var divEndRow = getRowNumber(div.offset().top + div.outerHeight());
          for (var i=0; i<highlightRowLabels.rows.length ; i++)
          {
            if (((divStartRow === null) || (divStartRow <= i)) && 
                ((divEndRow === null) || (i < divEndRow)))
            {
              highlightRowLabels.rows[i].addClass('selected');
            }
            else
            {
              highlightRowLabels.rows[i].removeClass('selected');
            }
          }
        }
        
        
        <?php // Remove any highlighting that has been applied to the row labels ?>
        function clearRowLabels()
        {
          if (highlightRowLabels.rows !== undefined)
          {
            for (var i=0; i<highlightRowLabels.rows.length; i++)
            {
              highlightRowLabels.rows[i].removeClass('selected');
            }
          }
        }
        
        
        var tableData = {};
        getTableData(table, tableData);
      
        <?php
        // bookedMap is an array of booked slots.   Each member of the array is an
        // object with four properties (n, s, e, w) representing the cooordinates (x or y)
        // of the side.   We will use this array to test whether a proposed
        // booking overlaps an existing booking.   We save populating this array until
        // the resize starts, because we want to exclude the booked slot that is being
        // resized.
        ?>
        var bookedMap = [];

        var downHandler = function(e) {
            <?php // Build the map of booked cells ?>
            table.find('td').not('td.new, td.row_labels').each(function() {
                bookedMap.push(getSides($(this)));
              });
            <?php // Apply a wrapper to turn off highlighting ?>
            table.wrap('<div class="resizing"><\/div>');
            var jqTarget = $(e.target);
            <?php // If we've landed on the + symbol we want the parent ?>
            if (e.target.nodeName.toLowerCase() == "img")
            {
              jqTarget = jqTarget.parent();
            }
            downHandler.origin = jqTarget.offset();
            downHandler.firstPosition = {x: e.pageX, y: e.pageY};
            <?php
            // Get the original link in case we need it later.    We can't be sure whether
            // the target was the <a> or the <td> so play safe and get all possibilities
            ?>
            downHandler.originalLink = jqTarget.find('a').andSelf('a').attr('href');
            downHandler.box = $('<div class="div_select">');
            <?php
            if (!$is_admin)
            {
              // If we're not an admin and we're not allowed to book repeats (in
              // the week view) or select multiple rooms (in the day view) then 
              // constrain the box to fit in the current slot width/height
              if ((($page == 'week') && $auth['only_admin_can_book_repeat']) ||
                  (($page == 'day') && $auth['only_admin_can_select_multiroom']))
              {
                if ($times_along_top)
                {
                  ?>
                  var slotHeight = jqTarget.outerHeight();
                  downHandler.maxHeight = true;
                  downHandler.box.css('max-height', slotHeight + 'px');
                  downHandler.box.css('min-height', slotHeight + 'px');
                  <?php
                }
                else
                {
                  ?>
                  var slotWidth = jqTarget.outerWidth();
                  downHandler.maxWidth = true;
                  downHandler.box.css('max-width', slotWidth + 'px');
                  downHandler.box.css('min-width', slotWidth + 'px');
                  <?php
                }
              }
            }
            ?>  
            downHandler.box.offset(downHandler.origin);
            $(document.body).append(downHandler.box);
          };
          
        var moveHandler = function(e) {
            var box = downHandler.box;
            var oldBoxOffset = box.offset();
            var oldBoxWidth = box.outerWidth();
            var oldBoxHeight = box.outerHeight();
            
            <?php
            // Check to see if we're only allowed to go one slot wide/high
            // and have gone over that limit.  If so, do nothing and return
            ?>
            if ((downHandler.maxWidth && (e.pageX < downHandler.origin.left)) ||
                (downHandler.maxHeight && (e.pageY < downHandler.origin.top)))
            {
              return;
            }
            <?php // Otherwise redraw the box ?>
            if (e.pageX < downHandler.origin.left)
            {
              if (e.pageY < downHandler.origin.top)
              {
                box.offset({top: e.pageY, left: e.pageX})
              }
              else
              {
                box.offset({top: downHandler.origin.top, left: e.pageX})
              }
            }
            else if (e.pageY < downHandler.origin.top)
            {
              box.offset({top: e.pageY, left: downHandler.origin.left})
            }
            else
            {
              box.offset(downHandler.origin);
            }
            box.width(Math.abs(e.pageX - downHandler.origin.left))
            box.height(Math.abs(e.pageY - downHandler.origin.top));
            var boxSides = getSides(box);
            snapToGrid(box, 'top');
            snapToGrid(box, 'bottom');
            snapToGrid(box, 'right');
            snapToGrid(box, 'left');
            <?php
            // If the new box overlaps a booked cell, then undo the changes
            ?>
            if (overlapsBooked(getSides(box)))
            {
              box.offset(oldBoxOffset)
                 .width(oldBoxWidth)
                 .height(oldBoxHeight);
            }
            <?php 
            // Check to see if we've moved outside the table and if we have
            // then give some visual feedback.   If we've moved back into the box
            // remove the feedback.
            ?>
            if (outsideTable({x: e.pageX, y: e.pageY}))
            {
              if (!moveHandler.outside)
              {
                box.addClass('outside');
                moveHandler.outside = true;
                clearRowLabels();
              }
            }
            else if (moveHandler.outside)
            {
              box.removeClass('outside');
              moveHandler.outside = false;
            }
            <?php 
            // Highlight the corresponding row label cells (provided we are 
            // inside the table)
            ?>
            if (!moveHandler.outside)
            {
              highlightRowLabels(box);
            }
          };
 
               
        var upHandler = function(e) {
            e.preventDefault();
            var tolerance = 2; <?php // px ?>
            var box = downHandler.box;
            var params = getBookingParams(box);
            $(document).unbind('mousemove',moveHandler);
            $(document).unbind('mouseup', upHandler);
            <?php // Remove the resizing wrapper so that highlighting comes back on ?>
            $('table.dwm_main').unwrap();
            <?php
            // If the user has released the button while outside the table it means
            // they want to cancel, so just return. 
            ?>
            if (outsideTable({x: e.pageX, y: e.pageY}))
            {
              box.remove();
              return;
            }
            <?php
            // If the user has hardly moved the mouse then just treat this as a
            // traditional click and follow the original link.   This will mean
            // that things such as the default duration are used.
            
            ?>
            else if ((Math.abs(e.pageX - downHandler.firstPosition.x) <= tolerance) &&
                     (Math.abs(e.pageY - downHandler.firstPosition.y) <= tolerance))
            {
              if (downHandler.originalLink !== undefined)
              {
                window.location = downHandler.originalLink;
              }
              else
              {
                box.remove();
              }
              return;
            }
            <?php
            // Otherwise get the selected parameters and go to the edit_entry page
            ?>
            var queryString = 'drag=1';  <?php // Says that we've come from a drag select ?>
            queryString += '&area=<?php echo $area ?>';
            queryString += '&start_seconds=' + params.seconds[0];
            queryString += '&end_seconds=' + params.seconds[params.seconds.length - 1];
            <?php
            if ($page == 'day')
            {
              ?>
              for (var i=0; i<params.room.length; i++)
              {
                queryString += '&rooms[]=' + params.room[i];
              }
              queryString += '&day=<?php echo $day ?>';
              queryString += '&month=<?php echo $month ?>';
              queryString += '&year=<?php echo $year ?>';
              <?php
            }
            else // it's a week
            {
              ?>
              queryString += '&rooms[]=<?php echo $room ?>';
              queryString += '&start_date=' + params.date[0];
              queryString += '&end_date=' + params.date[params.date.length - 1];
              <?php
            }
            ?>
            window.location = 'edit_entry.php?' + queryString;
            return;
          };

          
        <?php
        // Turn all the empty cells where a new multi-cell selection
        // can be created by dragging the mouse
        ?>     
        table.find('td.new').each(function() {
            $(this).find('a').click(function(event) {
                event.preventDefault();
              });
            $(this).mousedown(function(event) {
                event.preventDefault();
                downHandler(event);
                $(document).bind('mousemove', moveHandler);
                $(document).bind('mouseup', upHandler);
              })
          });
            
          
          
        <?php
        // Turn all the writable cells into resizable bookings
        ?>
        table.find('td.writable')
          .each(function() {
              <?php
              // resize event callback function
              ?>
              var divResize = function (event, ui)
              {
                if (divResize.origin === undefined)
                {
                  divResize.origin = divBooking.offset();
                  divResize.lastPosition = $.extend({}, divClone.position());
                  divResize.lastSize = {width: divClone.outerWidth(),
                                        height: divClone.outerHeight()};
                }

                var rectangle = {};
                rectangle.n = Math.round(divResize.origin.top + divClone.position().top);
                rectangle.w = Math.round(divResize.origin.left + divClone.position().left);
                rectangle.s = rectangle.n + Math.round(divClone.outerHeight());
                rectangle.e = rectangle.w + Math.round(divClone.outerWidth());
                            
                if (overlapsBooked(rectangle))
                {
                  divClone.resizable("disable");
                }
                else if (divClone.resizable('option', 'disabled'))
                {
                  divClone.resizable("enable");
                }
                <?php
                // Check to see if any of the four sides of the div have moved since the last time
                // and if so, see if they've got close enough to the next boundary that we can snap
                // them to the grid
                ?>
              
                <?php // left edge ?>
                if (divClone.position().left != divResize.lastPosition.left)
                {
                  snapToGrid(divClone, 'left');
                }
                <?php // right edge ?>
                if ((divClone.position().left + divClone.outerWidth()) != (divResize.lastPosition.left + divResize.lastSize.width))
                {
                  snapToGrid(divClone, 'right');
                }
                <?php // top edge ?>
                if (divClone.position().top != divResize.lastPosition.top)
                {
                  snapToGrid(divClone, 'top');
                }
                <?php // bottom edge ?>
                if ((divClone.position().top + divClone.outerHeight()) != (divResize.lastPosition.top + divResize.lastSize.height))
                {
                  snapToGrid(divClone, 'bottom');
                }
                
                highlightRowLabels(divClone);
                
                divResize.lastPosition = $.extend({}, divClone.position());
                divResize.lastSize = {width: divClone.outerWidth(),
                                      height: divClone.outerHeight()};
              }  <?php // divResize ?>
            
            
              <?php
              // callback function called when the resize starts
              ?>
              var divResizeStart = function (event, ui)
              {
                <?php
                // Add a wrapper so that we can disable the highlighting when we are
                // resizing (the flickering is a bit annoying)
                ?>
                table.wrap('<div class="resizing"><\/div>');
                <?php
                // Remove the constraint on the max width of the clone.  (We've had
                // to keep it there up until now because otherwise the div is 
                // sometimes 1px too wide.  Don't quite understand why - something to do
                // with rounding)
                ?>
                divClone.css('max-width', 'none');
                <?php
                // Add an outline to the original booking so that we can see where it
                // was.   The width and height are 2 pixels short of the original to allow
                // for a 1 pixel border all round.
                ?>
                $('<div class="outline"><\/div>')
                    .width(divClone.outerWidth() - 2)
                    .height(divClone.outerHeight() - 2)
                    .offset(divClone.offset())
                    .appendTo($('div.resizing'));
                <?php
                // Build the map of booked cells, excluding this cell (because we're
                // allowed to be in our own cell
                ?>
                table.find('td').not('td.new, td.row_labels').not(divBooking.closest('td')).each(function() {
                    bookedMap.push(getSides($(this)));
                  });

              }  <?php // divResizeStop ?>
            
            
              <?php
              // callback function called when the resize stops
              ?>
              var divResizeStop = function (event, ui)
              {          
                <?php // Clear the map of booked cells ?>
                bookedMap = [];
              
                if (divClone.resizable('option', 'disabled'))
                { 
                  <?php
                  // If the resize was disabled then just restore the original position
                  ?>
                  divClone.resizable('enable')
                          .offset(divBooking.offset())
                          .width(divBooking.outerWidth())
                          .height(divBooking.outerHeight());
                }
                else
                {
                  <?php
                  // Snap the edges to the grid, regardless of where they are.
                  ?>
                  snapToGrid(divClone, 'left', true);
                  snapToGrid(divClone, 'right', true);
                  snapToGrid(divClone, 'top', true);
                  snapToGrid(divClone, 'bottom', true);
                }
              
                <?php // Remove the outline ?>
                $('div.outline').remove();
                <?php // Remove the resizing wrapper so that highlighting comes back on ?>
                $('table.dwm_main').unwrap();
              
                var r1 = getSides(divBooking);
                var r2 = getSides(divClone);
                if (!rectanglesIdentical(r1, r2))
                {
                  <?php 
                  // We've got a change to the booking, so we need to send an Ajax
                  // request to the server to make the new booking
                  ?>
                  var data = {ajax: 1, 
                              commit: 1,
                              day: <?php echo $day ?>,
                              month: <?php echo $month ?>,
                              year: <?php echo $year ?>};
                  <?php // get the booking id ?>
                  data.id = divClone.data('id');
                  <?php // get the other parameters ?>
                  var oldParams = getBookingParams(divBooking);
                  var newParams = getBookingParams(divClone);
                  if (newParams.seconds !== undefined)
                  {
                    <?php
                    // We only send through the time parameters that have changed.
                    // This is so that edit_entry_handler.php knows whether to use
                    // the original booking parameters.    We need to do this so that
                    // we can properly handle multi-day bookings in the week view.
                    ?>
                    if (newParams.seconds[0] != oldParams.seconds[0])
                    {
                      data.start_seconds = newParams.seconds[0];
                    }
                    if (newParams.seconds[newParams.seconds.length - 1] !=
                        oldParams.seconds[oldParams.seconds.length - 1])
                    {
                      data.end_seconds = newParams.seconds[newParams.seconds.length - 1];
                      <?php
                      if ($enable_periods)
                      {
                        // When we're dealing with periods the end time is defined as
                        // the end of the last period (as opposed to the start of the
                        // next slot in times mode)
                        ?>
                        data.end_seconds -= 60;
                        <?php
                      }
                      ?>
                    }
                  }
                  <?php
                  if ($page == 'day')
                  {
                    ?>
                    data.page = 'day';
                    data.start_day = <?php echo $day ?>;
                    data.start_month = <?php echo $month ?>;
                    data.start_year = <?php echo $year ?>;
                    <?php
                  }
                  else  // it's 'week'
                  {
                    ?>
                    data.page = 'week';
                    var startDate = newParams.date[0].split('-');
                    data.start_year = startDate[0];
                    data.start_month = startDate[1];
                    data.start_day = startDate[2];
                    <?php
                    if ($is_admin || !$auth['only_admin_can_book_repeat'])
                    {
                      ?>
                      if (newParams.date.length > 1)
                      {
                        data.rep_type = <?php echo REP_DAILY ?>;
                        var repEndDate = newParams.date[newParams.date.length - 1].split('-');
                        data.rep_end_year = repEndDate[0];
                        data.rep_end_month = repEndDate[1];
                        data.rep_end_day = repEndDate[2];
                      }
                      <?php
                    }
                  }
                  ?>
                  data.end_day = data.start_day;
                  data.end_month = data.start_month;
                  data.end_year = data.start_year;
                  if (newParams.room !== undefined)
                  {
                    data.rooms = newParams.room;
                  }
                  <?php
                  if (isset($timetohighlight))
                  {
                    ?>
                    data.timetohighlight = <?php echo $timetohighlight ?>;
                    <?php
                  }
                  ?>
                  $.post('edit_entry_handler.php',
                         data,
                         function(result) {
                            if (result.valid_booking)
                            {
                              <?php
                              // The new booking succeeded.   (1) Empty the existing
                              // table in order to get rid of events and data and
                              // prevent memory leaks (2) insert the updated table HTML
                              // and then (3) trigger a window load event so that the
                              // resizable bookings are re-created.
                              ?>
                              table.empty();
                              table.html(result.table_innerhtml);
                              $(window).trigger('load');
                            }
                            else
                            {
                              divClone.offset(divBooking.offset())
                                      .width(divBooking.outerWidth())
                                      .height(divBooking.outerHeight());
                              var alertMessage = '';
                              if (result.conflicts.length > 0)
                              {
                                alertMessage += '<?php echo escape_js(mrbs_entity_decode(get_vocab("conflict"))) ?>' + ":  \n\n";
                                var conflictsList = getErrorList(result.conflicts);
                                alertMessage += conflictsList.text;
                              }
                              if (result.rules_broken.length > 0)
                              {
                                if (result.conflicts.length > 0)
                                {
                                  alertMessage += "\n\n";
                                }
                                alertMessage += '<?php echo escape_js(mrbs_entity_decode(get_vocab("rules_broken"))) ?>' + ":  \n\n";
                                var rulesList = getErrorList(result.rules_broken);
                                alertMessage += rulesList.text;
                              }
                              alert(alertMessage);
                            }
                          },
                         'json');
                }
              }  <?php // divResizeStop ?>
            
              <?php
              // Get the set of directions in which we are allowed to drag the
              // box.   At this stage we will do it by reference to the two axes,
              // the times axis and the other axis, which will be days or rooms.
              // Then later we will turn it into n/s/e/w handles (this will depend
              // on $times_along_top)
              ?>
              var directions = {times: {plus: true, minus: true},
                                other: {plus: true, minus: true}};
              if ($(this).hasClass('series'))
              {
                <?php
                // We only only members of a series to have their duration changed.
                // It would be a bit confusing to have an individual member of a 
                // series dragged across days to make it a new daily series, or rooms
                // to create new bookings in other rooms.
                ?>
                directions.other = {plus: false, minus: false};
              }
              <?php
              if (!$is_admin)
              {
                if ((($page == 'week') && $auth['only_admin_can_book_repeat']) ||
                    (($page == 'day') && $auth['only_admin_can_select_multiroom']))
                {
                  // If we're in the week view then if non-admins aren't allowed to
                  // make repeat bookings, or else if we're in the day view and they
                  // aren't allowed to select multiple rooms, then we want to restrict
                  // the handles we offer them so that they can't get that far.
                  ?>
                  directions.other = {plus: false, minus: false};
                  <?php
                }
                if ($auth['only_admin_can_book_multiday'])
                {
                  // Don't let non-admins drag multi-day bookings if
                  // $auth['only_admin_can_book_multiday'] is set
                  ?>
                  if ($(this).hasClass('multiday_start') ||
                      $(this).hasClass('multiday_end'))
                  {
                    directions.times = {plus: false, minus: false};
                  }
                  <?php
                }
              }
              // Don't allow multiday bookings to be moved at the end
              // which is joined to another day nor along the other axis
              ?>
              if ($(this).hasClass('multiday_start'))
              {
                directions.times.minus = false;
                directions.other = {plus: false, minus: false};
              }
              if ($(this).hasClass('multiday_end'))
              {
                directions.times.plus = false;
                directions.other = {plus: false, minus: false};
              }
              <?php
              // Now turn the directions in which we are allowed to move the 
              // boxes into an array of n/s/e/w handles, depending on the
              // setting of $times_along_top
              ?>
              var aHandles = [];
              if (directions.times.plus)
              {
                aHandles.push('<?php echo ($times_along_top) ? "e" : "s" ?>');
              }
              if (directions.times.minus)
              {
                aHandles.push('<?php echo ($times_along_top) ? "w" : "n" ?>');
              }
              if (directions.other.plus)
              {
                aHandles.push('<?php echo ($times_along_top) ? "s" : "e" ?>');
              }
              if (directions.other.minus)
              {
                aHandles.push('<?php echo ($times_along_top) ? "n" : "w" ?>');
              }
              <?php
              // Test each corner.  If we've got both the side handles then
              // add in the corner handle
              ?>
              var corners = ['nw', 'ne', 'se', 'sw'];
              for (var i=0; i<corners.length; i++)
              {
                if ((aHandles.indexOf(corners[i][0]) >= 0) &&
                    (aHandles.indexOf(corners[i][1]) >= 0))
                {
                  aHandles.push(corners[i]);
                }
              }
              var handles = aHandles.join(',');
              var divBooking = $(this).children('div');
              var divClone = divBooking.clone();
              divBooking.css('visibility', 'hidden');
              divClone.css('z-index', '500')
                      .css('position', 'absolute')
                      .css('top', '0')
                      .css('left', '0')
                      .css('background-color', $(this).css('background-color'))
                      .css('max-height', 'none')
                      .css('min-height', '<?php echo $main_cell_height ?>px')
                      .addClass('clone')
                      .width(divBooking.outerWidth())
                      .height(divBooking.outerHeight())
              if (handles)
              {
                divClone.resizable({handles: handles,
                                    resize: divResize,
                                    start: divResizeStart,
                                    stop: divResizeStop});
              }
              divClone.appendTo($(this));
              $(this).css('background-color', 'transparent')
                     .wrapInner('<div style="position: relative"><\/div>');
            });

        $(window).resize(function(event) {
            if (event.target == this)  <?php // don't want the ui-resizable event bubbling up ?>
            {
              <?php
              // The table dimensions have changed, so we need to redraw the clones
              // and re map the table
              ?>
              redrawClones();
              getTableData(table, tableData);
            }
          });

        <?php // also need to redraw and recalibrate if the multiple bookings are clicked ?>
        table.find('div.multiple_control')
            .click(function() {
                redrawClones();
                getTableData(table, tableData);
              });

      }  <?php // if (!lteIE8) ?>
      
      <?php
    } // if function_exists('json_encode')
  }
  
  
  
  // DAY.PHP, WEEK.PHP, MONTH.PHP
  // If we're running IE6 or below then we need to make bookable slots clickable
  // and respond to a mouse hovering over them (IE6 only supports the :hover pseudo
  // class on anchors).  We do this by toggling the class of the cell in question and also
  // the row_labels cells when the cell is hovered over.
  if (in_array($page, array('day', 'week', 'month')))
  {
    ?>
    if (lteIE6)
    {
      var dayWeekTable = $('#day_main, #week_main');
      dayWeekTable.find('td.new')
        .hover(function() {
            $(this).not('.multiple_booking').toggleClass('new_hover');
          });
      dayWeekTable.find('td')
        .hover(function() {
            $(this).parent().find('td.row_labels').toggleClass('row_labels_hover');
          });
      $('#month_main .valid a.new_booking')
        .parent().parent()
        .hover(function() {
            $(this).toggleClass('valid_hover');
          });
    }                             
                                     
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
    }
    
    <?php
    // Where we've got enabling checkboxes, apply a change event to them so that
    // when the enabling checkbox is changed the associated inputs are enabled or
    // disabled as appropriate.   Also trigger the change event when the page is loaded
    // so that the inputs are enabled/disabled correctly initially.
    ?>
    $('.enabler').change(function(){
        $(this).nextAll('input, select').attr('disabled', !$(this).is(':checked'));
      })
      .change();
    
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
    vocab['periods']['singular'] = '<?php echo escape_js(get_vocab("period_lc")) ?>';
    vocab['periods']['plural'] = '<?php echo escape_js(get_vocab("periods")) ?>';
    vocab['minutes'] = new Array();
    vocab['minutes']['singular'] = '<?php echo escape_js(get_vocab("minute_lc")) ?>';
    vocab['minutes']['plural'] = '<?php echo escape_js(get_vocab("minutes")) ?>';
    vocab['hours'] = new Array();
    vocab['hours']['singular'] = '<?php echo escape_js(get_vocab("hour_lc")) ?>';
    vocab['hours']['plural'] = '<?php echo escape_js(get_vocab("hours")) ?>';
    vocab['days'] = new Array();
    vocab['days']['singular'] = '<?php echo escape_js(get_vocab("day_lc")) ?>';
    vocab['days']['plural'] = '<?php echo escape_js(get_vocab("days")) ?>';
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
    allDay = form["all_day" + currentArea];
    if (allDay &&
        !allDay.disabled && 
        (parseInt(startSelect.options[startSelect.selectedIndex].value) == startOptions[currentArea][0]['value']) &&
        (parseInt(endSelect.options[endSelect.selectedIndex].value) == endOptions[currentArea][nEndOptions[currentArea] - 1]['value']))
    {
      allDay.checked = true;
      startSelect.disabled = true;
      endSelect.disabled = true;
      old_start = startSelect.options[startSelect.selectedIndex].value;
      old_end = endSelect.options[endSelect.selectedIndex].value;
    }
  }


  <?php
  // Set up the validation messages, but only if the function exists (which it
  // won't if we're on the login page)
  ?>
  if (typeof validationMessages === 'function')
  {
    validationMessages();
  }
  
  <?php
  // If anything like a submit button is pressed then add a data flag to the form so
  // that the function that checks for a valid booking can see if the change was
  // triggered by a Submit button being pressed, and if so, not to send an Ajax request.
  ?>
  $('form#main').find('[type="submit"], [type="button"], [type="image"]').click(function() {
    var trigger = $(this).attr('name');
    $(this).closest('form').data('submit', trigger);
  });

  $('form#main').bind('submit', function(e) {
      if ($(this).data('submit') == 'save_button')
      {
        <?php // Only validate the form if the Save button was pressed ?>
        var result = validate($(this));
        if (!result)
        {
          <?php // Clear the data flag if the validation failed ?>
          $(this).removeData('submit');
        }
        return result;
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
        var checkResults = $('#check_results');
        var checkTabs = $('#check_tabs');
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
        // to do is re-open the dialog if it has previously been closed and
        // select the tab corresponding to the div that was clicked
        ?>
        if (arguments.callee.alreadyExists)
        {
          if (!checkResults.dialog("isOpen"))
          {
            checkResults.dialog("open");
          }
          checkTabs.tabs("select", tabId);
          return;
        }
        <?php
        // We want to create a set of tabs that appear inside a dialog box,
        // with the whole structure being draggable.   Thanks to dbroox at
        // http://forum.jquery.com/topic/combining-ui-dialog-and-tabs for the solution.
        ?>
        checkTabs.tabs();
        checkTabs.tabs("select", tabId);
        checkResults.dialog({'width':400, 'height':200, 
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
    <?php // The Rights column has a span with title for sorting ?>
    tableOptions.aoColumnDefs = [{"sType": "title-numeric", "aTargets": [1]}]; 
    var usersTable = makeDataTable('#users_table',
                                   tableOptions,
                                   {sWidth: "relative", iWidth: 33});
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
                    var nTr = $(this).closest('.table_container').parent().prev();
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
      tableOptions.sScrollXInner = "100%";
      tableOptions.aoColumnDefs = colDefsMain;
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
          <?php // Rebind the handler ?>
          $(window).bind('resize', windowResizeHandler);
        };
      <?php
      // Remove the first column from the column visibility
      // list because it is the control column
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
      $ajax_url = basename($PHP_SELF) . "?" . $QUERY_STRING . "&ajax=1&phase=2";
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
                                 {"sType": "title-numeric", "aTargets": [3, 4, 5, -1]}]; 

    <?php
    // Fix the left hand column.  This has to be done when initialisation is 
    // complete as the language files are loaded asynchronously
    ?>
    tableOptions.fnInitComplete = function(){
        <?php
        // Try and get a sensible value for the fixed column width, which is the
        // smaller of the actual column width and a proportion of the overall table
        // width.   (Unfortunately the actual column width is just the width of the
        // column on the first page)
        ?>
        var table = $('#report_table');
        leftWidth = getFixedColWidth(table, {sWidth: "relative", iWidth: 33});
        var oFC = new FixedColumns(reportTable, {"iLeftColumns": 1,
                                                 "iLeftWidth": leftWidth,
                                                 "sLeftWidth": "fixed"});
        $('.js div.datatable_container').css('visibility', 'visible');
        <?php // Rebind the handler ?>
        $(window).bind('resize', windowResizeHandler);
        <?php 
        // Also add a "Delete entries button", provided that (a) the user is an
        // admin and (b) the configuration allows it
        if ($is_admin && $auth['show_bulk_delete'])
        {
          ?>
          $('<button id="delete_button"><?php echo escape_js(get_vocab("delete_entries")) ?><\/button>')
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
    // Remove the first column from the column visibility
    // list because it is fixed
    ?>
    tableOptions.oColVis = {aiExclude: [0]};
    <?php
    // and stop those first two columns being reordered
    ?>
    tableOptions.oColReorder = {iFixedColumns: 1};

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
      $ajax_url = basename($PHP_SELF) . "?" . $QUERY_STRING . "&ajax=1";
      ?>
      tableOptions.sAjaxSource = "<?php echo $ajax_url ?>";
      tableOptions.aoColumnDefs = [{"sType": "title-numeric", "aTargets": [2]}]; 
      
      var searchTable = makeDataTable('#search_results', 
                                      tableOptions, 
                                      {sWidth: "relative", iWidth: 33} );

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
  echo "<body class=\"non_js ".htmlspecialchars($page)."\">\n";
   
    // Add a class of "js" so that we know if we're using JavaScript or not
    // and remove the non_js class (it's sometimes useful to know that we're
    // not running JavaScript)
  ?>
    <script type="text/javascript">
      //<![CDATA[
      $('body').addClass('js').removeClass('non_js');
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
            // Suppress error messages in case the logo is a URL and allow_url_fopen
            // is not enabled in php.ini
            $logo_size = @getimagesize($mrbs_company_logo);
            echo "<img src=\"$mrbs_company_logo\" " . $logo_size[3] . " alt=\"$mrbs_company\">\n";
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
            $sql .= " AND create_by='" . sql_escape($user) . "'";
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
            <input type="search" name="search_str" value="<?php echo htmlspecialchars($search_str) ?>">
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

<div id="contents">
<?php

} // end of print_theme_header()
?>
