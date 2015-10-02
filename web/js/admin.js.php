<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

$user = getUserName();
$is_admin = (authGetUserLevel($user) >= $max_level);

// =================================================================================

// Extend the init() function 
?>

var oldInitAdmin = init;
init = function(args) {
  
  var fixedColumnsOptions = {leftColumns: 1};
  
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
    if (!lteIE8)
    {
      fixedColumnsOptions.rightColumns = 1;
    }
    <?php
  }
  ?>
  
  makeDataTable('#rooms_table', {}, fixedColumnsOptions);
};

