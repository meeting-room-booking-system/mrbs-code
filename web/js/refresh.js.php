<?php
namespace MRBS;

// Implements Ajax refreshing of the calendar view.   Only necessary, obviously,
// if $refresh_rate has been set to something non-zero

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}
?>

var refreshListenerAdded = false;

var intervalId;

<?php
// Make the columns in the calendar views of equal size.   We can't use an inline style,
// because this would cause an error on those servers that have a Content Security Policy of
// "default-src 'self'" or "script-src 'self'".  And we can't use a CSS file because we don't
// know how many columns there are.  So we have to use JavaScript.
?>
var sizeColumns = function() {
  
    var mainCols = $('.dwm_main thead th').not('th.first_last, th.hidden_day');
    mainCols.css('width', 100/mainCols.length + '%');

  };


var refreshPage = function refreshPage() {
    if (!isHidden() && 
        !$('table.dwm_main').hasClass('resizing') &&
        !isMeteredConnection())
    {
      var data = {ajax: 1,
                  view: args.view,
                  page_date: args.pageDate,
                  area: args.area,
                  room: args.room};
      if (args.timetohighlight !== undefined)
      {
        data.timetohighlight = args.timetohighlight;
      }

      <?php
      // Add a class of 'refreshable' to the table so that we know when the response comes
      // back whether we can use it to refresh the table.   The problem is that it is
      // possible - especially on slow connections - that in between the Ajax request being
      // made and the response being returned, the user could have moved to a different day,
      // which is just done by replacing the page body element.  In that case the refresh would
      // come back and refresh the table with the wrong day's data.  By adding the 'refreshable'
      // class to the table we ensure that this can't happen, because if the user moves to a
      // different day the new HTML won't have the class.
      ?>
      $('table.dwm_main').addClass('refreshable');

      $.post('index.php',
             data,
             function(result){
                 <?php
                 // (1) Empty the existing table in order to get rid of events
                 // and data and prevent memory leaks, (2) insert the updated 
                 // table HTML, (3) clear the existing interval timer and then
                 // (4) trigger a load event so that the resizable bookings are
                 // re-created and a new timer started.
                 ?>
                 if ((result.length > 0) && !isHidden() && !refreshPage.disabled)
                 {
                   var table = $('table.dwm_main');
                   if (!table.hasClass('resizing') && table.hasClass('refreshable'))
                   {
                     table.empty();
                     table.html(result);
                     window.clearInterval(intervalId);
                     intervalId = undefined;
                     table.trigger('tableload');
                   }
                 }
               },
             'html');
    }  <?php // if (!isHidden() etc.?>
  };
    
  

  
var refreshVisChanged = function refreshVisChanged() {
    var pageHidden = isHidden();

    if (pageHidden !== null)
    {
       <?php
      // Stop the interval timer.  If the page is now visible then refresh
      // the page, which will also start a new timer.   We clear the interval
      // and refresh the page rather than just disabling/enabling the page
      // refresh because we want the latest data to be displayed immediately the
      // page becomes visible again.  (It might have been hidden for a while
      // with lots of changes in the meantime).
      ?>
      if (typeof intervalId !== 'undefined')
      {
        window.clearInterval(intervalId);
        intervalId = undefined;
      }
      if (!pageHidden)
      {
        prefetch();  <?php // Refresh the prefetched pages ?>
        refreshPage();
      }
    }
  };


