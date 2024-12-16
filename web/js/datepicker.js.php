<?php
declare(strict_types=1);
namespace MRBS;

use MRBS\Intl\FormatterFlatpickr;use MRBS\Intl\IntlDatePatternConverter;

require "../defaultincludes.inc";

// We use ICU datetime patterns in the config file rather than flatpickr formatting
// tokens so that we can switch to a different datepicker if necessary, without
// having to change the config settings.
function get_date_format() : string
{
  global $datetime_formats;

  if (isset($datetime_formats['datepicker']['pattern']))
  {
    try
    {
      $converter = new IntlDatePatternConverter(new FormatterFlatpickr());
      return $converter->convert($datetime_formats['datepicker']['pattern']);
    }
    catch (\Exception $e)
    {
      // Report the error and fall through to the 'custom' format
      trigger_error($e->getMessage(), E_USER_WARNING);
    }
  }

  return 'custom';
}

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry
?>

'use strict';

<?php
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
}

<?php
// Turn a JavaScript year, month, day (ie with Jan = 0) into an
// ISO format YYYY-MM-DD date string.    Can cope with months
// outside the range 0..11, but days must be valid.
?>
function getISODate(year, month, day)
{
  <?php // toISOString() converts to UTC, so make the date a UTC date ?>
  var date = new Date(Date.UTC(year, month, day));
  return date.toISOString().split('T')[0];
}


// Given a JavaScript Date object returns a date string in YYYY-MM-DD
// format.  (Note that toISOString() returns a date in UTC time).
function getLocalISODateString(date)
{
  var month = (date.getMonth() + 1).toString().padStart(2, '0');
  var day = date.getDate().toString().padStart(2, '0');
  var year = date.getFullYear().toString();

  return [year, month, day].join('-');
}



<?php
// Functions to find the start and end dates of a week and month given a
// date in YYYY-MM-DD format.
// weekStarts is the start day of the week (0 for Sunday, 1 for Monday etc.)
// (Could be implemented by extending the Date class, but extends isn't
// supported by IE11.)
?>

function weekStart(date, weekStarts) {
  <?php
  // Need to add a time to make sure the date is interpreted as local rather than UTC.
  // "When the time zone offset is absent, date-only forms are interpreted as a UTC time and
  // date-time forms are interpreted as local time. This is due to a historical spec error
  // that was not consistent with ISO 8601 but could not be changed due to web compatibility."
  ?>
  var d = new Date(date + "T00:00:00");
  var diff = d.getDay() - weekStarts;
  if (diff < 0)
  {
    diff += 7;
  }
  d.setDate(d.getDate() - diff);
  return getLocalISODateString(d);
}

function weekEnd(date, weekStarts) {
  <?php // Need to add a time to make sure the date is interpreted as local rather than UTC. ?>
  var d = new Date(weekStart(date, weekStarts) + "T00:00:00");
  d.setDate(d.getDate() + 6);
  return getLocalISODateString(d);
}

function monthStart(date) {
  <?php // Need to add a time to make sure the date is interpreted as local rather than UTC. ?>
  var d = new Date(date + "T00:00:00");
  d.setDate(1);
  return getLocalISODateString(d);
}

function monthEnd(date) {
  <?php // Need to add a time to make sure the date is interpreted as local rather than UTC. ?>
  var d = new Date(date + "T00:00:00");
  <?php
  // Set the date to the first of the month, because otherwise we will
  // advance by two months when incrementing the month below if we're at the
  // end of the month and the next month has fewer days than this one.
  ?>
  d.setDate(1);
  d.setMonth(d.getMonth() + 1);
  d.setDate(0);
  return getLocalISODateString(d);
}


// Returns an array of dates in the range startDate..endDate, optionally
// excluding hidden days.
function datesInRange(startDate, endDate, excludeHiddenDays) {
  var result=[];
  <?php // Need to add a time to make sure the date is interpreted as local rather than UTC. ?>
  var e=new Date(endDate + "T00:00:00");
  var hiddenDays = [<?php echo implode(',', $hidden_days)?>];

  <?php // dates can be compared using > and < but not == or === ?>
  for (var d=new Date(startDate + "T00:00:00"); !(d>e); d.setDate(d.getDate()+1))
  {
    if (excludeHiddenDays && (hiddenDays.indexOf(d.getDay()) >= 0))
    {
      continue;
    }
    result.push(getLocalISODateString(d));
  }

  return result;
}

