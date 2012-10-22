<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}


// Get the sTypes, which are assumed to be in a data-sType in a <span> in the <th>
// of the table
?>
var getSTypes = function getSTypes(table) {
    var type,
        types = {},
        sTypes = [];
        
    table.find('thead tr:first th').each(function(i) {
       var type = $(this).find('span').data('stype');
       if (type)
       {
         if (types[type] === undefined)
         {
           types[type] = [];
         }
         types[type].push(i);
       }
      });

    for (type in types)
    {
      if (types.hasOwnProperty(type))
      {
        sTypes.push({sType: type, 
                     aTargets: types[type]});
      }
    }
    
    return sTypes;
  };
  

<?php
// Try and get a sensible value for the fixed column width, which is the
// smaller of the actual column width and either a fixed width or a
// proportion of the overall table width.
// 
// col is an object with two properties:  'iWidth' and 'sWidth', which work in
// the same way as the DataTables properties
?>
function getFixedColWidth(table, col)
{
  var tableWidth = table.outerWidth();
  var leftWidth = table.find('th:first-child').outerWidth();
  var maxWidthPx = (col.sWidth == "relative") ? tableWidth*col.iWidth/100 : col.iWidth;
  return Math.min(leftWidth, maxWidthPx);
}
        
<?php
// Turn the table with id 'id' into a DataTable, using specificOptions
// which are merged with the default options.   If the browser is IE6 or less
// we don't bother making a dataTable:  it can be done, but it's not worth it.
//
// leftCol and rightCol are two objects which if defined or not null will fix the left and/or
// right most columns.  They have properties 'iWidth' and 'sWidth' defining the
// maximum width of the fixed column.   If sWidth = "fixed" then the iWidth is
// a pixel value.  If it is "relative" then a percentage.
//
// If you want to do anything else as part of fnInitComplete then you'll need
// to define fnInitComplete in specificOptions
?>
var windowResizeHandler;
        
