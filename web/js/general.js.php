<?php
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

global $autocomplete_length_breaks;

// Function to determine whether the browser supports the HTML5
// <datalist> element.
?>
var supportsDatalist = function supportsDatalist() {
    <?php
    // The first two conditions work for most browsers.   The third condition is
    // necessary for Safari, which, certainly for versions up to 6.0, the latest at
    // the time of writing, return true for the first two conditions even though
    // it doesn't support <datalist>.
    ?>
    return ('list' in document.createElement('input')) &&
           ('options' in document.createElement('datalist')) &&
           (window.HTMLDataListElement !== undefined);
  };


<?php
// If we are operating on a wide screen when the standard form fieldsets are
// displayed as tables, then make sure that the left hand column in the standard
// form is of constant width.  If there are multiple fieldsets then each fieldset
// will have its own width, as the display:table only applies to that fieldset.
?>
var adjustLabelWidths = function adjustLabelWidths() {
  var standardFieldset = $('.standard fieldset');
  if ((standardFieldset.length !== 0) && (standardFieldset.css('display') == 'table'))
  {
    var labels = standardFieldset.children('div').children('label').not('.rep_type_details label');
    <?php // Let the labels find their natural widths ?>
    labels.width('auto');
    <?php // Add on one pixel to avoid what look to be like rounding problems in some browsers ?>
    labels.width(getMaxWidth(labels) + 1);
  }
}


function fillUsernameFields()
{
  var select = $('.ajax_usernames');
  
  select.each(function() {
      <?php // Turn the create_by select into a fancy select box. ?>
      var el = $(this);
      el.mrbsSelect(el.hasClass('datalist'));
    });
    
  <?php
  // Add a class to the body so that we can modify the CSS when the load
  // is in progress, eg by adding an animated GIF.  We remove the class
  // once the Ajax data has arrived.
  ?>
  $('body').addClass('ajax-loading');
  
  <?php
  // Fire off an Ajax request to get the data.  We do this because some authentication
  // schemes, eg LDAP, will take a long time to return the data if there are very many
  // users and we don't want to hold up the page load.  Most of the time the data won't
  // even be needed anyway because the booking will be made in the name of the current
  // user.
  //
  // Select2 offers an Ajax option, but it is not particularly suitable because (a) the
  // Ajax request is not fired until the Select2 element is opened, which means the clock
  // doesn't start ticking until then and (b) a new request is fired whenever the search
  // term is changed.  It does though offer some nice features such as pagination and
  // query terms, but these still aren't going to help much.  And LDAP searches of the
  // form "*TERM*" can be expensive.

  // See https://select2.org/data-sources/ajax for more details
  ?>
  $.post({
      url: 'ajax/usernames.php',
      dataType: 'json',
      data: {csrf_token: getCSRFToken(), site: args.site},
      success: function(data) {
          select.each(function() {
              var el = $(this);
              var newOption;
              <?php
              // Get the current option (there will only be one) so we know
              // which one should be selected in the new list
              ?>
              var currentOption = el.find('option').first();
              var currentValue = currentOption.val();
              var currentValueUpper = currentValue.toUpperCase();
              var currentText = currentOption.text();
              <?php
              // Remove the existing option, because it will be in the new dataset in
              // the correct position.
              ?>
              el.empty();
              <?php
              // Add the new data, selecting the option that was previously selected
              ?>
              var foundCurrent = false;
              $.each(data, function(index, option) {
                  // Make it a case-insensitive comparison as usernames are case-insensitive
                  var selected = (option.username.toUpperCase() === currentValueUpper);
                  foundCurrent = foundCurrent || selected;
                  var newOption = new Option(option.display_name, option.username, selected, selected);
                  el.append(newOption);
                });
              <?php
              // It's possible that the creator of the booking is no longer a user (they may have left
              // the organisation and been deleted from the user list).  If that's the case and we haven't
              // found them while running through the user list, then add them and make them the selected
              // option.  (Ideally the list should perhaps be sorted again, but then we'd have to worry
              // about locales. And having the original creator at the end of the list perhaps draws attention
              // to the fact that they no longer exist).
              ?>
              if (!foundCurrent)
              {
                newOption = new Option(currentText, currentValue, true, true);
                el.append(newOption);
              }
              <?php
              // If there was one, close the Select2 control and refresh it.  If it was open before the
              // close, then reopen it after the refresh.
              //
              ?>
              if (el.hasClass('select2-hidden-accessible'))
              {
                var wasOpen = el.select2('isOpen');
                el.select2('close').trigger('change');
                if (wasOpen)
                {
                  el.select2('open');
                }
              }
            });
          $('body').removeClass('ajax-loading');
        }
    });
}


