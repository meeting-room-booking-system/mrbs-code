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

  var tableOptions = {};
  var fixedColumnsOptions = {leftColumns: 1};

  <?php // Get the types and feed those into dataTables ?>
  tableOptions.columnDefs = getTypes($('#rooms_table'));

  <?php
  // Turn the list of rooms into a dataTable
  // If we're an admin, then fix the right hand column
  ?>
  if (args.isAdmin)
  {
    fixedColumnsOptions.rightColumns = 1;
  }

  makeDataTable('#rooms_table', tableOptions, fixedColumnsOptions);
});

