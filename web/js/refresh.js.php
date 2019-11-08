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
      var data = {refresh: 1,
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
  // This function is useful when trying to calculate an appropriate delay for pages that don't have
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

  <?php
  // Searches for time within the slots array and returns the result as an array consisting of the
  // index of the time slot and, if there is one (ie if it's the week view), the index of the day.
  // If time isn't within any of the slots then returns an empty array.
  ?>
  search: function(slots, time) {
    <?php
    // Tests whether the time is definitely with the interval defined by the beginning of the first slot
    // and the end of the last slot.
    ?>
    function within(slots, time)
    {
      <?php // Recursively gets the first element of a multi-dimensional array, eg arr[0][0][0]... ?>
      function getFirst(arr)
      {
        if (Array.isArray(arr))
        {
          return getFirst(arr[0]);
        }
        return arr;
      }

      <?php // Recursively gets the last element of a multi-dimensional array ?>
      function getLast(arr)
      {
        if (Array.isArray(arr))
        {
          return getFirst(arr[arr.length - 1]);
        }
        return arr;
      }

      return ((getFirst(slots) <= time) && (getLast(slots) > time));
    }

    <?php
    // Finds the index of the element that contains time and pushes it on to the result.
    // We iterate through the slots in reverse so that we hit the correct time on the transition into DST.  If
    // we were to iterate through the slots in the normal order we would land on the invalid hour, eg 0100-0200
    // which is really 0200-0300 when the clocks go forward.
    ?>
    function getIndex(slots, time) {
      var element;
      for (var i=slots.length - 1; i>=0; i--) {
        element = slots[i];
        if (within(element, time))
        {
          if (Array.isArray(element[0]))
          {
            getIndex(element, time);
          }
          result.push(i);
          break;
        }
      };
    }

    var result = [];
    console.dir(slots);
    console.log(time);
    <?php // Only look for an index if we know that the time is definitely within the slots somewhee ?>
    if ((typeof slots !== 'undefined') && within(slots, time))
    {
      console.log("Initial within");
      getIndex(slots, time);
    }

    return result;
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
    var table = $('#day_main, #week_main');
    var container = table.parent();
    var slots = table.find('thead').data('slots');
    var nowSlotIndices, slot, fraction, row, element;
    var view, slotSize, delay;
    var top, left, borderLeftWidth, width, height;
    var headers, headersFirstLast, headersNormal, headerFirstSize, headerLastSize

    nowSlotIndices = Timeline.search(slots, now);
    console.dir(nowSlotIndices);

    if (nowSlotIndices.length > 0)
    {
      slot = slots;
      for (var i=nowSlotIndices.length - 1; i>=0; i--)
      {
        slot = slot[nowSlotIndices[i]];
      }

      fraction = (now-slot[0]) / (slot[1]-slot[0]);

      switch(table.attr('id'))
      {
        case 'day_main':
          view = 'day';
          break;
        case 'week_main':
          view = 'week';
          break;
        default:
          view = null;
          break;
      }
      console.log(nowSlotIndices);
      <?php
      // We can display the table in two ways: with times along the top ...
      if ($times_along_top)
      {
        ?>
        <?php // Get the row that contains the current time ?>
        row = table.find('tbody tr').eq(nowSlotIndices[1]);
        <?php
        // We also need the <th> header cells in <thead> because they are useful for working out the
        // dimensions of slots in the table.  We can't rely on the <td> cells in the <tbody> because
        // they may rowspans attached to the them.
        ?>
        headers = table.find('thead tr').first().find('th');
        element = headers.not('.first_last').eq(nowSlotIndices[0]);
        slotSize = element.innerWidth();
        left = element.offset().left - table.parent().offset().left;
        left = left + fraction * slotSize;
        switch (view)
        {
          case 'day':
            var tbody = table.find('tbody');
            top = tbody.offset().top - container.offset().top;
            height = tbody.height();
            break;
          case 'week':
            top = row.offset().top - table.parent().offset().top;
            height = row.innerHeight();
            break;
          default:
            break;
        }
        <?php // Build the new timeline and add it to the DOM after the table ?>


        var timeline = $('<div class="timeline times_along_top"></div>')
          .height(height)
          .css({
            top: top + container.scrollTop() + 'px',
            left: left + container.scrollLeft() + 'px'
          });
        table.after(timeline);
        // Iterate through each of the table columns checking to see if the current time is in that column.
        table.find('thead th').reverse().each(function () {
          return false;
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

            return false; <?php // Break out of each() loop ?>
          }
        });
        <?php
      }

      // ... or the standard view, with times down the side
      else
      {
        ?>
        <?php // Get the row that contains the current time ?>
        row = table.find('tbody tr').eq(nowSlotIndices[0]);
        <?php
        // We also need the <th> header cells in <thead> because they are useful for working out the
        // dimensions of slots in the table.  We can't rely on the <td> cells in the <tbody> because
        // they may rowspans attached to the them.
        ?>
        headers = table.find('thead tr').first().find('th');
        <?php
        // Get the left edge and width of the timeline.  This is done differently depending on
        // whether it's a day or week view.
        ?>
        switch (view)
        {
          case 'day':
            <?php
            // In the day view the width is the width of the row that contains the timeline, less the width
            // of the first cell (the label) and, if the labels are repeated on the right hand side, the
            // width of the last cell.
            // The left edge is the left edge of the row, except that we have to add on the width of the label
            // cell (because we don't want the timeline going across the label) and also add on the width of the
            // border, so that the timeline aligns with left edge of booked slots.
            ?>
            headersFirstLast = headers.filter('.first_last');
            headersNormal = headers.not('.first_last');
            borderLeftWidth = parseInt(headersNormal.first().css('border-left-width'), 10);
            headerFirstSize = headersFirstLast.first().outerWidth();
            headerLastSize = (headersFirstLast.length > 1) ? headersFirstLast.last().outerWidth() : 0;
            width = row.innerWidth() - (headerFirstSize + headerLastSize);
            left = row.offset().left - table.parent().offset().left + borderLeftWidth + headerFirstSize;
            break;
          case 'week':
            <?php
            // In the week view the width is the same as the width of the header cell in the same column.
            // The left edge is the left edge of the corresponding header cell, and then we adjust it to
            // take into account the border.
            ?>
            element = headers.not('.first_last').eq(nowSlotIndices[1]);
            borderLeftWidth = parseInt(element.css('border-left-width'), 10);
            width = element.innerWidth();
            left = element.offset().left - table.parent().offset().left + borderLeftWidth;
            break;
          default:
            console.log('Unsupported view ' + view);
            break;
        }
        <?php
        // Work out where the top of the timeline should be.  This is the top of the row that contains
        // the current time, plus the fraction of the height of that row that has passed.
        ?>
        slotSize = row.outerHeight();
        top = row.offset().top - table.parent().offset().top;
        top = top + fraction * slotSize;
        <?php // We need to know the containing element so that we can adjust for scrolling ?>
        <?php // Create the timeline and add it to the DOM ?>
        var timeline = $('<div class="timeline"></div>')
          .width(width)
          .css({top: top + container.scrollTop() + 'px',
                left: left + container.scrollLeft() + 'px'
            });
        table.after(timeline);
        <?php
      }  // end else (standard view)

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

