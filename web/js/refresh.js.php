<?php

// $Id$

// Implements Ajax refreshing of the calendar view.   Only necessary, obviously,
// if $refresh_rate has been set to something non-zero

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry
  
if (!empty($refresh_rate))
{
  if ($use_strict)
  {
    echo "'use strict';\n";
  }

  // refreshPage will be defined later as a function, once we know
  // the page data, which won't be until init()
  ?>
  var refreshPage;

  <?php
  // Set a timeout to refresh the page, but only if one isn't
  // outstanding
  ?>
  var refreshTimer = function refreshTimer() {
      <?php
      if (!empty($refresh_rate))
      {
        // setTimeout not setInterval because the 'load' trigger restarts us ?>
        if (typeof refreshTimer.id === 'undefined')
        {
          refreshTimer.id = window.setTimeout(function() {
              refreshTimer.id = undefined;
              refreshPage();
            }, <?php echo $refresh_rate * 1000 ?>);
        }
        <?php
      }
      ?>
    };

  var refreshVisChanged = function refreshVisChanged() {
      var hidden = isHidden();
    
      <?php
      // If the page is hidden stop the timer;  if it is now visible
      // then refresh the page, which will also start a timer;  if we
      // don't know the status then don't do anything.
      ?>
      switch (hidden)
      {
        case true:
          window.clearTimeout(refreshTimer.id);
          refreshTimer.id = undefined;
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
                   table.empty();
                   table.html(result);
                   $(window).trigger('load');
                 },
               'html');
      };
    
    if (!isHidden())
    {
      <?php
      // Set a timer if the page is visible or if we don't know the status
      ?>
      refreshTimer();
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
  };
  <?php
}
?>