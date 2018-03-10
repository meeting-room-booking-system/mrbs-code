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
    
  <?php
  // Set up datepickers.  We convert all inputs of type 'date' into flatpickr
  // datepickers.  Note that by default flatpickr will use the native datepickers
  // on mobile devices because they are generally better.
  
  // Localise the flatpickr
  if (null !== ($flatpickr_lang_file = get_flatpickr_lang_file('flatpickr/l10n')))
  {
    // Strip the '.js' off the end of the filename
    echo 'flatpickr.localize(flatpickr.l10ns.' . substr($flatpickr_lang_file, 0, -3) . ');';
  }
  ?>
  
  var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    );
  
  
  var formatDate = function(dateObj, formatStr) {
      var result;
      <?php
      $locales = get_lang_preferences();
      if (!empty($locales))
      {
        ?>
        var locales = ['<?php echo implode("','", get_lang_preferences())?>'];
        <?php
      }
      ?>
      if (typeof locales === 'undefined')
      {
        result = new Intl.DateTimeFormat().format(dateObj);
      }
      else
      {
        result = new Intl.DateTimeFormat(locales).format(dateObj);
      }
      console.log(result);
      return result;
    };
      
  var config = {
      locale: {firstDayOfWeek: <?php echo $weekstarts ?>},
      altInput: true,
      formatDate: formatDate,
      onChange: function(selectedDates, dateStr, instance) {
        var submit = $(this.element).data('submit');
        if (submit)
        {
          $('#' + submit).submit();
        }
        else
        {
          $(this.element).change();
        }
      }
    };
    
  if (!isMobile)
  {
    <?php
    // Setting weekNumbers causes flatpickr not to use the native datepickers on mobile
    // devices.  As these are generally better than flatpickr's, it's probably better
    // to have the native datepicker and do without the week numbers.
    ?>
    config.weekNumbers = <?php echo ($view_week_number) ? 'true' : 'false' ?>;
  }
    
  flatpickr('input[type="date"]', config);
  
};