var args;


$(document).on('page_ready', function() {

  <?php // Retrieve the data that the JavaScript files need. ?>
  args = $('body').data();
  
  <?php // Fire off the Ajax requests for username fields ?>
  fillUsernameFields();

  <?php
  // If we're required to log the user out after a period of inactivity then the user filling in
  // an MRBS form counts as activity and we need to record it.   In fact we'll record any key or
  // mouse activity for this document as activity.
  if (($auth["session"] == "php") && !empty($auth["session_php"]["inactivity_expire_time"]))
  {
    ?>
    var recordActivity = function recordActivity() {
        var d = new Date(),
            t = d.getTime()/1000;
        <?php
        // Only tewll the server that there's been some user activity if we're coming up to
        // the inactivity timeout
        ?>
        if ((typeof recordActivity.lastRecorded === 'undefined') ||
            ((t - recordActivity.lastRecorded) > (<?php echo $auth["session_php"]["inactivity_expire_time"]?> - 1)))
        {
          recordActivity.lastRecorded = t;

          var params = {activity: 1};
          if(args.site)
          {
            params.site = args.site;
          }

          $.post('ajax/record_activity.php', params, function() {
            });
        }
      };

    $(document).on('keydown mousemove mousedown', function() {
        recordActivity();
      });
    <?php
  }

  // Add in a hidden input to the header search forms so that we can tell if we are using DataTables
  // (which will be if JavaScript is enabled).   We need to know this because when we're using an
  // an Ajax data source we don't want to send the HTML version of the table data.
  //
  // Also add 'datatable=1' to the link for the user list for the same reason
  ?>

  $('<input>').attr({
      type: 'hidden',
      name: 'datatable',
      value: '1'
    }).appendTo('form[action="search.php"]');

  $('header a[href^="edit_users.php"]').each(function() {
      var href = $(this).attr('href');
      href += (href.indexOf('?') < 0) ? '?' : '&';
      href += 'datatable=1';
      $(this).attr('href', href);
    });

  <?php
  // There are some forms that have multiple submit buttons, eg a "Back" and "Save"
  // buttons.   In these cases we want hitting the Enter key in a text input field
  // to result in a "Save" rather than "Back".    So in these cases we have assigned
  // a class of 'default_action' to the one that we want to be executed when we hit
  // Enter.   (Note that it is a class rather than an id just in case we have two or
  // more such forms on a page.   However we should ensure that there is only one
  // button with this class per form.)
  ?>
  $('form input.default_action').each(function() {
      var defaultSubmitButton = $(this);
      $(this).parents('form').find('input').on('keypress', function(event) {
          if (event.which == 13)  // the Enter key
          {
            defaultSubmitButton.trigger('click');
            return false;
          }
          else
          {
            return true;
          }
        });
    });

  if (supportsDatalist())
  {
    <?php
    // One problem with using a datalist with an input element is the way different browsers
    // handle autocomplete.  If you have autocomplete on, and also an id or name attribute, then some
    // browsers, eg Edge, will bring the history up on top of the datalist options so that you can't
    // see the first few options.  But if you have autocomplete off, then other browsers, eg Chrome,
    // will not present the datalist options at all.  We fix this in JavaScript by having a second,
    // hidden, input which holds the actual form value and mirrors the visible input.  Because we can't
    // rely on JavaScript being enabled we will create the basic HTML using autocomplete on, ie the default,
    // which is the least bad alternative.  One disadvantage of this method is that the label is no longer
    // tied to the visible input, but this isn't as important for a text input as it is, say, for a checkbox
    // or radio button.
    ?>
    $('input[list]').each(function() {
      var input = $(this),
          hiddenInput = $('<input type="hidden">');

      <?php
      // Create a hidden input with the id, name and value of the original input.  Then remove the id and
      // name from the original input (so that history doesn't work).   Finally make sure that
      // the hidden input is updated whenever the original input is changed.
      ?>
      hiddenInput.attr('id', input.attr('id'))
                 .attr('name', input.attr('name'))
                 .val(input.val());

      input.removeAttr('id')
           .removeAttr('name')
           .after(hiddenInput);

      <?php
      // We use the 'input' rather than 'change' event because 'input' isn't fired in
      // Edge when a datalist option is selected.
      ?>
      input.on('input', function() {
        hiddenInput.val($(this).val());
      });

    });

    <?php
    // Because there are some browsers, eg MSIE and Edge, that will still give you form history even
    // though the input has no id or name, then we need to clear the values from those inputs just
    // before the form is submitted.   Note that we can't do it on the submit event because by that time
    // the browser has cached the values.  So we do it when the Submit button is clicked - and this event
    // is also triggered if Enter is entered into an input field.   But
    // of course we can't clear the value if the input field needs to be
    // validated, otherwise the validation will fail.
    ?>
    $('form:has(input[list]) input[type="submit"]').on('click', function() {
      $(this).closest('form')
             .find('input:not([name])')
             .not('input[type="submit"]')
             .each(function() {
                 if (!$(this).prop('required') &&
                     (typeof($(this).attr('pattern')) == 'undefined'))
                 {
                   $(this).val('');
                 }
               });

    });

  }
  else
  {
    <?php
    // Add jQuery UI Autocomplete functionality for those browsers that do not
    // support the <datalist> element.
    ?>
    $('datalist').each(function() {
        var datalist = $(this);
        var options = [];
        datalist.parent().find('option').each(function() {
            var option = {};
            option.label = $(this).text();
            option.value = $(this).val();
            options.push(option);
          });
        var minLength = 0;
        <?php
        // Work out a suitable value for the autocomplete minLength
        // option, ie the number of characters that must be typed before
        // a list of options appears.   We want to avoid presenting a huge
        // list of options.
        if (isset($autocomplete_length_breaks) && is_array($autocomplete_length_breaks))
        {
          ?>
          var breaks = [<?php echo implode(',', $autocomplete_length_breaks) ?>];
          var nOptions = options.length;
          var i=0;
          while ((i<breaks.length) && (nOptions >= breaks[i]))
          {
            i++;
            minLength++;
          }
          <?php
        }
        ?>
        var formInput = datalist.prev();
        formInput.empty().autocomplete({
            source: options,
            minLength: minLength
          });
        <?php
        // If the minLength is 0, then the autocomplete widget doesn't do
        // quite what you might expect and you need to force it to display
        // the available options when it receives focus
        ?>
        if (minLength === 0)
        {
          formInput.on('focus', function() {
              $(this).autocomplete('search', '');
            });
        }
      });
  }

  <?php // Add a fallback for browsers that don't support the time input ?>
  $('[type="time"]').each(function() {
    if ($(this).prop('type') != 'time')
    {
      $(this).attr('placeholder', 'hh:mm')
             .attr('pattern', '<?php echo trim(REGEX_HHMM, '/') ?>')
             .on('input', function(e) {
                 e.target.setCustomValidity('');
                 if (!e.target.validity.valid)
                 {
                   e.target.setCustomValidity('<?php echo escape_js(get_vocab('invalid_time_format'))?>');
                 }
               });
    }
  });

  adjustLabelWidths();

  $(window)
    <?php // Make resizing smoother by not redoing labels on every resize event ?>
    .on('resize', throttle(function() {
        adjustLabelWidths();
      }, 100));

});

<?php // We define our own page ready event so that we can trigger it after an Ajax load ?>
$(function() {
  $(document).trigger('page_ready');
});
