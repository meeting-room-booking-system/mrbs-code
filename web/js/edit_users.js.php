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

  <?php // Turn the list of users into a dataTable ?>
  
  var tableOptions = {};
  
  <?php // Use an Ajax source - gives much better performance for large tables ?>
  var queryString = window.location.search;
  tableOptions.ajax = 'edit_users.php' + queryString;
  
  <?php // Get the types and feed those into dataTables ?>
  tableOptions.columnDefs = getTypes($('#users_table'));
  makeDataTable('#users_table', tableOptions, {leftColumns: 1});
  
});