function makeDataTable(id, specificOptions, leftCol, rightCol)
{
  var winWidth = $(window).width();
  var winHeight = $(window).height();
          
  windowResizeHandler = function()
  {
    <?php
    // IE8 and below will trigger $(window).resize not just when the window
    // is resized but also when an element in the window is resized.   We 
    // therefore need to check that this is a genuine window resize event
    // otherwise we end up in an infinite loop
    ?>
    var winNewWidth = $(window).width();
    var winNewHeight = $(window).height();
    if ((winNewWidth == winWidth) && (winNewHeight == winHeight))
    {
      return;
    }
    winWidth = winNewWidth;
    winHeight = winNewHeight;
    <?php
    // This is a genuine resize event.   Unbind the handler to stop any
    // more resize events while we are dealing with this one
    ?>
    $(window).unbind('resize', windowResizeHandler);
    <?php
    // Need to re-create the datatable when the browser window is resized.  We
    // can't just do a fnDraw() because that does not redraw the Fixed Columns
    // properly.
            
    // We set a timeout to make the resizing a bit smoother, as otherwise it's
    // fairly CPU intensive
    ?>
    window.setTimeout(function() {
        <?php
        // If we're using an Ajax data source then we don't want to have to make
        // an Ajax call and wait for the data every time we resize.   So retrieve
        // the data from the table and pass it directly to the new table.
        ?>
        if (mergedOptions.sAjaxSource)
        {
          mergedOptions.aaData = oTable.fnGetData();
          mergedOptions.sAjaxSource = null;
        }
        <?php
        // Save the language strings, because we don't need to make another Ajax
        // to fetch the language strings again when we resize
        ?>
        $.extend(true, mergedOptions.oLanguage, oTable.fnSettings().oLanguage);
                
        oTable.fnDestroy();
        oTable = table.dataTable(mergedOptions);
      }, 200);
            
  };
          
  if (lteIE6)
  {
    $('.js div.datatable_container').css('visibility', 'visible');
    return false;
  }
  else
  {
    var table = $(id);
    if (table.length === 0)
    {
      return false;
    }
    <?php
    // Remove the <colgroup>.  This is only needed to assist in the formatting
    // of the non-datatable version of the table.   When we have a datatable,
    // the datatable sorts out its own formatting.
    ?>
    table.find('colgroup').remove();
    <?php // Set up the default options ?>
    var defaultOptions = {};
    <?php
    // Set the language file to be used
    if ($lang_file = get_datatable_lang())
    {
      ?>
      defaultOptions.oLanguage = {"sUrl": "<?php echo $lang_file ?>"};
      <?php
    }
    ?>
    defaultOptions.bDeferRender = true;
    defaultOptions.bPaginate = true;
    defaultOptions.bProcessing = true;
    defaultOptions.bScrollCollapse = true;
    defaultOptions.bStateSave = true;
    defaultOptions.iDisplayLength = 25;
    defaultOptions.sDom = 'C<"clear">lfrtip';
    defaultOptions.sScrollX = "100%";
    defaultOptions.sPaginationType = "full_numbers";
    defaultOptions.oColReorder = {};
    defaultOptions.oColVis = {sSize: "auto",
                              buttonText: '<?php echo escape_js(get_vocab("show_hide_columns")) ?>',
                              bRestore: true,
                              sRestore: '<?php echo escape_js(get_vocab("restore_original")) ?>'};

    defaultOptions.fnInitComplete = function(){
    
        if (((leftCol !== undefined) && (leftCol !== null)) ||
            ((rightCol !== undefined) && (rightCol !== null)) )
        {
          <?php 
          // Fix the left and/or right columns.  This has to be done when 
          // initialisation is complete as the language files are loaded
          // asynchronously
          ?>
          var options = {};
          if ((leftCol !== undefined) && (leftCol !== null))
          {
            options.iLeftColumns = 1;
            options.sLeftWidth = "fixed";
            options.iLeftWidth = getFixedColWidth(table, leftCol);
          }
          if ((rightCol !== undefined) && (rightCol !== null))
          {
            options.iRightColumns = 1;
            options.sRightWidth = "fixed";
            options.iRightWidth = getFixedColWidth(table, rightCol);
          }

          var oFC = new FixedColumns(this, options);
          <?php
          // Not quite sure why we have to adjust the column sizing here,
          // but if we don't then the table isn't quite the right width 
          // when first drawn
          ?>
          this.fnAdjustColumnSizing();
        }
        $('.js div.datatable_container').css('visibility', 'visible');
        <?php // Rebind the handler ?>
        $(window).bind('resize', windowResizeHandler);
      };
              
    <?php
    // If we've fixed the left or right hand columns, then (a) remove it
    // from the column visibility list because it is fixed and (b) stop it
    // being reordered
    ?>
    var colVisExcludeCols = [];
    if ((leftCol !== undefined) && (leftCol !== null))
    { 
      colVisExcludeCols.push(0);
      defaultOptions.oColReorder = {iFixedColumns: 1};
    }
    if ((rightCol !== undefined) && (rightCol !== null))
    { 
      var nCols = table.find('tr:first-child th').length;
      colVisExcludeCols.push(nCols - 1);
      <?php
      // Actually we stop them all from being reordered because at the moment
      // dataTables only has a way of stopping the leftmost n columns from
      // being reordered.  May be fixed in a future release
      ?>
      defaultOptions.oColReorder = {iFixedColumns: nCols};
    }
    defaultOptions.oColVis.aiExclude = colVisExcludeCols;
    <?php
    // Merge the specific options with the default options.  We do a deep
    // merge.
    ?>
    var mergedOptions = $.extend(true, {}, defaultOptions, specificOptions);

    var oTable = table.dataTable(mergedOptions);

    <?php
    // If we're using an Ajax data source then don't offer column reordering.
    // This is a problem at the moment in DataTables because if you reorder a column
    // DataTables doesn't know that the Ajax data is still in the original order.
    // May be fixed in a future release of DataTables
    ?>
    if (!specificOptions.sAjaxSource)
    {
      <?php
      /*
      // In fact we don't use column reordering at all, because (a) it doesn't
      // work with an Ajax source (b) there's no way of fixing the right hand column
      // (c) iFixedColumns doesn't seem to work properly and (d) it's confusing
      // for the user having reordering enabled sometimes and sometimes not.  Better
      // to wait for a future release of DataTables when these issues have been
      // fixed.  In the meantime the line of code we need is there below so we can see
      // how it is done, but commented out.
              
      var oCR = new ColReorder(oTable, mergedOptions);
              
      */
      ?>
    }

    $(window).bind('resize', windowResizeHandler);
    return oTable;
  }
}