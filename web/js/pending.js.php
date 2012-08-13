<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry

// =================================================================================

// Extend the init() function 
?>
var oldInitPending = init;
init = function(args) {
  oldInitPending.apply(this, [args]);

  <?php
  // Turn the table into a datatable, with subtables that appear/disappear when
  // the control is clicked, with the subtables also being datatables.  Note though
  // that the main and sub-datatables are independent and we only display the main search
  // box which just applies to the main table rows.  (I suppose it would be possible to do
  // something clever with the main search box and get it to search the subtables as well)
  //
  // The datatables with subtables don't seem to work properly in IE7, so don't
  // bother with them if we're using IE7
  ?>
  if (lteIE7)
  {
    $('.js div.datatable_container').css('visibility', 'visible');
  }
  else
  {
    var maintable = $('#pending_table');
    <?php
    // Add a '-' control to the subtables and make them close on clicking it
    ?>
    maintable.find('table.sub th.control')
             .text('-')
             .live('click', function (event) {
                  var nTr = $(this).closest('.table_container').parent().prev();
                  var serial = $(this).parent().parent().parent().attr('id').replace('subtable_', '');
                  $('#subtable_' + serial + '_wrapper').slideUp( function () {
                      pendingTable.fnClose(nTr.get(0));
                      nTr.show();
                    });
                });
    <?php
    // Detach all the subtables from the DOM (detach keeps a copy) so that they
    // don't appear, but so that we've got the data when we want to "open" a row
    ?>
    var subtables = maintable.find('tr.sub_table').detach();
    <?php
    // Set up the column definitions, fixing the widths of the first and last columns
    // Get the width of the last column by finding the width of the largest content
    // (assuming all the content is wrapped in the first child)
    ?>
    var maxActionWidth = 0;
    $('th:last-child, td:last-child').each(function() {
        var actionWidth = $(this).children().eq(0).outerWidth(true);
        maxActionWidth = Math.max(maxActionWidth, actionWidth);
      });
    maxActionWidth += 16; <?php // to allow for padding in the <td> ?>
    var colDefsMain = [{"sWidth": "1.2em", "aTargets": [0] },
                       {"sWidth": maxActionWidth + "px", "aTargets": [6] },
                       {"sType": "title-numeric", "aTargets": [5]} ];
    <?php
    // Set up a click event that "opens" the table row and inserts the subtable
    ?>
    maintable.find('td.control')
             .text('+')
             .live('click', function (event) {
                  var nTr = $(this).parent();
                  var serial = nTr.attr('id').replace('row_', '');
                  var subtableId = 'subtable_' + serial;
                  var subtable = subtables.find('#' + subtableId).parent().clone();                                
                  var columns = [];          
                  <?php
                  // We want the columns in the main and sub tables to align.  So
                  // find the widths of the main table columns and use those values
                  // to set the widths of the subable columns.   [This doesn't work
                  // 100% - I'm not sure why - but I have left the code in]
                  ?>
                  maintable.find('tr').eq(0).find('th').each(function(i){
                      var def = new Object();
                      switch (i)
                      {
                        case 0: <?php // expand control ?>
                          def.bSortable = false;
                          break;
                        case 5: <?php // start-time ?>
                          def.sType = "title-numeric";
                          break;
                      }
                      def.sWidth = ($(this).innerWidth()) + "px";
                      columns.push(def);
                    });

                  nTr.hide();
                  pendingTable.fnOpen(nTr.get(0), subtable.get(0), 'table_container');

                  $('#' + subtableId).dataTable({"bAutoWidth": false,
                                                 "bPaginate": false,
                                                 "sDom": 't',
                                                 "aoColumns": columns});

                  $('#subtable_' + serial + '_wrapper').hide().slideDown();
                });
                  
    <?php // Turn the table into a datatable ?>
    var tableOptions = new Object();
    tableOptions.sScrollXInner = "100%";
    tableOptions.aoColumnDefs = colDefsMain;
    <?php
    // For some reason I don't understand, fnOpen() doesn't seem to work when
    // using FixedColumns.   We also have to turn off bStateSave.  I have raised
    // this on the dataTables forum.  In the meantime we comment out the FixedColumns.
    ?>
    tableOptions.bStateSave = false;
    <?php
    // Fix the left hand column.  This has to be done when 
    // initialisation is complete as the language files are loaded
    // asynchronously
    ?>
    tableOptions.fnInitComplete = function(){
        /*
        new FixedColumns(pendingTable, {"iLeftColumns": 1,
                                        "iLeftWidth": 30,
                                        "sLeftWidth": "fixed"});
        */
        $('.js div.datatable_container').css('visibility', 'visible');
        <?php // Rebind the handler ?>
        $(window).bind('resize', windowResizeHandler);
      };
    <?php
    // Remove the first column from the column visibility
    // list because it is the control column
    ?>
    tableOptions.oColVis = {aiExclude: [0]};
    <?php
    // and stop the first column being reordered
    ?>
    tableOptions.oColReorder = {"iFixedColumns": 1};
    var pendingTable = makeDataTable('#pending_table', tableOptions);
  }  // if (!lteie6)
};
