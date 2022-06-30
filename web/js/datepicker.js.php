<?php
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

// Fix for iOS 13 where the User Agent string has been changed.
// See https://github.com/flatpickr/flatpickr/issues/1992
?>
function iPadMobileFix() {
  return function(instance) {
    return {
      onParseConfig: function() {
          if (instance.isMobile)
          {
            return;
          }
          if (isIos())
          {
            instance.isMobile = true;
          }
        }
      };
    };
};

<?php
// Turn a JavaScript year, month, day (ie with Jan = 0) into an
// ISO format YYYY-MM-DD date string.    Can cope with months
// outside the range 0..11, but days must be valid.
?>
function getISODate(year, month, day)
{
  while (month > 11)
  {
    month = month-12;
    year++;
  }

  while (month < 0)
  {
    month = month+12;
    year--;
  }

  return [
      year,
      ('0' + (month + 1)).slice(-2),
      ('0' + day).slice(-2)
    ].join('-');
}



$(document).on('page_ready', function() {

  var locales = $('body').data('langPrefs');

  <?php
  // Set up datepickers.  We convert all inputs of type 'date' into flatpickr
  // datepickers.  Note that by default flatpickr will use the native datepickers
  // on mobile devices because they are generally better.

  // Localise the flatpickr
  if (null !== ($flatpickr_lang_path = get_flatpickr_lang_path()))
  {
    // Map the flatpickr lang file onto a flatpickr l10ns property and then localize
    echo 'flatpickr.localize(flatpickr.l10ns.' . get_flatpickr_property($flatpickr_lang_path) . ');';
  }


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
      // If window.Intl is supported then we can format dates in the user's preferred
      // locale.  Otherwise, in practice just IE10, they have to make do with ISO
      // (YYYY-MM-DD) dates.
      ?>
      if (window.Intl && (formatStr === 'custom'))
      {
        return (typeof locales === 'undefined') ?
                new Intl.DateTimeFormat().format(dateObj) :
                new Intl.DateTimeFormat(locales).format(dateObj);
      }

      return getISODate(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate());
    };


  var onDayCreate = function(dObj, dStr, fp, dayElem) {
      <?php
      // If this is a hidden day and the user is a booking admin then add a class to
      // the day so that it can be styled differently.  If they're not a booking admin
      // the day will be disabled - see later on in this file.
      if (!empty($hidden_days))
      {
        ?>
        if (args.isBookAdmin)
        {
          var hiddenDays = [<?php echo implode(',', $hidden_days)?>];
          if (hiddenDays.indexOf(dayElem.dateObj.getDay()) >= 0) {
            dayElem.classList.add('mrbs-hidden');
          }
        }
        <?php
      }
      ?>

      <?php // And add a class if it's a weekend day ?>
      var weekDays = [<?php echo implode(',', $weekdays)?>];
      if (weekDays.indexOf(dayElem.dateObj.getDay()) < 0) {
        dayElem.classList.add('mrbs-weekend');
      }
    };


  <?php
  // Sync all the minicalendars with this instance of one.   In other words
  // make the mini-calendars show sequential months, aligning with this one.
  ?>
  function syncCals(instance)
  {
    var thisId = instance.element.attributes.id.nodeValue,
        thisIndex = parseInt(thisId.substring(3), 10),
        currentMonth = parseInt(instance.currentMonth, 10),
        currentYear = parseInt(instance.currentYear, 10);

    $.each(minicalendars, function(key, value) {
        if (value.element.attributes.id.nodeValue !== thisId)
        {
          var index = parseInt(value.element.attributes.id.nodeValue.substring(3), 10);
          value.jumpToDate(getISODate(currentYear, currentMonth + index - thisIndex, 1));
        }
      });
  }


  var onMonthChange = function(selectedDates, dateStr, instance) {
      syncCals(instance);
    };

  var onYearChange = function(selectedDates, dateStr, instance) {
      syncCals(instance);
    };

  var onMinicalChange = function(selectedDates, dateStr, instance) {
      <?php
      // The order of the query string parameters is important here.  It needs to be the
      // same as the order in the Prev anbd Next navigation links so that the pre-fetched
      // pages can be used when possible.
      ?>
      var href = 'index.php';
      href += '?view=' + args.view;
      href += '&page_date=' + dateStr;
      href += '&area=' + args.area;
      href += '&room=' + args.room;
      if (args.site)
      {
        href += '&site=' + encodeURIComponent(args.site);
      }
      updateBody(href);  <?php // Update the body via an Ajax call to avoid flickering ?>
    };

  var config = {
      plugins: [iPadMobileFix()],
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
          $(this.element).trigger('change');
        }
      }
    };

  <?php
  // Disable hidden days, unless the user is a booking admin.  (If they're a booking
  // admin then they'll still be able to select the date but it will be given a different
  // class so that it can be styled differently - see code above in this file.)
  if (!empty($hidden_days))
  {
    ?>
    if (!args.isBookAdmin)
    {
      config.disable = [
        function (date) {
          return ([<?php echo implode(',', $hidden_days); ?>].indexOf(date.getDay()) >= 0);
        }
      ];
    }
    <?php
  }
  ?>


  <?php
  // Setting weekNumbers causes flatpickr not to use the native datepickers on mobile
  // devices.  As these are generally better than flatpickr's, it's probably better
  // to have the native datepicker and do without the week numbers.
  ?>
  if (isMobile())
  {
    $('input.flatpickr-input').width('auto');
  }
  else
  {
    config.weekNumbers = <?php echo ($view_week_number) ? 'true' : 'false' ?>;
  }

  flatpickr('input[type="date"]', config);

  <?php
  if (!empty($display_mincals))
  {
    ?>
    if (!isMobile())
    {
      var div = $('.minicalendars');
      if (div.length > 0)
      {
        for (var i = 0; i < 2; i++)
        {
          div.append($('<span class="minicalendar" id="cal' + i + '"></span>'));
        }
        config.inline = true;
        config.onMonthChange = onMonthChange;
        config.onYearChange = onYearChange;
        config.onChange = onMinicalChange;
        config.mode=args.view=='day'?'single':'range';
        
        var minicalendars = flatpickr('span.minicalendar', config);

        $.each(minicalendars, function (key, value) {
            if(args.view=='day'){
              value.setDate(args.pageDate);
            }
            else if(args.view=='week'){
              value.setDate([
                moment(args.pageDate,'YYYY-MM-DD').startOf('week').format('YYYY-MM-DD'),
                moment(args.pageDate,'YYYY-MM-DD').endOf('week').format('YYYY-MM-DD')
                ]);
            }
            else if(args.view=='month'){
              value.setDate([
                moment(args.pageDate,'YYYY-MM-DD').startOf('month').format('YYYY-MM-DD'),
                moment(args.pageDate,'YYYY-MM-DD').endOf('month').format('YYYY-MM-DD')
                ]);
            }
            value.changeMonth(key);
          });

        <?php
        // Align the top of the mini-calendars with the top of the navigation bar
        ?>
        div.css('margin-top', $('.view_container h2').outerHeight(true) + 'px');

        <?php
        // Once the calendars are formed thern we add the class 'formed' which will
        // bring into play CSS media queries.    We need to do this because if we
        // form them when the media queries are operational then they won't get
        // formed if the result of the query is 'display: none', which means that if
        // the window is later widened, for example, they still won't appear when they
        // should do.
        ?>
        div.addClass('formed');
      }
    }
    <?php
  }

  // Only show the main table and navigation once the mini-calendars are in place
  // in order to avoid the screen jiggling about.
  ?>
  $('.view_container').removeClass('js_hidden');

  <?php
  // Show the datepicker in the banner, which has been hidden up until now
  ?>
  $('#form_nav').removeClass('js_hidden');
});

