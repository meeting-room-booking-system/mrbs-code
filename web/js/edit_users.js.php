<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry

// =================================================================================

// Extend the init() function 
?>

var oldInitEditUsers = init;
init = function(args) {
  oldInitEditUsers.apply(this, [args]);

  <?php // Turn the list of users into a dataTable ?>
  
  var tableOptions = new Object();
  <?php // The Rights column has a span with title for sorting ?>
  tableOptions.aoColumnDefs = [{"sType": "title-numeric", "aTargets": [1]}]; 
  var usersTable = makeDataTable('#users_table',
                                 tableOptions,
                                 {sWidth: "relative", iWidth: 33});
}
