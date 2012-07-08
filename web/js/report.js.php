<?php

// $Id$

require_once "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry

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

$user = getUserName();
$is_admin = (authGetUserLevel($user) >= $max_level);



// =================================================================================


// Extend the init() function 
?>

var oldInitReport = init;
init = function(args) {
  oldInitReport.apply(this, [args]);
  
  <?php
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
  
  // We don't support iCal output for the Summary.   So if the Summary button is pressed
  // disable the iCal button and, if iCal output is checked, check another format.  If the
  // Report button is pressed then re-enable the iCal button.
  ?>
  $('input[name="output"]').change(function() {
      var output = $(this).filter(':checked').val();
      var formatButtons = $('input[name="output_format"]');
      var icalButton = formatButtons.filter('[value="' + <?php echo OUTPUT_ICAL ?> + '"]');
      if (output == <?php echo SUMMARY ?>)
      {
        icalButton.attr('disabled', 'disabled');
        if (icalButton.is(':checked'))
        {
          formatButtons.filter('[value="' + <?php echo OUTPUT_HTML ?> + '"]').attr('checked', 'checked');
        }
      }
      else
      {
        icalButton.removeAttr('disabled');
      }
    }).trigger('change');
  
  <?php
  // Turn the list of users into a dataTable
  ?>
  var tableOptions = new Object();
  <?php
  // Use an Ajax source if we're able to - gives much better
  // performance for large tables
  if (function_exists('json_encode'))
  {
    list( ,$query_string) = explode('?', $HTTP_REFERER, 2);
    $ajax_url = "report.php?" . $query_string . "&ajax=1&phase=2";
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
  
}