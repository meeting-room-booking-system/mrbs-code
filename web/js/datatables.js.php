<?php
declare(strict_types=1);
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
  60*30);  // 30 minute expiry

// See https://learn.microsoft.com/en-us/dotnet/api/documentformat.openxml.spreadsheet.pagesetup?view=openxml-2.8.1
define('EXCEL_PAGE_SIZES', array(
  1 =>  'LETTER',
  3 =>  'TABLOID',
  5 =>  'LEGAL',
  8 =>  'A3',
  9 =>  'A4',
  11 => 'A5'
));

// Get the Excel paper size constant.  If the config setting hasn't been set for some reason choose
// a suitable default.  Otherwise if it's one of the predefined strings get its value, or else just
// use the value itself.
if (!isset($excel_default_paper))
{
  $excel_paper_size = array_search('A4', EXCEL_PAGE_SIZES);
}
elseif ((in_arrayi($excel_default_paper, EXCEL_PAGE_SIZES)))
{
  $excel_paper_size = array_search($excel_default_paper, EXCEL_PAGE_SIZES);
}
else
{
  $excel_paper_size = $excel_default_paper;
}
?>

'use strict';

<?php
// Get the types, which are assumed to be in a data-type in a <span> in the <th>
// of the table
?>
var getTypes = function getTypes($table) {
  var types = {},
      result = [];

  $table.find('thead tr:first th').each(function(i) {
    var $span = $(this).find('span'),
        type = $span.data('type');

    if (type) {
      if (types[type] === undefined) {
        types[type] = [];
      }
      types[type].push(i);
    }
  });

  for (var type in types) {
    if (types.hasOwnProperty(type)) {
      result.push({
        type: type,
        targets: types[type]
      });
    }
  }

  return result;
};

<?php
// Actions to take once the datatable's initialisation is complete.
?>
function initCompleteActions(settings, json) {
  <?php // Make the table visible ?>
  $('.datatable_container').css('visibility', 'visible');
  <?php // Need to adjust column sizing after the table is made visible ?>
  var dt = this;
  if (dt && dt.api) {
    var api = dt.api();
    if (api && typeof api.columns === 'function') {
      setTimeout(function() {
        api.columns.adjust();
      }, 100);
    }
  }
}

<?php
// Extract email addresses from mailto: links in the columns defined by columnSelector and
// copy them to the clipboard, optionally sorting the result.
?>
var extractEmailAddresses = function(dt, columnSelector, sort) {
  var result = [];
  var message;
  const scheme = 'mailto:';

  $.each(dt.columns(columnSelector).data(), function (i, column) {
    $.each(column, function (j, value) {
      try {
        var valueObject = $(value);
        <?php // Need to search for an href in both this element and its descendants ?>
        var href = valueObject.find('a').add(valueObject.filter('a')).attr('href');
        if ((href !== undefined) && href.startsWith(scheme)) {
          var address = href.substring(scheme.length);
          if ((address !== '') && !result.includes(address)) {
            result.push(address);
          }
        }
      } catch (error) {
        <?php
        // No need to do anything. This will catch the cases when $(value) fails because
        // value is not a valid anchor element, and so we are not interested in it anyway.
        ?>
      }
    });
  });

  if (sort) {
    result.sort();
  }

  navigator.clipboard.writeText(result.join(', '))
    .then(() => {
      message = '<?php echo get_js_vocab('unique_addresses_copied')?>';
      message = message.replace('%d', result.length.toString());
    })
    .catch((err) => {
      message = '<?php echo get_js_vocab('clipboard_copy_failed')?>';
      console.error(err);
    })
    .finally(() => {
      dt.buttons.info(
        dt.i18n('buttons.copyTitle', 'Copy to clipboard'),
        message,
        2000
      )
    });
};

