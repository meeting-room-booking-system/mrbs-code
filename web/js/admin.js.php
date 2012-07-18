<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry

$user = getUserName();
$is_admin = (authGetUserLevel($user) >= $max_level);

// =================================================================================

// Extend the init() function 
?>

var oldInitAdmin = init;
init = function(args) {
  oldInitAdmin.apply(this, [args]);
  
  <?php
  // Turn the list of rooms into a dataTable
  // If we're an admin, then fix the right hand column
  // (but not if we're running IE8 or below because for some reason I can't
  // get a fixed right hand column to work there.  It should do though, as it
  // works on the DataTables examples page)
  if ($is_admin)
  {
    ?>
    var rightCol = (lteIE8) ? null: {sWidth: "fixed", iWidth: 40};
    <?php
  }
  else
  {
    ?>
    var rightCol = null;
    <?php
  }
  ?>
  var roomsTable = makeDataTable('#rooms_table',
                                 {},
                                 {sWidth: "relative", iWidth: 33},
                                 rightCol);
}

