<?php
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

// Show or Hide the settings for Times and the note about Periods as
// appropriate.  Also toggle the required property on the area_periods[]
// inputs: if they are left as required when they are hidden, then the
// browser will try and make you complete them, but throw an error because
// they cannot be brought in to focus. ?>
function toggleMode(speed)
{
  if (typeof speed === 'undefined')
  {
    speed = 'slow';
  }
  
  if ($('input:radio[name=area_enable_periods]:checked').val() === '0')
  {
    $('#book_ahead_periods_note').hide(speed);
    $('#time_settings').show(speed);
    $('#period_settings').hide(speed);
    $('input[name="area_periods[]"]').prop('required', false);
  }
  else
  {
    $('#book_ahead_periods_note').show(speed);
    $('#time_settings').hide(speed);
    $('#period_settings').show(speed);
    $('input[name="area_periods[]"]').prop('required', true);
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
   var ap,
       timeString,
       minutes = time % 60;
   time -= minutes;
   var hour = time/60;
   if (!twentyfourhour_format)
   {
     if (hour > 11)
     {
       ap = "<?php echo utf8_strftime($strftime_format['ampm'], mktime(14, 0, 0)) ?>";
     }
     else
     {
       ap = "<?php echo utf8_strftime($strftime_format['ampm'], mktime(10, 0, 0)) ?>";
     }
     if (hour > 12)
     {
       hour = hour - 12;
     }
     if (hour === 0)
     {
       hour = 12;
     }
   }
   if (hour < 10)
   {
     hour   = "0" + hour;
   }
   if (minutes < 10)
   {
     minutes = "0" + minutes;
   }
   timeString = hour + ':' + minutes;
   if (!twentyfourhour_format)
   {
     timeString += ap;
   }
   return timeString;
} // function getTimeString()


<?php // Get the resolution in minutes ?>
function getResolutionMinutes()
{
  return parseInt($('#area_res_mins').val(), 10);
}


<?php
// Converts a time string in the format 'hh:mm' to minutes.
// Returns null if the string is not properly formed.
?> 
function hhmmToMins(hhmm)
{
  if (hhmm === null)
  {
    return null;
  }
  
  if (!<?php echo REGEX_HHMM ?>.test(hhmm))
  {
    return null;
  }
  
  var array = hhmm.split(':');
  return (parseInt(array[0], 10) * 60) + parseInt(array[1], 10);
}


<?php // Gets the start of the first slot in minutes past midnight  ?>
function getStartFirstSlot()
{
  return hhmmToMins($('input[name="area_start_first_slot"]').val());
}


<?php // Gets the start of the last slot in minutes past midnight  ?>
function getStartLastSlot()
{
  return hhmmToMins($('[name="area_start_last_slot"]').val());
}


function generateLastSlotSelect()
{
  <?php
  // Turn the last slot field into a select box that only contains permitted values
  // given the first slot and resolution
  ?>
  var resMins, tCorrected,
      firstSlot, lastSlot, 
      minsPerDay = <?php echo MINUTES_PER_DAY ?>;
      
  resMins = getResolutionMinutes();
  if (isNaN(resMins) || (resMins === null) || (resMins === 0))
  {
    return;  <?php // avoid endless loops and divide by zero errors ?>
  }
  
  firstSlot = getStartFirstSlot();
  lastSlot = getStartLastSlot();
  
  if (firstSlot === null)
  {
    return;
  }
               
  <?php 
  // Construct the <select> element.
  // We allow the "day" to go all the way past midnight and up to the start of the
  // next first slot.
  ?>
  var lastPossible = minsPerDay + firstSlot - resMins;
  var name = 'area_start_last_slot';
  var element = $('[name="' + name + '"]');
  
  var select = $('<select>').attr('name', name);
                            
  for (var t=firstSlot; t <= lastPossible; t += resMins)
  {
    tCorrected = t % minsPerDay;  <?php // subtract one day if past midnight?>
    <?php // Calculate the closest option to the old last slot ?>
    if (Math.abs(lastSlot - tCorrected) <= resMins/2)
    {
      lastSlot = tCorrected;
    }
    select.append($('<option>')
                  .val(getTimeString(tCorrected, true))
                  .text(getTimeString(tCorrected, <?php echo ($twentyfourhour_format ? "true" : "false") ?>)));
  }
  
  <?php // and make the selected option the new last slot value ?>
  select.val(getTimeString(lastSlot, true));
  <?php // finally, replace the element with the new <select> ?>
  element.replaceWith(select);
  $('#last_slot').css('visibility', 'visible');
}


<?php
// Check to see if there's only one period name left and, if so,
// disable the delete button, to make sure there's always at least one
// period.  (We could in theory have no period names, but it doesn't
// have a practical use.  Besides, always having at least one makes the
// code a little simpler because there will always be something to clone.
?>
function checkForLastPeriodName()
{
  if ($('.period_name').length === 1)
  {
    $('.delete_period').hide();
  }
}




$(document).on('page_ready', function() {
  
  <?php
  // We need to hide the sections of the form relating to times
  // slots if the form is loaded with periods enabled.   We hide
  // the times sections instantly by setting speed = 0;
  // Also show or hide the periods note as appropriate
  // [This method works if there are no periods-specific settings.
  // When we get those we will have to do something different]
  ?>
  $('input:radio[name=area_enable_periods]').on('click', function() {
      toggleMode('fast');
    });
  toggleMode(0);
  
  <?php
  // Work out if we can display the delete symbols, and then only
  // after we have done that make them visible.  (This stops the
  // delete symbol appearing for a moment and then being removed).
  ?>
  checkForLastPeriodName();
  $('.delete_period').css('visibility', 'visible');

  <?php
  // When the Add Period button is clicked, duplicate the last period
  // name input field, clearing its contents.  Re-enable all the delete
  // icons because there must be more than one having added one.
  ?>
  $('#add_period').on('click', function() {
      var lastPeriodName = $('#period_settings .period_name').last(),
          clone = lastPeriodName.clone(true); <?php // duplicate data and events ?>
          
      clone.find('input').val('');
      clone.insertAfter(lastPeriodName).find('input').focus();
      $('.delete_period').show();
    });
  
  <?php // Delete a period name input field ?>  
  $('.delete_period').on('click', function() {
      $(this).parent().remove();
      checkForLastPeriodName();
    });
    
  <?php
  // Where we've got enabling checkboxes, apply a change event to them so that
  // when the enabling checkbox is changed the associated inputs are enabled or
  // disabled as appropriate.   Also trigger the change event when the page is loaded
  // so that the inputs are enabled/disabled correctly initially.
  ?>
  $('.enabler').change(function(){
      var enablerChecked = $(this).is(':checked');
      if ($(this).attr('id') === 'area_max_duration_enabled')
      {
        <?php // This is structured slightly differently ?>
        $('#area_max_duration_periods, #area_max_duration_value, #area_max_duration_units').prop('disabled', !enablerChecked);
      }
      else
      {
        $(this).nextAll('input, select').prop('disabled', !enablerChecked);
      }
    })
    .change();
  
  <?php // Disable the default duration if "All day" is checked. ?>
  $('input[name="area_def_duration_all_day"]').change(function() {
      $('#area_def_duration_mins').prop('disabled', $(this).prop('checked'));
    }).change();
  
  $('input[name="area_start_first_slot"], input[name="area_res_mins"]')
      .change(function() {
          generateLastSlotSelect();
        });
  generateLastSlotSelect();
  
});
