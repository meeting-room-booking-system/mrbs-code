<?php
declare(strict_types=1);
namespace MRBS;

require "../defaultincludes.inc";

http_headers(array("Content-type: application/x-javascript"),
             60*30);  // 30 minute expiry
?>

'use strict';

<?php // function to reverse a collection of jQuery objects ?>
$.fn.reverse = [].reverse;

<?php
// Get the sides, optionally including the border, of the rectangle represented by
// the jQuery object obj.The result object is indexed by 'n', 'w', 's' and 'e' as
// well as 'top', 'left', 'bottom' and 'right'.
//
// [Note: this depends on the object having box-sizing of content-box, as
// jQuery has problems getting the correct dimensions when using border-box
// and the browser zoom level is not 100%.  If you need to use border-box,
// then JavaScript's getBoundingClientRect() works, but remember that it
// returns positions relative to the viewport and not the document.]
?>
function getSides(obj, includeBorder)
{
  var result = {};

  if (includeBorder)
  {
    result.n = obj.offset().top;
    result.w = obj.offset().left;
    result.s = result.n + obj.outerHeight();
    result.e = result.w + obj.outerWidth();
  }
  else
  {
    <?php
    // We need to use parseFloat instead of parseInt because the CSS width may be
    // a float, especially if the browser zoom level is not 100%.
    ?>
    result.n = obj.offset().top + parseFloat(obj.css('border-top-width'));
    result.w = obj.offset().left + parseFloat(obj.css('border-left-width'));
    result.s = result.n + obj.innerHeight();
    result.e = result.w + obj.innerWidth();
  }

  result.top = result.n;
  result.left = result.w;
  result.bottom = result.s;
  result.right = result.e;

  return result;
}


<?php // Checks to see whether two rectangles occupy the same space ?>
function rectanglesIdentical(r1, r2)
{
  <?php // Allow some tolerance for fractional pixels ?>
  return ((Math.floor(Math.abs(r1.n - r2.n)) <= 1) &&
          (Math.floor(Math.abs(r1.s - r2.s)) <= 1) &&
          (Math.floor(Math.abs(r1.e - r2.e)) <= 1) &&
          (Math.floor(Math.abs(r1.w - r2.w)) <= 1));
}


<?php // Checks whether two rectangles overlap ?>
function rectanglesOverlap(r1, r2)
{
  <?php
  // If none of the sides of r1 is clear of the opposite side of r2, then the two
  // rectangles overlap. We allow the sides to be on top of each other (ie we use
  // < and > instead of <= and >=).
  ?>
  return ((Math.round(r1.n - r2.s) < 0) &&
          (Math.round(r1.s - r2.n) > 0) &&
          (Math.round(r1.w - r2.e) < 0) &&
          (Math.round(r1.e - r2.w) > 0));
}


<?php
// Gets the side-most side of the array of rectangles.
// side can be 'n', 's', 'e' or 'w'
?>
function getClosestSide(rectangles, side)
{
  var result = null;

  rectangles.forEach(function(rectangle) {
      if (result === null)
      {
        result = rectangle[side];
      }
      else if ((side === 'e') || (side === 's'))
      {
        result = Math.max(result, rectangle[side]);
      }
      else
      {
        result = Math.min(result, rectangle[side]);
      }
    });

  return result;
}


<?php
// Get the name of the data attribute in this jQuery object.
?>
function getDataName(jqObject)
{
  var possibleNames = ['room', 'date', 'seconds'];
  for (var i=0; i<possibleNames.length; i++)
  {
    if (jqObject.data(possibleNames[i]) !== undefined)
    {
      return possibleNames[i];
    }
  }
  return false;
}


<?php
// The object ui.element is only altered when the resize stops, so we can only
// get its current position and size using ui.position and ui.size. This function
// creates an invisible element in the DOM with the same position and size as
// ui.element would have when resizing stops. This clone is useful for passing to
// functions that expect a jQuery object looking like the eventual ui.element.
?>
function uiDummyClone(ui)
{
  return $('<div></div>')
    .css('position', 'absolute')
    .appendTo($('body'))
    .offset(ui.position)
    .width(ui.size.width)
    .height(ui.size.height);
}


