<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry

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
          $.post('record_activity_ajax.php', {ajax: 1, activity: 1}, function() {
            });
        }
      };
      
    $(document).bind('keydown mousemove mousedown', function() {
        recordActivity();
      });
    <?php
  }
  
  // if there's a logon box, set the username input field in focus
  ?>
  var logonForm = document.getElementById('logon');
  if (logonForm && logonForm.NewUserName)
  {
    logonForm.NewUserName.focus();
  }
  
  <?php
  // Add in a hidden input to the header search form so that we can tell if we are using DataTables
  // (which will be if JavaScript is enabled and we're not running IE6 or below).   We
  // need to know this because when we're using an Ajax data source we don't want to send
  // the HTML version of the table data.
  //
  // Also add 'datatable=1' to the link for the user list for the same reason
  ?>
  if (!lteIE6)
  {
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
  }
  
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
    
   
  <?php 
  // Add jQuery UI Autocomplete functionality for those browsers that do not
  // support the <datalist> element.  (We don't support autocomplete in IE6 and
  // below because the browser doesn't render the autocomplete box properly - it
  // gets hidden behind other elements.   Although there are fixes for this,
  // it's not worth it ...)
  ?> 
  if (supportsDatalist() || lteIE6)
  {
    <?php
    // If the browser does support the datalist we have to do a bit of tweaking
    // if we are running Opera.  We normally have the autocomplete atribute set
    // to off because in most browsers this stops the browser suggesting previous
    // input and confines the list to our options.   However in Opera turning off
    // autocomplete turns off our options as well, so we have to turn it back on.
    ?>
    if (navigator.userAgent.toLowerCase().indexOf('opera') >= 0)
    {
      $('datalist').prev().attr('autocomplete', 'on');
    }
  }
  else
  {
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
  
  $('#Form1 input[type="submit"]').css('visibility', 'visible');

  
  var floatingTables = $('table#day_main, table#week_main');

  createFloatingHeaders(floatingTables);
  
  $(window)
    <?php // Make resizing smoother by not redoing headers on every resize event ?>
    .resize(throttle(function() {
        createFloatingHeaders(floatingTables);
        updateTableHeaders(floatingTables);
      }, 100))
    .scroll(function() {
        updateTableHeaders(floatingTables);
      })
    .trigger('scroll');
    
};
