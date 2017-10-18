<?php
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

// Populate the three sub-fields associated with the alt input altID
?>
function populateAltComponents(altId)
{
  var date = $('#' + altId).val().split('-');

  $('#' + altId + '_year').val(date[0]);
  $('#' + altId + '_month').val(date[1]);
  $('#' + altId + '_day').val(date[2]);
}


<?php
// Writes out the day, month and year values to the three hidden inputs
// created by the PHP function genDateSelector().    It gets the date values
// from the _alt input, which is the alternate field populated by datepicker
// and is populated by datepicker with a date in yy-mm-dd format.
//
// (datepicker can only have one alternate field, which is why we need to write
// to the three fields ourselves).
//
// Blur the datepicker input field on select, so that the datepicker will reappear
// if you select it.    (Not quite sure why you need this.  It only seems
// to be necessary when you are using Firefox and the datepicker is draggable).

// If formId is defined, submit the form
//
// Finally, trigger a datePickerUpdated event so that it can be dealt with elsewhere
// by code that relies on having updated values in the alt fields
?>
function datepickerSelect(inst, formId)
{
  var id = inst.id,
      datepickerInput = $('#' + id);

  populateAltComponents(id + '_alt');
  datepickerInput.blur();
  
  if (formId)
  {
    $('#' + formId).submit();
  }
  
  datepickerInput.trigger('datePickerUpdated');
}

<?php
// =================================================================================

// Extend the init() function 
?>

var oldInitDatepicker = init;
init = function() {
  oldInitDatepicker.apply(this);

  $.datepicker.setDefaults({
      showOtherMonths: true,
      selectOtherMonths: true,
      changeMonth: true,
      changeYear: true,
      duration: 'fast',
      showWeek: <?php echo ($view_week_number) ? 'true' : 'false' ?>,
      firstDay: <?php echo $weekstarts ?>,
      altFormat: 'yy-mm-dd',
      onSelect: function(dateText, inst) {datepickerSelect(inst);}
    });
    
  <?php
  // Overwrite the date selectors with a datepicker
  ?>
  $('span.dateselector').each(function() {
      var span = $(this);
      var prefix  = span.data('prefix'),
          minYear = span.data('minYear'),
          maxYear = span.data('maxYear'),
          formId  = span.data('formId');
      var dateData = {day:   parseInt(span.data('day'), 10),
                      month: parseInt(span.data('month'), 10),
                      year:  parseInt(span.data('year'), 10)};
      var unit;
      var initialDate = new Date(dateData.year,
                                 dateData.month - 1,  <?php // JavaScript months run from 0 to 11 ?>
                                 dateData.day);
      var disabled = span.find('select').first().is(':disabled'),
          baseId = prefix + 'datepicker';
      
      span.empty();

      <?php
      // The next input is disabled because we don't need to pass the value through to
      // the form and we don't want the value cluttering up the URL (if it's a GET).
      // It's just used as a holder for the date in a known format so that it can
      // then be used by datepickerSelect() to populate the following three inputs.
      ?>
      $('<input>').attr('type', 'hidden')
                  .attr('id', baseId + '_alt')
                  .attr('name', prefix + '_alt')
                  .prop('disabled', true)
                  .val(dateData.year + '-' + dateData.month + '-' + dateData.day)
                  .appendTo(span);
      <?php
      // These three inputs (day, week, month) we do want
      ?>
      for (unit in dateData)
      {
        if (dateData.hasOwnProperty(unit))
        {
          $('<input>').attr('type', 'hidden')
                      .attr('id', baseId + '_alt_' + unit)
                      .attr('name', prefix + unit)
                      .val(dateData[unit])
                      .appendTo(span);
        }
      }
      <?php // Finally the main datepicker field ?>
      $('<input>').attr('class', 'date')
                  .attr('type', 'text')
                  .attr('id', baseId)
                  .datepicker({altField: '#' + baseId + '_alt',
                               disabled: disabled,
                               yearRange: minYear + ':' + maxYear})
                  .datepicker('setDate', initialDate)
                  .change(function() {
                      <?php // Allow the input field to be updated manually ?>
                      $(this).datepicker('setDate', $(this).val());
                      populateAltComponents(baseId + '_alt');
                      $(this).trigger('datePickerUpdated');
                    })
                  .appendTo(span);
                  
      if (formId.length > 0)
      {
        $('#' + baseId).datepicker('option', 'onSelect', function(dateText, inst) {
            datepickerSelect(inst, formId);
          });
      }
      
      <?php
      // Set the visibility to 'inherit' rather than 'visible' because the parent
      // element may itself be hidden, eg if multiday booking is not allowed.
      ?>
      span.css('visibility', 'inherit');
      
      $('.ui-datepicker').draggable();
    });
};

