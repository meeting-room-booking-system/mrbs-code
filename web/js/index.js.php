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

<?php
// =================================================================================

// Extend the init() function 
?>

var oldInitIndex = init;
init = function(args) {
  
  oldInitIndex.apply(this, [args]);
  
  checkNav();
  $(window).scroll(checkNav);
  $(window).resize(checkNav);
  
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
};
