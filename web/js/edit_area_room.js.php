<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}
?>

var enablePeriods;
  
function toggleMode(form, speed)
{
  if (speed === undefined)
  {
    speed = 'slow';
  }
  var periodsChecked = form.area_enable_periods[0].checked;
  if (periodsChecked != enablePeriods)
  {
    enablePeriods = !enablePeriods;
    $('#time_settings').animate({
      opacity : 'toggle',
      height: 'toggle'
      }, speed);
  }
  <?php // Show or Hide the note about periods as appropriate ?>
  if (periodsChecked)
  {
    $('#book_ahead_periods_note').show(speed);
  }
  else
  {
    $('#book_ahead_periods_note').hide(speed);
  }
}

<?php


// =================================================================================

// Extend the init() function 
?>

var oldInitEditAreaRoom = init;
init = function() {
  oldInitEditAreaRoom.apply(this);
  
  <?php
  // We need to hide the sections of the form relating to times
  // slots if the form is loaded with periods enabled.   We hide
  // the times sections instantly by setting speed = 0;
  // Also show or hide the periods note as appropriate
  // [This method works if there are no periods-specific settings.
  // When we get those we will have to do something different]
  ?>
  var form = document.getElementById('edit_area');
  if (form)
  {
    enablePeriods = false;
    if (form.area_enable_periods[0].checked)
    {
      toggleMode(form, 0);
      $('#book_ahead_periods_note').show();
    }
    else
    {
      $('#book_ahead_periods_note').hide();
    }
  }
    
  <?php
  // Where we've got enabling checkboxes, apply a change event to them so that
  // when the enabling checkbox is changed the associated inputs are enabled or
  // disabled as appropriate.   Also trigger the change event when the page is loaded
  // so that the inputs are enabled/disabled correctly initially.
  ?>
  $('.enabler').change(function(){
      $(this).nextAll('input, select').attr('disabled', !$(this).is(':checked'));
    })
    .change();
};
