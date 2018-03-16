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
  
  
  <?php
  // Custom date formatter.  At the moment, only two format strings are supported:
  //
  //    'custom'      The date is formatted in numeric form in the user's preferred locale,
  //                  as expressed by their browser preferences and subject to any
  //                  overriding config settings.  Note that 'custom' is not supported on
  //                  IE10, as it requires Intl.DateTimeFormat(), and so IE10 users
  //                  are given a date in 'Y-m-d' format.
  //
  //    everything    All other format strings are treated as 'Y-m-d'.
  //    else      
  ?>
  var formatDate = function(dateObj, formatStr) {
      <?php
      $locales = get_lang_preferences();
      if (!empty($locales))
      {
        ?>
        var locales = ['<?php echo implode("','", get_lang_preferences())?>'];
        <?php
      }
      
      // If window.Intl is supported then we can format dates in the user's preferred
      // locale.  Otherwise, in practice just IE10, they have to make do with ISO
      // (YYYY-MM-DD) dates.
      ?>
      if (window.Intl && (formatStr == 'custom'))
      {
        return (typeof locales === 'undefined') ?
                new Intl.DateTimeFormat().format(dateObj) :
                new Intl.DateTimeFormat(locales).format(dateObj);
      }
      
      return [
          dateObj.getFullYear(),
          ('0' + (dateObj.getMonth() + 1)).slice(-2),
          ('0' + dateObj.getDate()).slice(-2)
        ].join('-');
    };
      
      
  var config = {
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: 'custom',
      formatDate: formatDate,
      locale: {firstDayOfWeek: <?php echo $weekstarts ?>},
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
  
  
  <?php
  // If we are using $hidden days and the input has a class of 'hidden' then we need
  // to tell flatpickr to disable those dates.
  if (empty($hidden_days))
  {
    ?>
    flatpickr('input[type="date"]', config);
    <?php
  }
  else
  {
    ?>
    flatpickr('input[type="date"]:not(.hidden)', config);
    config.disable = [function(date) {
        var hiddenDays = [<?php echo implode(',', $hidden_days)?>];
        return (hiddenDays.indexOf(date.getDay()) >= 0);
      }];
    flatpickr('input[type="date"].hidden', config);
    <?php
  }
  ?>
  
};

