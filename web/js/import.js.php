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

function checkSourceType(object) {
  var isFile = (object.val() === 'file');
  $('#field_file').toggle(isFile);
  $('#field_url').toggle(!isFile);
  <?php
  // Disable the URL field if it's not being used in order to
  // stop the browser trying to validate the content.
  ?>
  $('input[name="url"').prop('disabled', isFile);
}

$(document).on('page_ready', function() {

  $('input[name="source_type"]').on('change', function() {
      checkSourceType($(this));
    }).filter(':checked').trigger('change');

});
