<?php
declare(strict_types=1);
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry
?>

'use strict';

var isBookAdmin;

<?php
// Set (if set is true) or clear (if set is false) a timer
// to check for conflicts periodically in case someone else
// books the slot you are looking at.  If setting the timer
// it also performs an immediate check.
?>
var conflictTimer = function conflictTimer(set) {
    <?php
    if (!empty($ajax_refresh_rate))
    {
      ?>
      if (set)
      {
        <?php
        // (Note the config variable is in seconds, but the setInterval() function
        // uses milliseconds)
        // Only set the timer if the page is visible
        ?>
        if (!isHidden())
        {
          checkConflicts(true);
          conflictTimer.id = window.setInterval(function() {
              checkConflicts(true);
            }, <?php echo $ajax_refresh_rate * 1000 ?>);
        }
      }
      else if (typeof conflictTimer.id !== 'undefined')
      {
        window.clearInterval(conflictTimer.id);
      }
      <?php
    }
    ?>
  };


<?php
// Function to (1) add 'required' attributes to the rep_interval and rep_end_date fields
// if it's a repeating booking, (2) show/hide the repeat end date and skip fields and
// (3) display the secondary repeat type fieldset appropriate to the selected repeat type.
?>
var changeRepTypeDetails = function changeRepTypeDetails() {
    var repType = parseInt($('input[name="rep_type"]:checked').val(), 10);
    var isRepeat = (repType !== <?php echo RepeatRule::NONE ?>);
    <?php
    // Add a 'required' attribute to the rep_interval input to prevent users entering an
    // empty string.  But remove it if it's not a repeating entry, because if they happen
    // to have an empty string they won't see the validation message since the input will
    // be hidden.
    ?>
    $('#rep_interval').prop('required', isRepeat);
    <?php
    // Add a 'required' attribute to the rep_end_date input if shown to prevent users
    //  entering an empty string.  Show/hide the repeat end date and skip fields
    ?>
    $('#rep_end_date').prop('required', isRepeat).parent().toggle(isRepeat);
    $('#skip').parent().toggle(isRepeat);
    <?php // Show the appropriate details ?>
    $('.rep_type_details').hide();
    switch (repType)
    {
      case <?php echo RepeatRule::WEEKLY ?>:
        $('#rep_weekly').show();
        break;
      case <?php echo RepeatRule::MONTHLY ?>:
        $('#rep_monthly').show();
        break;
      default:
        break;
    }
  };


<?php
// Function to change the units for the repeat interval to match the repeat type.
?>
var changeRepIntervalUnits = function changeRepIntervalUnits() {
    var repType = parseInt($('input[name="rep_type"]:checked').val(), 10);
    var repInterval = parseInt($('input[name="rep_interval"]').val(), 10);
    var units = $('#interval_units');
    var text;
    switch (repType)
    {
      case <?php echo RepeatRule::DAILY ?>:
        text = (repInterval === 1) ? '<?php echo get_js_vocab('day') ?>' : '<?php echo get_js_vocab('days') ?>';
        break;
      case <?php echo RepeatRule::WEEKLY ?>:
        text = (repInterval === 1) ? '<?php echo get_js_vocab('week') ?>' : '<?php echo get_js_vocab('weeks') ?>';
        break;
      case <?php echo RepeatRule::MONTHLY ?>:
        text = (repInterval === 1) ? '<?php echo get_js_vocab('month') ?>' : '<?php echo get_js_vocab('months') ?>';
        break;
      case <?php echo RepeatRule::YEARLY ?>:
        text = (repInterval === 1) ? '<?php echo get_js_vocab('year_lc') ?>' : '<?php echo get_js_vocab('years') ?>';
        break;
      default:
        text = units.text();
        break;
    }
    units.text(text);

    units.parent().toggle(repType !== <?php echo RepeatRule::NONE ?>);
  };


// areaConfig returns the properties ('enable_periods', etc.) for an area,
// by default the current area
var areaConfig = function areaConfig(property, areaId) {

    var properties = ['enable_periods', 'n_periods', 'default_duration', 'max_duration_enabled',
                      'max_duration_secs', 'max_duration_periods', 'max_duration_qty',
                      'max_duration_units', 'timezone'];
    var i, p, room;

    if ($.inArray(property, properties) < 0)
    {
      throw new Error("areaConfig(): invalid property '" + property + "' passed to areaConfig");
    }

    if (areaId === undefined)
    {
      areaId = $('#area').val();
    }

    if (areaConfig.data === undefined)
    {
      areaConfig.data = [];
    }
    if (areaConfig.data[areaId] === undefined)
    {
      areaConfig.data[areaId] = {};
      room = $('#rooms' + areaId);
      for (i=0; i<properties.length; i++)
      {
        p = properties[i];
        areaConfig.data[areaId][p] = room.data(p);
      }
    }
    return areaConfig.data[areaId][property];
  };


<?php
// Check to see whether any time slots should be removed from the time
// select on the grounds that they don't exist due to a transition into DST.
// Don't do this if we're using periods, because it doesn't apply then
//
//    jqDate          a jQuery object for the datepicker in question
?>
function checkTimeSlots(jqDate)
{
  if (!areaConfig('enable_periods'))
  {
    var siblings = jqDate.siblings();
    var select = jqDate.parent().parent().siblings('select:visible');
    var slots = [];
    select.find('option').each(function() {
        slots.push($(this).val());
      });
    <?php
    // We pass the id of the element as the request id so that we can match
    // the result to the request
    ?>
    var params = {csrf_token: getCSRFToken(),
                  id: select.attr('id'),
                  day: parseInt(siblings.filter('input[id*="day"]').val(), 10),
                  month: parseInt(siblings.filter('input[id*="month"]').val(), 10),
                  year: parseInt(siblings.filter('input[id*="year"]').val(), 10),
                  tz: areaConfig('timezone'),
                  slots: slots};

    if(args.site)
    {
      params.site = args.site;
    }

    $.post('ajax/check_slot.php', params, function(result) {
        $.each(result.slots, function(key, value) {
            $('#' + result.id).find('option[value="' + value + '"]').remove();
          });
        <?php
        // Now that we've removed some options we need to equalise the widths
        ?>
        adjustWidth($('#start_seconds'),
                    $('#end_seconds'));
      }, 'json');
  } <?php // if (!areaConfig('enable_periods')) ?>

}


