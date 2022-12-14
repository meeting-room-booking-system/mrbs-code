<?php
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

?>


$(document).on('page_ready', function() {

  <?php // If it's the exit page then disable everything except the exit form ?>
  if ($('#kiosk_exit').length) {
    $(window).on('click keypress', function (e) {
      if ($(e.target).parents('#kiosk_exit').length === 0)
      {
        e.preventDefault();
        return false;
      }
    });
  }

  <?php // Otherwise, toggle the area and room selects depending on the mode ?>
  else {
    $('[name="mode"]').on('change', function () {
      var isRoom = ($('input[name="mode"]:checked').val() === 'room');
      var form = $('#kiosk_enter');
      form.find('[name="area"]').parent().toggle(!isRoom);
      form.find('[name="room"]').parent().toggle(isRoom);
    }).trigger('change');
  }

});

