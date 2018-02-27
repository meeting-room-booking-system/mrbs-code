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

// This will be a function, defined later ?>
var arrowClick;  

<?php
// Function for handling the topping up of the mini-calendars via Ajax.  When the current
// calendar is nearing the end of the list in the DOM, an Ajax request for a new batch of
// calendars is sent to the server.  The settings below need to be chosen so that enough
// new calendars are fetched to keep up with the fastest clicking rate of the user.  Of course,
// this will depend on the network speed and latency, and in theory one could be more
// sophisticated and measure the Ajax round-trip time and adjust the settings dynamically,
// but it's probably not worth it.
?>
function Mincals() {
  this.maxSize = 100;   <?php // Maximum number of mini-calendars to hold in the DOM ?>
  this.batchSize = 10;  <?php // Number of new mini-calendars to get in each Ajax call ?>
  this.trigger = 10;    <?php // Get more mini-calendars if we are this close to the end ?>
  this.data = [];
}

<?php
// Build a sorted array of mini-calendars available in the DOM, so that we can top it
// up by Ajax when we're getting close to the end
?>
Mincals.prototype.getAll = function() {
  var mincals = this;
  $('.minicalendar').each(function() {
    mincals.data.push($(this).data('month'));
  });
  mincals.data.sort();
};

<?php // Add a month to our list of available mini-calendars ?>
Mincals.prototype.add = function(month) {
  this.data.push(month);
  this.data.sort();
};

<?php
// Add a month to our list of available mini-calendars and, if
// the list is now bigger than its maximum size, recommend a month
// for removal, which will be the one at the other end from the
// inserted month.
?>
Mincals.prototype.addAndPrune = function(month) {
  var remove = null;
  this.add(month);
  if (this.data.length > this.maxSize)
  {
    if (this.data.indexOf(month) < this.data.length/2)
    {
      remove = this.data.pop();
    }
    else
    {
      remove = this.data.shift();
    }
  }
  return remove;
};

<?php // Test whether 'month' is in the list ?>
Mincals.prototype.has = function(month) {
  return (this.data.indexOf(month) >= 0)
};

<?php
// Check to see whether we're nearly running out of mini-calendars and if so top-up
// the stock by getting some more from the server by Ajax and adding them after 'element'.
?>
Mincals.prototype.checkAndTopup = function(args, mincal, element) {
  <?php // Check to see if we need to top up the of stock mini-calendars ?>
  var mincals = this,
      index = this.data.indexOf(mincal),
      n = this.data.length,
      reference = null,
      relative;
  if (index < this.trigger)
  {
    <?php // Get the one before the first ?>
    reference = this.data[0];
    relative = -1;
  }
  else if ((n-index) <= this.trigger)
  {
    <?php // Get the one after the last ?>
    reference = this.data[n - 1];
    relative = 1;
  }
  if (reference)
  {
    <?php // We need more mini-calendars ?>
    var data = {csrf_token: getCSRFToken(),
                reference: reference,
                relative: relative,
                length: 5,
                page: args.page + '.php',
                view: args.view,
                page_date: args.page_date,
                area: args.area,
                room: args.room};
                
    $.post('ajax/minicalendar.php',
           data,
           function(data) {
             <?php
             // Add the new mini-calendars to the DOM and also to our
             // list of mini-calendars.  But first of all check that we
             // haven't already got them from another Ajax request that might
             // have been fired while we were waiting for this one.
             // When we add a new one we also check whether we need to remove
             // one from the other end to stop the DOM growing too large.
             ?>
             $(data).filter('.minicalendar').each(function() {
               var thisCalendar = $(this),
                   month = thisCalendar.data('month'),
                   remove;
               if (!mincals.has(month))
               {
                 thisCalendar.find('a.arrow').click(arrowClick);
                 element.after(thisCalendar);
                 remove = mincals.addAndPrune(month);
                 if (remove)
                 {
                   thisCalendar.parent().find('[data-month="' + remove + '"]').remove();
                 }
               }
             });

           },
           'html');
  }
};


<?php
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
// Set up a cloned <thead> for use with floating headers
?>
var createFloatingHeaders = function createFloatingHeaders(tables) {
    tables.each(function() {
      var originalHeader = $('thead', this),
          existingClone = $('.floatingHeader', this).first(),
          clonedHeader;
      <?php
      // We need to know if there's already a clone, because we only need to create one
      // if there isn't one already (otherwise we'll end up with millions of them).  If
      // there already is a clone, all we need to do is adjust its width.
      ?>
      if (existingClone.length)
      {
        clonedHeader = existingClone;
      }
      else
      {
        clonedHeader = originalHeader.clone();
        clonedHeader.addClass('floatingHeader');
      }
      <?php
      // Now we need to set the width of the cloned header to equal the width of the original 
      // header.   But we also need to set the widths of the header cells, because when they are
      // not connected to the table body the constraints on the width are different and the columns
      // may not line up.
      //
      // When calculating the width of the original cells we use getBoundingClientRect().width to
      // avoid problems with IE which would otherwise round the widths.   But since
      // getBoundingClientRect().width gives us the width including padding and borders (but not
      // margins) we need to set the box-sizing model accordingly when setting the width.
      //
      // Note that these calculations assume border-collapse: separate.   If we were using 
      // collapsed borders then we'd have to watch out for the fact that the borders are shared
      // and then subtract half the border width (possibly on the inner cells only?).
      ?>
      clonedHeader
          .css('width', originalHeader.width())
          .find('th')
              .css('box-sizing', 'border-box')
              .css('width', function (i) {
                  return originalHeader.find('th').get(i).getBoundingClientRect().width;
                });
      if (!existingClone.length)
      {
        clonedHeader.insertAfter(originalHeader);
      }
    });
  };
  