$(document).on('page_ready', function() {

  var locales = $('body').data('langPrefs');

  <?php
  // Set up datepickers.  We convert all inputs of type 'date' into flatpickr
  // datepickers.  Note that by default flatpickr will use the native datepickers
  // on mobile devices because they are generally better.

  // Localise the flatpickr
  // Could use new URLSearchParams(document.currentScript.src); and get the lang parameter from the
  // file's query string, but document.currentScript is not supported by IE (though that probably
  // doesn't matter much anymore).
  if (null !== ($flatpickr_lang_path = get_flatpickr_lang_path()))
  {
    // Map the flatpickr lang file onto a flatpickr l10ns property and then localize
    echo 'flatpickr.localize(flatpickr.l10ns["' . get_flatpickr_property($flatpickr_lang_path) . '"]);';
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
      // If the datepicker is used for navigation, and this is a hidden day, and the user is
      // a booking admin, then add a class to the day so that it can be styled differently.
      // If they're not a booking admin, and it's a navigation datepicker, the day will be
      // disabled - see later on in this file.
      ?>
      const hiddenDays = [<?php echo implode(',', $hidden_days)?>];
      if (hiddenDays.length &&
          args.isBookAdmin &&
          fp.altInput.classList.contains('navigation') &&
          (hiddenDays.indexOf(dayElem.dateObj.getDay()) >= 0))
      {
        dayElem.classList.add('mrbs-hidden');
      }

      <?php // And add a class if it's a weekend day ?>
      const weekDays = [<?php echo implode(',', $weekdays)?>];
      if (weekDays.indexOf(dayElem.dateObj.getDay()) < 0) {
        dayElem.classList.add('mrbs-weekend');
      }
    };


  <?php
  // Sync all the mini-calendars with this instance of one. In other words
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
      // same as the order in the Prev and Next navigation links so that the pre-fetched
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
      <?php
      // Set the new date in the mini-calendar, in order to avoid the previous one
      // still showing as selected.
      // TODO: change the date in the other mini-calendar of the date appears there as well?
      ?>
      instance.setDate([selectedDates[0], selectedDates[0]], false);
      updateBody(href);  <?php // Update the body via an Ajax call to avoid flickering ?>
    };

  var lastValidDate = {};

  <?php
  $date_format = get_date_format();
  ?>

  var config = {
      plugins: [iPadMobileFix()],
      dateFormat: 'Y-m-d',
      altInput: true,
      altFormat: <?php echo "'" . get_date_format() . "'" ?>,
      <?php
      if ($date_format == 'custom')
      {
        echo "formatDate: formatDate,\n";
      }
      ?>
      onDayCreate: onDayCreate,
      locale: {firstDayOfWeek: <?php echo $weekstarts ?>},
      onChange: function(selectedDates, dateStr, instance) {
        var element = $(instance.element);
        var submit = element.data('submit');
        var elementName = element.attr('name');
        <?php
        // Flatpickr allows the user to delete the date in the input field, even when allowInput
        // is false, resulting in an empty string, which then causes problems for edit_entry_handler.php.
        // See https://github.com/flatpickr/flatpickr/issues/936 and
        // https://github.com/flatpickr/flatpickr/pull/2252 . At the time of writing the PR has not been
        // merged.  To get round this we substitute an empty string for the last known valid date,
        // but only for required inputs.
        ?>
        if (dateStr) {
          lastValidDate[elementName] = dateStr;
        }
        else if (element.prop('required') && lastValidDate[elementName]) {
          instance.setDate(lastValidDate[elementName]);
        }
        <?php // Submit will be set for the datepicker in the banner ?>
        if (submit) {
          $('#' + submit).trigger('submit');
        }
        else {
          element.trigger('change');
        }
      },
      onReady: function(selectedDates, dateStr, instance) {
        if (instance.altInput.ariaLabel === null) {
          instance.altInput.ariaLabel = instance.input.ariaLabel;
        }
        if (dateStr) {
          lastValidDate[instance.element.getAttribute('name')] = dateStr;
        }
      }
    };


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
    <?php // Only display the week number if the MRBS week starts on the first day of the week ?>
    config.weekNumbers = <?php echo ($mincals_week_numbers && ($weekstarts == DateTime::firstDayOfWeek($timezone, get_mrbs_locale()))) ? 'true' : 'false' ?>;
  }

  flatpickr('input[type="date"]:not(.navigation)', config);

  <?php
  // For datepickers used for navigation we need to modify the config to take account
  // of hidden days.

  // Disable hidden days, unless the user is a booking admin.  (If they're a booking
  // admin then they'll still be able to select the date, but it will be given a different
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

  flatpickr('input[type="date"].navigation', config);

  <?php
  // Build the mini-calendars, if required.
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
          <?php // Add the 'navigation' class so that the JavaScript knows it can use hidden days ?>
          div.append($('<span class="minicalendar navigation" id="cal' + i + '"></span>'));
        }
        config.inline = true;
        config.onMonthChange = onMonthChange;
        config.onYearChange = onYearChange;
        config.onChange = onMinicalChange;
        <?php
        // Setting a range only works if there are no hidden days: it does not make
        // sense to set a start date of a range on a disabled day.
        if (empty($hidden_days))
        {
          ?>
          config.mode = 'range';
          <?php
        }
        ?>

        var minicalendars = flatpickr('span.minicalendar', config);

        $.each(minicalendars, function (key, value) {
            var startDate, endDate;
            if (args.view === 'month')
            {
              startDate = monthStart(args.pageDate);
              endDate = monthEnd(args.pageDate);
            }
            else if (args.view === 'week')
            {
              startDate = weekStart(args.pageDate, <?php echo $weekstarts?>);
              endDate = weekEnd(args.pageDate, <?php echo $weekstarts?>);
            }
            else
            {
              startDate = args.pageDate;
              endDate = startDate;
            }
            <?php
            if (empty($hidden_days))
            {
              ?>
              value.setDate([startDate, endDate]);
              <?php
            }
            else
            {
              // If we've got hidden days then highlight in the mini-calendars those
              // days in the range that are not hidden.
              ?>
              value.setDate(datesInRange(startDate, endDate, true));
              <?php
            }
            ?>
            value.changeMonth(key);
          });

        <?php
        // Align the top of the mini-calendars with the top of the navigation bar
        ?>
        div.css('margin-top', $('.view_container h2').outerHeight(true) + 'px');

        <?php
        // Once the calendars are formed then we add the class 'formed' which will
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

