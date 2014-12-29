<?php

// $Id$

// Implements Ajax refreshing of the calendar view.   Only necessary, obviously,
// if $refresh_rate has been set to something non-zero

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}
?>

var intervalId;

var refreshPage = function refreshPage() {
    if (!isHidden() && !refreshPage.disabled)
    {
      var data = {ajax: 1, 
                  day: refreshPage.args.day,
                  month: refreshPage.args.month,
                  year: refreshPage.args.year,
                  room: refreshPage.args.room,
                  area: refreshPage.args.area};
      if (refreshPage.args.timetohighlight !== undefined)
      {
        data.timetohighlight = refreshPage.args.timetohighlight;
      }
      
      $.post(refreshPage.args.page + '.php',
             data,
             function(result){
                 var table;
                 <?php
                 // (1) Empty the existing table in order to get rid of events
                 // and data and prevent memory leaks, (2) insert the updated 
                 // table HTML, (3) clear the existing interval timer and then
                 // (4) trigger a load event so that the resizable bookings are
                 // re-created and a new timer started.
                 ?>
                 if ((result.length > 0) && !isHidden() && !refreshPage.disabled)
                 {
                   table = $('table.dwm_main');
                   table.empty();
                   table.html(result);
                   createFloatingHeaders(table);
                   updateTableHeaders(table);
                   window.clearInterval(intervalId);
                   intervalId = undefined;
                   table.trigger('load');
                 }
               },
             'html');
    }  <?php // if (!isHidden() && !refreshPage.disabled) ?>
  };

<?php
// Functions to turn off and on page refresh.  We don't want the page to be
// refreshed while we are in the middle of resizing a booking or selecting a
// set of empty cells.
?>
var turnOffPageRefresh = function turnOffPageRefresh() {
    refreshPage.disabled = true;
  };
  
  
var turnOnPageRefresh = function turnOnPageRefresh() {
    refreshPage.disabled = false;
  };
    
  
<?php
if (!empty($refresh_rate))
{
  ?>
  
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
          refreshPage();
        }
      }
    };
  

  <?php
  // =================================================================================

  // Extend the init() function 
  ?>
  var oldInitRefresh = init;
  init = function(args) {
    oldInitRefresh.apply(this, [args]);
    
    refreshPage.args = args;
    
    <?php
    // Set up the timer on the table load rather than the window load event because
    // we will only want to reinitialise the table when it is refreshed rather than the
    // whole window.   For example if we've got the datepicker open we don't want that
    // to be reset.
    ?>
    $('table.dwm_main').load(function() {
        <?php
        // Set an interval timer to refresh the page, unless there's already one in place
        ?>
        if (typeof intervalId === 'undefined')
        {
          intervalId = setInterval(refreshPage, <?php echo $refresh_rate * 1000 ?>);
        }
    
        <?php
        // Add an event listener to detect a change in the visibility
        // state.  We can then suspend Ajax refreshing when the page is
        // hidden to save on server, client and network load.
        ?>
        var prefix = visibilityPrefix();
        if (document.addEventListener &&
            (prefix !== null) && 
            !init.refreshListenerAdded)
        {
          document.addEventListener(prefix + "visibilitychange", refreshVisChanged);
          init.refreshListenerAdded = true;
        }
      }).trigger('load');
  };
  <?php
}