<?php
// Make the floating header visible or hidden depending on the vertical scroll
// position.  We also need to take account of horizontal scroll
?>
var updateTableHeaders = function updateTableHeaders(tables) {
    tables.each(function() {

        var el             = $(this),
            offset         = el.offset(),
            scrollTop      = $(window).scrollTop(),
            floatingHeader = $(".floatingHeader", this);
            
        if ((scrollTop > offset.top) && (scrollTop < offset.top + el.height()))
        {
          floatingHeader.show();
        } 
        else
        {
          floatingHeader.hide();
        }
        <?php 
        // Also need to adjust the horizontal position as the element
        // has a fixed position
        ?>
        floatingHeader.css('left', offset.left - $(window).scrollLeft());
    });
  };
  
<?php
// =================================================================================

// Extend the init() function 
?>

var oldInitGeneral = init;
init = function(args) {
  oldInitGeneral.apply(this, [args]);

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
          $.post('ajax/record_activity_ajax.php', {ajax: 1, activity: 1}, function() {
            });
        }
      };
      
    $(document).on('keydown mousemove mousedown', function() {
        recordActivity();
      });
    <?php
  }

  // Add in a hidden input to the header search form so that we can tell if we are using DataTables
  // (which will be if JavaScript is enabled).   We need to know this because when we're using an
  // an Ajax data source we don't want to send the HTML version of the table data.
  // 
  // Also add 'datatable=1' to the link for the user list for the same reason
  ?>

  $('<input>').attr({
      type: 'hidden',
      name: 'datatable',
      value: '1'
    }).appendTo('#header_search');
    
  $('#user_list_link').each(function() {
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
      $(this).parents('form').find('input').keypress(function(event) {
          if (event.which == 13)  // the Enter key
          {
            defaultSubmitButton.click();
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
           
      input.change(function() {
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
    $('form:has(input[list]) input[type="submit"]').click(function() {
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
          formInput.focus(function() {
              $(this).autocomplete('search', '');
            });
        }
      });
  }
  
  <?php 
  // Make sure that the left hand column in the standard form is of
  // constant width.  If there are multiple fieldsets then each fieldset
  // will have its own width, as the display:table only applies to that
  // fieldset.
  ?>
  var labels = $('.standard fieldset > div > label').not('.rep_type_details label');
   
  function getMaxWidth (selection) {
    return Math.max.apply(null, selection.map(function() {
      return $(this).width();
    }).get());
  }
  
  <?php
  // Add on one pixel to avoid what look to be like rounding
  // problems in some browsers
  ?>
  labels.width(getMaxWidth(labels) + 1);
  
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


  var floatingTables = $('table#day_main, table#week_main');

  createFloatingHeaders(floatingTables);
  
  $(window)
    <?php // Make resizing smoother by not redoing headers on every resize event ?>
    .resize(throttle(function() {
        createFloatingHeaders(floatingTables);
        updateTableHeaders(floatingTables);
        labels.width('auto');
        labels.width(getMaxWidth(labels));
      }, 100))
    .scroll(function() {
        updateTableHeaders(floatingTables);
      })
    .trigger('scroll');
  
  <?php // Set up Ajax loading of new mini-calendars ?>
  var mincals = new Mincals();
  mincals.getAll();
  
  <?php
  // When the Prev and Next arrows on the mini-calendars are clicked, show the prev/next
  // calendar if it's there in the DOM and hide the current one.  If it's not there then
  // we just have to follow the link to the server, which will be slower.  (TO DO - get
  // more calendars via Ajax as necessary).
  ?>
  arrowClick = function(event) {
    var href = $(this).attr('href'),
        mincal = getParameterByName('mincal', href),
        nextcal = $('.minicalendar[data-month="' + mincal + '"]'),
        refMincal = null,
        neededPrev = false;
    if (nextcal.length)
    {
      event.preventDefault();
      $(this).closest('.minicalendar').hide();
      nextcal.show();
      mincals.checkAndTopup(args, mincal, nextcal);
    }
  };

  $('.minicalendar a.arrow').click(arrowClick);
};