<?php
// Set up the configuration options for the DataTables constructor
?>
function makeDataTable(id, specificOptions, fixedColumnsOptions)
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
  var defaultOptions = {
    buttons: [{
      extend: 'colvis',
      text: '<?php echo get_js_vocab("show_hide_columns") ?>'
    }],
    deferRender: true,
    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, '<?php echo get_js_vocab('dt_all') ?>']],
    paging: true,
    pageLength: 25,
    pagingType: 'full_numbers',
    processing: true,
    scrollCollapse: true,
    stateSave: <?php echo (empty($state_save)) ? 'false' : 'true' ?>,
    stateDuration: <?php echo $state_duration ?? 0 ?>,
    dom: 'B<"clear">lfrtip',
    scrollX: '100%',
    colReorder: {},
    drawCallback: function(settings) {
      var api = this.api();
      if (api && typeof api.columns === 'function') {
        api.columns.adjust();
      }
      $('.datatable_container').css('visibility', 'visible');
    }
  };

  <?php
  // Make room for any extra buttons after the first button, which is assumed
  // to be the colvis button.
  ?>
  if (specificOptions && specificOptions.buttons)
  {
    for (i=0; i<specificOptions.buttons.length - 1; i++)
    {
      defaultOptions.buttons.push({});
    }
  }

  <?php
  // For all pages except the pending page, which has collapsible rows which don't work well with the
  // buttons, add the Copy/CSV/etc. buttons.
  ?>
  if (args.page !== 'pending')
  {
    defaultOptions.buttons = defaultOptions.buttons.concat(
      $.extend(true, {}, {
        exportOptions: {
          columns: ':visible',
          format: {
            body: function (data, row, column, node) {
              var div = $('<div>' + data + '</div>');
              <?php
              // Remove any elements used for sorting, which are all <span>s that don't
              // have a class of 'normal' (which the CSS makes visible). Note that we cannot
              // just remove :hidden elements because that would also remove everything that's
              // not on the current page and visible on screen.
              // (We can get rid of this step when we move to using orthogonal data.)
              ?>
              div.find('span:not(.normal)').remove();
              <?php // Apply the default export data stripping ?>
              var result = $.fn.dataTable.Buttons.stripData(div.html());
              <?php
              // If that is the empty string then it may be that the data is actually a form
              // and the text we want is the text in the submit button.
              ?>
              if (result === '')
              {
                var value = div.find('input[type="submit"]').attr('value');
                if (value !== undefined)
                {
                  result = value;
                }
              }
              return result;
            }
          }
        }
      }, {
        extend: 'copy',
        text: '<?php echo get_js_vocab('copy') ?>'
      }),
      $.extend(true, {}, {
        exportOptions: {
          columns: ':visible',
          format: {
            body: function (data, row, column, node) {
              var div = $('<div>' + data + '</div>');
              <?php
              // Remove any elements used for sorting, which are all <span>s that don't
              // have a class of 'normal' (which the CSS makes visible). Note that we cannot
              // just remove :hidden elements because that would also remove everything that's
              // not on the current page and visible on screen.
              // (We can get rid of this step when we move to using orthogonal data.)
              ?>
              div.find('span:not(.normal)').remove();
              <?php // Apply the default export data stripping ?>
              var result = $.fn.dataTable.Buttons.stripData(div.html());
              <?php
              // If that is the empty string then it may be that the data is actually a form
              // and the text we want is the text in the submit button.
              ?>
              if (result === '')
              {
                var value = div.find('input[type="submit"]').attr('value');
                if (value !== undefined)
                {
                  result = value;
                }
              }
              return result;
            }
          }
        },
        extend: 'csv',
        text: '<?php echo get_js_vocab('csv') ?>'
      }),
      $.extend(true, {}, {
        exportOptions: {
          columns: ':visible',
          format: {
            body: function (data, row, column, node) {
              var div = $('<div>' + data + '</div>');
              <?php
              // Remove any elements used for sorting, which are all <span>s that don't
              // have a class of 'normal' (which the CSS makes visible). Note that we cannot
              // just remove :hidden elements because that would also remove everything that's
              // not on the current page and visible on screen.
              // (We can get rid of this step when we move to using orthogonal data.)
              ?>
              div.find('span:not(.normal)').remove();
              <?php // Apply the default export data stripping ?>
              var result = $.fn.dataTable.Buttons.stripData(div.html());
              <?php
              // If that is the empty string then it may be that the data is actually a form
              // and the text we want is the text in the submit button.
              ?>
              if (result === '')
              {
                var value = div.find('input[type="submit"]').attr('value');
                if (value !== undefined)
                {
                  result = value;
                }
              }
              return result;
            }
          }
        },
        extend: 'excel',
        text: '<?php echo get_js_vocab('excel') ?>',
        customize: customizeExcel
      }),
      $.extend(true, {}, {
        exportOptions: {
          columns: ':visible',
          format: {
            body: function (data, row, column, node) {
              var div = $('<div>' + data + '</div>');
              <?php
              // Remove any elements used for sorting, which are all <span>s that don't
              // have a class of 'normal' (which the CSS makes visible). Note that we cannot
              // just remove :hidden elements because that would also remove everything that's
              // not on the current page and visible on screen.
              // (We can get rid of this step when we move to using orthogonal data.)
              ?>
              div.find('span:not(.normal)').remove();
              <?php // Apply the default export data stripping ?>
              var result = $.fn.dataTable.Buttons.stripData(div.html());
              <?php
              // If that is the empty string then it may be that the data is actually a form
              // and the text we want is the text in the submit button.
              ?>
              if (result === '')
              {
                var value = div.find('input[type="submit"]').attr('value');
                if (value !== undefined)
                {
                  result = value;
                }
              }
              return result;
            }
          }
        },
        <?php
        // Use 'pdfHtml5' rather than 'pdf'.  See
        // https://github.com/meeting-room-booking-system/mrbs-code/issues/3512
        ?>
        extend: 'pdfHtml5',
        text: '<?php echo get_js_vocab('pdf') ?>',
        orientation: '<?php echo $pdf_default_orientation ?>',
        pageSize: '<?php echo $pdf_default_paper ?>'
      }),
      $.extend(true, {}, {
        exportOptions: {
          columns: ':visible',
          format: {
            body: function (data, row, column, node) {
              var div = $('<div>' + data + '</div>');
              <?php
              // Remove any elements used for sorting, which are all <span>s that don't
              // have a class of 'normal' (which the CSS makes visible). Note that we cannot
              // just remove :hidden elements because that would also remove everything that's
              // not on the current page and visible on screen.
              // (We can get rid of this step when we move to using orthogonal data.)
              ?>
              div.find('span:not(.normal)').remove();
              <?php // Apply the default export data stripping ?>
              var result = $.fn.dataTable.Buttons.stripData(div.html());
              <?php
              // If that is the empty string then it may be that the data is actually a form
              // and the text we want is the text in the submit button.
              ?>
              if (result === '')
              {
                var value = div.find('input[type="submit"]').attr('value');
                if (value !== undefined)
                {
                  result = value;
                }
              }
              return result;
            }
          }
        },
        extend: 'print',
        text: '<?php echo get_js_vocab('print') ?>'
      })
    );
  }

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
    var colVisIncludeCols = [];
    var nCols = table.find('tr:first-child th').length;
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
  var mergedOptions = $.extend(true, {}, defaultOptions, specificOptions);

  <?php
  // Localise the sorting.  See https://datatables.net/blog/2017-02-28 ?>
  $.fn.dataTable.ext.order.intl($('body').data('langPrefs'));

  var dataTable = table.DataTable(mergedOptions);

  if (fixedColumnsOptions)
  {
    new $.fn.dataTable.FixedColumns(dataTable, fixedColumnsOptions);
  }

  <?php
  // Adjust the column sizing on a window resize
  ?>
  $(window).on('resize', function () {
    if (dataTable && dataTable.api) {
      var api = dataTable.api();
      if (api && typeof api.columns === 'function') {
        setTimeout(function() {
          api.columns.adjust();
        }, 100);
      }
    }
  });

  return dataTable;
}

var customizeExcel = function(xlsx) {
  <?php // See https://datatables.net/forums/discussion/45277/modify-page-orientation-in-xlxs-export ?>
  var sheet = xlsx.xl.worksheets['sheet1.xml'];
  var pageSetup = sheet.createElement('pageSetup');
  sheet.childNodes['0'].appendChild(pageSetup);
  var settings = sheet.getElementsByTagName('pageSetup')[0];
  settings.setAttribute("r:id", "rId1"); <?php // Relationship ID - do not change ?>
  settings.setAttribute('orientation', '<?php echo $excel_default_orientation ?>');
  settings.setAttribute('paperSize', '<?php echo $excel_paper_size ?>');
};
