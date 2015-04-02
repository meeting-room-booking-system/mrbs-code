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
// Turn the table with id 'id' into a DataTable, using specificOptions
// which are merged with the default options.   If the browser is IE6 or less
// we don't bother making a dataTable:  it can be done, but it's not worth it.
//
// fixedColumnsOptions is an optional object that gets passed directly to the
// DataTables FixedColumns constructor
//
// If you want to do anything else as part of fnInitComplete then you'll need
// to define fnInitComplete in specificOptions
?>
var windowResizeHandler;
        
function makeDataTable(id, specificOptions, fixedColumnsOptions)
{
  var winWidth  = $(window).width(),
      winHeight = $(window).height(),
      i,
      defaultOptions, mergedOptions,
      nCols,
      table;
          
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
    if ((winNewWidth === winWidth) && (winNewHeight === winHeight))
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
        if (mergedOptions.ajax)
        {
          mergedOptions.aaData = oTable.fnGetData();
          mergedOptions.ajax = null;
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
    table = $(id);
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
    defaultOptions = {};
    <?php
    // Set the language file to be used
    if ($lang_file = get_datatable_lang_file('../jquery/datatables/language'))
    {
      // If using the language.url way of loading a DataTables language file,
      // then the file must be valid JSON.   The .lang files that can be 
      // downloaded from GitHub are not valid JSON as they contain comments.  They
      // therefore cannot be used with language.url, but instead have to be
      // included directly.   Note that if ever we go back to using the url
      // method then the '../' would need to be stripped off the pathname, as in
      //    $lang_file = substr($lang_file, 3); // strip off the '../'
      ?>
      defaultOptions.oLanguage = <?php include $lang_file ?>;
      <?php
    }
    ?>
    defaultOptions.deferRender = true;
    defaultOptions.paging = true;
    defaultOptions.pageLength = 25;
    defaultOptions.pagingType = "full_numbers";
    defaultOptions.processing = true;
    defaultOptions.scrollCollapse = true;
    defaultOptions.stateSave = true;
    defaultOptions.pageLength = 25;
    defaultOptions.dom = 'C<"clear">lfrtip';
    defaultOptions.scrollX = "100%";
    defaultOptions.colReorder = {};
    defaultOptions.colVis = {buttonText: '<?php echo escape_js(get_vocab("show_hide_columns")) ?>',
                             restore: '<?php echo escape_js(get_vocab("restore_original")) ?>'};

    defaultOptions.fnInitComplete = function(){
        if (fixedColumnsOptions)
        {
          <?php 
          // Fix the left and/or right columns.  This has to be done when 
          // initialisation is complete as the language files are loaded
          // asynchronously (actually they aren't but just in case they ever are)
          ?>
          new $.fn.dataTable.FixedColumns(this, fixedColumnsOptions);
        }
        $('.js div.datatable_container').css('visibility', 'visible');
        <?php // Need to adjust column sizing after the table is made visible ?>
        this.fnAdjustColumnSizing();
        <?php // Rebind the handler ?>
        $(window).bind('resize', windowResizeHandler);
      };
              
    <?php
    // If we've fixed the left or right hand columns, then (a) remove them
    // from the column visibility list because they are fixed and (b) stop them
    // from being reordered
    ?>
    var colVisExcludeCols = [];
    if (fixedColumnsOptions)
    {
      if (fixedColumnsOptions.leftColumns)
      { 
        for (i=0; i<fixedColumnsOptions.leftColumns; i++)
        {
          colVisExcludeCols.push(i);
        }
        defaultOptions.colReorder.fixedColumnsLeft = fixedColumnsOptions.leftColumns;
      }
      if (fixedColumnsOptions.rightColumns)
      { 
        nCols = table.find('tr:first-child th').length;
        for (i=0; i<fixedColumnsOptions.rightColumns; i++)
        {
          colVisExcludeCols.push(nCols - (i+1));
        }
        defaultOptions.colReorder.fixedColumnsRight = fixedColumnsOptions.rightColumns;
      }
    }
    defaultOptions.colVis.exclude = colVisExcludeCols;
    <?php
    // Merge the specific options with the default options.  We do a deep
    // merge.
    ?>
    mergedOptions = $.extend(true, {}, defaultOptions, specificOptions);

    var oTable = table.dataTable(mergedOptions);

    <?php
    // If we're using an Ajax data source then don't offer column reordering.
    // This is a problem at the moment in DataTables because if you reorder a column
    // DataTables doesn't know that the Ajax data is still in the original order.
    // May be fixed in a future release of DataTables
    ?>
    if (!specificOptions.ajax)
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