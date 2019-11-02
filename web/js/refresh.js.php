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
  timerRunning: null,

  <?php
  // Get the first non-zero slot size in the table, or else if they are all zero then return that.
  // This function is useful when trying to calculate an appropriate deay for pages that don't have
  // a timeline.  For pages that do, we can't assume that all the slots are the same size, so it's
  // better to get the size of the slot that contains the timeline.
  ?>
  getFirstNonZeroSlotSize: function() {
    var slotSize;
    <?php
    if ($times_along_top)
    {
      ?>
      $('#day_main').find('thead th').not('.first_last').each(function () {
          slotSize = $(this).outerWidth();
          if (slotSize)
          {
            return false;
          }
        });
    <?php
    }
    else
    {
      ?>
      $('#day_main').find('tbody tr').each(function () {
          slotSize = $(this).outerHeight();
          if (slotSize)
          {
            return false;
          }
        });
    <?php
    }
    ?>
    return slotSize;
  },

  show: function () {
    <?php // No point in do anything if the page is hidden ?>
    if (isHidden())
    {
      return;
    }

    <?php // Remove any existing timeline ?>
    $('.timeline').remove();

    var now = Math.floor(Date.now() / 1000);
    var table = $('#day_main');
    var theadData = table.find('thead').data();
    var slotSize, delay;
    
    <?php
    // Only look for the slot that corresponds to the current time if we know it's going to be on this page,
    // otherwise there's no point in iterating through each time slot looking for the current time. [We could
    // optimise further and avoid iterating through the time slots by calculating which slot the current time
    // corresponds to, knowing the resolution.  However this method won't work on DST transition days without
    // some extra data, because the number of slots changes on those days.]
    //
    // We iterate through the slots in reverse so that we hit the correct time on the transition into DST.  If
    // we were to iterate through the slots in the normal order we would land on the invalid hour, eg 0100-0200
    // which is really 0200-0300 when the clocks go forward.
    ?>
    if ((typeof theadData !== 'undefined') &&
        (theadData.start_first_slot <= now) &&
        (theadData.end_last_slot > now))
    {
      <?php
      // We can display the table in two ways: with times along the top ...
      if ($times_along_top)
      {
      ?>
      // Iterate through each of the table columns checking to see if the current time is in that column.
      table.find('thead th').reverse().each(function () {
        var start_timestamp = $(this).data('start_timestamp');
        var end_timestamp = $(this).data('end_timestamp');
        <?php
        // Need to calculate the slot size each time, because it won't always be the same, for example
        // if there are multiple bookings in a slot
        ?>
        slotSize = $(this).outerWidth();

        if ((start_timestamp <= now) && (end_timestamp > now)) {
          <?php
          // If we've found the column then construct a timeline and position it corresponding to the fraction
          // of the column that has expired
          ?>
          var fraction = (now - start_timestamp) / (end_timestamp - start_timestamp);
          var left = $(this).offset().left - table.parent().offset().left;
          left = left + fraction * slotSize;
          <?php // Build the new timeline and add it to the DOM after the table ?>
          var tbody = table.find('tbody');
          var container = table.parent();
          var timeline = $('<div class="timeline times_along_top"></div>')
            .height(tbody.outerHeight())
            .css({
              top: tbody.offset().top + container.scrollTop() - container.offset().top + 'px',
              left: left + container.scrollLeft() + 'px'
            });
          table.after(timeline);
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
        table.find('tbody tr').reverse().each(function () {
          var start_timestamp = $(this).data('start_timestamp');
          var end_timestamp = $(this).data('end_timestamp');
          <?php
          // Need to calculate the slot size each time, because it won't always be the same, for example
          // if there are multiple bookings in a slot
          ?>
          slotSize = $(this).outerHeight();

          if ((start_timestamp <= now) &&
            (end_timestamp > now)) {
            <?php
            // If we've found the row then construct a timeline and position it corresponding to the fraction
            // of the row that has expired
            ?>
            var fraction = (now - start_timestamp) / (end_timestamp - start_timestamp);
            var top = $(this).offset().top - table.parent().offset().top;
            var labelsWidth = 0;
            <?php
            // We don't want to overwrite the labels so work out how wide they are so that we can set
            // the correct width for the timeline.
            ?>
            $(this).find('th').each(function () {
              labelsWidth = labelsWidth + $(this).outerWidth();
            });
            top = top + fraction * slotSize;
            <?php // Build the new timeline and add it to the DOM after the table ?>
            var container = table.parent();
            var timeline = $('<div class="timeline"></div>')
              .width($(this).outerWidth() - labelsWidth)
              .css({
                top: top + container.scrollTop() + 'px',
                left: $(this).find('th').first().outerWidth() + container.scrollLeft() + 'px'
              });
            table.after(timeline);
            return false; <?php // Break out of each() loop ?>
          }
        });
        <?php
      }
    // Set a timer so that the timeline will be updated with time.  No point in setting the delay for less than
    // half the time represented by one pixel.  And make the delay a minimum of one second.
    // Only set the timer if there's not already one running (could happen if show() is called twice)
    ?>
    }
    if (Timeline.timerRunning === null)
    {
      <?php // If we haven't got a slot size, because the poge doesn't have a timeline, then get one ?>
      if (typeof slotSize === 'undefined')
      {
         slotSize = Timeline.getFirstNonZeroSlotSize();
      }
      <?php // If we've now got a slot size then calculate a delay ?>
      if (slotSize)
      {
        delay = <?php echo $resolution ?>/(2 * slotSize);
        delay = parseInt(delay * 1000, 10); <?php // Convert to milliseconds ?>
        delay = Math.max(delay, 1000);
      }
      <?php // If we still haven't got one, or else it's zero, then set a sensible default delay ?>
      else
      {
        delay = 10000; <?php // 10 seconds ?>
      }

      Timeline.timerRunning = window.setInterval(Timeline.show, delay);
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

