<?php
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

?>

$(document).on('page_ready', function() {
  
  <?php
  // Tidy up the presentation of the first header row by merging the cells.
  // The div is hidden while we are manipulating it so that it doesn't flicker;
  // we have to make it visible when we have finished
  ?>
  var summaryDiv = $('#div_summary'),
      summaryHead = summaryDiv.find('thead'),
      tableOptions;
      
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
  $('input[name="output"]').on('change', function() {
      var output = $(this).filter(':checked').val(),
          formatButtons = $('input[name="output_format"]'),
          icalButton = formatButtons.filter('[value="' + <?php echo OUTPUT_ICAL ?> + '"]');
          
      if (output === '<?php echo SUMMARY ?>')
      {
        icalButton.prop('disabled', true);
        if (icalButton.is(':checked'))
        {
          formatButtons.filter('[value="' + <?php echo OUTPUT_HTML ?> + '"]').attr('checked', 'checked');
        }
      }
      else
      {
        icalButton.prop('disabled', false);
      }
    }).trigger('change');
  
  
  <?php
  // Turn the list of users into a dataTable
  ?>
  tableOptions = {};
  <?php
  // Use an Ajax source - gives much better performance for large tables

  // May need to use the FormData emulation (https://github.com/francois2metz/html5-formdata)
  // for older browsers
  ?>
  tableOptions.ajax = {url: 'report.php',
                       method: 'POST', 
                       processData: false,
                       contentType: false,
                       data: function() {
                           var formdata = new FormData($('#report_form')[0]);
                           return formdata;
                         } };
  <?php
  // Add in a hidden input to the form so that we can tell if we are using DataTables
  // (which will be if JavaScript is enabled).   We need to know this because when we're using an 
  // Ajax data source we don't want to send the HTML version of the table data.
  ?>

  $('<input>').attr({
      type: 'hidden',
      name: 'datatable',
      value: '1'
    }).appendTo('#report_form');

  var table = $('#report_table'),
      reportTable;
  
  <?php 
  // Get the types and feed those into dataTables
  ?>
  tableOptions.columnDefs = getTypes(table);

  <?php 
  // Add a "Delete entries button", provided that (a) the user is an
  // admin and (b) the configuration allows it
  if ($auth['show_bulk_delete'])
  {
    ?>
    if (args.isAdmin)
    {
      tableOptions.initComplete = function(){
        
            $('<button id="delete_button"><?php echo escape_js(get_vocab("delete_entries")) ?><\/button>')
                  .on('click', function() {
                      var data = reportTable.rows({filter: 'applied'}).data().toArray(),
                          nEntries = data.length;
                          
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
                        var batchSize = <?php echo DEL_ENTRY_AJAX_BATCH_SIZE ?>,
                            batches = [],
                            batch = [],
                            nBatches,
                            results,
                            i,
                            j;
                            
                        for (i=0; i<nEntries; i++)
                        {
                          batch.push($(data[i][0]).data('id'));
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
                        nBatches = batches.length;
                        if (nBatches > 0)
                        {
                          results = [];
                          $('#report_table_processing').css('visibility', 'visible');
                          for (j=0; j<nBatches; j++)
                          {
                            $.post('ajax/del_entry.php',
                                   {csrf_token: getCSRFToken(),
                                    ids: batches[j]},
                                   function(result) {
                                      var nDeleted,
                                          isInt,
                                          i,
                                          oSettings,
                                          span;
                                          
                                      results.push(result);
                                      <?php // Check whether everything has finished ?>
                                      if (results.length >= nBatches)
                                      {
                                        $('#report_table_processing').css('visibility', 'hidden');
                                        <?php
                                        // If all's gone well the result will contain the number of entries deleted
                                        ?>
                                        nDeleted = 0;
                                        isInt = /^\s*\d+\s*$/;
                                        for (i=0; i<results.length; i++)
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

                                        if (reportTable.ajax.url() && 
                                            !reportTable.page.info().serverSide &&
                                            ($('#div_summary').length === 0))
                                        {
                                          reportTable.ajax.reload();
                                          <?php
                                          // We also need to update the count of the number of entries.  We
                                          // can't just get the length of the data, because the new data is
                                          // loaded asynchronously and won't be there yet.  So we calculate it
                                          // by subtracting the number of entries deleted from theee previous count.
                                          ?>
                                          span = $('#n_entries');
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

        };
    }
    <?php
  }
  ?>


  reportTable = makeDataTable('#report_table', tableOptions, {leftColumns: 1});
  
});