var Table = {
  selector: ".dwm_main",
  borderLeftWidth: undefined,
  borderTopWidth: undefined,
  bookedMap: [],
  grid: {},

  <?php
  // Return the parameters for the booking represented by el
  // The result is an object with property of the data name (eg
  // 'seconds', 'time', 'room') and each property is an array of
  // the values for that booking (for example an array of room ids)
  ?>
  getBookingParams: function(el) {
      var rtl = ($(Table.selector).css('direction').toLowerCase() === 'rtl'),
          params = {},
          data,
          tolerance = 2, <?php //px ?>
          cell = {x: {}, y: {}},
          i,
          axis;

      cell.x.start = el.offset().left;
      cell.y.start = el.offset().top;
      cell.x.end = cell.x.start + el.outerWidth();
      cell.y.end = cell.y.start + el.outerHeight();
      for (axis in cell)
      {
        if (cell.hasOwnProperty(axis))
        {
          data = Table.grid[axis].data;
          if (params[Table.grid[axis].key] === undefined)
          {
            params[Table.grid[axis].key] = [];
          }
          if (rtl && (axis==='x'))
          {
            for (i = data.length - 1; i >= 0; i--)
            {
              if ((data[i].coord + tolerance) < cell[axis].start)
              {
                <?php
                // 'seconds' behaves slightly differently to the other parameters:
                // we need to know the end time for the new slot.    Also it's possible
                // for us to have a zero element, eg when selecting a new booking, and if
                // so we need to make sure there's something returned
                ?>
                if ((Table.grid[axis].key === 'seconds') ||
                    (params[Table.grid[axis].key].length === 0))
                {
                  params[Table.grid[axis].key].push(data[i].value);
                }
                break;
              }
              if ((data[i].coord + tolerance) < cell[axis].end)
              {
                params[Table.grid[axis].key].push(data[i].value);
              }
            }
          }
          else
          {
            for (i=0; i<data.length; i++)
            {
              if ((data[i].coord + tolerance) > cell[axis].end)
              {
                <?php
                // 'seconds' behaves slightly differently to the other parameters:
                // we need to know the end time for the new slot.    Also it's possible
                // for us to have a zero element, eg when selecting a new booking, and if
                // so we need to make sure there's something returned
                ?>
                if ((Table.grid[axis].key === 'seconds') ||
                    (params[Table.grid[axis].key].length === 0))
                {
                  params[Table.grid[axis].key].push(data[i].value);
                }
                break;
              }
              if ((data[i].coord + tolerance) > cell[axis].start)
              {
                params[Table.grid[axis].key].push(data[i].value);
              }
            } <?php // for ?>
          }
        }
      } <?php // for (axis in cell) ?>
      return params;
    },  <?php // getBookingParams() ?>


  getRowNumber: function(y) {
      for (var i=0; i<Table.grid.y.data.length - 1; i++)
      {
        if ((y >= Table.grid.y.data[i].coord) &&
            (y < Table.grid.y.data[i+1].coord))
        {
          return i;
        }
      }
      return null;
    },  <?php // getRowNumber ?>


  <?php // Remove any highlighting that has been applied to the row labels ?>
  clearRowLabels: function() {
      if (Table.highlightRowLabels.rows !== undefined)
      {
        for (var i=0; i < Table.highlightRowLabels.rows.length; i++)
        {
          Table.highlightRowLabels.rows[i].removeClass('selected');
        }
      }
    },  <?php // clearRowLabels ?>


  <?php
  // function to highlight the row labels in the table that are level
  // with the element el
  ?>
  highlightRowLabels: function (el) {
      if (Table.highlightRowLabels.rows === undefined)
      {
        <?php // Cache the row label cells in an array ?>
        Table.highlightRowLabels.rows = [];
        $(Table.selector).find('tbody tr').each(function() {
            Table.highlightRowLabels.rows.push($(this).find('th'));
          });
      }
      var elStartRow = Table.getRowNumber(el.offset().top);
      var elEndRow = Table.getRowNumber(el.offset().top + el.outerHeight());
      for (var i=0; i<Table.highlightRowLabels.rows.length ; i++)
      {
        if (((elStartRow === null) || (elStartRow <= i)) &&
            ((elEndRow === null) || (i < elEndRow)))
        {
          Table.highlightRowLabels.rows[i].addClass('selected');
        }
        else
        {
          Table.highlightRowLabels.rows[i].removeClass('selected');
        }
      }
    },  <?php // highlightRowLabels ?>


  init: function() {
    var table = $(Table.selector);
    var container = table.parent();
    <?php
    // Initialise the bookedMap, which is an array of booked slots. Each member of the array is an
    // object with four properties (n, s, e, w) representing the cooordinates (x or y)
    // of the side.   We will use this array to test whether a proposed
    // booking overlaps an existing booking. Select just the visible cells because there
    // could be hidden days.
    ?>
    Table.bookedMap = [];
    table.find('td.booked:visible').each(function() {
        Table.bookedMap.push(getSides($(this)));
      });
    <?php // Size the table ?>
    Table.size();
    Table.sizeTbodyViewport();
  },


  <?php
  // Tests whether the point p with coordinates x and y is outside the table
  ?>
  outside: function(p) {
      var headBottoms = $(Table.selector).find('thead').map(function() {
          return $(this).offset().top + $(this).outerHeight();
        }).get();

      <?php
      // We might have floating headers in operation, in which case
      // it doesn't make sense to drag in the part of the table behind
      // the floating header because you can't see what's happening.
      // So test to see if the cursor is above the bottom of the lowest
      // table header.
      ?>
      if (p.y < (Math.max.apply(null, headBottoms)))
      {
        return true;
      }

      return ((p.x < Table.grid.x.data[0].coord) ||
              (p.y < Table.grid.y.data[0].coord) ||
              (p.x > Table.grid.x.data[Table.grid.x.data.length - 1].coord) ||
              (p.y > Table.grid.y.data[Table.grid.y.data.length - 1].coord) );
    },  <?php // outside() ?>


  <?php
  // Check whether the rectangle (with sides n,s,e,w) overlaps any
  // of the booked slots in the table.   Returns an array of overlapped
  // bookings.
  //    stopAtFirst       (optional) If true then only the first overlap found will
  //                      be returned.  Default false.
  //    ignoreRectangle   (optional).  A rectangle that is to be ignored when checking
  //                      for overlaps.
  ?>
  overlapsBooked: function overlapsBooked(rectangle, stopAtFirst, ignoreRectangle)
  {
    var result = [];

    for (var i=0; i<Table.bookedMap.length; i++)
    {
      if (!(ignoreRectangle && rectanglesIdentical(ignoreRectangle, Table.bookedMap[i])))
      {
        if (rectanglesOverlap(rectangle, Table.bookedMap[i]))
        {
          result.push(Table.bookedMap[i]);
          if (stopAtFirst)
          {
            break;
          }
        }
      }
    }

    return result;
  },


  <?php // Clip the ui.helper so that it doesn't protrude outside the table body. ?>
  setClipPath: function(ui) {
      const top = Table.tbodyViewport.top - ui.position.top;
      const right = ui.position.left + ui.size.width - Table.tbodyViewport.right;
      const bottom = ui.position.top + ui.size.height - Table.tbodyViewport.bottom;
      const left = Table.tbodyViewport.left - ui.position.left;

      let path = 'none';

      if ((top > 0) || (right > 0) || (bottom > 0) || (left > 0))
      {
        <?php // Set the top, right, bottom and left offsets ?>
        let offsets = [];
        offsets.push((top > 0) ? top + 'px' : '0');
        offsets.push((right > 0) ? right + 'px' : '0');
        offsets.push((bottom > 0) ? bottom + 'px' : '0');
        offsets.push((left > 0) ? left + 'px' : '0');
        path = 'inset(' + offsets.join(' ') + ')';
      }

      ui.helper.css('clip-path', path);
    },

  scrollContainerBy: function(xCoord, yCoord) {
    var container = $(Table.selector).parent();
    <?php
    // If we use 'smooth' behavior then the code has to be more complicated
    // in order to prevent another mousemove set of actions being triggered
    // before the scrolling has completed.
    ?>
    container[0].scrollBy({
      top: yCoord,
      left: xCoord,
      behavior: 'instant'
    });
  },

  <?php
  // Calculate the amount by which the table container should be scrolled given an event 'e'.
  // Returns an object with 'x' and 'y' properties.
  ?>
  scrollDelta: function(e) {
    <?php
    // Set the distance from the edge of the visible tbody at which we should start scrolling.
    // It should not be more than half the height/width of the visible tbody
    ?>
    const scrollGapX = Math.min(30, Math.floor((Table.tbodyViewport.right - Table.tbodyViewport.left)/2));
    const scrollGapY = Math.min(30, Math.floor((Table.tbodyViewport.bottom - Table.tbodyViewport.top)/2));
    const table = $(Table.selector);
    const tableContainer = table.parent();
    let result = {x: 0, y: 0};

    <?php // First check whether we are approaching the top. ?>
    if ((e.pageY - Table.tbodyViewport.top) < scrollGapY)
    {
      <?php // Don't go beyond the top ?>
      result.y = -Math.min(scrollGapY, tableContainer.scrollTop());
    }

    <?php // Then whether we are approaching the bottom. ?>
    else if ((Table.tbodyViewport.bottom - e.pageY) < scrollGapY)
    {
      <?php // Don't go beyond the bottom ?>
      result.y = Math.min(scrollGapY, table.outerHeight() - tableContainer.outerHeight() - tableContainer.scrollTop());
      result.y = Math.max(result.y, 0);
      <?php
      // In Chrome, when the browser is zoomed the pixel numbers can be floating, so round down anything less than 1.
      // See https://stackoverflow.com/questions/5828275/how-to-check-if-a-div-is-scrolled-all-the-way-to-the-bottom-with-jquery
      ?>
      if (result.y < 1)
      {
        result.y = 0;
      }
    }

    <?php // Then the left hand side ?>
    if ((e.pageX - Table.tbodyViewport.left) < scrollGapX)
    {
      <?php // Don't go beyond the left hand side ?>
      result.x = -Math.min(scrollGapX, tableContainer.scrollLeft());
    }

    <?php // And finally the right ?>
    else if ((Table.tbodyViewport.right - e.pageX) < scrollGapX)
    {
      <?php // Don't go beyond the bottom ?>
      result.x = Math.min(scrollGapX, table.outerWidth() - tableContainer.outerWidth() - tableContainer.scrollLeft());
      result.x = Math.max(result.x, 0);
      <?php
      // In Chrome, when the browser is zoomed the pixel numbers can be floating, so round down anything less than 1.
      // See https://stackoverflow.com/questions/5828275/how-to-check-if-a-div-is-scrolled-all-the-way-to-the-bottom-with-jquery
      ?>
      if (result.x < 1)
      {
        result.x = 0;
      }
    }

    return result;
  },

  size: function() {
      <?php // Don't do anything if this is the all-rooms week view ?>
      if ((args.view === 'week') && args.view_all)
      {
        return;
      }
      <?php
      // Get the width of the top and left borders of the first proper slot cell
      // in the main table (ie ignore the row labels cell).  This won't be the
      // same as the value in the CSS if the browsers zoom level is not 100%.
      ?>
      var table = $(Table.selector);
      var td = table.find('tbody tr:first-child td:first-of-type');
      Table.borderLeftWidth = parseFloat(td.css('border-left-width'));
      Table.borderTopWidth = parseFloat(td.css('border-top-width'));

      <?php
      // Build an object holding all the data we need about the table, which is
      // the coordinates of the cell boundaries and the names and values of the
      // data attributes.    The object has two properties, x and y, which in turn
      // are objects containing the data for the x and y axes.  Each of these
      // objects has a key property which holds the name of the data attribute and a
      // data object, which is an array of objects holding the coordinate and data
      // value at each cell boundary.
      //
      // Note that jQuery.offset() measures to the top left hand corner of the content
      // and does not take into account padding.   So we need to make sure that the padding-top
      // and padding-left is the same for all elements that we are going to measure so
      // that we can compare them properly.   It is simplest to use zero and put any
      // padding required on the contained element.
      ?>
      var rtl = ((table.css('direction') !== undefined) &&
                 (table.css('direction').toLowerCase() === 'rtl'));
      var resolution = table.data('resolution');
      Table.grid.x = {};
      Table.grid.x.data = [];
      <?php
      // We need :visible because there might be hidden days
      ?>
      var columns = table.find('thead tr:first-child th:visible').not('.first_last');

      <?php
      // If the table has direction rtl, as it may do if you're using a RTL language
      // such as Hebrew, then the columns will have been presented in the order right
      // to left and we'll need to reverse the columns.
      ?>
      if (rtl)
      {
        columns.reverse();
      }
      columns.each(function() {
          if (Table.grid.x.key === undefined)
          {
            Table.grid.x.key = getDataName($(this));
          }
          Table.grid.x.data.push({coord: $(this).offset().left,
                                  value: $(this).data(Table.grid.x.key)});
        });
      <?php
      // and also get the right hand edge (and also the left hand edge if the
      // direction is RTL, as in Hebrew).  If we're dealing with seconds
      // we need to know what the end time of the slot would be
      ?>
      if (rtl)
      {
        columns.filter(':first').each(function() {
            var value = null;
            if (Table.grid.x.key === 'seconds')
            {
              value = Table.grid.x.data[0].value + resolution;
            }
            var edge = $(this).offset().left;
            Table.grid.x.data.unshift({coord: edge, value: value});
          });
      }

      columns.filter(':last').each(function() {
          var value = null;
          if (Table.grid.x.key === 'seconds')
          {
            value = Table.grid.x.data[Table.grid.x.data.length - 1].value + resolution;
          }
          var edge = $(this).offset().left + $(this).outerWidth();
          Table.grid.x.data.push({coord: edge, value: value});
        });


      Table.grid.y = {};
      Table.grid.y.data = [];
      var rows = table.find('tbody th:first-child');
      rows.each(function() {
          if (Table.grid.y.key === undefined)
          {
            Table.grid.y.key = getDataName($(this));
          }
          Table.grid.y.data.push({coord: $(this).offset().top,
                                  value: $(this).data(Table.grid.y.key)});
        });
      <?php // and also get the bottom edge ?>
      rows.filter(':last').each(function() {
          var value = null;
          if (Table.grid.y.key === 'seconds')
          {
            value = Table.grid.y.data[Table.grid.y.data.length - 1].value + resolution;
          }
          Table.grid.y.data.push({coord: $(this).offset().top + $(this).outerHeight(),
                                  value: value});
        });
    }, <?php // size() ?>

  <?php // Get the boundaries of the visible part of the tbody. ?>
  sizeTbodyViewport: function() {
    const table = $(Table.selector);
    const tableContainer = table.parent();
    const thead = table.find('thead');
    const tfoot = table.find('tfoot');
    const tfootHeight = (tfoot.length) ? tfoot.outerHeight() : 0;
    const tbodyFirstRowTh = table.find('tbody tr:first th');
    const tbodyRightTh = tbodyFirstRowTh.eq(2);
    const tbodyRightThWidth = (tbodyRightTh.length) ? tbodyRightTh.outerWidth() : 0;

    Table.tbodyViewport = {
      top: tableContainer.offset().top + thead.outerHeight(),
      left: tableContainer.offset().left + tbodyFirstRowTh.first().outerWidth(),
      bottom: tableContainer.offset().top + tableContainer.outerHeight() - tfootHeight,
      right: tableContainer.offset().left + tableContainer.outerWidth() - tbodyRightThWidth
    };
  }, <?php // sizeTbodyViewport() ?>

  <?php
  // Given an object 'obj', calculate the changes that must be made to its position,
  // width and height to snap the side 'side' to the grid. ('side' can be 'left',
  // 'right', 'top' or 'bottom'.)  If 'force' is true, then the side is snapped regardless
  // of where it is.
  //
  // Returns an object with properties 'top', 'left', 'width' and 'height'.
  ?>
  snapDelta: function (obj, side, force) {
    <?php // Check the side argument is valid ?>
    if (!['top', 'left', 'bottom', 'right'].includes(side))
    {
      throw new Error("Invalid argument '" + side + "' for parameter side.")
    }

    <?php // Initialise the result object.  By default, no change. ?>
    let result = {
      top: 0,
      left: 0,
      width: 0,
      height: 0
    };

    const snapGap = 35, <?php // px ?>
          tolerance = 2, <?php // px ?>
          isLR = (side === 'left') || (side === 'right'),
          data = (isLR) ? Table.grid.x.data : Table.grid.y.data;

    let topLeft, bottomRight, gap, gapTopLeft, gapBottomRight;

    let rectangle = obj.offset();
        rectangle.bottom = rectangle.top + obj.innerHeight();
        rectangle.right = rectangle.left + obj.innerWidth();

    const outerWidth = rectangle.right - rectangle.left,
          outerHeight = rectangle.bottom - rectangle.top,
          thisCoord = rectangle[side];

    for (let i=0; i<(data.length -1); i++)
    {
      topLeft = data[i].coord;
      bottomRight = data[i+1].coord;
      <?php
      // Allow for the borders: .offset() includes borders.
      ?>
      if (side === 'left')
      {
        topLeft += Table.borderLeftWidth;
        bottomRight += Table.borderLeftWidth;
      }
      else if (side === 'top')
      {
        topLeft += Table.borderTopWidth;
        bottomRight += Table.borderTopWidth;
      }

      gapTopLeft = thisCoord - topLeft;
      gapBottomRight = bottomRight - thisCoord;

      if (((gapTopLeft > 0) && (gapBottomRight > 0)) ||
          <?php // containment tests ?>
          ((i === 0) && (gapTopLeft < 0)) ||
          ((i === (data.length-2)) && (gapBottomRight < 0)) )
      {
        gap = bottomRight - topLeft;

        <?php
        // If we're forcing to the top or left side, or else the gap
        // to the top or left side is within snapping distance ...
        ?>
        if ((force && ((side === 'top') || (side === 'left'))) ||
            (!force && (gapTopLeft <= gap/2) && (gapTopLeft < snapGap)))
        {
          switch (side)
          {
            case 'top':
              result.top = topLeft - rectangle.top;
              result.height = gapTopLeft;
              break;

            case 'left':
              result.left = topLeft - rectangle.left;
              result.width = gapTopLeft;
              break;

            case 'bottom':
              <?php // Don't let the height become zero. ?>
              if ((outerHeight - gapTopLeft) < tolerance)
              {
                result.height = gapBottomRight;
              }
              else
              {
                result.height = -gapTopLeft;
              }
              break;

            case 'right':
              <?php // Don't let the width become zero. ?>
              if ((outerWidth - gapTopLeft) < tolerance)
              {
                result.width = gapBottomRight;
              }
              else
              {
                result.width = -gapTopLeft;
              }
              break;
          }
          return result;
        }

        <?php
        // If we're forcing to the bottom or right side, or else the gap
        // to the bottom or right side is within snapping distance ...
        ?>
        if ((force && ((side === 'bottom') || (side === 'right'))) ||
            (!force && (gapBottomRight <= gap/2) && (gapBottomRight < snapGap)))
        {
          switch (side)
          {
            case 'top':
              <?php // Don't let the height become zero.  ?>
              if ((outerHeight - gapBottomRight) < tolerance)
              {
                result.top = topLeft - rectangle.top;
                result.height = gapTopLeft;
              }
              else
              {
                result.top = bottomRight - rectangle.top;
                result.height = -gapBottomRight;
              }
              break;

            case 'left':
              <?php // Don't let the width become zero.  ?>
              if ((outerWidth - gapBottomRight) < tolerance)
              {
                result.left = topLeft - rectangle.left;
                result.width = gapTopLeft;
              }
              else
              {
                result.left = bottomRight - rectangle.left;
                result.width = -gapBottomRight;
              }
              break;

            case 'bottom':
              result.height = gapBottomRight;
              break;

            case 'right':
              result.width = gapBottomRight;
              break;
          }
          return result;
        }
      }
    }  <?php // for ?>

    return result;
  },  <?php // snapDelta() ?>


  <?php
  // Given the jQuery object 'obj', snap the side specified (can be 'left', 'right', 'top'
  // or 'bottom') to the nearest grid line, if the side is within the snapping range.
  // If force is true, then the side is snapped regardless of where it is.
  ?>
  snapToGrid: function (obj, side, force) {
      <?php
      // Get the changes that must be made to the object and apply them as necessary.
      ?>
      const delta = this.snapDelta(obj, side, force);

      if ((delta.top !== 0) || (delta.left !==0))
      {
        const offset = obj.offset();
        obj.offset({top: offset.top + delta.top, left: offset.left + delta.left});
      }

      if (delta.width !== 0)
      {
        obj.outerWidth(obj.outerWidth() + delta.width);
      }

      if (delta.height !== 0)
      {
        obj.outerHeight(obj.outerHeight() + delta.height);
      }

    },  <?php // snapToGrid() ?>


  <?php
  // Snap a jQuery UI object to the grid.  Snap the side specified (can be 'left', 'right',
  // 'top' or 'bottom') to the nearest grid line, if the side is within the snapping range.
  // If force is true, then the side is snapped regardless of where it is.
  //
  // We have to provide our own snapUiToGrid function instead of using the grid
  // option in the jQuery UI resize widget because our table may not have uniform
  // row heights and column widths - either because overflow: hidden isn't being
  // used, or just because of the way the browser lays out the table - so we can't
  // specify a grid in terms of a simple array as required by the resize widget.
  ?>
  snapUiToGrid: function (ui, side, force) {
    <?php
    // Get the changes that must be made to the UI element and apply them by updating
    // ui.position and ui.size.  The Helper element will automatically follow the
    // new position and size.
    ?>
    const obj = uiDummyClone(ui);
    const delta = this.snapDelta(obj, side, force);
    obj.remove();  <?php // Remove the object so it doesn't clutter the DOM. ?>

    ui.position.top += delta.top;
    ui.position.left += delta.left;
    ui.size.width += delta.width;
    ui.size.height += delta.height;
  }

};


