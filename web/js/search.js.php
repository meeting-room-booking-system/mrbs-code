<?php
declare(strict_types=1);
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry
?>

'use strict';

$(document).on('page_ready', function() {

  var searchForm = $('#search_form'),
      table = $('#search_results'),
      tableOptions;

  <?php
  // Turn the list of results into a dataTable, provided that we can use
  // an Ajax source.  Otherwise they just get the old style search page
  // with "Next" and "Prev" buttons to get new pages from the server.
  ?>

  if (table.length)
  {
    tableOptions = {ajax: {url: 'search.php' + ((args.site) ? '?site=' + args.site : ''),
                           method: 'POST',
                           data: function() {
                               <?php
                               // Get the search parameters, which are all in data- attributes, so
                               // that we can use them in an Ajax post; add in the datatable
                               // flag and also the CSRF token
                               ?>
                               var data = table.data();
                               data.datatable = '1';
                               data.csrf_token = getCSRFToken();
                               return data;
                             }}};

    <?php // Get the types and feed those into dataTables ?>
    tableOptions.columnDefs = getTypes(table);

    tableOptions.buttons = [
      {
        <?php // The first button is assumed to be the colvis button ?>
        extend: 'colvis'
      },
      {
        <?php
        // Add in an extra button to export the results as an iCalendar (.ics) file.
        ?>
        text: '<?php echo get_js_vocab('export_as_ics') ?>',
        action: function (e, dt, node, config) {
          <?php // Get the form data, which will already include a CSRF token ?>
          var data = $('#search_form').serializeArray();
          <?php // Add in a parameter to tell the server we want an iCalendar export ?>
          data.push({name: 'ics', value: 1});
          $.post({
            url: window.location.href,
            data: data,
            success: function(blob, status, xhr) {
              <?php
              // See https://stackoverflow.com/questions/16086162/handle-file-download-from-ajax-post
              ?>
              var contentType = xhr.getResponseHeader('Content-Type');
              var contentTypeRegex = /^[^;]*/;
              var matches = contentType.match(contentTypeRegex);
              var type = (matches === null) ? '' : matches[0];
              var filename = "";
              var disposition = xhr.getResponseHeader('Content-Disposition');
              if (disposition && disposition.indexOf('attachment') !== -1) {
                var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                matches = filenameRegex.exec(disposition);
                if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
              }

              if (typeof window.navigator.msSaveBlob !== 'undefined') {
                // IE workaround for "HTML7007: One or more blob URLs were revoked by closing the blob for which they were created. These URLs will no longer resolve as the data backing the URL has been freed."
                window.navigator.msSaveBlob(blob, filename);
              } else {
                var URL = window.URL || window.webkitURL;
                var downloadUrl = URL.createObjectURL(new Blob([blob], {type: type}));

                if (filename) {
                  // use HTML5 a[download] attribute to specify filename
                  var a = document.createElement("a");
                  // safari doesn't support this yet
                  if (typeof a.download === 'undefined') {
                    window.location.href = downloadUrl;
                  } else {
                    a.href = downloadUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                  }
                } else {
                  window.location.href = downloadUrl;
                }

                setTimeout(function () { URL.revokeObjectURL(downloadUrl); }, 100); // cleanup
              }
            }
          });
        }
      }
    ]

    makeDataTable('#search_results', tableOptions, {"leftColumns": 1});
  }

});

