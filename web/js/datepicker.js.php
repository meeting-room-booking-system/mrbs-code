<?php
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}


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
      altFormat: 'yy-mm-dd'
    });
    
  <?php
  // Set up datepickers.  We convert all inputs of type 'date' into
  // jQueryUI datepickers.   In the future we might want to do something a bit
  // more sophisticated and only convert the inputs for some browsers, because
  // the native date controls on some modern browsers, eg Chrome mobile, are
  // better than the jQueryUI datepicker.
  ?>
  $('input[type="date"]').each(function() {
      var input = $(this),
          thisDate = input.val(),
          thisName = input.attr('name'),
          altId = thisName + '_alt';
      
      <?php
      // Create a hidden field, which will be the alt field, that will
      // hold the date value in the standard format.
      ?>
      $('<input>').attr('type', 'hidden')
                  .attr('id', altId)
                  .attr('name', thisName)
                  .val(thisDate)
                  .insertAfter(input);
          
      input.attr('type','text')
           .removeAttr('name')
           .addClass('date')
           .datepicker({altField: '#' + altId,
                        onSelect: function(dateText, inst) {
                            var submit = $(this).data('submit');
                            if (submit)
                            {
                              $('#' + submit).submit();
                            }
                            else
                            {
                              $(this).change();
                            }
                          }
                        });
           
      <?php
      // Initialise the date in the field.   Our date is in yy-mm-ddSelect
      // format, so we have to save the current datepicker format,
      // change the format, set the date and then restore the old format.
      // (Note: the other way of doing it by using a Date object presents
      // timezone complications).
      ?>
      var dateFormat = input.datepicker('option', 'dateFormat');
      input.datepicker('option', 'dateFormat', 'yy-mm-dd');
      input.datepicker('setDate', thisDate);
      input.datepicker('option', 'dateFormat', dateFormat);
    });
};