<?php
// Add scroll positions, if any, of a jQuery object to the location.
// This enables the scroll position to be preserved after a booking
// has been made.  The originalScroll parameter is an object with left
// and top properties giving the original scroll positions of object.
// The scroll positions that are added are the minima of the current and
// original positions.  We do this so that the top left of the booking,
// and thus the brief description is in view when the booking has been
// saved and MRBS returns to the calendar (index) page.
?>
function addScrollPosition(location, object, originalScroll)
{
  if (object.isHScrollable())
  {
    location += '&left=' + encodeURIComponent(Math.min(object.scrollLeft(), originalScroll.left));
  }
  if (object.isVScrollable())
  {
    location += '&top=' + encodeURIComponent(Math.min(object.scrollTop(), originalScroll.top));
  }

  return location;
}


<?php
// Extend the jQuery UI resizable widget so that we can scroll the container.  When the
// container is scrolled we need to be able to change some of the ui variables which we
// don't have access to from the standard widget.

// The extension offers two additional options:
//
//    scrollableContainer   A jQuery object representing the element that can be scrolled.
//                          Default: null
//    scrollDelta           A function that returns the amount by which the container must
//                          be scrolled on a given event.  Returns an object with x and y
//                          properties.  Default: null
?>
$.widget('ui.resizable', $.ui.resizable, {
  options: {
    scrollableContainer: null,
    scrollDelta: null
  },
  _mouseDrag: function(event) {
    if (this.options.scrollableContainer && this.options.scrollDelta)
    {
      <?php // Calculate the amount to scroll by. ?>
      const delta = this.options.scrollDelta.call(this, event);

      if (delta.x || delta.y)
      {
        <?php // Scroll the container. ?>
        this.options.scrollableContainer[0].scrollBy({
          top: delta.y,
          left: delta.x,
          behavior: 'instant'
        });
        <?php
        // Adjust the original mouse position and the original, current and previous positions.
        ?>
        ['originalMousePosition', 'originalPosition', 'position', 'prevPosition'].forEach((property) => {
          if (delta.x) {
            this[property].left -= delta.x;
          }
          if (delta.y) {
            this[property].top -= delta.y;
          }
        });
        <?php // Adjust the position of the helper. ?>
        const helperOffset = this.helper.offset();
        this.helper.css({
          top: (helperOffset.top - delta.y) + 'px',
          left: (helperOffset.left - delta.x) + 'px'
        });
        <?php // Resize the table to adjust the position of existing bookings and grid lines. ?>
        Table.size();
      }
    }

    // Invoke the parent widget's _mouseDrag().
    return this._super(event);
  }
});