<?php
// Executed when the user clicks on the all_day checkbox.
?>
function onAllDayClick()
{
  var form = $('#main');
  if (form.length === 0)
  {
    return;
  }

  var startSelect = form.find('#start_seconds'),
      endSelect = form.find('#end_seconds'),
      allDay = form.find('#all_day');

  var startDatepicker = form.find('#start_date'),
      endDatepicker = form.find('#end_date');

  var date, firstSlot, lastSlot;

  if (allDay.is(':checked')) // If checking the box...
  {
    <?php
    // Save the old values, disable the inputs and, to avoid user confusion,
    // show the start and end times as the beginning and end of the booking
    ?>
    firstSlot = parseInt(startSelect.find('option').first().val(), 10);
    lastSlot = parseInt(endSelect.find('option').last().val(), 10);
    onAllDayClick.oldStart = parseInt(startSelect.val(), 10);
    onAllDayClick.oldStartDatepicker = startDatepicker.val();
    startSelect.val(firstSlot);
    startSelect.prop('disabled', true);
    onAllDayClick.oldEnd = parseInt(endSelect.val(), 10);
    onAllDayClick.oldEndDatepicker = endDatepicker.val();
    endSelect.val(lastSlot);
    if ((lastSlot < firstSlot) &&
        (onAllDayClick.oldStartDatepicker === onAllDayClick.oldEndDatepicker))
    {
      <?php
      // If the booking day spans midnight then the first and last slots
      // are going to be on different days.
      // This code works because new Date() with just a date string generates a UTC
      // date and toISOString() always returns a UTC datetime.
      ?>
      if (onAllDayClick.oldStart < firstSlot)
      {
        date = new Date(onAllDayClick.oldStartDatepicker);
        date.setDate(date.getDate() - 1);
        startDatepicker.val(date.toISOString().split('T')[0]);
      }
      else
      {
        date = new Date(onAllDayClick.oldEndDatepicker);
        date.setDate(date.getDate() + 1);
        endDatepicker.val(date.toISOString().split('T')[0]);
      }
    }
    endSelect.prop('disabled', true);
  }
  else  <?php // restore the old values and re-enable the inputs ?>
  {
    startSelect.val(onAllDayClick.oldStart);
    startDatepicker.val(onAllDayClick.oldStartDatepicker);
    startSelect.prop('disabled', false);
    endSelect.val(onAllDayClick.oldEnd);
    endDatepicker.val(onAllDayClick.oldEndDatepicker);
    endSelect.prop('disabled', false);
  }

  adjustSlotSelectors(); <?php // need to get the duration right ?>

}

<?php
// Set the error messages to be used for the various fields.     We do this twice:
// once to redefine the HTML5 error message and once for JavaScript alerts, for those
// browsers not supporting HTML5 field validation.
?>
function validationMessages()
{
  var field, label;
  <?php
  // First of all create a property in the vocab object for each of the mandatory
  // fields.    The name and rooms field are implicitly mandatory.
  ?>
  validationMessages.vocab = {};
  validationMessages.vocab['name'] = '';
  validationMessages.vocab['rooms'] = '';
  <?php
  foreach ($is_mandatory_field as $key => $value)
  {
    if ($value)
    {
      list($table, $fieldname) = explode('.', $key, 2);
      if ($table == 'entry')
      {
        $prefix = (in_array($fieldname, $standard_fields['entry'])) ? '' : VAR_PREFIX;
        ?>
        validationMessages.vocab['<?php echo escape_js($prefix . $fieldname) ?>'] = '';
        <?php
      }
    }
  }

  // Then (a) fill each of those properties with an error message and (b) redefine
  // the HTML5 error message
  ?>
  for (var key in validationMessages.vocab)
  {
    if (validationMessages.vocab.hasOwnProperty(key))
    {
      label = $("label[for=" + key + "]");
      if (label.length > 0)
      {
        validationMessages.vocab[key] = label.text();
        validationMessages.vocab[key] = '"' + validationMessages.vocab[key] + '" ';
        validationMessages.vocab[key] += '<?php echo get_js_vocab("is_mandatory_field") ?>';

        field = document.getElementById(key);
        if (field.setCustomValidity && field.willValidate)
        {
          <?php
          // We define our own custom event called 'validate' that is triggered on the
          // 'change' and 'input' events.  We need both events because (a) the change event
          // for text input is only triggered when the element loses focus and we want the
          // validation to happen whenever a character is input, (b) autocomplete in some
          // browsers, eg Firefox, does not trigger the input event and (c) the input event
          // is not triggered for checkboxes or select elements.
          ?>
          $(field).on('validate', function(e) {
            <?php
            // need to clear the custom error message otherwise the browser will
            // assume the field is invalid
            ?>
            e.target.setCustomValidity("");
            if (!e.target.validity.valid)
            {
              e.target.setCustomValidity(validationMessages.vocab[$(e.target).attr('id')]);
            }
          });

          $(field).on('change input', function() {
            $(this).trigger('validate');
          });

          <?php
          // When a form validation fails we need to clear the submit flag because
          // otherwise checkConflicts() won't do anything (because we don't check
          // for conflicts on a submit)
          ?>
          $(field).on('invalid', function() {
            $(this).closest('form').removeData('submit');
          });
          <?php
          // Trigger the validate event when the form is first loaded
          ?>
          $(field).trigger('validate');
        }
      }  <?php // if (label.length > 0) ?>
    }  <?php // if (validationMessages.vocab.hasOwnProperty(key)) ?>
  }  <?php //for ?>
}


