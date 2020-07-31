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

  <?php // Turn the list of users into a dataTable ?>

  var tableOptions = {};

  <?php // Use an Ajax source - gives much better performance for large tables ?>
  var queryString = window.location.search;
  tableOptions.ajax = 'edit_users.php' + queryString;

  <?php // Get the types and feed those into dataTables ?>
  tableOptions.columnDefs = getTypes($('#users_table'));
  tableOptions.buttons = [
    {
      <?php // The first button is assumed to be the colvis button ?>
      extend: 'colvis'
    },
    {
      <?php
      // Add in an extra button to copy email addresses as a comma separated list so
      // that they can be pasted into an address field in an email client.
      ?>
      extend: 'copy',
      text: '<?php echo escape_js(get_vocab('copy_email_addresses')) ?>',
      header: false,
      title: null,
      customize: function(data) {
          return data.replace(/\n/g, ', ').replace(/\r/g, '');
        },
      exportOptions: {
          columns: '#col_email'
        }
    }
  ]
  makeDataTable('#users_table', tableOptions, {leftColumns: 1});

});

