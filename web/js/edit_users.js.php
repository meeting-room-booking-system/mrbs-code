<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(0); // Cannot cache file because it depends on $HTTP_REFERER

// =================================================================================

// Extend the init() function 
?>

var oldInitEditUsers = init;
init = function(args) {
  oldInitEditUsers.apply(this, [args]);

  <?php // Turn the list of users into a dataTable ?>
  
  var tableOptions = {};
  <?php
  // Use an Ajax source if we can - gives much better performance for large tables
  if (function_exists('json_encode'))
  {
    if (strpos($HTTP_REFERER, '?') !== FALSE)
    {
      list( ,$query_string) = explode('?', $HTTP_REFERER, 2);
    }
    $ajax_url = "edit_users.php?" . (empty($query_string) ? '' : "$query_string&") . "ajax=1";
    ?>
    tableOptions.sAjaxSource = "<?php echo $ajax_url ?>";
    <?php
  }
  ?>
  <?php // The Rights column has a span with title for sorting ?>
  tableOptions.aoColumnDefs = [{"sType": "title-numeric", "aTargets": [1]}]; 
  var usersTable = makeDataTable('#users_table',
                                 tableOptions,
                                 {sWidth: "relative", iWidth: 33});
};

