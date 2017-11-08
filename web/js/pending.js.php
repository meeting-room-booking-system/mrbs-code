<?php
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

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
  ?>
  var maintable = $('#pending_table'),
      subtables,
      startTimeCol = maintable.find('thead tr:first th.header_start_time').index(),
      tableOptions,
      pendingDataTable,
      i,
      colVisIncludeCols;
  
  <?php
  // Add a '-' control to the subtables and make them close on clicking it
  ?>
  maintable.find('table.sub th.control')
           .text('-');
 
  
  $(document).on('click', 'table.sub th.control', function () {
      var nTr = $(this).closest('.table_container').parent().prev(),
          serial = $(this).parent().parent().parent().attr('id').replace('subtable_', '');
          
      $('#subtable_' + serial + '_wrapper').slideUp( function () {
          pendingDataTable.row(nTr).child.hide();
          nTr.show();
        });
    });
    
  <?php
  // Detach all the subtables from the DOM (detach keeps a copy) so that they
  // don't appear, but so that we've got the data when we want to "open" a row
  ?>
  subtables = maintable.find('tr.sub_table').detach();
  
  <?php
  // Set up a click event that "opens" the table row and inserts the subtable
  ?>
  maintable.find('td.control')
           .text('+');
           
  $(document).on('click', 'td.control', function () {
      
      var nTr = $(this).parent(),
          serial = nTr.attr('id').replace('row_', ''),
          subtableId = 'subtable_' + serial,
          subtable = subtables.find('#' + subtableId).parent().clone(),
          columnDefs = [],
          subDataTable;

      <?php
      // We want the columns in the main and sub tables to align.  So
      // find the widths of the main table columns and use those values
      // to set the widths of the subtable columns. 
      ?>
      maintable.find('tr').eq(0).find('th').each(function(i){
          columnDefs.push({width: ($(this).outerWidth()) + "px",
                           targets: i});
        });
      
      columnDefs.push({orderable: false, targets: 0});
      columnDefs = columnDefs.concat(getTypes(subtable));

      nTr.hide();
      pendingDataTable.row(nTr).child(subtable.get(0)).show();
      subtable.closest('td').addClass('table_container');

      subDataTable = $('#' + subtableId).DataTable({autoWidth: false,
                                                    paging: false,
                                                    dom: 't',
                                                    order: [[startTimeCol, 'asc']],
                                                    columnDefs: columnDefs});

      $('#subtable_' + serial + '_wrapper').hide().slideDown();
    });
                
  <?php // Turn the table into a datatable ?>
  tableOptions = {order: [[startTimeCol, 'asc']]};
  tableOptions.columnDefs = [{orderable: false, targets: 0}];
  tableOptions.columnDefs = tableOptions.columnDefs.concat(getTypes(maintable));
  <?php
  // For some reason I don't understand, fnOpen() doesn't seem to work when
  // using FixedColumns.   We also have to turn off bStateSave.  I have raised
  // this on the dataTables forum.  In the meantime we comment out the FixedColumns.
  ?>
  tableOptions.stateSave = false;
  
  <?php
  // Remove the first column from the column visibility
  // list because it is the control column
  ?>
  colVisIncludeCols = [];
  for (i=1; i<maintable.find('thead tr:first th').length; i++)
  {
    colVisIncludeCols.push(i);
  }
  tableOptions.buttons = [{extend: 'colvis',
                           columns: colVisIncludeCols}];
  <?php
  // and stop the first column being reordered
  ?>
  tableOptions.colReorder = {"fixedColumnsLeft": 1};
  pendingDataTable = makeDataTable('#pending_table', tableOptions);
};
