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

var oldInitMultiple = init;
init = function(args) {
  oldInitMultiple.apply(this, [args]);
  
  var table = $('table.dwm_main');
    
  <?php // Turn all the multiple booking slots into the minimized state ?>
  table.find('td.multiple_booking')
      .removeClass('maximized')
      .addClass('minimized');
  <?php // Enable toggling on the +/- control ?>
  table.find('div.multiple_control')
      .click(function() {
          var cell = $(this).closest('td');
          if (cell.hasClass('maximized'))
          {
            cell.removeClass('maximized')
            cell.addClass('minimized');
          }
          else
          {
            cell.removeClass('minimized')
            cell.addClass('maximized');
          }
        });
};