<?php
// do a little form verifying
?>
function validate(form)
{
  var testInput = document.createElement("input");
  var testSelect = document.createElement("select");
  var validForm = true;

  <?php
  // Mandatory fields (INPUT elements, except for checkboxes).
  // Only necessary if the browser doesn't support the HTML5 pattern or
  // required attributes
  ?>
  if (!("pattern" in testInput) || !("required" in testInput))
  {
    form.find('input').not('[type="checkbox"]').each(function() {
      var id = $(this).attr('id');
      if (validationMessages.vocab[id])
      {
        if (<?php echo REGEX_TEXT_NEG ?>.test($(this).val()))
        {
          window.alert(validationMessages.vocab[id]);
          validForm = false;
          return false;
        }
      }
    });
    if (!validForm)
    {
      return false;
    }
  }

  <?php
  // Mandatory fields (INPUT elements, checkboxes only).
  // Only necessary if the browser doesn't support the HTML5 required attribute
  ?>
  if (!("required" in testInput))
  {
    form.find('input').filter('[type="checkbox"]').each(function() {
      var id = $(this).attr('id');
      if (validationMessages.vocab[id])
      {
        if (!$(this).is(':checked'))
        {
          window.alert(validationMessages.vocab[id]);
          validForm = false;
          return false;
        }
      }
    });
    if (!validForm)
    {
      return false;
    }
  }

  <?php
  // Mandatory fields (TEXTAREA elements).
  // Note that the TEXTAREA element only supports the "required" attribute and not
  // the "pattern" attribute.    So we need to do these tests in all cases because
  // the browser will let through a string consisting only of whitespace.
  ?>
  form.find('textarea').each(function() {
    var id = $(this).attr('id');
    if (validationMessages.vocab[id])
    {
      if (<?php echo REGEX_TEXT_NEG ?>.test($(this).val()))
      {
        window.alert(validationMessages.vocab[id]);
        validForm = false;
        return false;
      }
    }
  });
  if (!validForm)
  {
    return false;
  }

  <?php
  // Mandatory fields (SELECT elements).
  // Only necessary if the browser doesn't support the HTML5 required attribute
  ?>
  if (!("required" in testSelect))
  {
    form.find('select').each(function() {
      var id = $(this).attr('id');
      if (validationMessages.vocab[id])
      {
        if ($(this).val() === '')
        {
          window.alert(validationMessages.vocab[id]);
          validForm = false;
          return false;
        }
      }
    });
    if (!validForm)
    {
      return false;
    }
  }

  <?php // Check that the start date is not after the end date ?>
  var dateDiff = getDateDifference();
  if (dateDiff < 0)
  {
    window.alert("<?php echo get_js_vocab('start_after_end_long')?>");
    return false;
  }

  <?php // Repeat checks ?>
  var repType = form.find('input:radio[name=rep_type]:checked').val();
  if ((repType !== undefined) && (parseInt(repType, 10) !== <?php echo RepeatRule::NONE ?>))
  {
    <?php
    // Check that there's a sensible value for rep_interval.   Only necessary
    // if the browser doesn't support the HTML5 min and step attributes
    ?>
    if ((!("min" in testInput) || !(("step" in testInput))) &&
        (form.find('#rep_interval').val() < 1))
    {
      window.alert("<?php echo get_js_vocab('invalid_rep_interval') ?>");
      return false;
    }
    <?php
    // Check that the repeat end date has been set (people often forget to do so).  If it's the
    // same as the entry end date then it probably hasn't.
    ?>
    if ($('input[name="rep_end_date"]').val() === $('input[name="end_date"]').val())
    {
      if (!window.confirm("<?php echo get_js_vocab('confirm_rep_end_date') ?>"))
      {
        return false;
      }
    }
  }

  <?php
  // Form submit can take some time, especially if mails are enabled and
  // there are more than one recipient. To avoid users doing weird things
  // like clicking more than one time on submit button, we hide it as soon
  // it is clicked.
  ?>
  form.find('input[type=submit]').prop('disabled', true);

  <?php
  // would be nice to also check date to not allow Feb 31, etc...
  ?>

  return true;
}


<?php
// function to check whether the proposed booking would (a) conflict with any other bookings
// and (b) conforms to the booking policies.   Makes an Ajax call to edit_entry_handler but does
// not actually make the booking.
//
// If optional is true then the check is not carried out if there's already an
// outstanding request in the queue
?>
function checkConflicts(optional)
{
  <?php // Get the value of the field in the form ?>
  function getFormValue(formInput)
  {
    var value;
    <?php
    // Scalar parameters (three types - checkboxes, radio buttons and the rest)
    ?>
    if (formInput.attr('name').indexOf('[]') === -1)
    {
      if (formInput.filter(':checkbox').length > 0)
      {
        value = formInput.is(':checked') ? '1' : '';
      }
      else if (formInput.filter(':radio').length > 0)
      {
        value = formInput.filter(':checked').val();
      }
      else
      {
        value = formInput.val();
      }
    }
    <?php
    // Array parameters (two types - checkboxes and the rest, which could be
    // <select> elements or else multiple ordinary inputs with a *[] name
    ?>
    else
    {
      value = [];
      formInput.each(function() {
          if ((formInput.filter(':checkbox').length === 0) || $(this).is(':checked'))
          {
            var thisValue = $(this).val();
            if ($.isArray(thisValue))
            {
              $.merge(value, thisValue);
            }
            else
            {
              value.push($(this).val());
            }
          }
        });
    }
    return value;
  } <?php // function getFormValue()


  // Keep track of how many requests are still with the server.   We don't want
  // to keep sending them if they're not coming back
  ?>
  if (checkConflicts.nOutstanding === undefined)
  {
    checkConflicts.nOutstanding = 0;
  }
  <?php
  // If this is an optional request and there are already some check requests
  // in the queue, then don't bother with this one.
  ?>
  if (optional && checkConflicts.nOutstanding)
  {
    return;
  }

  <?php
  // We set a small timeout on checking the booking in order to allow time for
  // the click handler on the Submit buttons to set the data in the form.  We then
  // test the data and if it is set we don't validate the booking because we're going off
  // somewhere else.  [This isn't an ideal way of doing this.   The problem is that
  // the change event for a text input can be fired when the user clicks the submit
  // button - but how can you tell that it was the clicking of the submit button that
  // caused the change event?]
  ?>
  var timeout = 200; <?php // ms ?>
  window.setTimeout(function() {
    var params = {};
    var form = $('form#main');
    <?php
    // Don't do anything if (a) the form doesn't exist (which it won't if the user
    // hasn't logged in) or (b) if the submit button has been pressed
    ?>
    if ((form.length === 0) || form.data('submit'))
    {
      return;
    }

    <?php
    // Load the params object with the values of all the form fields that are not
    // disabled and are not submit buttons of one kind or another
    ?>
    var relevantFields = form.find('[name]').not(':disabled, [type="submit"], [type="button"], [type="image"]');
    relevantFields.each(function() {
        <?php
        // Go through each of the fields and if we haven't got the value for a name
        // then go and get it.  (Remember that arrays can give more than one field
        // with the same name
        ?>
        var fieldName = $(this).attr('name');
        if (params[fieldName] === undefined)
        {
          params[fieldName] = getFormValue(relevantFields.filter('[name=' + fieldName.replace('[', '\\[').replace(']', '\\]') + ']'));
        }
      });

    <?php
    // For some reason I don't understand, posting an empty array will
    // give you a PHP array of ('') at the other end.    So to avoid
    // that problem, delete the property if the array (really an object) is empty
    ?>
    $.each(params, function(i, val) {
        if ((typeof(val) === 'object') && ((val === null) || (val.length === 0)))
        {
          delete params[i];
        }
      });

    if(args.site)
    {
      params.site = args.site;
    }

    checkConflicts.nOutstanding++;
    $.post('edit_entry_handler.php', params)
      .fail(function() {
        $('#checks').hide();
      })
      .done(function(result) {
        if (!result)
        {
          $('#checks').hide();
        }
        else {
          $('#checks').show();
          checkConflicts.nOutstanding--;
          var conflictDiv = $('#conflict_check');
          var scheduleDetails = $('#schedule_details');
          var policyDetails = $('#policy_details');
          var titleText, detailsHTML;
          if (result.conflicts.length === 0)
          {
            conflictDiv.attr('class', 'good');
            titleText = '<?php echo escape_js(html_entity_decode(get_vocab("no_conflicts"))) ?>';
            detailsHTML = titleText;
          }
          else
          {
            conflictDiv.attr('class', 'bad');
            detailsHTML = "<p>";
            titleText = '<?php echo escape_js(html_entity_decode(get_vocab("conflict"))) ?>' + "\n\n";
            detailsHTML += titleText + "<\/p>";
            var conflictsList = getErrorList(result.conflicts);
            detailsHTML += conflictsList.html;
            titleText += conflictsList.text;
          }
          conflictDiv.attr('title', titleText);
          scheduleDetails.html(detailsHTML);

          <?php
          // Display the results of the policy check. Set the class to "good" if there
          // are no policy violations at all; to "notice" if there are no errors, but some
          // notices (this happens when an admin user makes a booking that an ordinary user
          // would not be allowed to); otherwise "bad".  Content and styling are supplied by CSS.
          ?>
          var policyDiv = $('#policy_check');
          var rulesList;
          if (result.violations.errors.length === 0)
          {
            if (result.violations.notices.length === 0)
            {
              policyDiv.attr('class', 'good');
              titleText = '<?php echo escape_js(html_entity_decode(get_vocab("no_rules_broken"))) ?>';
              detailsHTML = titleText;
            }
            else
            {
              policyDiv.attr('class', 'notice');
              detailsHTML = "<p>";
              titleText = '<?php echo escape_js(html_entity_decode(get_vocab("rules_broken_notices"))) ?>' + "\n\n";
              detailsHTML += titleText + "<\/p>";
              rulesList = getErrorList(result.violations.notices);
              detailsHTML += rulesList.html;
              titleText += rulesList.text;
            }
          }
          else
          {
            policyDiv.attr('class', 'bad');
            detailsHTML = "<p>";
            titleText = '<?php echo escape_js(html_entity_decode(get_vocab("rules_broken"))) ?>' + "\n\n";
            detailsHTML += titleText + "<\/p>";
            rulesList = getErrorList(result.violations.errors);
            detailsHTML += rulesList.html;
            titleText += rulesList.text;
          }
          policyDiv.attr('title', titleText);
          policyDetails.html(detailsHTML);
        }  <?php // if (!result) else ?>
      }, 'json');
  }, timeout);  <?php // setTimeout() ?>

} <?php // function checkConflicts()


