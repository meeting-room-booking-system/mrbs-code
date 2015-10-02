<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(0); // Cannot cache file because it depends on $HTTP_REFERER

if ($use_strict)
{
  echo "'use strict';\n";
}

// =================================================================================


// Extend the init() function 
?>

var oldInitSearch = init;
init = function(args) {
  oldInitSearch.apply(this, [args]);
  
  <?php
  // put the search string field in focus
  ?>
  var searchForm = $('#search_form');
  searchForm.find('#search_str').focus();
    
  <?php
  // Turn the list of users into a dataTable, provided that we can use
  // an Ajax source.  Otherwise they just get the old style search page
  // with "Next" and "Prev" buttons to get new pages from the server.
  // [We could of course use dataTables with server side processing, but
  // that's a lot of work.  A better option would probably be to write one's
  // own json_encode function for PHP versions that don't have it]
  if (function_exists('json_encode'))
  {
    // Add in a hidden input to the search form so that we can tell if we are using DataTables
    // (which will be if JavaScript is enabled and we're not running IE6 or below).   We
    // need to know this because when we're using an Ajax data source we don't want to send
    // the HTML version of the table data.
    ?>
    if (!lteIE6)
    {
      $('<input>').attr({
          type: 'hidden',
          name: 'datatable',
          value: '1'
        }).appendTo(searchForm);
    }
      
    var tableOptions = {};
    <?php
    // Use an Ajax source - gives much better performance for large tables
    list( ,$query_string) = explode('?', $HTTP_REFERER, 2);
    $ajax_url = "search.php?" . (empty($query_string) ? '' : "$query_string&") . "ajax=1";
    ?>
    tableOptions.ajax = "<?php echo $ajax_url ?>";
    <?php // Get the types and feed those into dataTables ?>
    tableOptions.columnDefs = getTypes($('#search_results'));
      
    makeDataTable('#search_results', tableOptions, {"leftColumns": 1});

    <?php
  }  //  if (function_exists('json_encode')) ?>
};

