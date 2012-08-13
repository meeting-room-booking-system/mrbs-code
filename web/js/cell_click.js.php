<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry


// =================================================================================

// Extend the init() function 
?>

var oldInitCellClick = init;
init = function(args) {
  oldInitCellClick.apply(this, [args]);
  
  if (lteIE6)
  {
    <?php
    // DAY.PHP, WEEK.PHP, MONTH.PHP
    // If we're running IE6 or below then we need to make bookable slots clickable
    // and respond to a mouse hovering over them (IE6 only supports the :hover pseudo
    // class on anchors).  We do this by toggling the class of the cell in question and also
    // the row_labels cells when the cell is hovered over.
    ?>
    var dayWeekTable = $('#day_main, #week_main');
    dayWeekTable.find('td.new')
      .hover(function() {
          $(this).not('.multiple_booking').toggleClass('new_hover');
        });
    dayWeekTable.find('td')
      .hover(function() {
          $(this).parent().find('td.row_labels').toggleClass('row_labels_hover');
        });
    $('#month_main .valid a.new_booking')
      .parent().parent()
      .hover(function() {
          $(this).toggleClass('valid_hover');
        });
  }                             
};