// Get the current vocab (in the appropriate language) for periods,
// minutes, hours and days
?>
var vocab = {};
vocab.periods = {singular: '<?php echo get_js_vocab("period_lc") ?>',
                 plural:   '<?php echo get_js_vocab("periods") ?>'};
vocab.minutes = {singular: '<?php echo get_js_vocab("minute_lc") ?>',
                 plural:   '<?php echo get_js_vocab("minutes") ?>'};
vocab.hours   = {singular: '<?php echo get_js_vocab("hour_lc") ?>',
                 plural:   '<?php echo get_js_vocab("hours") ?>'};
vocab.days    = {singular: '<?php echo get_js_vocab("day") ?>',
                 plural:   '<?php echo get_js_vocab("days") ?>'};


<?php
// Removes any trailing zeroes after the decimal point.
?>
function durFormat(r)
{
  var lastChar;

  r = r.toFixed(2);
  r = parseFloat(r);
  r = r.toLocaleString();

  if ((r.indexOf('.') >= 0) || (r.indexOf(',') >= 0))
  {
    while (r.slice(-1) === '0')
    {
      r = r.slice(0, -1);
    }

    lastChar = r.slice(-1);
    if ((lastChar === '.') || (lastChar === ','))
    {
      r = r.slice(0, -1);
    }
  }

  return r;
}

<?php
// Returns a string giving the duration having chosen sensible units,
// translated into the user's language, and formatted the number, taking
// into account the user's locale.    Note that when using periods one
// is added to the duration because the model is slightly different
//   - from   the start time (in seconds since the start of the day
//   - to     the end time (in seconds since the start of the day)
//   - days   the number of days difference
?>
function getDuration(from, to, days)
{
  var duration, durUnits;
  var text = '';
  var currentArea = $('#area').data('current');
  var enablePeriods = areaConfig('enable_periods');
  var durDays;
  var minutesPerDay = <?php echo MINUTES_PER_DAY ?>;


  durUnits = (enablePeriods) ? '<?php echo "periods" ?>' : '<?php echo "minutes" ?>';
  duration = Math.floor((to - from) / 60);

  if (enablePeriods)
  {
    duration++;  <?php // a period is a period rather than a point ?>
  }

  <?php
  // Adjust the days and duration so that 0 <= duration < minutesPerDay.    If we're using
  // periods then if necessary add/subtract multiples of the number of periods in a day
  ?>
  durDays = Math.floor(duration/minutesPerDay);
  if (durDays !== 0)
  {
    days += durDays;
    duration -= durDays * ((enablePeriods) ? $('#start_seconds' + currentArea).find('option').length : minutesPerDay);
  }

  if (!enablePeriods && (duration >= 60))
  {
    durUnits = "hours";
    duration = durFormat(duration/60);
  }

  <?php
  // As durFormat returns a string, duration can now be either
  // a number or a string, so convert it to a string so that we
  // know what we are dealing with
  ?>
  duration = duration.toString();

  if (days !== 0)
  {
    text += days + ' ';
    text += (days === 1) ? vocab.days.singular : vocab.days.plural;
    if (duration !== '0')
    {
      text +=  ', ';
    }
  }

  if (duration !== '0')
  {
    text += duration + ' ';
    text += (duration === '1') ? vocab[durUnits].singular : vocab[durUnits].plural;
  }

  return text;
}

