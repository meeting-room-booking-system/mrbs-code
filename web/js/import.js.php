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

  <?php
  // Show/hide the file or URL input fields as the source type
  // field changes.
  ?>
  $('input[name="source_type"]').on('change', function() {
      checkSourceType($(this));
    }).filter(':checked').trigger('change');

  <?php
  // Show/hide the location handling fieldsets as the ignore_location
  // checkbox changes.
  ?>
  $('input[name="ignore_location"]').on('change', function() {
      var ignoreLocation = $(this).is(':checked');
      $('#location_parsing').toggle(!ignoreLocation);
      $('#ignore_location_settings').toggle(ignoreLocation);
    }).trigger('change');

});
