<?php
namespace MRBS;

require '../defaultincludes.inc';

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}


// Only show the bottom nav bar if no part of the top one is visible.
?>
var checkNav = function() {
    if ($('nav.main_calendar').eq(0).visible(true))
    {
      $('nav.main_calendar').eq(1).hide();
    }
    else
    {
      $('nav.main_calendar').eq(1).show();
    }
  };
  

$(document).on('page_ready', function() {
  
  <?php
  // Turn the room and area selects into fancy select boxes and then
  // show the location menu (it's hidden to avoid screen jiggling).
  // If we are using a mobile device then keep the native select elements
  // as they tend to be better.
  ?>
  if (!isMobile())
  {
    $('.room_area_select').select2();
    <?php
    // Select2 doesn't always get the width right, so increase it by a
    // few pixels to make sure we don't get a '...'
    ?>
    $('.select2-container').each(function() {
      var container = $(this);
      container.width(container.width() + 5);
    });
  }
  $('nav.location').removeClass('js_hidden');
  
  <?php
  // The bottom navigation was hidden while the Select2 boxes were formed
  // so that the correct widths could be established.  It is then shown if
  // the top navigation is not visible.
  ?>
  $('nav.main_calendar').removeClass('js_hidden');
  checkNav();
  $(window).scroll(checkNav);
  $(window).resize(checkNav);
  
  <?php
  // Only reveal the color key once the bottom navigation has been determined,
  // in order to avoid jiggling.
  ?>
  $('.color_key').removeClass('js_hidden');
  
  $('nav.arrow a, nav.view a').click(updateBody);
  
});