<?php
// Returns the number of days between the start and end dates
?>
function getDateDifference()
{
  var diff,
      secondsPerDay = <?php echo SECONDS_PER_DAY ?>,
      start = $('#start_date').val().split('-'),
      startDate = new Date(parseInt(start[0], 10),
                           parseInt(start[1], 10) - 1,
                           parseInt(start[2], 10),
                           12),
      endDate = $('#end_date'),
      end;

  if (endDate.length === 0)
  {
    <?php
    // No end date selector, so assume the end date is
    // the same as the start date
    ?>
    diff = 0;
  }
  else
  {
    end = endDate.val().split('-');
    endDate = new Date(parseInt(end[0], 10),
                       parseInt(end[1], 10) - 1,
                       parseInt(end[2], 10),
                       12);

    diff = (endDate - startDate)/(secondsPerDay * 1000);
    diff = Math.round(diff);
  }

  return diff;
}


<?php
// Make two jQuery objects the same width.
?>
function adjustWidth(a, b)
{
  <?php
  // Note that we set the widths of both objects, even though it would seem
  // that just setting the width of the smaller should be sufficient.
  // But if you don't set both of them then you end up with a few
  // pixels difference.  In other words doing a get and then a set
  // doesn't leave you where you started - not quite sure why.
  // The + 2 is a fudge factor to make sure that the option text in select
  // elements isn't truncated - not quite sure why it is necessary.
  // The width: auto is necessary to get the elements to resize themselves
  // according to their new contents.
  ?>
  a.css({width: "auto"});
  b.css({width: "auto"});
  var aWidth = a.width();
  var bWidth = b.width();
  var maxWidth = Math.max(aWidth, bWidth) + 2;
  a.width(maxWidth);
  b.width(maxWidth);
}


var reloadSlotSelector = function reloadSlotSelector(select, area) {
    select.html($('#' + select.attr('id') + area).html())
          .val(select.data('current'));
  };


var updateSelectorData = function updateSelectorData(){
    var selectors = ['area', 'start_seconds', 'end_seconds'];
    var i, select;

    for (i=0; i<selectors.length; i++)
    {
      select = $('#' + selectors[i]);
      select.data('previous', select.data('current'));
      select.data('current', select.val());
    }
  };


function adjustSlotSelectors()
{
  <?php
  // Adjust the start and end time slot select boxes.
  // (a) If the start time has changed then adjust the end time so
  //     that the duration is still the same, provided that the endtime
  //     does not go past the start of the booking day
  // (b) If the end time has changed then adjust the duration.
  // (c) Make sure that you can't have an end time before the start time.
  // (d) Tidy up the two select boxes so that they are the same width
  // (e) if oldArea etc. are set, then we've switched areas and we want
  //     to have a go at finding a time/period in the new area as close
  //     as possible to the one that was selected in the old area.
  ?>
  var area = $('#area'),
      oldArea = area.data('previous'),
      currentArea = area.data('current');

  var enablePeriods    = areaConfig('enable_periods'),
      oldEnablePeriods = areaConfig('enable_periods', oldArea),
      defaultDuration  = areaConfig('default_duration');

  var startSelect = $('#start_seconds'),
      endSelect = $('#end_seconds'),
      allDay = $('#all_day');

  var startKeepDisabled = startSelect.hasClass('keep_disabled'),
      endKeepDisabled = endSelect.hasClass('keep_disabled'),
      allDayKeepDisabled = allDay.hasClass('keep_disabled');

  var oldStartValue = parseInt(startSelect.data('previous'), 10),
      oldEndValue = parseInt(endSelect.data('previous'), 10);

  var nbsp = '\u00A0',
      startValue, endValue, firstValue, lastValue, optionClone;

  if (startSelect.length === 0)
  {
    return;
  }
  <?php
  // If All Day is checked then just set the start and end values to the first
  // and last possible options.
  ?>
  if (allDay.is(':checked'))
  {
    startValue = parseInt(startSelect.find('option').first().val(), 10);
    endValue = parseInt(endSelect.find('option').last().val(), 10);
    <?php
    // If we've come here from another area then we need to make sure that the
    // start and end selectors are disabled.  (We won't change the old_end and old_start
    // values, because there's a chance the existing ones may still work - for example if
    // the user flicks from Area A to Area B and then back to Area A, or else if the time/
    // period slots in Area B match those in Area.)
    ?>
    if (oldArea !== currentArea)
    {
      startSelect.prop('disabled', true);
      endSelect.prop('disabled', true);
    }
  }
  <?php
  // Otherwise what we do depends on whether we've come here as a result
  // of the area being changed
  ?>
  else if (oldArea !== currentArea)
  {
    <?php
    // If we've changed areas and the modes are the same, we can try and match times/periods.
    // We will try and be conservative and find a start time that includes the previous start time
    // and an end time that includes the previous end time.   This means that by default the
    // booking period will include the old booking period (unless we've hit the start or
    // end of day).   But it does mean that as you switch between areas the booking period
    // tends to get bigger:  if you switch fromn Area 1 to Area 2 and then back again it's
    // possible that the booking period for Area 1 is longer than it was originally.
    ?>
    if (oldEnablePeriods === enablePeriods)
    {
      <?php
      // Step back through the start options until we find one that is less than or equal to the previous value,
      // or else we've got to the first option
      ?>
      startSelect.find('option').reverse().each(function() {
          startValue = parseInt($(this).val(), 10);
          if (startValue <= oldStartValue)
          {
            return false;
          }
        });
      <?php
      // And step forward through the end options until we find one that is greater than
      // or equal to the previous value, or else we've got to the last option
      ?>
      endSelect.find('option').each(function() {
          endValue = parseInt($(this).val(), 10);
          if (endValue >= oldEndValue)
          {
            return false;
          }
        });
    }
    <?php
    // The modes are different, so it doesn't make any sense to match up old and new
    // times/periods.   The best we can do is choose some sensible defaults, which
    // is to set the start to the first possible start, and the end to the start + the
    // default duration (or the last possible end value if that is less)
    ?>
    else
    {
      startValue = parseInt(startSelect.find('option').first().val(), 10);
      if (enablePeriods)
      {
        endValue = startValue;
      }
      else
      {
        endValue = startValue + defaultDuration;
      }
    }
  }
  <?php
  // We haven't changed areas.  In this case get the currently selected start and
  // end values
  ?>
  else
  {
    startValue = parseInt(startSelect.val(), 10);
    endValue = parseInt(endSelect.val(), 10);
    <?php
    // If the start value has changed then we adjust the end value
    // to keep the duration the same.  (If the end value has changed
    // then the duration will be changed when we recalculate durations below)
    ?>
    if (startValue !== oldStartValue)
    {
      endValue = endValue + (startValue - oldStartValue);
    }
  }

  var dateDifference = getDateDifference();

  <?php
  // If All Day isn't checked then we need to work out whether the start
  // and end dates are valid.   If the end date is before the start date
  // then we disable all the time selectors (start, end and All Day) until
  // the dates are fixed.
  ?>
  if (!allDay.is(':checked'))
  {
    var newState = (dateDifference < 0);
    if (newState || startKeepDisabled)
    {
      startSelect.prop('disabled', true);
    }
    else
    {
      startSelect.prop('disabled', false);
    }
    if (newState || endKeepDisabled)
    {
      endSelect.prop('disabled', true);
    }
    else
    {
      endSelect.prop('disabled', false);
    }
    if (newState || allDayKeepDisabled)
    {
      allDay.prop('disabled', true);
    }
    else
    {
      allDay.prop('disabled', false);
    }
  }

  <?php // Destroy and rebuild the start select ?>
  startSelect.html($('#start_seconds' + currentArea).html());
  startSelect.val(startValue);
  startSelect.data('current', startValue);

  <?php // Destroy and rebuild the end select ?>
  endSelect.empty();

  $('#end_time_error').text('');  <?php  // Clear the error message ?>

  $('#end_seconds' + currentArea).find('option').each(function(i) {

      var thisValue = parseInt($(this).val(), 10),
          nPeriods           = areaConfig('n_periods'),
          maxDurationEnabled = areaConfig('max_duration_enabled'),
          maxDurationSecs    = areaConfig('max_duration_secs'),
          maxDurationPeriods = areaConfig('max_duration_periods'),
          maxDurationQty     = areaConfig('max_duration_qty'),
          maxDurationUnits   = areaConfig('max_duration_units'),
          secondsPerDay      = <?php echo SECONDS_PER_DAY ?>,
          duration,
          maxDuration;

      <?php
      // Limit the end slots to the maximum duration if that is enabled, if the
      // user is not a booking admin
      ?>
      if (!isBookAdmin)
      {
        if (maxDurationEnabled)
        {
          <?php
          // Calculate the duration in periods or seconds
          ?>
          duration =  thisValue - startValue;
          if (enablePeriods)
          {
            duration = duration/60 + 1;  <?php // because of the way periods work ?>
            duration += dateDifference * nPeriods;
          }
          else
          {
            duration += dateDifference * secondsPerDay;
          }
          maxDuration = (enablePeriods) ? maxDurationPeriods : maxDurationSecs;
          if (duration > maxDuration)
          {
            if (i === 0)
            {
              endSelect.append($(this).val(thisValue).text(nbsp));
              var errorMessage = '<?php echo get_js_vocab("max_booking_duration") ?>' + nbsp;
              if (enablePeriods)
              {
                errorMessage += maxDurationPeriods + nbsp;
                errorMessage += (maxDurationPeriods > 1) ? vocab.periods.plural : vocab.periods.singular;
              }
              else
              {
                errorMessage += maxDurationQty + nbsp + maxDurationUnits;
              }
              $('#end_time_error').text(errorMessage);
            }
            else
            {
              return false;
            }
          }
        }
      }

      if ((thisValue > startValue) ||
          ((thisValue === startValue) && enablePeriods) ||
          (dateDifference !== 0))
      {
        optionClone = $(this).clone();
        if (dateDifference < 0)
        {
          optionClone.text('<?php echo get_js_vocab("start_after_end")?>');
        }
        else
        {
          optionClone.text($(this).text() + nbsp + nbsp +
                           '(' + getDuration(startValue, thisValue, dateDifference) +
                           ')');
        }
        endSelect.append(optionClone);
      }
    });

  firstValue = parseInt(endSelect.find('option').first().val(), 10);
  lastValue = parseInt(endSelect.find('option').last().val(), 10);
  if (isNaN(endValue)) <?php // Is this possible? ?>
  {
    endValue = lastValue;
  }
  else
  {
    <?php
    // We constrain the end value to stay on the same day, but it might be
    // better to move the end date selector back one day if the new end value
    // would be before the beginning of the day, and similarly forward one day
    // if it would be after the end of the day.  This is what some other calendar
    // systems, eg Outlook, do, but it gets a little more complicated when the
    // booking day is less than 24 hours, as it is by default in MRBS.
    ?>
    endValue = Math.max(endValue, firstValue);
    endValue = Math.min(endValue, lastValue);
  }

  endSelect.val(endValue);
  endSelect.data('current', endValue);

  adjustWidth(startSelect, endSelect);

} <?php // function adjustSlotSelectors() ?>


