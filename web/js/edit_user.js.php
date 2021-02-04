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
  tableOptions.ajax = 'edit_user.php' + queryString;

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

  $('[name="roles[]"], [name="level"]').on('change', function() {
      var data = {};
      var roles = [];
      var table = $('#effective_permissions').find('table');
      $('[name="roles[]"').each(function() {
          if ($(this).is(':checked'))
          {
            roles.push(parseInt($(this).val(), 10));
          }
        });
      data.csrf_token = getCSRFToken();
      data.id = table.data('id');
      data.level = $('[name="level"]').val();
      data.roles = roles;
      table.addClass('fetching');
      $.post('ajax/effective_permissions.php', data, function(result) {
        table.replaceWith(result);
      });
    });

  <?php
  // TODO: this is only necessary because a fieldset doesn't work properly with
  // TODO: display-table.  We really need to redo the forms so that either we
  // TODO: don't use fieldsets or else we have a container div within the fieldset.
  // TODO: In the meantime we fix it with JavaScript
  ?>
  $('#fieldset_roles').each(function () {
      var lastColumnLeft = $(this).find('div').first().find('span').last().offset().left;
      $(this).find('input:last-child').offset({left: lastColumnLeft});
    })

});

