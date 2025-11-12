<?php
declare(strict_types=1);
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry
?>

'use strict';

$(document).on('page_ready', function() {
  // Initialize any select elements that might need DataTables functionality
  if ($.fn.select2) {
    $('select').select2({
      width: 'resolve',
      theme: 'bootstrap'
    });
  }
  
  // Initialize any tables that might be present
  if ($.fn.DataTable) {
    $('table.admin_table').each(function() {
      var tableOptions = {
        pageLength: 25,
        stateSave: true,
        buttons: ['colvis'],
        dom: 'Bfrtip'
      };
      
      makeDataTable(this, tableOptions);
    });
  }
});
