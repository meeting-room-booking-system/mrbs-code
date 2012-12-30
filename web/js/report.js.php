<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(0); // Cannot cache file because it depends on $HTTP_REFERER

if ($use_strict)
{
  echo "'use strict';\n";
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
  // Tidy up the presentation of the first header row by merging the cells.
  // The div is hidden while we are manipulating it so that it doesn't flicker;
  // we have to make it visible when we have finished
  ?>
  var summaryDiv = $('#div_summary');
  var summaryHead = summaryDiv.find('thead');
  summaryHead.find('tr:first th:odd').attr('colspan', '2');
  summaryHead.find('tr:first th:even').not(':first').remove();
  summaryHead.find('tr:first th:first').attr('rowspan', '2');
  summaryHead.find('tr:eq(1) th:first').remove();
  summaryDiv.css('visibility', 'visible');
  
  
  <?php
  // We don't support iCal output for the Summary.   So if the Summary button is pressed
  // disable the iCal button and, if iCal output is checked, check another format.  If the
  // Report button is pressed then re-enable the iCal button.
  ?>
  $('input[name="output"]').change(function() {
      var output = $(this).filter(':checked').val();
      var formatButtons = $('input[name="output_format"]');
      var icalButton = formatButtons.filter('[value="' + <?php echo OUTPUT_ICAL ?> + '"]');
      if (output === '<?php echo SUMMARY ?>')
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
  var tableOptions = {};
  <?php
  // Use an Ajax source if we're able to - gives much better
  // performance for large tables
  if (function_exists('json_encode'))
  {
    list( ,$query_string) = explode('?', $HTTP_REFERER, 2);
    $ajax_url = "report.php?" . (empty($query_string) ? '' : "$query_string&") . "ajax=1&phase=2";
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
  
  var table = $('#report_table');
  
  <?php 
  // Get the sTypes and feed those into dataTables
  ?>
  tableOptions.aoColumnDefs = getSTypes(table);

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
      var leftWidth = getFixedColWidth(table, {sWidth: "relative", iWidth: 33});
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
                  if (window.confirm("<?php echo escape_js(get_vocab('delete_entries_warning')) ?>" +
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
                      batch.push($(aData[i][0]).data('id'));
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
                                        window.alert("<?php echo escape_js(get_vocab('delete_entries_failed')) ?>");
                                        break;
                                      }
                                      nDeleted += parseInt(results[i], 10);
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
                                        ($('#div_summary').length === 0))
                                    {
                                      reportTable.fnReloadAjax();
                                      <?php
                                      // We also need to update the count of the number of entries.  We
                                      // can't just get the length of the data, because the new data is
                                      // loaded asynchronously and won't be there yet.  So we calculate it
                                      // by subtracting the number of entries deleted from theee previous count.
                                      ?>
                                      var span = $('#n_entries');
                                      span.text(parseInt(span.text(), 10) - nDeleted);
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
  
};
