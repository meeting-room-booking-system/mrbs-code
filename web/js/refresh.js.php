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

// refreshPage will be defined later as a function, once we know
// the page data, which won't be until init().
?>
var refreshPage = {};

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
      var hidden = isHidden();
    
      <?php
      // If the page is hidden stop the timer, if any;  if it is now visible
      // then refresh the page, which will also start a timer;  if we
      // don't know the status then don't do anything.    We clear the interval
      // and refresh the page rather than just disabling/enabling the page
      // refresh because we want the latest data to be displayed immediately the
      // page becomes visible again.  (It might have been hidden for a while
      // with lots of changes in the meantime).
      ?>
      switch (hidden)
      {
        case true:
          if (typeof intervalId !== 'undefined')
          {
            window.clearInterval(intervalId);
          }
          break;
        case false:
          refreshPage();
          break;
        default:
          break;
      }
    };
  

  <?php
  // =================================================================================

  // Extend the init() function 
  ?>
  var oldInitRefresh = init;
  init = function(args) {
    oldInitRefresh.apply(this, [args]);

    refreshPage = function refreshPage() {
        if (!isHidden() && !refreshPage.disabled)
        {
          clearInterval(intervalId);
          var data = {ajax: 1, 
                      day: args.day,
                      month: args.month,
                      year: args.year,
                      room: args.room,
                      area: args.area};
          if (args.timetohighlight !== undefined)
          {
            data.timetohighlight = args.timetohighlight;
          }
          var table = $('table.dwm_main');
          $.post(args.page + '.php',
                 data,
                 function(result){
                     <?php
                     // (1) Empty the existing table in order to get rid of events
                     // and data and prevent memory leaks (2) insert the updated 
                     // table HTML and then (3) trigger a window load event so that 
                     // the resizable bookings are re-created
                     ?>
                     if (!isHidden() && !refreshPage.disabled)
                     {
                       table.empty();
                       table.html(result);
                       $(window).trigger('load');
                     }
                   },
                 'html');
        }  <?php // if (!isHidden() && !refreshPage.disabled) ?>
      };
    
    <?php
    // Set an interval timer to refresh the page
    ?>  
    var intervalId = setInterval(refreshPage, <?php echo $refresh_rate * 1000 ?>);
    

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
  };
  <?php
}
?>