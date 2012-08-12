<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry



// =================================================================================

// Extend the init() function 
?>

var oldInitRefresh = init;
init = function(args) {
  oldInitRefresh.apply(this, [args]);

  <?php
  if (!empty($refresh_rate))
  {
    ?>
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
    <?php // setTimeout not setInterval because the 'load' trigger restarts us ?>
    window.setTimeout(function() {
        $.post(args.page + '.php',
               data,
               function(result){
                   <?php
                   // (1) Empty the existing table in order to get rid of events
                   // and data and prevent memory leaks (2) insert the updated 
                   // table HTML and then (3) trigger a window load event so that 
                   // the resizable bookings are re-created
                   ?>
                   table.empty()
                   table.html(result);
                   $(window).trigger('load');
                 },
               'html');
      }, <?php echo $refresh_rate * 1000 ?>);
    <?php
  } // if (!empty($refresh_rate))
  ?>
}
