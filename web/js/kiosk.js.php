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

  <?php // Toggle the area and room selects depending on the mode ?>
  $('[name="mode"]').on('change', function() {
    var isRoom = ($('input[name="mode"]:checked').val() === 'room');
    var form = $('#kiosk');
    form.find('[name="area"]').parent().toggle(!isRoom);
    form.find('[name="room"]').parent().toggle(isRoom);
  }).trigger('change');

});

