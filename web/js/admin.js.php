<?php
declare(strict_types=1);
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry
?>

'use strict';

$(document).on('page_ready', function() {
  // Wait for DOM to be fully ready
  setTimeout(function() {
    var $table = $('#rooms_table');
    
    if ($table.length === 0) {
      return;
    }

    var tableOptions = {
      pageLength: 25,
      order: [[0, 'asc']],
      columnDefs: getTypes($table),
      drawCallback: function(settings) {
        var api = this.api();
        if (api && typeof api.columns === 'function') {
          api.columns.adjust();
        }
      }
    };
    
    var fixedColumnsOptions = {
      leftColumns: 1,
      rightColumns: args.isAdmin ? 1 : 0
    };

    makeDataTable('#rooms_table', tableOptions, fixedColumnsOptions);
  }, 0);
});
