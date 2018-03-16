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
init = function(args) {
  oldInitDatepicker.apply(this, [args]);
    
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
      
      
  var onDayCreate = function(dObj, dStr, fp, dayElem) {
      <?php
      // If this is a hidden day, add a class to the element. If we're not an admin
      // then add 'disabled', which will grey out the dates and prevent them being picked.
      // If we are an admin then add 'nextMonthDay' will will grey out the dates, but
      // still allow them to be picked.  [Note: it would be better to define our own
      // class instead of using 'nextMonthDay' as that will probably have some 
      // unintended consequences if we want to do special things with the next month.]
      if (!empty($hidden_days))
      {
        ?>
        var hiddenDays = [<?php echo implode(',', $hidden_days)?>];
        if (hiddenDays.indexOf(dayElem.dateObj.getDay()) >= 0)
        {
          dayElem.classList.add((args.isAdmin) ? 'nextMonthDay' : 'disabled');
        }
        <?php
      }
      ?>
    };
      
  var config = {
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: 'custom',
      formatDate: formatDate,
      onDayCreate: onDayCreate,
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
  
  
  <?php
  // Setting weekNumbers causes flatpickr not to use the native datepickers on mobile
  // devices.  As these are generally better than flatpickr's, it's probably better
  // to have the native datepicker and do without the week numbers.
  ?>
  if (!isMobile)
  {
    config.weekNumbers = <?php echo ($view_week_number) ? 'true' : 'false' ?>;
  }
  
  flatpickr('input[type="date"]', config);
  
};

