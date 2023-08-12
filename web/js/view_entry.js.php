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

  var tableOptions = {};

  <?php // If we're an admin then add a "Copy email addresses" button ?>
  if (args.isAdmin) {
    tableOptions.buttons = [
      {
        <?php // The first button is assumed to be the colvis button ?>
        extend: 'colvis'
      },
      {
        <?php
        // Add in an extra button to copy email addresses as a unique, sorted, comma separated
        // list so that they can be pasted into an address field in an email client.
        // Useful for sending messages to those booked on a certain day or in a certain room.
        ?>
        text: '<?php echo escape_js(get_vocab('copy_email_addresses')) ?>',
        action: function (e, dt, node, config) {
          <?php // Don't sort the addresses because it makes comparison with the table easier ?>
          extractEmailAddresses(dt, '.name', false);
        }
      }
    ];
  }

  makeDataTable('#registrants', tableOptions);
});