var editEntryVisChanged = function editEntryVisChanged() {
    <?php
    // Clear the conflict timer and then restart it.   We want
    // a check to be performed immediately the page becomes
    // visible again.
    ?>
    conflictTimer(false);
    conflictTimer(true);
  };


function populateFromSessionStorage(form)
{
  var storedData = sessionStorage.getItem('form_data');
  if (storedData)
  {
    var form_data = JSON.parse(storedData);

    <?php
    // Before we populate the form we have to set the area select to the correct
    // area and then change selects that depend on it, eg the room selects.
    ?>
    $.each(form_data, function (index, field)
    {
      if (field.name === 'area')
      {
        $('#area').val(field.value).trigger('change');
        return false;  // We've found the area field so we can stop.
      }
    });

    <?php // Now iterate through the data again and populate the form ?>
    var selects = {};

    $.each(form_data, function (index, field)
    {
      <?php // Don't change the CSRF token - the form will have its own one. ?>
      if (field.name === 'csrf_token')
      {
        return;
      }

      var el = $('[name="' + field.name + '"]'),
        tagName = el.prop('tagName'),
        type;

      <?php // Some of the variables, eg 'top', won't have a corresponding field ?>
      if (tagName === undefined)
      {
        return;
      }

      <?php
      // If it's a select element then these can be multi-valued.  If we just do
      // el.val() for each one it will change the value each time, rather than adding
      // another one.  So instead we need to assemble an array of values and do a single
      // el.val() at the end.
      ?>
      if (tagName.toLowerCase() === 'select')
      {
        if (!selects[field.name])
        {
          selects[field.name] = []
        }
        selects[field.name].push(field.value);
      }
      <?php // Otherwise we can just process them as they come ?>
      else
      {
        type = el.attr('type');
        switch (type)
        {
          case 'checkbox':
          <?php // If the name ends in '[]' it's an array and needs to be handled differently ?>
            if (field.name.match(/\[]$/))
            {
              el.filter('[value="' + field.value + '"]').attr('checked', 'checked');
            }
            else
            {
              el.attr('checked', 'checked');
            }
            break;
          case 'radio':
            el.filter('[value="' + field.value + '"]').attr('checked', 'checked');
            break;
          default:
            el.val(field.value);
            break;
        }
      }
    });

    <?php // Now assign values to the selects ?>
    for (var property in selects)
    {
      $('[name="' + property + '"]').val(selects[property]).change();
    }
    <?php // Fix up the datalists so that the correct value is displayed ?>
    form.find('datalist').each(function() {
      <?php
      // Datalists in MRBS have the structure
      //   <input type="text" list="yyy">
      //   <input type="hidden" name="xxx">
      //   <datalist id="yyy">
      // and we want to copy the value from the hidden input to the visible one
      ?>
      var prev1 = $(this).prev();
      var prev2 = prev1.prev();
      if ($(this).attr('id') === prev2.attr('list'))
      {
        prev2.val(prev1.val());
      }
      else
      {
        console.warn("MRBS: something has gone wrong - maybe the MRBS datalist structure has changed.")
      }
    });

    <?php
    // Fix up the flatpickr inputs.  Although the dates in the hidden inputs will have been set to
    // the correct values, we need to force the dates in the visible fields to be set, not just
    // to the correct value, but also in the correct format.
    ?>
    form.find('.flatpickr-input').each(function() {
        document.querySelector('#' + $(this).attr('id'))._flatpickr.setDate($(this).val(), true);
      });

  }
}