var Timeline = {
  timerRunning: false,

  show: function () {
    <?php // No point in do anything if the page is hidden ?>
    if (isHidden())
    {
      return;
    }

    var now = Math.floor(Date.now() / 1000);
    var slotSize, delay;
    <?php // Remove any existing timeline ?>
    $('.timeline').remove();

    <?php
    // We can display the table in two ways: with times along the top ...
    if ($times_along_top)
    {
      ?>
      // Iterate through each of the table columns checking to see if the current time is in that column
      $('#day_main').find('thead th').each(function () {
        var start_timestamp = $(this).data('start_timestamp');
        var end_timestamp = $(this).data('end_timestamp');
        if ((start_timestamp <= now) &&
          (end_timestamp > now))
        {
          <?php
          // If we've found the column then construct a timeline and position it corresponding to the fraction
          // of the column that has expired
          ?>
          var fraction = (now - start_timestamp) / (end_timestamp - start_timestamp);
          var left = $(this).offset().left - $('.dwm_main').parent().offset().left;
          slotSize = $(this).outerWidth();
          left = left + fraction * $(this).outerWidth();
          <?php // Build the new timeline and add it to the DOM after the table ?>
          var tbody = $('.dwm_main tbody');
          var timeline = $('<div class="timeline times_along_top"></div>')
            .height(tbody.outerHeight())
            .css({top: tbody.offset().top - $('.dwm_main').parent().offset().top + 'px', left: left + 'px'});
          $('table.dwm_main').after(timeline);
          return false; <?php // Break out of each() loop ?>
        }
      });
      <?php
    }
    // ... or the standard view, with times down the side
    else
    {
      ?>
      // Iterate through each of the table rows checking to see if the current time is in that row
      $('#day_main').find('tbody tr').each(function () {
        var start_timestamp = $(this).data('start_timestamp');
        var end_timestamp = $(this).data('end_timestamp');
        console.log(start_timestamp);
        if ((start_timestamp <= now) &&
          (end_timestamp > now))
        {
          <?php
          // If we've found the row then construct a timeline and position it corresponding to the fraction
          // of the row that has expired
          ?>
          var fraction = (now - start_timestamp) / (end_timestamp - start_timestamp);
          var top = $(this).offset().top - $('.dwm_main').parent().offset().top;
          var labelsWidth = 0;
          <?php
          // We don't want to overwrite the labels so work out how wide they are so that we can set
          // the correct width for the timeline.
          ?>
          $(this).find('th').each(function () {
            labelsWidth = labelsWidth + $(this).outerWidth();
          });
          slotSize = $(this).outerHeight();
          top = top + fraction * $(this).outerHeight();
          <?php // Build the new timeline and add it to the DOM after the table ?>
          var timeline = $('<div class="timeline"></div>')
            .width($(this).outerWidth() - labelsWidth)
            .css({top: top + 'px', left: $(this).find('th').first().outerWidth() + 'px'});
          $('table.dwm_main').after(timeline);
          return false; <?php // Break out of each() loop ?>
        }
      });
      <?php
    }
    // Set a timer so that the timeline will be updated with time.  No point in setting the delay for less than
    // half the time represented by one pixel.  And make the delay a minimum of one second.
    // Only set the timer if there's not already one running (could happen if show() is called twice)
    ?>
    if (!Timeline.timerRunning)
    {
      delay = <?php echo $resolution ?>/(2 * slotSize);
      delay = parseInt(delay * 1000, 10); <?php // Convert to milliseconds ?>
      delay = Math.max(delay, 1000);
      Timeline.timerRunning = true;
      window.setInterval(Timeline.show, delay);
    }
  }
};


$(document).on('page_ready', function() {
  
  <?php
  // Set up the timer on the table load rather than the window load event because
  // we will only want to reinitialise the table when it is refreshed rather than the
  // whole window.   For example if we've got the datepicker open we don't want that
  // to be reset.
  ?>
  $('table.dwm_main').on('tableload', function() {
    
      sizeColumns();
      
      <?php
      if (!empty($refresh_rate))
      {
        // Set an interval timer to refresh the page, unless there's already one in place
        ?>
        if (typeof intervalId === 'undefined')
        {
          intervalId = setInterval(refreshPage, <?php echo $refresh_rate * 1000 ?>);
        }
        <?php
      }

      // Add an event listener to detect a change in the visibility
      // state.  We can then suspend Ajax refreshing when the page is
      // hidden to save on server, client and network load.
      
      // We also need to resume refreshing and refresh the pre-fetched 
      // pages when the page becomes visible again.
      ?>
      var prefix = visibilityPrefix();
      if (document.addEventListener &&
          (prefix !== null) && 
          !refreshListenerAdded)
      {
        document.addEventListener(prefix + "visibilitychange", refreshVisChanged);
        refreshListenerAdded = true;
      }

      <?php
      if ($show_timeline && !$enable_periods)
      {
        // If the page isn't hidden, then add a timeline showing the current time
        ?>
        Timeline.show();
        <?php
      }
      ?>

    }).trigger('tableload');
    
});

