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


function convertTo24(hour, ampm)
{
  if ((ampm === "pm") && (hour < 12))
  {
    hour += 12;
  }
  if ((ampm === "am") && (hour > 11))
  {
    hour -= 12;
  }
  return hour; 
}


<?php // Get the resolution in minutes ?>
function getResolutionMinutes()
{
  return parseInt($('#area_res_mins').val(), 10);
}


<?php // Gets the start of the first slot in minutes past midnight  ?>
function getStartFirstSlot()
{
  var morningStarts, result;
  
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
  result = (morningStarts * 60) +
           parseInt($('#area_morningstarts_minutes').val(), 10);
           
  return result;
}


<?php // Gets the start of the last slot in minutes past midnight  ?>
function getStartLastSlot()
{
  var eveningEnds, eveningEndsInput, result;
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
    result = (eveningEnds * 60) +
             parseInt($('#area_eveningends_minutes').val(), 10);
  }
  else
  {
    result = parseInt($('#area_eveningends_t').val(), 10);
  }
  
  return result;
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
  if (resMins === 0)
  {
    return;  <?php // avoid endless loops and divide by zero errors ?>
  }
  firstSlot = getStartFirstSlot();
  lastSlot = getStartLastSlot();
               
  <?php 
  // Construct the <select> element.
  // We allow the "day" to go all the way past midnight and up to the start of the
  // next first slot.
  ?>
  var lastPossible = minsPerDay + firstSlot - resMins;
  var id = 'area_eveningends_t';
  var label = $('<label>').attr('for', id)
                          .text('<?php echo get_vocab("area_last_slot_start")?>');
  var select = $('<select>').attr('id', id)
                            .attr('name', id);
                            
  for (var t=firstSlot; t <= lastPossible; t += resMins)
  {
    tCorrected = t % minsPerDay;  <?php // subtract one day if past midnight?>
    <?php // Calculate the closest option to the old last slot ?>
    if (Math.abs(lastSlot - tCorrected) <= resMins/2)
    {
      lastSlot = tCorrected;
    }
    select.append($('<option>')
                  .val(tCorrected)
                  .text(getTimeString(tCorrected, <?php echo ($twentyfourhour_format ? "true" : "false") ?>)));
  }
  
  <?php // and make the selected option the new last slot value ?>
  select.val(lastSlot);
  <?php // finally, replace the contents of the <div> with the new <select> ?>
  $('#last_slot').empty()
                 .append(label)
                 .append(select)
                 .css('visibility', 'visible');
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
  $('input:radio[name=area_enable_periods]').click(function() {
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
  $('#add_period').click(function() {
      var lastPeriodName = $('#period_settings .period_name').last(),
          clone = lastPeriodName.clone(true); <?php // duplicate data and events ?>
          
      clone.find('input').val('');
      clone.insertAfter(lastPeriodName).find('input').focus();
      $('.delete_period').show();
    });
  
  <?php // Delete a period name input field ?>  
  $('.delete_period').click(function() {
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
    
  $('#area_morningstarts, #area_morningstarts_minutes, input[name=area_morning_ampm], #area_res_mins')
      .change(function() {
          generateLastSlotSelect();
        });
  generateLastSlotSelect();
};