$(document).on('page_ready', function() {

  isBookAdmin = args.isBookAdmin;

  var form = $('#main'),
      areaSelect = $('#area'),
      startAndEndDates = $('#start_date, #end_date'),
      startSelect,
      endSelect,
      allDay;

  <?php
  // If there's only one enabled area in the database there won't be an area
  // select input, so we'll have to create a dummy input because the code
  // relies on it.
  ?>
  if (areaSelect.length === 0)
  {
    areaSelect = $('<input id="area" type="hidden" value="' + args.area + '">');
    $('#div_rooms').before(areaSelect);
  }

  $('#div_areas').show();

  $('#start_seconds, #end_seconds')
      .each(function() {
          $(this).data('current', $(this).val());
          $(this).data('previous', $(this).val());
        })
      .on('change', function() {
          updateSelectorData();
          reloadSlotSelector($(this), $('#area').val());
          adjustSlotSelectors();
          updateSelectorData();
        });


  areaSelect
      .data('current', areaSelect.val())
      .data('previous', areaSelect.val())
      .on('change', function() {
          var newArea = $(this).val();

          updateSelectorData();

          <?php // Switch room selects ?>
          var roomSelect = $('#rooms');
          roomSelect.html($('#rooms' + newArea).html());

          <?php // Switch start time select ?>
          reloadSlotSelector($('#start_seconds'), newArea);

          <?php // Switch all day checkbox ?>
          var allDayCheckbox = $('#all_day');
          allDayCheckbox.html($('#all_day' + newArea).html());

          <?php // Switch end time select ?>
          reloadSlotSelector($('#end_seconds'), newArea);

          adjustSlotSelectors();
        });

  $('input[name="all_day"]').on('click', function() {
      onAllDayClick();
    });

  <?php
  // If we've got back here from edit_entry_handler.php then repopulate the form
  // with the original data.
  ?>
  if (form.data('back'))
  {
    populateFromSessionStorage(form);
  }

  <?php
  // (1) Adjust the slot selectors
  // (2) Add some Ajax capabilities to the form (if we can) so that when
  //  a booking parameter is changed MRBS checks to see whether there would
  //  be any conflicts
  ?>

  adjustSlotSelectors();

  <?php
  // If this is an All Day booking then check the All Day box and disable the
  // start and end time boxes
  ?>
  startSelect = form.find('#start_seconds');
  endSelect = form.find('#end_seconds');
  allDay = form.find('#all_day');
  if (allDay.is(':visible') &&
      (allDay.is(':disabled') === false) &&
      (startSelect.val() === startSelect.find('option').first().val()) &&
      (endSelect.val() === endSelect.find('option').last().val()))
  {
    allDay.attr('checked', 'checked');
    startSelect.prop('disabled', true);
    endSelect.prop('disabled', true);
    onAllDayClick.oldStart = startSelect.val();
    onAllDayClick.oldEnd = endSelect.val();
    onAllDayClick.oldStartDatepicker = form.find('#start_date').val();
    onAllDayClick.oldEndDatepicker = form.find('#end_date').val();
  }



  <?php
  // Set up the validation messages, but only if the function exists (which it
  // won't if we're on the login page)
  ?>
  if (typeof validationMessages === 'function')
  {
    validationMessages();
  }

  <?php
  // If anything like a submit button is pressed then add a data flag to the form so
  // that the function that checks for a valid booking can see if the change was
  // triggered by a Submit button being pressed, and if so, not to send an Ajax request.
  ?>
  form.find('[type="submit"], [type="button"], [type="image"]').on('click', function() {
    var trigger = $(this).attr('name');
    $(this).closest('form').data('submit', trigger);
  });

  form.on('submit', function()
  {
    var result = true;
    if ($(this).data('submit') === 'save_button')
    {
      <?php // Only validate the form if the Save button was pressed ?>
      result = validate($(this));
      if (!result)
      {
        <?php // Clear the data flag if the validation failed ?>
        $(this).removeData('submit');
      }
    }
    <?php
    // If we're OK to submit then store the form data in session storage so that
    // we can repopulate the form if there's an error and we need to come back to
    // the form from edit_entry_handler.php.
    ?>
    if (result)
    {
      sessionStorage.setItem('form_data', JSON.stringify($(this).serializeArray()));
    }
    return result;
  });

  <?php
  // Add a change event handler to each of the form fields - except for those that
  // are disabled and anything that might be a submit button - so that when they change
  // the validity of the booking is re-checked.   (This probably causes more checking
  // than is really necessary, eg when the brief description is changed, but on the other
  // hand it (a) removes the need to know the names of the fields you want and (b) keeps
  // the data available for policy checking as complete as possible just in case somebody
  // decides to set a policy based on for example the brief description, for some reason).
  //
  // Use a click event for checkboxes as it seems that in some browsers the event fires
  // before the value is changed.
  ?>
  var formFields = form.find('input.date, [name]').not(':disabled, [type="submit"], [type="button"], [type="image"]');
  formFields.filter(':checkbox')
            .on('click', function() {
                checkConflicts();
              });
  formFields.not(':checkbox')
            .on('change', function() {
                checkConflicts();
              });

  <?php
  // and a div to hold the dialog box which gives more details.    The dialog
  // box contains a set of tabs.   And because we want the tabs to act as the
  // dialog box we add an extra tab where we're going to put the dialog close
  // button and then we hide the dialog itself
  ?>
  var tabsHTML =
'<div id="check_tabs">' +
'<ul id="details_tabs">' +
'<li><a href="#schedule_details"><?php echo get_js_vocab('schedule') ?></a></li>' +
'<li><a href="#policy_details"><?php echo get_js_vocab('policy') ?></a></li>' +
'<li id="ui-tab-dialog-close"></li>' +
'</ul>' +
'<div id="schedule_details"></div>' +
'<div id="policy_details"></div>' +
'</div>';

  $('<div>').attr('id', 'check_results')
            .css('display', 'none')
            .html(tabsHTML)
            .appendTo(form);

  $('#conflict_check, #policy_check').on('click', function manageTabs() {
      var tabId,
          tabIndex,
          checkResults = $('#check_results'),
          checkTabs = $('#check_tabs');
      <?php
      // Work out which tab should be selected
      // (Slightly long-winded using a switch, but there may be more tabs in future)
      ?>
      switch ($(this).attr('id'))
      {
        case 'policy_check':
          tabId = 'policy_details';
          break;
        case 'conflict_check':
        default:
          tabId = 'schedule_details';
          break;
      }
      tabIndex = $('#details_tabs a[href="#' + tabId + '"]').parent().index();

      <?php
      // If we've already created the dialog and tabs, then all we have
      // to do is re-open the dialog if it has previously been closed and
      // select the tab corresponding to the div that was clicked
      ?>
      if (manageTabs.alreadyExists)
      {
        if (!checkResults.dialog("isOpen"))
        {
          checkResults.dialog("open");
        }
        checkTabs.tabs('option', 'active', tabIndex);
        return;
      }
      <?php
      // We want to create a set of tabs that appear inside a dialog box,
      // with the whole structure being draggable.   Thanks to dbroox at
      // http://forum.jquery.com/topic/combining-ui-dialog-and-tabs for the solution.
      ?>
      checkTabs.tabs();
      checkTabs.tabs('option', 'active', tabIndex);
      checkResults.dialog({'width': 400,
                           'height': 200,
                           'minWidth': 300,
                           'minHeight': 150,
                           'draggable': true});
      <?php //steal the close button ?>
      var detailsTabs = $('#details_tabs');
      detailsTabs.append($('button.ui-dialog-titlebar-close'));
      <?php //move the tabs out of the content and make them draggable ?>
      $('.ui-dialog').addClass('ui-tabs')
                     .prepend(detailsTabs)
                     .draggable('option', 'handle', '#details_tabs');
      <?php //switch the titlebar class ?>
      $('.ui-dialog-titlebar').remove();
      detailsTabs.addClass('ui-dialog-titlebar');

      manageTabs.alreadyExists=true;
    });

  <?php
  // Finally, set a timer so that conflicts are periodically checked for,
  // in case someone else books that slot before you press Save.
  ?>
  conflictTimer(true);

  <?php
  // Actions to take when the start and end datepickers are closed
  ?>
  startAndEndDates.on('change', function() {

    <?php
    // (1) If the end_datepicker isn't visible and we change the start_datepicker,
    //     then set the end date to be the same as the start date.  (This will be
    //     the case if multi-day bookings are not allowed)
    ?>

    var endDate = $('#end_date');

    if ($(this).attr('id') === 'start_date')
    {
      if (endDate.css('visibility') === 'hidden')
      {
        endDate.val($(this).val());
      }
    }

    <?php
    // (2) If the start date is after the end date, then change the end date to match the
    //     start date if the start date was changed, or change the start date to match the
    //     end date if the end date was changed.
    ?>
    if (getDateDifference() < 0)
    {
      var selector = ($(this).attr('id') === 'start_date') ? '#end_date' : '#start_date';
      var fp = document.querySelector(selector)._flatpickr;
      fp.setDate($(this).val());
    }

    <?php
    // (3) Go and adjust the start and end time/period select options, because
    //     they are dependent on the start and end dates
    ?>
    adjustSlotSelectors();

    <?php
    // (4) If we're doing Ajax checking of the form then we have to check
    //     for conflicts when the datepicker is closed
    ?>
    checkConflicts();

    <?php
    // (5) Check to see whether any time slots should be removed from the time
    //     select on the grounds that they don't exist due to a transition into DST.
    ?>
    checkTimeSlots($(this));

  });

  startAndEndDates.each(function() {
      checkTimeSlots($(this));
    });

  $('input[name="rep_interval"]').on('change', changeRepIntervalUnits);

  $('input[name="rep_type"]').on('change', function() {
    changeRepTypeDetails();
    changeRepIntervalUnits();
  }).trigger('change');

  <?php
  // Add an event listener to detect a change in the visibility
  // state.  We can then suspend Ajax checking when the page is
  // hidden to save on server, client and network load.
  ?>
  var prefix = visibilityPrefix();
  if (document.addEventListener &&
      (prefix !== null))
  {
    document.addEventListener(prefix + "visibilitychange", editEntryVisChanged);
  }

  form.removeClass('js_hidden');

  <?php
  // Put the booking name field in focus (but only for new bookings,
  // ie when the field is empty:  if it's a new booking you have to
  // complete that field, but if it's an existing booking you might
  // want to edit any field)
  ?>
  var nameInput = form.find('#name');

  if (nameInput.length && !(nameInput.prop('disabled') || nameInput.val().length))
  {
    nameInput.trigger('focus');
  }


  <?php
  // If the allow_registration checkbox is changed then we need to toggle the
  // disabled property of all the controls in the registration fieldset.  However,
  // because some of them are also enabler groups themselves then if allow_registration
  // is checked we need to trigger a change event on them so that they set their own
  // disabled properties appropriately.
  ?>
  var allowRegistration = $('#allow_registration');
  allowRegistration.on('change', function() {
      var registration = $('#registration');
      var allowRegistrationChecked = $(this).is(':checked');
      registration.find('input, select').not($(this)).prop('disabled', !allowRegistrationChecked);
      if (allowRegistrationChecked)
      {
        registration.find('.enabler').trigger('change');
      }
    })
    .trigger('change');

  <?php
  // Enable the checkboxes which may have been disabled, otherwise their values
  // will not be posted.
  ?>
  form.on('submit', function() {
      $('#registration').find('input[type="checkbox"]').prop('disabled', false);
    });

});
