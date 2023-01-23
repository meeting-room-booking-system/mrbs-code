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

  <?php
  // If it's the exit page then (a) disable everything except the exit form and
  // (b) set a timeout on the page.
  ?>
  if ($('#kiosk_exit').length) {

    var idleTimer;

    function resetTimer() {
      clearTimeout(idleTimer);
      idleTimer = setTimeout(whenUserIdle, <?php echo $kiosk_exit_page_timeout ?>*1000);
    }

    function whenUserIdle(){
      window.location.replace('index.php');
    }

    $(document.body).on('click keydown mousemove', resetTimer)
                    .on('click keydown', function (e) {
      if ($(e.target).parents('#kiosk_exit').length === 0)
      {
        e.preventDefault();
        return false;
      }
    });

    resetTimer(); // Start the timer
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

