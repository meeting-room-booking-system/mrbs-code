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
  tableOptions.ajax = {
      url: 'report.php' + ((args.site) ? '?site=' + args.site : ''),
      method: 'POST',
      processData: false,
      contentType: false,
      data: function() {
          return new FormData($('#report_form')[0]);
        }
    };

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

    var addDeleteButton = function addDeleteButton() {

      var data;
      var batches;
      var nBatches;
      var nEntries;
      var progressContainer = $('<div id="progress_container"></div>');
      var requests;
      var requestsCompleted;
      var requestsAborted;

      <?php // Initialise the delete button and progress bar ?>
      function initDeleteButton()
      {
        var title = '<?php echo escape_js(get_vocab("deleting_n_entries")) ?>';
        data = reportTable.rows({filter: 'applied'}).data().toArray();
        nEntries = data.length;
        requests = [];
        requestsCompleted = 0;
        requestsAborted = 0;
        title = title.replace('%d', nEntries.toLocaleString());
        progressContainer.find('span').text(title);
      }

      function reloadReport() {
        $('#report_table_processing').css('visibility', 'hidden');

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
          reportTable.ajax.reload(function() {
            <?php
            // Once the report data has been reloaded we also need to update the number of entries,
            // hide the progress bar and reinitialise the delete button for the new data.
            ?>
            progressContainer.hide();
            initDeleteButton();
            $('#n_entries').text(nEntries);
          });
        }
        else
        {
          window.location.reload();
        }
      }

      <?php
      // Add a progress bar, with a title and a cancel button. The title text will be
      // added later, once we know how many entries there are to be deleted.
      ?>
      progressContainer.append('<span></span>')
        .append('<progress></progress>')
        .append('<button id="cancel_deletions"><?php echo escape_js(get_vocab("cancel")) ?></button>')
        .insertAfter('#report_table_paginate');

      <?php // Add a delete button ?>
      $('<button id="delete_button"><?php echo escape_js(get_vocab("delete_entries")) ?><\/button>')
        .on('click', function() {
            if (nEntries === 0) {
              return;
            }

            <?php
            // Fire off an Ajax POST request.  Upon completion fire off another one until there
            // are none left.
            ?>
            var postBatch = function(batch) {
              var params = {
                csrf_token: getCSRFToken(),
                <?php // The ids are JSON encoded to avoid hitting the php.ini max_input_vars limit ?>
                ids: JSON.stringify(batch)
              };
              if (args.site) {
                params.site = args.site;
              }
              <?php // Save the XHR request in case we need to abort it ?>
              requests.push($.post(
                  'ajax/del_entries.php',
                  params,
                  function (result) {
                    var isInt = /^\s*\d+\s*$/;
                    console.log(result);
                    requestsCompleted++;
                    if (isInt.test(result)) {
                      totalDeleted += parseInt(result, 10);
                      progress.val(totalDeleted).text(totalDeleted);
                    } else {
                      success = false;
                    }
                    <?php // Fire off another request if there is one ?>
                    var batch = batches.pop();
                    if (batch !== undefined) {
                      postBatch(batch);
                    }
                    <?php // Otherwise check whether everything has finished ?>
                    else if (requestsCompleted + requestsAborted >= nBatches) {
                      <?php
                      // Log the time it took to delete the entries if we're got $debug set.
                      if ($debug)
                      {
                        ?>
                        console.log((Date.now() - startTime)/1000 + " seconds");
                        <?php
                      }
                      ?>
                      if (!success) {
                        window.alert("<?php echo escape_js(get_vocab('delete_entries_failed')) ?>");
                      }
                      reloadReport();
                    }
                  }
                )
              )
            }

            var message = "<?php echo escape_js(get_vocab('delete_entries_warning')) ?>";
            message = message.replace('%s', nEntries.toLocaleString());
            if (!window.confirm(message)) {
              return;
            }
            var progress = progressContainer.find('progress');
            var success = true;
            var totalDeleted = 0;
            <?php
            // If $debug is set record how long it takes to delete the entries
            if ($debug)
            {
              ?>
              var startTime = Date.now();
              <?php
            }
            ?>

            progress.attr('max', nEntries).val(0).text('0');
            progressContainer.show();
            <?php
            // We're going to split the POST requests into batches because if a
            // single POST request is too large we could get a 406 error. The POST
            // requests are fired off asynchronously, so we need to count them all
            // back before we know that we've finished.
            ?>
            var batchSize = <?php echo $del_entries_ajax_batch_size ?>,
                batch = [],
                i;

            batches = [];

            for (i=0; i<nEntries; i++) {
              batch.push($(data[i][0]).data('id'));
              if (batch.length >= batchSize) {
                batches.push(batch);
                batch = [];
              }
            }
            if (batch.length > 0) {
              batches.push(batch);
            }
            <?php
            // Dispatch the batches (if any) setting off parallel Ajax requests up to the maximum
            // number determined by the config settings.  When each request completes it will set
            // off another one until all the batches have been processed.
            ?>
            nBatches = batches.length;
            if (nBatches > 0) {
              $('#report_table_processing').css('visibility', 'visible');
              for (i=0; i<<?php echo $del_entries_parallel_requests ?>; i++) {
                batch = batches.pop();
                if (batch !== undefined) {
                  postBatch(batch);
                }
              }
            }
          })
        .insertAfter('#report_table_paginate');

      <?php // While deletion is in progress disable all interaction except with the cancel button ?>
      $(window).on('click keypress', function(e) {
        if (progressContainer.is(':visible'))
        {
          if (e.target.id === 'cancel_deletions')
          {
            $.each(requests, function(index, request) {
              <?php
              // Abort all requests that haven't yet been sent, ie all those where the
              // readyState hasn't yet reached 2 (HEADERS_RECEIVED).  For more details see
              // https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest/readyState.
              // We don't abort those that have already been sent because we can't stop the
              // server processing the deletions, and we want to know the result.
              ?>
              if (request.readyState < 2)
              {
                request.abort();
                requestsAborted++;
                if (requestsCompleted + requestsAborted + batches.length >= nBatches)
                {
                  reloadReport();
                }
              }
            });
          }
          e.preventDefault();
          return false;
        }
      });

      initDeleteButton();
    };

    if (args.isAdmin)
    {
      tableOptions.initComplete = addDeleteButton;
    }
    <?php
  }
  ?>

  <?php
  // If the sort columns are specified then tell DataTables to sort by those columns on
  // initialisation, otherwise it will sort by the default column.  We also need to
  // tell DataTables not to use the saved state, otherwise it will confuse the user
  // who has just changed the "Sort by" option on the report form.
  ?>
  var sortColumns = table.data('sortColumns');
  if ((sortColumns !== undefined) && (sortColumns.length))
  {
    tableOptions.order = [];
    sortColumns.forEach(function(column) {
      tableOptions.order.push([column, 'asc']);
    });
    tableOptions.stateSave = false;
  }

  <?php // If we're an admin then add a "Copy email addresses" button ?>
  if (args.isAdmin) {
    tableOptions.buttons = [
      {
        <?php // The first button is assumed to be the colvis button ?>
        extend: 'colvis'
      },
      {
        <?php
        // Add in an extra button to copy email addresses as a unique, sorted, comma separated
        // list so that they can be pasted into an address field in an email client.
        // Useful for sending messages to those booked on a certain day or in a certain room.
        ?>
        text: '<?php echo escape_js(get_vocab('copy_email_addresses')) ?>',
        action: function (e, dt, node, config) {
          extractEmailAddresses(dt, '#col_create_by', true);
        }
      }
    ];
  }

  reportTable = makeDataTable('#report_table', tableOptions, {leftColumns: 1});

});
