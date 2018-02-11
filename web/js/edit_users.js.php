<?php
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

// =================================================================================

// Extend the init() function 
?>

var oldInitEditUsers = init;
init = function(args) {
  oldInitEditUsers.apply(this, [args]);

  <?php // Turn the list of users into a dataTable ?>
  
  var tableOptions = {};
  
  <?php // Use an Ajax source - gives much better performance for large tables ?>
  var queryString = window.location.search;
  if (queryString.length === 0)
  {
    queryString = '?';
  }
  queryString += '&ajax=1';
  tableOptions.ajax = 'edit_users.php' + queryString;
  
  <?php // Get the types and feed those into dataTables ?>
  tableOptions.columnDefs = getTypes($('#users_table'));
  makeDataTable('#users_table', tableOptions, {leftColumns: 1});
};

