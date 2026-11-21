<?php
declare(strict_types=1);
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30-minute expiry
?>

'use strict';

$(document).on('page_ready', function() {

  <?php // Turn the list of users into a dataTable ?>

  const tableOptions = {};

  <?php // Use an Ajax source - gives much better performance for large tables ?>
  const queryString = window.location.search;
  tableOptions.ajax = 'edit_users.php' + queryString;

  <?php // Get the types and feed those into dataTables ?>
  tableOptions.columnDefs = getTypes($('#users_table'));
  <?php
  // If there is an email column, add an extra button to copy email addresses as a unique, comma-separated list so
  // that they can be pasted into an address field in an email client. Useful for sending messages to all users.
  ?>
  const emailColumn = '#col_email';
  if ($(emailColumn).length) {
    tableOptions.buttons = [
      {
        <?php // The first button is assumed to be the colvis button ?>
        extend: 'colvis'
      },
      {
        text: '<?php echo get_js_vocab('copy_email_addresses') ?>',
        action: function (e, dt, node, config) {
          <?php // Don't sort the addresses because it makes comparison with the table easier ?>
          extractEmailAddresses(dt, emailColumn, false);
        }
      }
    ]
  }
  makeDataTable('#users_table', tableOptions, {leftColumns: 1});

});

