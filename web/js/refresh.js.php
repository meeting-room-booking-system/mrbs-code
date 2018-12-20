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
                   if (!table.hasClass('resizing'))
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
        refreshPage();
      }
    }
  };



$(function() {
  
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
        // Add an event listener to detect a change in the visibility
        // state.  We can then suspend Ajax refreshing when the page is
        // hidden to save on server, client and network load.
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
      }
      ?>
      
    }).trigger('tableload');
    
});