$(document).on('page_ready', function() {

  <?php // Don't do anything if we're in kiosk mode ?>
  if (args.kiosk)
  {
    return;
  }

  <?php
  // Resizable bookings work by creating an element which is a clone of the real booking
  // element and making it resizable.   We can't make the real element resizable
  // because it is bound by the table cell walls (THIS ISN'T TRUE ANYMORE!).   So we give
  // the clone an absolute position and a positive z-index.    We work out what
  // new booking the user is requesting by comparing the coordinates of the clone
  // with the table grid.   We also put the booking parameters (eg room id) as HTML5
  // data attributes in the cells of the header row and the column labels, so that we
  // can then get a set of parameters to send to edit_entry_handler as an Ajax request.
  // The result is a JSON object containing a success/failure boolean and the new table
  // HTML if successful or the reasons for failure if not.

  // We set up the resizable bookings on a table load rather than a window load event
  // because when we have the refresh timer going we just want to reload the table.  Reloading
  // the window causes other things to be re-initialised, which we don't want.   For example
  // if we have the datepicker open we don't want that to be reset.
  ?>
  $(Table.selector).on('tableload', function() {
      var table = $(this);
      var tableContainer = table.parent();

      <?php
      // Don't do anything if this is an empty table, or the all-rooms week view,
      // or the month view
      ?>
      if ((args.view === 'month') ||
          ((args.view === 'week') && args.view_all) ||
          table.find('tbody').data('empty'))
      {
        return;
      }

      Table.init();

      var mouseDown = false;

      var downHandler = function(e) {
          mouseDown = true;

          <?php // Save the original scroll position ?>
          downHandler.originalScroll = {
            left: tableContainer.scrollLeft(),
            top: tableContainer.scrollTop()
          };

          <?php
          // Apply a class so that we know that resizing is in progress, eg to turn off
          // highlighting
          ?>
          table.addClass('resizing');

          var jqTarget = $(e.target);
          <?php // If we've landed on the + symbol we want the parent ?>
          if (e.target.nodeName.toLowerCase() === "img")
          {
            jqTarget = jqTarget.parent();
          }

          downHandler.origin = jqTarget.offset();
          downHandler.firstPosition = {x: e.pageX, y: e.pageY};

          <?php
          // Get the original link in case we need it later.    We can't be sure whether
          // the target was the <a> or the <td> so play safe and get all possibilities
          ?>
          downHandler.originalLink = jqTarget.find('a').addBack('a').attr('href');
          downHandler.box = $('<div class="div_select">');

          if (!args.isBookAdmin)
          {
            <?php
            // If we're not an admin and we're not allowed to book repeats (in
            // the week view) or select multiple rooms (in the day view) then
            // constrain the box to fit in the current slot width/height
            ?>
            if (((args.view === 'week') && <?php echo ($auth['only_admin_can_book_repeat']) ? 'true' : 'false'?>) ||
                ((args.view === 'day') && <?php echo ($auth['only_admin_can_select_multiroom']) ? 'true' : 'false'?>))
            {
              <?php
              if ($times_along_top)
              {
                ?>
                var slotHeight = jqTarget.outerHeight();
                downHandler.maxHeightSet = true;
                downHandler.box.css('max-height', slotHeight + 'px');
                <?php
              }
              else
              {
                ?>
                var slotWidth = jqTarget.outerWidth();
                downHandler.maxWidthSet = true;
                downHandler.box.css('max-width', slotWidth + 'px');
                <?php
              }
              ?>
            }
          }

          <?php // Attach the element to the table container before setting the offset ?>
          tableContainer.append(downHandler.box);
          downHandler.box.offset(downHandler.origin);
        };

      var moveHandler = function(e) {
          <?php
          // Check to see if we're only allowed to go one slot wide/high
          // and have gone over that limit.  If so, do nothing and return
          ?>
          if ((downHandler.maxWidthSet && (e.pageX < downHandler.origin.left)) ||
              (downHandler.maxHeightSet && (e.pageY < downHandler.origin.top)))
          {
            return;
          }

          const box = downHandler.box;
          const oldBoxOffset = box.offset();
          const oldBoxWidth = box.outerWidth();
          const oldBoxHeight = box.outerHeight();
          const delta = Table.scrollDelta(e);

          <?php // Scroll the table if necessary ?>
          if (delta.x || delta.y)
          {
            Table.scrollContainerBy(delta.x, delta.y);
            <?php
            // Need to resize the table after a scroll because the coordinates
            // of the grid lines and booked cells will have changed.
            // TODO: Optimise performance by just recording the cumulative x- and
            // TODO: y-deltas and then use those in snapToGrid() etc.?
            ?>
            Table.size();
            <?php
            // Because we've scrolled we need to correct the positions of
            // downHandler.firstPosition and oldBoxOffset.
            ?>
            downHandler.firstPosition.x -= delta.x;
            downHandler.firstPosition.y -= delta.y;
            oldBoxOffset.left -= delta.x;
            oldBoxOffset.top -= delta.y;
          }

          <?php // Otherwise redraw the box ?>
          if (e.pageX < downHandler.firstPosition.x)
          {
            if (e.pageY < downHandler.firstPosition.y)
            {
              box.offset({top: e.pageY, left: e.pageX});
            }
            else
            {
              box.offset({top: downHandler.firstPosition.y, left: e.pageX});
            }
          }
          else if (e.pageY < downHandler.firstPosition.y)
          {
            box.offset({top: e.pageY, left: downHandler.firstPosition.x});
          }
          else
          {
            box.offset({top: downHandler.firstPosition.y, left: downHandler.firstPosition.x});
          }
          box.width(Math.abs(e.pageX - downHandler.firstPosition.x));
          box.height(Math.abs(e.pageY - downHandler.firstPosition.y));
          <?php
          // Snap the box to grid boundaries if it's close, and even if it's not
          // if you're dragging away from that edge.
          ?>
          const draggingDown = (e.pageY > downHandler.firstPosition.y);
          const draggingRight = (e.pageX > downHandler.firstPosition.x);
          Table.snapToGrid(box, 'top', draggingDown);
          Table.snapToGrid(box, 'left', draggingRight);
          Table.snapToGrid(box, 'bottom', !draggingDown);
          Table.snapToGrid(box, 'right', !draggingRight);

          <?php
          // If the new box overlaps a booked cell, then undo the changes
          // We set stopAtFirst=true because we just want to know if there is
          // *any* overlap.
          ?>
          if (Table.overlapsBooked(getSides(box), true).length)
          {
            box.offset(oldBoxOffset)
               .width(oldBoxWidth)
               .height(oldBoxHeight);
          }
          <?php
          // Check to see if we've moved outside the table and if we have
          // then give some visual feedback.   If we've moved back into the box
          // remove the feedback.
          ?>
          if (Table.outside({x: e.pageX, y: e.pageY}))
          {
            if (!moveHandler.outside)
            {
              box.addClass('outside');
              moveHandler.outside = true;
              Table.clearRowLabels();
            }
          }
          else if (moveHandler.outside)
          {
            box.removeClass('outside');
            moveHandler.outside = false;
          }
          <?php
          // Highlight the corresponding row label cells (provided we are
          // inside the table)
          ?>
          if (!moveHandler.outside)
          {
            Table.highlightRowLabels(box);
          }
        };


      var upHandler = function(e) {
          mouseDown = false;
          e.preventDefault();
          var tolerance = 2; <?php // px ?>
          var box = downHandler.box;
          var params = Table.getBookingParams(box);
          $(document).off('mousemove',moveHandler);
          $(document).off('mouseup', upHandler);

          <?php
          // If the user has released the button while outside the table it means
          // they want to cancel, so just return.
          ?>
          if (Table.outside({x: e.pageX, y: e.pageY}))
          {
            box.remove();
            $(Table.selector).removeClass('resizing');
            return;
          }
          <?php
          // If the user has hardly moved the mouse then just treat this as a
          // traditional click and follow the original link.   This will mean
          // that things such as the default duration are used.

          ?>
          else if ((Math.abs(e.pageX - downHandler.firstPosition.x) <= tolerance) &&
                   (Math.abs(e.pageY - downHandler.firstPosition.y) <= tolerance))
          {
            if (downHandler.originalLink !== undefined)
            {
              window.location = addScrollPosition(downHandler.originalLink, tableContainer, downHandler.originalScroll);
            }
            else
            {
              box.remove();
              $(Table.selector).removeClass('resizing');
            }
            return;
          }
          <?php
          // Otherwise get the selected parameters and go to the edit_entry page
          ?>
          var queryString = 'drag=1';  <?php // Says that we've come from a drag select ?>
          queryString += '&area=' + args.area;
          queryString += '&start_seconds=' + params.seconds[0];
          queryString += '&end_seconds=' + params.seconds[params.seconds.length - 1];
          if (args.view === 'day')
          {
            for (var i=0; i<params.room.length; i++)
            {
              queryString += '&rooms[]=' + params.room[i];
            }
            queryString += '&start_date=' + args.pageDate;
          }
          else <?php // it's a week ?>
          {
            queryString += '&rooms[]=' + args.room;
            queryString += '&start_date=' + params.date[0];
            queryString += '&end_date=' + params.date[params.date.length - 1];
          }
          if (args.site)
          {
            queryString += '&site=' + encodeURIComponent(args.site);
          }
          window.location = addScrollPosition('edit_entry.php?' + queryString, tableContainer, downHandler.originalScroll);
        };


      <?php
      // resize event callback function
      ?>
      var resize = function(event, ui)
      {
        var closest,
            rectangle = {},
            sides = {n: false, s: false, e: false, w: false};

        <?php
        // Get the sides of the desired rectangle and also the direction(s) of
        // resize.  Note that the desired rectangle may be being moved in two
        // directions at once (eg nw) if a corner has been grabbed. Use Math.round
        // to avoid problems with floats.
        ?>
        if (Math.round(ui.position.top - ui.originalPosition.top) === 0)
        {
          rectangle.n = ui.position.top;
        }
        else
        {
          rectangle.n = event.pageY;
          sides.n = true;
        }

        if (Math.round(ui.position.left - ui.originalPosition.left) === 0)
        {
          rectangle.w = ui.position.left;
        }
        else
        {
          rectangle.w = event.pageX;
          sides.w = true;
        }

        if (Math.round((ui.position.top + ui.size.height) -
                       (ui.originalPosition.top + ui.originalSize.height)) === 0)
        {
          rectangle.s = ui.position.top + ui.size.height;
        }
        else
        {
          rectangle.s = event.pageY;
          sides.s = true;
        }

        if (Math.round((ui.position.left + ui.size.width) -
                       (ui.originalPosition.left + ui.originalSize.width)) === 0)
        {
          rectangle.e = ui.position.left + ui.size.width;
        }
        else
        {
          rectangle.e = event.pageX;
          sides.e = true;
        }

        <?php
        // Get all the bookings that the desired rectangle would overlap.  Note
        // that it could overlap more than one other booking, so we need to find them
        // all and then find the closest one.

        // Recalculate the original rectangle which will have move if we have scrolled.
        ?>
        const originalRectangle = {
          n: ui.originalPosition.top,
          s: ui.originalPosition.top + ui.originalSize.height,
          w: ui.originalPosition.left,
          e: ui.originalPosition.left + ui.originalSize.width
        };

        var overlappedElements = Table.overlapsBooked(rectangle, false, originalRectangle);

        if (!overlappedElements.length)
        {
          <?php // No overlaps: remove any constraints ?>
          ui.element.resizable('option', {maxHeight: null,
                                          maxWidth: null});
        }
        else
        {
          <?php
          // There is at least overlap, so for each direction that the booking is being
          // resized, get the closest booking in that direction.  If there's an overlap
          // then constrain the desired rectangle not to overlap.
          ?>
          if (sides.n)
          {
            closest = getClosestSide(overlappedElements, 's');
            if (event.pageY <= closest)
            {
              ui.position.top = closest + Table.borderTopWidth;
              ui.element.resizable('option', 'maxHeight', ui.originalSize.height + ui.originalPosition.top - ui.position.top);
            }
            else
            {
              ui.element.resizable('option', 'maxHeight', null);
            }
          }

          if (sides.w)
          {
            closest = getClosestSide(overlappedElements, 'e');
            if (event.pageX <= closest)
            {
              ui.position.left = closest + Table.borderLeftWidth;
              ui.element.resizable('option', 'maxWidth', ui.originalSize.width + ui.originalPosition.left - ui.position.left);
            }
            else
            {
              ui.element.resizable('option', 'maxWidth', null);
            }
          }

          if (sides.s)
          {
            closest = getClosestSide(overlappedElements, 'n');
            if (event.pageY >= closest)
            {
              ui.element.resizable('option', 'maxHeight', closest - ui.originalPosition.top);
            }
            else
            {
              ui.element.resizable('option', 'maxHeight', null);
            }
          }

          if (sides.e)
          {
            closest = getClosestSide(overlappedElements, 'w');
            if (event.pageX >= closest)
            {
              ui.element.resizable('option', 'maxWidth', closest - ui.originalPosition.left);
            }
            else
            {
              ui.element.resizable('option', 'maxWidth', null);
            }
          }
        }


        <?php
        // Check to see if any of the four sides of the div have moved since the last time
        // and if so, see if they've got close enough to the next boundary that we can snap
        // them to the grid.   (Note: using the condition sides.w etc. doesn't seem to work
        // properly for some reason - it will pull the other edge off the grid slightly).
        ?>

        <?php // left edge ?>
        if (sides.w)
        {
          Table.snapUiToGrid(ui, 'left');
        }
        <?php // right edge ?>
        if (sides.e)
        {
          Table.snapUiToGrid(ui, 'right');
        }
        <?php // top edge ?>
        if (sides.n)
        {
          Table.snapUiToGrid(ui, 'top');
        }
        <?php // bottom edge ?>
        if (sides.s)
        {
          Table.snapUiToGrid(ui, 'bottom');
        }

        const obj = uiDummyClone(ui);
        Table.highlightRowLabels(obj);
        obj.remove();

        Table.setClipPath(ui);
      };  <?php // resize ?>


      <?php
      // callback function called when the resize starts
      ?>
      var resizeStart = function(event, ui)
      {
        resizeStart.originalOffset = ui.element.offset();

        resizeStart.oldParams = Table.getBookingParams(ui.originalElement.find('a'));

        <?php
        // Add a class so that we can disable the highlighting when we are
        // resizing (the flickering is a bit annoying)
        ?>
        table.addClass('resizing');
      };  <?php // resizeStart ?>


      <?php
      // callback function called when the resize stops
      ?>
      var resizeStop = function(event, ui)
      {
        <?php
        // Snap the edges of both the helper and the element being resized to the grid,
        // regardless of where they are.
        ?>
        ['left', 'right', 'top', 'bottom'].forEach(function(side) {
            Table.snapUiToGrid(ui, side, true);
          });

        <?php // Recalculate the original rectangle which will have move if we have scrolled. ?>
        const originalRectangle = {
            n: ui.originalPosition.top,
            s: ui.originalPosition.top + ui.originalSize.height,
            w: ui.originalPosition.left,
            e: ui.originalPosition.left + ui.originalSize.width
          };

        if (rectanglesIdentical(originalRectangle, getSides(ui.helper)))
        {
          <?php
          // Restore the proper height and width so that if the browser zoom
          // level is changed then the booking fills the slot properly.  (The
          // resizing will have set actual pixel values instead of percentages).
          ?>
          ui.element.css({width: '100%', height: '100%'});
          $(Table.selector).removeClass('resizing');
          return;
        }

        <?php
        // We've got a change to the booking, so we need to send an Ajax
        // request to the server to make the new booking
        ?>
        var data = {csrf_token: getCSRFToken(),
                    commit: 1},
            booking = ui.element.find('a');
        <?php // get the booking id and type ?>
        data.id = booking.data('id');
        data.type = booking.data('type');
        <?php // get the other parameters ?>
        var oldParams = resizeStart.oldParams;
        var newParams = Table.getBookingParams(booking);
        if (newParams.seconds !== undefined)
        {
          <?php
          // We only send through the time parameters that have changed.
          // This is so that edit_entry_handler.php knows whether to use
          // the original booking parameters.    We need to do this so that
          // we can properly handle multi-day bookings in the week view.
          ?>
          if (newParams.seconds[0] !== oldParams.seconds[0])
          {
            data.start_seconds = newParams.seconds[0];
          }
          if (newParams.seconds[newParams.seconds.length - 1] !==
              oldParams.seconds[oldParams.seconds.length - 1])
          {
            data.end_seconds = newParams.seconds[newParams.seconds.length - 1];
            <?php
            if ($enable_periods)
            {
              // When we're dealing with periods the end time is defined as
              // the start of the last period (as opposed to the start of the
              // next slot in times mode)
              ?>
              data.end_seconds -= 60;
              <?php
            }
            ?>
          }
        }
        data.view = args.view;
        data.view_all = args.view_all;
        if (args.view === 'day')
        {
          data.start_date = args.pageDate;

        }
        else  <?php // it's 'week' ?>
        {
          data.start_date = newParams.date[0];
          var onlyAdminCanBookRepeat = <?php echo ($auth['only_admin_can_book_repeat']) ? 'true' : 'false';?>;
          if (args.isBookAdmin || !onlyAdminCanBookRepeat)
          {
            if (newParams.date.length > 1)
            {
              data.rep_type = <?php echo RepeatRule::DAILY ?>;
              data.rep_interval = 1;
              data.rep_end_date = newParams.date[newParams.date.length - 1];
            }
          }
        }
        data.end_date = data.start_date;
        data.rooms = (typeof newParams.room === 'undefined') ? args.room : newParams.room;
        <?php
        if (isset($timetohighlight))
        {
          ?>
          data.timetohighlight = <?php echo $timetohighlight ?>;
          <?php
        }

        // Give some visual feedback that the change is being saved.   Note that the span
        // is inserted after the elemement rather than appended, because if it's a child
        // then any opacity rule that is applied to the parent will also apply to the child.
        ?>
        booking.addClass('saving')
               .after('<span class="saving"><?php echo get_js_vocab('saving'); ?></span>');

        if(args.site)
        {
          data.site = args.site;
        }

        $.post('edit_entry_handler.php',
               data,
               function(result) {
                  <?php
                  // Load the new HTML.   (1) Empty the existing
                  // table in order to get rid of events and data and
                  // prevent memory leaks (2) insert the updated table HTML
                  // and then (3) trigger a table load event so that the
                  // resizable bookings are re-created
                  ?>
                  table.empty()
                       .html(result.table_innerhtml)
                       .trigger('tableload');
                  <?php
                  // If the booking failed then show an alert explaining why.
                  ?>
                  if (!result.valid_booking)
                  {
                    var alertMessage = '';
                    if (result.conflicts.length > 0)
                    {
                      alertMessage += '<?php echo escape_js(html_entity_decode(get_vocab("conflict"))) ?>' + ":  \n\n";
                      var conflictsList = getErrorList(result.conflicts);
                      alertMessage += conflictsList.text;
                    }
                    if (result.violations.errors.length > 0)
                    {
                      if (result.conflicts.length > 0)
                      {
                        alertMessage += "\n\n";
                      }
                      alertMessage += '<?php echo escape_js(html_entity_decode(get_vocab("rules_broken"))) ?>' + ":  \n\n";
                      var rulesList = getErrorList(result.violations.errors);
                      alertMessage += rulesList.text;
                    }
                    window.alert(alertMessage);
                  }
                },
               'json')
          .fail(function() {
            <?php
            // The Ajax request failed for some reason, so remove the "Saving ..." message
            // and restore the element to its original position and size.
            ?>
            booking.removeClass('saving')
                   .next().remove();
            ui.element.offset(resizeStart.originalOffset)
                      .width(ui.originalSize.width)
                      .height(ui.originalSize.height);
            <?php // Re-initialise the table ?>
            $('table.dwm_main').trigger('tableload');
            <?php // Allow some time for the changes above to complete and then alert the user ?>
            setTimeout(function() {
                alert("<?php echo get_js_vocab('resize_error')?>");
              }, 250
            );

          });

      };  <?php // resizeStop ?>


      <?php
      // Turn all the empty cells where a new multi-cell selection
      // can be created by dragging the mouse
      ?>
      table.find('td.new').each(function() {
          $(this).find('a').on('click', function(event) {
              event.preventDefault();
            });
          $(this).on('mousedown', function(event) {
              event.preventDefault();
              downHandler(event);
              $(document).on('mousemove', moveHandler);
              $(document).on('mouseup', upHandler);
            });
        });



      <?php
      // Make all the writable bookings resizable
      ?>
      table.find('.writable')
        .each(function() {

            <?php
            // Get the set of directions in which we are allowed to drag the
            // box.   At this stage we will do it by reference to the two axes,
            // the times axis and the other axis, which will be days or rooms.
            // Then later we will turn it into n/s/e/w handles (this will depend
            // on $times_along_top)
            ?>
            var directions = {times: {plus: true, minus: true},
                              other: {plus: true, minus: true}};

            if ($(this).hasClass('series'))
            {
              <?php
              // We only only members of a series to have their duration changed.
              // It would be a bit confusing to have an individual member of a
              // series dragged across days to make it a new daily series, or rooms
              // to create new bookings in other rooms.
              ?>
              directions.other = {plus: false, minus: false};
            }
            if (!args.isBookAdmin)
            {
              if (((args.view === 'week') && <?php echo ($auth['only_admin_can_book_repeat']) ? 'true' : 'false'?>) ||
                  ((args.view === 'day') && <?php echo ($auth['only_admin_can_select_multiroom']) ? 'true' : 'false'?>))
              {
                <?php
                // If we're in the week view then if non-admins aren't allowed to
                // make repeat bookings, or else if we're in the day view and they
                // aren't allowed to select multiple rooms, then we want to restrict
                // the handles we offer them so that they can't get that far.
                ?>
                directions.other = {plus: false, minus: false};
              }
              <?php
              if ($auth['only_admin_can_book_multiday'])
              {
                // Don't let non-admins drag multi-day bookings if
                // $auth['only_admin_can_book_multiday'] is set
                ?>
                if ($(this).hasClass('multiday_start') ||
                    $(this).hasClass('multiday_end'))
                {
                  directions.times = {plus: false, minus: false};
                }
                <?php
              }
              ?>
            }
            <?php
            // Don't allow multiday bookings to be moved at the end
            // which is joined to another day nor along the other axis
            ?>
            if ($(this).hasClass('multiday_start'))
            {
              directions.times.minus = false;
              directions.other = {plus: false, minus: false};
            }
            if ($(this).hasClass('multiday_end'))
            {
              directions.times.plus = false;
              directions.other = {plus: false, minus: false};
            }
            <?php
            // Now turn the directions in which we are allowed to move the
            // boxes into an array of n/s/e/w handles, depending on the
            // setting of $times_along_top
            ?>
            var aHandles = [];
            if (directions.times.plus)
            {
              aHandles.push('<?php echo ($times_along_top) ? "e" : "s" ?>');
            }
            if (directions.times.minus)
            {
              aHandles.push('<?php echo ($times_along_top) ? "w" : "n" ?>');
            }
            if (directions.other.plus)
            {
              aHandles.push('<?php echo ($times_along_top) ? "s" : "e" ?>');
            }
            if (directions.other.minus)
            {
              aHandles.push('<?php echo ($times_along_top) ? "n" : "w" ?>');
            }
            <?php
            // Test each corner.  If we've got both the side handles then
            // add in the corner handle
            ?>
            ['nw', 'ne', 'se', 'sw'].forEach(function(corner) {
                if ((aHandles.indexOf(corner[0]) >= 0) &&
                    (aHandles.indexOf(corner[1]) >= 0))
                {
                  aHandles.push(corner);
                }
              });

            var handles = aHandles.join(',');

            if (handles)
            {
              $(this).resizable({
                handles: handles,
                helper: 'resizable-helper',
                start: resizeStart,
                resize: resize,
                stop: resizeStop,
                scrollableContainer: tableContainer,
                scrollDelta: Table.scrollDelta
              });
            }

            $(this).css('background-color', 'transparent');
          });

      <?php
      // We want to disable page refresh if the user is hovering over
      // the resizable handles.   We trigger a mouseenter event on page
      // load so we can work out whether the mouse is over the handle
      // on page load (but we only need to trigger one event).
      //
      // mouseDown will also be set by the event handlers for drag selection
      // of new bookings, so that we don't turn on page refresh while in the
      // middle of a drag selection when we pass over a resizable handle
      ?>
      $('.ui-resizable-handle')
        .on('mouseenter', function(e) {
            if (!mouseDown)
            {
              if ($(this).is(':hover'))
              {
                table.addClass('resizing');
              }
              else
              {
                table.removeClass('resizing');
              }
            }
          })
        .on('mouseleave', function() {
            if (!mouseDown)
            {
              table.removeClass('resizing');
            }
          })
        .on('mousedown', function() {
            mouseDown = true;
            if ($(this).is(':hover'))
            {
              table.addClass('resizing');
            }
          })
        .on('mouseup', function() {
            mouseDown = false;
            if (!$(this).is(':hover'))
            {
              table.removeClass('resizing');
            }
          })
        .first().trigger('mouseenter');

    }).trigger('tableload');

  $(window).on('resize', throttle(function(event) {
      if (event.target === this)  <?php // don't want the ui-resizable event bubbling up ?>
      {
        <?php
        // The table dimensions have changed, so we need to re-map the table
        ?>
        Table.init();
      }
    }, 50));

  <?php
  // We need to re-initialise when the table container is scrolled because all the coordinates
  // are relative to the document rather than  the container.  (The jQuery UI widget gives
  // positions this way).
  ?>
  $(Table.selector).parent().on('scroll', throttle(function() {
      Table.init();
    }));

});

