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


function getTimeString(time, twentyfourhour_format)
{
   <?php
   // Converts a time (in minutes since midnight) into a string
   // of the form hh:mm if twentyfourhour_format is true,
   // otherwise of the form hh:mm am/pm.
           
   // This function doesn't do a great job of replicating the PHP
   // internationalised format, but is probably sufficient for a 
   // rarely used admin page.
   ?>
   var minutes = time % 60;
   time -= minutes;
   var hour = time/60;
   if (!twentyfourhour_format)
   {
     var ap = "<?php echo utf8_strftime($strftime_format['ampm'], mktime(10, 0, 0)) ?>";
     if (hour > 11) {ap = "<?php echo utf8_strftime($strftime_format['ampm'], mktime(14, 0, 0)) ?>";}
     if (hour > 12) {hour = hour - 12;}
     if (hour == 0) {hour = 12;}
   }
   if (hour < 10) {hour   = "0" + hour;}
   if (minutes < 10) {minutes = "0" + minutes;}
   var timeString = hour + ':' + minutes;
   if (!twentyfourhour_format)
   {
     timeString += ap;
   }
   return timeString;
} // function getTimeString()


function convertTo24(hour, ampm)
{
  if ((ampm == "pm") && (hour < 12))
  {
    hour += 12;
  }
  if ((ampm == "am") && (hour > 11))
  {
    hour -= 12;
  }
  return hour; 
}


function generateLastSlotSelect()
{
  <?php
  // Turn the last slot field into a select box that only contains permitted values
  // given the first slot and resolution
  ?>
  var resMins, firstSlot, lastSlot, morningStarts, eveningEnds, eveningEndsInput;
  resMins = parseInt($('#area_res_mins').val(), 10);
  if (resMins == 0)
  {
    return;  <?php // avoid endless loops and divide by zero errors ?>
  }
 
  <?php // Get the first slot time, adjusting for a 12 hour clock if necessary ?> 
  morningStarts = parseInt($('#area_morningstarts').val(), 10);
  <?php
  if (!$twentyfourhour_format)
  {
    ?>
    morningStarts = convertTo24(morningStarts, 
                                $('input:radio[name=area_morning_ampm]:checked').val());
    <?php
  }
  ?>
  firstSlot = (morningStarts * 60) +
               parseInt($('#area_morningstarts_minutes').val(), 10);
               
  <?php 
  // Get the last slot time, adjusting for a 12 hour clock if necessary.
  // We need to check whether the non-JavaScript input is still there, or 
  // whether it has been overwritten by a select box
  ?> 
  eveningEndsInput = $('#area_eveningends');
  if (eveningEndsInput.length > 0)
  {
    eveningEnds = parseInt(eveningEndsInput.val(), 10);
    <?php
    if (!$twentyfourhour_format)
    {
      ?>
      eveningEnds = convertTo24(eveningEnds,
                                $('input:radio[name=area_evening_ampm]:checked').val());
      <?php
    }
    ?>
    lastSlot = (eveningEnds * 60) +
                parseInt($('#area_eveningends_minutes').val(), 10);
  }
  else
  {
    lastSlot = parseInt($('#area_eveningends_t').val(), 10);
  }

  <?php // Construct the <select> element ?>
  var last_possible = (24 * 60) - resMins;
  var id = 'area_eveningends_t';
  var label = $('<label>').attr('for', id)
                          .text('<?php echo get_vocab("area_last_slot_start")?>:');
  var select = $('<select>').attr('id', id)
                            .attr('name', id);
                            
  for (var t=firstSlot; t <= last_possible; t += resMins)
  {
    select.append($('<option>')
                  .val(t)
                  .text(getTimeString(t, <?php echo ($twentyfourhour_format ? "true" : "false") ?>)));
  }
  var lastOption = Math.max(firstSlot, t-resMins);
  
  <?php // and make the selected option the last value, rounded up ?>
  var remainder = (lastSlot - firstSlot) % resMins;
  if (remainder != 0)
  {
    lastSlot += resMins - remainder;
  }
  lastSlot = Math.max(lastSlot, firstSlot);
  lastSlot = Math.min(lastSlot, lastOption);
  select.val(lastSlot);
  <?php // finally, replace the contents of the <div> with the new <select> ?>
  $('#last_slot').empty()
                 .append(label)
                 .append(select);
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
    
  $('#area_morningstarts, #area_morningstarts_minutes, input[name=area_morning_ampm], #area_res_mins')
      .change(function() {
          generateLastSlotSelect();
        });
  generateLastSlotSelect();
};
