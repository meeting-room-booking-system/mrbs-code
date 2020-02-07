<?php
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}


// Get the types, which are assumed to be in a data-type in a <span> in the <th>
// of the table
?>
var getTypes = function getTypes(table) {
    var type,
        types = {},
        result = [];

    table.find('thead tr:first th').each(function(i) {
       var type = $(this).find('span').data('type');

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
        result.push({type: type,
                     targets: types[type]});
      }
    }

    return result;
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

function makeDataTable(id, specificOptions, fixedColumnsOptions)
{
  var i,
      defaultOptions,
      mergedOptions,
      colVisIncludeCols,
      nCols,
      table,
      dataTable,
      fixedColumns;

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

  <?php
  // In the latest releases of DataTables a CSS rule of 'width: 100%' does not work with FixedColumns.
  // Instead you have to either set a style attribute of 'width: 100%' or set a width attribute of '100%'.
  // The former would cause problems with sites that have a Content Security Policy of "style-src 'self'" -
  // though this is a bit academic since DataTables contravenes it anyway, but maybe things will change
  // in the future.  The latter isn't ideal either because 'width' is a deprecated attribute, but we set
  // the width attribute here as the lesser of two evils.
  ?>
  table.attr('width', '100%');

  <?php // Set up the default options ?>
  defaultOptions = {
    buttons: [{extend: 'colvis',
               text: '<?php echo escape_js(get_vocab("show_hide_columns")) ?>'}],
    deferRender: true,
    paging: true,
    pageLength: 25,
    pagingType: 'full_numbers',
    processing: true,
    scrollCollapse: true,
    stateSave: true,
    dom: 'B<"clear">lfrtip',
    scrollX: '100%',
    colReorder: {}
  };

  <?php
  // For all pages except the pending page, which has collapsible rows which don't work well with the
  // buttons, add the Copy/CSV/etc. buttons.
  ?>
  if (args.page != 'pending')
  {
    defaultOptions.buttons = defaultOptions.buttons.concat(
      {extend: 'copy',
        text: '<?php echo escape_js(get_vocab('copy')) ?>'},
      {extend: 'csv',
        text: '<?php echo escape_js(get_vocab('csv')) ?>'},
      {extend: 'excel',
        text: '<?php echo escape_js(get_vocab('excel')) ?>'},
      {extend: 'pdf',
        text: '<?php echo escape_js(get_vocab('pdf')) ?>',
        orientation: '<?php echo $pdf_default_orientation ?>',
        pageSize: '<?php echo $pdf_default_paper ?>'},
      {extend: 'print',
        text: '<?php echo escape_js(get_vocab('print')) ?>'}
    );
  }

  <?php
  // Set the language file to be used
  $datatable_dir = '../jquery/datatables/language';
  if ($lang_file = get_datatable_lang_file($datatable_dir))
  {
    // If using the language.url way of loading a DataTables language file,
    // then the file must be valid JSON.   The .lang files that can be
    // downloaded from GitHub are not valid JSON as they contain comments.  They
    // therefore cannot be used with language.url, but instead have to be
    // included directly.   Note that if ever we go back to using the url
    // method then the '../' would need to be stripped off the pathname, as in
    //    $lang_file = substr($lang_file, 3); // strip off the '../'
    ?>
    defaultOptions.language = <?php include $datatable_dir . '/' . $lang_file ?>;
    <?php
  }
  ?>


  <?php
  // Construct the set of columns to be included in the column visibility
  // button.  If specificOptions is set then use that.  Otherwise include
  // all columns except any fixed columns.
  ?>
  if (specificOptions &&
      specificOptions.buttons &&
      specificOptions.buttons[0] &&
      specificOptions.buttons[0].columns)
  {
    defaultOptions.buttons[0].columns = specificOptions.buttons;
  }
  else
  {
    colVisIncludeCols = [];
    nCols = table.find('tr:first-child th').length;
    for (i=0; i<nCols; i++)
    {
      if (fixedColumnsOptions)
      {
        if (fixedColumnsOptions.leftColumns && (i < fixedColumnsOptions.leftColumns))
        {
          continue;
        }
        if (fixedColumnsOptions.rightColumns && (i >= nCols-fixedColumnsOptions.rightColumns))
        {
          continue;
        }
      }
      colVisIncludeCols.push(i);
    }
    defaultOptions.buttons[0].columns = colVisIncludeCols;
  }
  <?php
  // Merge the specific options with the default options.  We do a deep
  // merge.
  ?>
  mergedOptions = $.extend(true, {}, defaultOptions, specificOptions);
  dataTable = table.DataTable(mergedOptions);

  if (fixedColumnsOptions)
  {
    fixedColumns = new $.fn.dataTable.FixedColumns(dataTable, fixedColumnsOptions);
  }

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

  $('.datatable_container').css('visibility', 'visible');
  <?php // Need to adjust column sizing after the table is made visible ?>
  dataTable.columns.adjust();

  <?php
  // Adjust the column sizing on a window resize.   We shouldn't have to do this because
  // columns.adjust() is called automatically by DataTables on a window resize, but if we
  // don't then a right hand fixed column appears twice when a window's width is increased.
  // I have tried to create a simple test case, but everything works OK in the test case, so
  // it's something to do with the way MRBS uses DataTables - maybe the CSS, or maybe the
  // JavaScript.
  ?>
  $(window).on('resize', function () {
    dataTable.columns.adjust();
  });

  return dataTable;

}
