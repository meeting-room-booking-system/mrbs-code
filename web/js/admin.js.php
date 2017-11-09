<?php
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

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
  
  if ($is_admin)
  {
    ?>
    fixedColumnsOptions.rightColumns = 1;
    <?php
  }
  ?>
  
  makeDataTable('#rooms_table', {}, fixedColumnsOptions);
};

