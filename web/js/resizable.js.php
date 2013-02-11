<?php

// $Id$

require "../defaultincludes.inc";

header("Content-type: application/x-javascript");
expires_header(60*30); // 30 minute expiry

if ($use_strict)
{
  echo "'use strict';\n";
}

$user = getUserName();
$is_admin = (authGetUserLevel($user) >= $max_level);


// function to reverse a collection of jQuery objects
?>
$.fn.reverse = [].reverse;


<?php
// Get the sides of the rectangle represented by the jQuery object jqObject
// We round down the size of the rectangle to avoid any spurious overlaps
// caused by rounding errors
?>
function getSides(jqObject)
{
  var sides = {};
  sides.n = Math.ceil(jqObject.offset().top);
  sides.w = Math.ceil(jqObject.offset().left);
  sides.s = Math.floor(sides.n + jqObject.outerHeight());
  sides.e = Math.floor(sides.w + jqObject.outerWidth());
  return sides;
}
        
        
<?php // Checks to see whether two rectangles occupy the same space ?>
function rectanglesIdentical(r1, r2)
{
  var tolerance = 2;  <?php //px ?>
  return ((Math.abs(r1.n - r2.n) < tolerance) &&
          (Math.abs(r1.s - r2.s) < tolerance) &&
          (Math.abs(r1.e - r2.e) < tolerance) &&
          (Math.abs(r1.w - r2.w) < tolerance));
}
            
                              
<?php // Checks whether two rectangles overlap ?>         
function rectanglesOverlap(r1, r2)
{
  <?php
  // We check whether two rectangles overlap by checking whether any of the
  // sides of one rectangle intersect the sides of the other.   In the condition
  // below, we are checking on the first line to see if either of the vertical
  // sides of r1 intersects either of the horizontal sides of r2.  The second line
  // checks for intersection of the horizontal sides r1 with the vertical sides of r2.
  ?>
  if ( (( ((r1.w > r2.w) && (r1.w < r2.e)) || ((r1.e > r2.w) && (r1.e < r2.e)) ) && (r1.n < r2.s) && (r1.s > r2.n)) ||
       (( ((r1.n > r2.n) && (r1.n < r2.s)) || ((r1.s > r2.n) && (r1.s < r2.s)) ) && (r1.w < r2.e) && (r1.e > r2.w)) )
  {
    return true;
  }
  <?php // they also overlap if r1 is inside r2 ?>
  if ((r1.w >= r2.w) && (r1.n >= r2.n) && (r1.e <= r2.e) && (r1.s <= r2.s))
  {
    return true;
  }
  <?php // or r2 is inside r1 ?>
  if ((r2.w >= r1.w) && (r2.n >= r1.n) && (r2.e <= r1.e) && (r2.s <= r1.s))
  {
    return true;
  }
  return false;
}
            
            
<?php
// Check whether the rectangle (with sides n,s,e,w) overlaps any
// of the booked slots in the table.
?>
function overlapsBooked(rectangle, bookedMap)
{
  <?php
  // Check each of the booked cells in turn to see if it overlaps
  // the rectangle.  If it does return true immediately.
  ?>
  for (var i=0; i<bookedMap.length; i++)
  {
    if (rectanglesOverlap(rectangle, bookedMap[i]))
    {
      return true;
    }
  }
  return false;
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
        
        
function redrawClones(table)
{
  table.find('div.clone').each(function() {
      var clone = $(this);
      var original = clone.prev();
      clone.width(original.outerWidth())
           .height(original.outerHeight());
    });
}
        
function getTableData(table, tableData)
{
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
             table.css('direction').toLowerCase() === 'rtl');
  var resolution = table.data('resolution');
  tableData.x = {};
  tableData.x.data = [];
  <?php // We need :visible because there might be hidden days // ?>
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
      if (tableData.x.key === undefined)
      {
        tableData.x.key = getDataName($(this));
      }
      tableData.x.data.push({coord: $(this).offset().left,
                             value: $(this).data(tableData.x.key)});
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
        if (tableData.x.key === 'seconds')
        {
          value = tableData.x.data[0].value + resolution;
        }
        var edge = $(this).offset().left;
        tableData.x.data.unshift({coord: edge, value: value});
      });
  }

  columns.filter(':last').each(function() {
      var value = null;
      if (tableData.x.key === 'seconds')
      {
        value = tableData.x.data[tableData.x.data.length - 1].value + resolution;
      }
      var edge = $(this).offset().left + $(this).outerWidth();
      tableData.x.data.push({coord: edge, value: value});
    });

    
  tableData.y = {};
  tableData.y.data = [];
  var rows = table.find('tbody td:first-child').not('.multiple_booking td');
  rows.each(function() {
      if (tableData.y.key === undefined)
      {
        tableData.y.key = getDataName($(this));
      }
      tableData.y.data.push({coord: $(this).offset().top,
                             value: $(this).data(tableData.y.key)});
    });
  <?php // and also get the bottom edge ?>
  rows.filter(':last').each(function() {
      var value = null;
      if (tableData.y.key === 'seconds')
      {
        value = tableData.y.data[tableData.y.data.length - 1].value + resolution;
      }
      tableData.y.data.push({coord: $(this).offset().top + $(this).outerHeight(),
                             value: value});
    });
}
        
        
<?php
// Tests whether the point p with coordinates x and y is outside the table
?>
function outsideTable(tableData, p)
{
  return ((p.x < tableData.x.data[0].coord) ||
          (p.y < tableData.y.data[0].coord) ||
          (p.x > tableData.x.data[tableData.x.data.length - 1].coord) ||
          (p.y > tableData.y.data[tableData.y.data.length - 1].coord) );
}
        
<?php
// Given 'div', snap the side specified (can be 'left', 'right', 'top' or 'bottom') to 
// the nearest grid line, if the side is within the snapping range.
//
// If force is true, then the side is snapped regardless of where it is.
//
// We also contain the resize within the set of bookable cells
//
// We have to provide our own snapToGrid function instead of using the grid
// option in the jQuery UI resize widget because our table may not have uniform
// row heights and column widths - so we can't specify a grid in terms of a simple
// array as required by the resize widget.
?>
function snapToGrid(tableData, div, side, force)
{
  var snapGap = (force) ? 100000: 30; <?php // px ?>
  var tolerance = 2; <?php //px ?>
  var isLR = (side==='left') || (side==='right');
 
  var data = (isLR) ? tableData.x.data : tableData.y.data;
  
  var topLeft, bottomRight, divTop, divLeft, divWidth, divHeight, thisCoord,
      gap, gapTopLeft, gapBottomRight;
      
  divTop = div.offset().top;
  divLeft = div.offset().left;
  divWidth = div.outerWidth();
  divHeight = div.outerHeight();
  switch (side)
  {
    case 'top':
      thisCoord = divTop;
      break;
    case 'bottom':
      thisCoord = divTop + divHeight;
      break;
    case 'left':
      thisCoord = divLeft;
      break;
    case 'right':
      thisCoord = divLeft + divWidth;
      break;
  }

  for (var i=0; i<(data.length -1); i++)
  {
    topLeft = data[i].coord + <?php echo $main_table_cell_border_width ?>;
    bottomRight = data[i+1].coord;
    
    gapTopLeft = thisCoord - topLeft;
    gapBottomRight = bottomRight - thisCoord;
            
    if (((gapTopLeft>0) && (gapBottomRight>0)) ||
        <?php // containment tests ?>
        ((i===0) && (gapTopLeft<0)) ||
        ((i===(data.length-2)) && (gapBottomRight<0)) )
    {
      gap = bottomRight - topLeft;
              
      if ((gapTopLeft <= gap/2) && (gapTopLeft < snapGap))
      {
        switch (side)
        {
          case 'left':
            div.offset({top: divTop, left: topLeft});
            div.width(divWidth + gapTopLeft);
            break;
          case 'right':
            <?php
            // Don't let the width become zero.   (We don't need to do
            // this for height because that's protected by a min-height
            // rule.   Unfortunately we can't rely on uniform column widths
            // so we can't use a min-width rule.
            ?>
            if ((divWidth - gapTopLeft) < tolerance)
            {
              div.width(divWidth + gapBottomRight);
            }
            else
            {
              div.width(divWidth - gapTopLeft);
            }
            break;
          case 'top':
            div.offset({top: topLeft, left: divLeft});
            div.height(divHeight + gapTopLeft);
            break;
          case 'bottom':
            div.height(divHeight - gapTopLeft);
            break;
        }
        return;
      }
      else if ((gapBottomRight <= gap/2) && (gapBottomRight < snapGap))
      {
        switch (side)
        {
          case 'left':
            <?php // Don't let the width become zero.  ?>
            if ((divWidth - gapBottomRight) < tolerance)
            {
              div.offset({top: div.Top, left: topLeft});
              div.width(divWidth + gapTopLeft);
            }
            else
            {
              div.offset({top: divTop, left: bottomRight});
              div.width(divWidth - gapBottomRight);
            }
            break;
          case 'right':
            div.width(divWidth + gapBottomRight);
            break;
          case 'top':
            div.offset({top: bottomRight, left: divLeft});
            div.height(divHeight - gapBottomRight);
            break;
          case 'bottom':
            div.height(divHeight + gapBottomRight);
            break;
        }
        return;
      }
    }
  }  <?php // for ?>
}  <?php // snapToGrid() ?>
              

<?php
// Return the parameters for the booking represented by div
// The result is an object with property of the data name (eg
// 'seconds', 'time', 'room') and each property is an array of
// the values for that booking (for example an array of room ids)
?>
function getBookingParams(table, tableData, div)
{ 
  var rtl = (table.css('direction').toLowerCase() === 'rtl'),
      params = {},
      data,
      tolerance = 2, <?php //px ?>
      cell = {x: {}, y: {}},
      i,
      axis;
      
  cell.x.start = div.offset().left;
  cell.y.start = div.offset().top;
  cell.x.end = cell.x.start + div.outerWidth();
  cell.y.end = cell.y.start + div.outerHeight();
  for (axis in cell)
  {
    if (cell.hasOwnProperty(axis))
    {
      data = tableData[axis].data;
      if (params[tableData[axis].key] === undefined)
      {
        params[tableData[axis].key] = [];
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
            // for us to have a zero div, eg when selecting a new booking, and if
            // so we need to make sure there's something returned
            ?>
            if ((tableData[axis].key === 'seconds') ||
                (params[tableData[axis].key].length === 0))
            {
              params[tableData[axis].key].push(data[i].value);
            }
            break;
          }
          if ((data[i].coord + tolerance) < cell[axis].end)
          {
            params[tableData[axis].key].push(data[i].value);
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
            // for us to have a zero div, eg when selecting a new booking, and if
            // so we need to make sure there's something returned
            ?>
            if ((tableData[axis].key === 'seconds') ||
                (params[tableData[axis].key].length === 0))
            {
              params[tableData[axis].key].push(data[i].value);
            }
            break;
          }
          if ((data[i].coord + tolerance) > cell[axis].start)
          {
            params[tableData[axis].key].push(data[i].value);
          }
        } <?php // for ?>
      }
    }
  } <?php // for (axis in cell) ?>
  return params;
}
        
        
function getRowNumber(tableData, y)
{
  for (var i=0; i<tableData.y.data.length - 1; i++)
  {
    if (y >= tableData.y.data[i].coord && y < tableData.y.data[i+1].coord)
    {
      return i;
    }
  }
  return null;
}


<?php
// function to highlight the row labels in the table that are level
// with div
?>
var highlightRowLabels = function (table, tableData, div)
{
  if (highlightRowLabels.rows === undefined)
  {
    <?php // Cache the row label cells in an array ?>
    highlightRowLabels.rows = [];
    table.find('tbody tr').each(function() {
        highlightRowLabels.rows.push($(this).find('td.row_labels'));
      });
  }
  var divStartRow = getRowNumber(tableData, div.offset().top);
  var divEndRow = getRowNumber(tableData, div.offset().top + div.outerHeight());
  for (var i=0; i<highlightRowLabels.rows.length ; i++)
  {
    if (((divStartRow === null) || (divStartRow <= i)) && 
        ((divEndRow === null) || (i < divEndRow)))
    {
      highlightRowLabels.rows[i].addClass('selected');
    }
    else
    {
      highlightRowLabels.rows[i].removeClass('selected');
    }
  }
};
      
      
<?php // Remove any highlighting that has been applied to the row labels ?>
function clearRowLabels()
{
  if (highlightRowLabels.rows !== undefined)
  {
    for (var i=0; i<highlightRowLabels.rows.length; i++)
    {
      highlightRowLabels.rows[i].removeClass('selected');
    }
  }
}

<?php

// =================================================================================

// Extend the init() function 
?>

var oldInitResizable = init;
init = function(args) {
  oldInitResizable.apply(this, [args]);

  <?php
  // Resizable bookings (needs json_encode()).   These work by creating a div which 
  // is a clone of the real booking div and making it resizable.   We can't make the
  // real div resizable because it is bound by the table cell walls.   So we give
  // the clone an absolute position and a positive z-index.    We work out what
  // new booking the user is requesting by comparing the coordinates of the clone
  // with the table grid.   We also put the booking parameters (eg room id) as HTML5
  // data attributes in the cells of the header row and the column labels, so that we
  // can then get a set of parameters to send to edit_entry_handler as an Ajax request.
  // The result is a JSON object containg a success/failure boolean and the new table
  // HTML if successful or the reasons for failure if not.
  if (function_exists('json_encode'))
  {
    // 
    // We don't allow resizable bookings for IE8 and below.   In theory they should
    // be OK, but there seems to be a problem getting the resizing working properly.
    // (It looks as though it's probably something to do with the way .offset()
    // works in IE8 and below:  it seems to be giving some strange figures for the 
    // table coordinates.)
    ?>
    if (!lteIE8)
    {
      var table = $('table.dwm_main');
      
      <?php // Don't do anything if this is an empty table ?>
      if (table.find('tbody').data('empty'))
      {
        return;
      }
         
      var tableData = {};
      getTableData(table, tableData);
      
      <?php
      // bookedMap is an array of booked slots.   Each member of the array is an
      // object with four properties (n, s, e, w) representing the cooordinates (x or y)
      // of the side.   We will use this array to test whether a proposed
      // booking overlaps an existing booking.   We save populating this array until
      // the resize starts, because we want to exclude the booked slot that is being
      // resized.
      ?>
      var bookedMap = [];

      var downHandler = function(e) {
          turnOffPageRefresh();
          <?php // Build the map of booked cells ?>
          table.find('td').not('td.new, td.row_labels').each(function() {
              bookedMap.push(getSides($(this)));
            });
          <?php // Apply a wrapper to turn off highlighting ?>
          table.wrap('<div class="resizing"><\/div>');
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
          downHandler.originalLink = jqTarget.find('a').andSelf('a').attr('href');
          downHandler.box = $('<div class="div_select">');
          <?php
          if (!$is_admin)
          {
            // If we're not an admin and we're not allowed to book repeats (in
            // the week view) or select multiple rooms (in the day view) then 
            // constrain the box to fit in the current slot width/height
            ?>
            if (((args.page == 'week') && <?php echo ($auth['only_admin_can_book_repeat']) ? 'true' : 'false'?>) ||
                ((args.page == 'day') && <?php echo ($auth['only_admin_can_select_multiroom']) ? 'true' : 'false'?>))
            {
              <?php
              if ($times_along_top)
              {
                ?>
                var slotHeight = jqTarget.outerHeight();
                downHandler.maxHeight = true;
                downHandler.box.css('max-height', slotHeight + 'px');
                downHandler.box.css('min-height', slotHeight + 'px');
                <?php
              }
              else
              {
                ?>
                var slotWidth = jqTarget.outerWidth();
                downHandler.maxWidth = true;
                downHandler.box.css('max-width', slotWidth + 'px');
                downHandler.box.css('min-width', slotWidth + 'px');
                <?php
              }
              ?>
            }
            <?php
          }
          ?>  
          downHandler.box.offset(downHandler.origin);
          $(document.body).append(downHandler.box);
        };
          
      var moveHandler = function(e) {
          var box = downHandler.box;
          var oldBoxOffset = box.offset();
          var oldBoxWidth = box.outerWidth();
          var oldBoxHeight = box.outerHeight();
            
          <?php
          // Check to see if we're only allowed to go one slot wide/high
          // and have gone over that limit.  If so, do nothing and return
          ?>
          if ((downHandler.maxWidth && (e.pageX < downHandler.origin.left)) ||
              (downHandler.maxHeight && (e.pageY < downHandler.origin.top)))
          {
            return;
          }
          <?php // Otherwise redraw the box ?>
          if (e.pageX < downHandler.origin.left)
          {
            if (e.pageY < downHandler.origin.top)
            {
              box.offset({top: e.pageY, left: e.pageX});
            }
            else
            {
              box.offset({top: downHandler.origin.top, left: e.pageX});
            }
          }
          else if (e.pageY < downHandler.origin.top)
          {
            box.offset({top: e.pageY, left: downHandler.origin.left});
          }
          else
          {
            box.offset(downHandler.origin);
          }
          box.width(Math.abs(e.pageX - downHandler.origin.left));
          box.height(Math.abs(e.pageY - downHandler.origin.top));
          snapToGrid(tableData, box, 'top');
          snapToGrid(tableData, box, 'bottom');
          snapToGrid(tableData, box, 'right');
          snapToGrid(tableData, box, 'left');
          <?php
          // If the new box overlaps a booked cell, then undo the changes
          ?>
          if (overlapsBooked(getSides(box), bookedMap))
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
          if (outsideTable(tableData, {x: e.pageX, y: e.pageY}))
          {
            if (!moveHandler.outside)
            {
              box.addClass('outside');
              moveHandler.outside = true;
              clearRowLabels();
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
            highlightRowLabels(table, tableData, box);
          }
        };
 
               
      var upHandler = function(e) {
          e.preventDefault();
          var tolerance = 2; <?php // px ?>
          var box = downHandler.box;
          var params = getBookingParams(table, tableData, box);
          $(document).unbind('mousemove',moveHandler);
          $(document).unbind('mouseup', upHandler);
          <?php // Remove the resizing wrapper so that highlighting comes back on ?>
          $('table.dwm_main').unwrap();
          <?php
          // If the user has released the button while outside the table it means
          // they want to cancel, so just return. 
          ?>
          if (outsideTable(tableData, {x: e.pageX, y: e.pageY}))
          {
            box.remove();
            turnOnPageRefresh();
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
              window.location = downHandler.originalLink;
            }
            else
            {
              box.remove();
            }
            turnOnPageRefresh();
            return;
          }
          <?php
          // Otherwise get the selected parameters and go to the edit_entry page
          ?>
          var queryString = 'drag=1';  <?php // Says that we've come from a drag select ?>
          queryString += '&area=' + args.area;
          queryString += '&start_seconds=' + params.seconds[0];
          queryString += '&end_seconds=' + params.seconds[params.seconds.length - 1];
          if (args.page === 'day')
          {
            for (var i=0; i<params.room.length; i++)
            {
              queryString += '&rooms[]=' + params.room[i];
            }
            queryString += '&day=' + args.day;
            queryString += '&month=' + args.month;
            queryString += '&year=' + args.year;
          }
          else <?php // it's a week ?>
          {
            queryString += '&rooms[]=' + args.room;
            queryString += '&start_date=' + params.date[0];
            queryString += '&end_date=' + params.date[params.date.length - 1];
          }
          turnOnPageRefresh();
          window.location = 'edit_entry.php?' + queryString;
          return;
        };

          
      <?php
      // Turn all the empty cells where a new multi-cell selection
      // can be created by dragging the mouse
      ?>     
      table.find('td.new').each(function() {
          $(this).find('a').click(function(event) {
              event.preventDefault();
            });
          $(this).mousedown(function(event) {
              event.preventDefault();
              downHandler(event);
              $(document).bind('mousemove', moveHandler);
              $(document).bind('mouseup', upHandler);
            });
        });
            
          
          
      <?php
      // Turn all the writable cells into resizable bookings
      ?>
      table.find('td.writable')
        .each(function() {
            <?php
            // resize event callback function
            ?>
            var divResize = function (event, ui)
            {
              if (divResize.origin === undefined)
              {
                divResize.origin = divBooking.offset();
                divResize.lastPosition = $.extend({}, divClone.position());
                divResize.lastSize = {width: divClone.outerWidth(),
                                      height: divClone.outerHeight()};
              }

              var rectangle = {};
              rectangle.n = Math.round(divResize.origin.top + divClone.position().top);
              rectangle.w = Math.round(divResize.origin.left + divClone.position().left);
              rectangle.s = rectangle.n + Math.round(divClone.outerHeight());
              rectangle.e = rectangle.w + Math.round(divClone.outerWidth());

              if (overlapsBooked(rectangle, bookedMap))
              {
                divClone.resizable("disable");
              }
              else if (divClone.resizable('option', 'disabled'))
              {
                divClone.resizable("enable");
              }
              <?php
              // Check to see if any of the four sides of the div have moved since the last time
              // and if so, see if they've got close enough to the next boundary that we can snap
              // them to the grid
              ?>
              
              <?php // left edge ?>
              if (divClone.position().left !== divResize.lastPosition.left)
              {
                snapToGrid(tableData, divClone, 'left');
              }
              <?php // right edge ?>
              if ((divClone.position().left + divClone.outerWidth()) !== (divResize.lastPosition.left + divResize.lastSize.width))
              {
                snapToGrid(tableData, divClone, 'right');
              }
              <?php // top edge ?>
              if (divClone.position().top !== divResize.lastPosition.top)
              {
                snapToGrid(tableData, divClone, 'top');
              }
              <?php // bottom edge ?>
              if ((divClone.position().top + divClone.outerHeight()) !== (divResize.lastPosition.top + divResize.lastSize.height))
              {
                snapToGrid(tableData, divClone, 'bottom');
              }
                
              highlightRowLabels(table, tableData, divClone);
                
              divResize.lastPosition = $.extend({}, divClone.position());
              divResize.lastSize = {width: divClone.outerWidth(),
                                    height: divClone.outerHeight()};
            };  <?php // divResize ?>
            
            
            <?php
            // callback function called when the resize starts
            ?>
            var divResizeStart = function (event, ui)
            {
              turnOffPageRefresh();
              <?php
              // Add a wrapper so that we can disable the highlighting when we are
              // resizing (the flickering is a bit annoying)
              ?>
              table.wrap('<div class="resizing"><\/div>');
              <?php
              // Remove the constraint on the max width of the clone.  (We've had
              // to keep it there up until now because otherwise the div is 
              // sometimes 1px too wide.  Don't quite understand why - something to do
              // with rounding)
              ?>
              divClone.css('max-width', 'none');
              <?php
              // Add an outline to the original booking so that we can see where it
              // was.   The width and height are 2 pixels short of the original to allow
              // for a 1 pixel border all round.
              ?>
              $('<div class="outline"><\/div>')
                  .width(divClone.outerWidth() - 2)
                  .height(divClone.outerHeight() - 2)
                  .offset(divClone.offset())
                  .appendTo($('div.resizing'));
              <?php
              // Build the map of booked cells, excluding this cell (because we're
              // allowed to be in our own cell
              ?>
              table.find('td').not('td.new, td.row_labels').not(divBooking.closest('td')).each(function() {
                  bookedMap.push(getSides($(this)));
                });

            };  <?php // divResizeStart ?>
            
            
            <?php
            // callback function called when the resize stops
            ?>
            var divResizeStop = function (event, ui)
            {          
              <?php // Clear the map of booked cells ?>
              bookedMap = [];
              
              if (divClone.resizable('option', 'disabled'))
              { 
                <?php
                // If the resize was disabled then just restore the original position
                ?>
                divClone.resizable('enable')
                        .offset(divBooking.offset())
                        .width(divBooking.outerWidth())
                        .height(divBooking.outerHeight());
              }
              else
              {
                <?php
                // Snap the edges to the grid, regardless of where they are.
                ?>
                snapToGrid(tableData, divClone, 'left', true);
                snapToGrid(tableData, divClone, 'right', true);
                snapToGrid(tableData, divClone, 'top', true);
                snapToGrid(tableData, divClone, 'bottom', true);
              }
              
              <?php // Remove the outline ?>
              $('div.outline').remove();
              <?php // Remove the resizing wrapper so that highlighting comes back on ?>
              $('table.dwm_main').unwrap();
              
              var r1 = getSides(divBooking);
              var r2 = getSides(divClone);
              if (rectanglesIdentical(r1, r2))
              {
                turnOnPageRefresh();
              }
              else
              {
                <?php 
                // We've got a change to the booking, so we need to send an Ajax
                // request to the server to make the new booking
                ?>
                var data = {ajax: 1, 
                            commit: 1,
                            day: args.day,
                            month: args.month,
                            year: args.year};
                <?php // get the booking id ?>
                data.id = divClone.data('id');
                <?php // get the other parameters ?>
                var oldParams = getBookingParams(table, tableData, divBooking);
                var newParams = getBookingParams(table, tableData, divClone);
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
                if (args.page === 'day')
                {
                  data.page = 'day';
                  data.start_day = args.day;
                  data.start_month = args.month;
                  data.start_year = args.year;
                }
                else  <?php // it's 'week' ?>
                {
                  data.page = 'week';
                  var startDate = newParams.date[0].split('-');
                  data.start_year = startDate[0];
                  data.start_month = startDate[1];
                  data.start_day = startDate[2];
                  <?php
                  if ($is_admin || !$auth['only_admin_can_book_repeat'])
                  {
                    ?>
                    if (newParams.date.length > 1)
                    {
                      data.rep_type = <?php echo REP_DAILY ?>;
                      var repEndDate = newParams.date[newParams.date.length - 1].split('-');
                      data.rep_end_year = repEndDate[0];
                      data.rep_end_month = repEndDate[1];
                      data.rep_end_day = repEndDate[2];
                    }
                    <?php
                  }
                  ?>
                }
                data.end_day = data.start_day;
                data.end_month = data.start_month;
                data.end_year = data.start_year;
                data.rooms = (typeof newParams.room === 'undefined') ? args.room : newParams.room;
                <?php
                if (isset($timetohighlight))
                {
                  ?>
                  data.timetohighlight = <?php echo $timetohighlight ?>;
                  <?php
                }
                ?>
                $.post('edit_entry_handler.php',
                       data,
                       function(result) {
                          if (result.valid_booking)
                          {
                            <?php
                            // The new booking succeeded.   (1) Empty the existing
                            // table in order to get rid of events and data and
                            // prevent memory leaks (2) insert the updated table HTML
                            // and then (3) trigger a window load event so that the
                            // resizable bookings are re-created and then (4) give the
                            // user some positive visual feedback that the change has 
                            // been saved
                            ?>
                            table.empty();
                            table.html(result.table_innerhtml);
                            $(window).trigger('load');
                            <?php // Now for the visual feedback ?>
                            $.each(result.new_details, function(i, value) {
                                var cell = $('[data-id="' + value.id + '"]');
                                var cellAnchor = cell.find('a');
                                var oldHTML = cellAnchor.html();
                                var duration = 1000; <?php // ms ?>
                                cellAnchor.fadeOut(duration, function(){
                                    cellAnchor.html('<?php echo get_vocab("changes_saved")?>').fadeIn(duration, function() {
                                        cellAnchor.fadeOut(duration, function() {
                                            cellAnchor.html(oldHTML).fadeIn(duration);
                                          });
                                      });
                                  });
                              });
                          }
                          else
                          {
                            divClone.offset(divBooking.offset())
                                    .width(divBooking.outerWidth())
                                    .height(divBooking.outerHeight());
                            var alertMessage = '';
                            if (result.conflicts.length > 0)
                            {
                              alertMessage += '<?php echo escape_js(mrbs_entity_decode(get_vocab("conflict"))) ?>' + ":  \n\n";
                              var conflictsList = getErrorList(result.conflicts);
                              alertMessage += conflictsList.text;
                            }
                            if (result.rules_broken.length > 0)
                            {
                              if (result.conflicts.length > 0)
                              {
                                alertMessage += "\n\n";
                              }
                              alertMessage += '<?php echo escape_js(mrbs_entity_decode(get_vocab("rules_broken"))) ?>' + ":  \n\n";
                              var rulesList = getErrorList(result.rules_broken);
                              alertMessage += rulesList.text;
                            }
                            window.alert(alertMessage);
                          }
                          turnOnPageRefresh();
                        },
                       'json');
              }   <?php // if (rectanglesIdentical(r1, r2)) ?>
              
            };  <?php // divResizeStop ?>
            
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
            <?php
            if (!$is_admin)
            {
              ?>
              if (((args.page == 'week') && <?php echo ($auth['only_admin_can_book_repeat']) ? 'true' : 'false'?>) ||
                  ((args.page == 'day') && <?php echo ($auth['only_admin_can_select_multiroom']) ? 'true' : 'false'?>))
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
            }
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
            var corners = ['nw', 'ne', 'se', 'sw'];
            for (var i=0; i<corners.length; i++)
            {
              if ((aHandles.indexOf(corners[i][0]) >= 0) &&
                  (aHandles.indexOf(corners[i][1]) >= 0))
              {
                aHandles.push(corners[i]);
              }
            }
            var handles = aHandles.join(',');
            var divBooking = $(this).children('div');
            var divClone = divBooking.clone();
            divBooking.css('visibility', 'hidden');
            divClone.css('z-index', '500')
                    .css('position', 'absolute')
                    .css('top', '0')
                    .css('left', '0')
                    .css('background-color', $(this).css('background-color'))
                    .css('max-height', 'none')
                    .css('min-height', '<?php echo $main_cell_height ?>px')
                    .addClass('clone')
                    .width(divBooking.outerWidth())
                    .height(divBooking.outerHeight());
            if (handles)
            {
              divClone.resizable({handles: handles,
                                  resize: divResize,
                                  start: divResizeStart,
                                  stop: divResizeStop});
            }
            divClone.appendTo($(this));
            $(this).css('background-color', 'transparent')
                   .wrapInner('<div style="position: relative"><\/div>');
          });
                                  
      $(window).resize(function(event) {
          if (event.target === this)  <?php // don't want the ui-resizable event bubbling up ?>
          {
            <?php
            // The table dimensions have changed, so we need to redraw the clones
            // and re map the table
            ?>
            redrawClones(table);
            getTableData(table, tableData);
          }
        });
      
      <?php
      // We want to disable page refresh if the user is hovering over
      // the resizable handles.   We trigger a mouseenter event on page
      // load so we can work out whether the mouse is over the handle
      // on page load (but we only need to trigger one event)
      ?>  
      var mouseDown = false;  
      $('div.clone .ui-resizable-handle')
        .mouseenter(function(e) {
            if (!mouseDown)
            {
              if ($(this).is(':hover'))
              {
                turnOffPageRefresh();
              }
              else
              {
                turnOnPageRefresh();
              }
            }
          })
        .mouseleave(function() {
            if (!mouseDown)
            {
              turnOnPageRefresh();
            }
          })
        .mousedown(function() {
            mouseDown = true;
            if ($(this).is(':hover'))
            {
              turnOffPageRefresh();
            }
          })
        .mouseup(function() {
            mouseDown = false;
            if (!$(this).is(':hover'))
            {
              turnOnPageRefresh();
            }
          })
        .first().trigger('mouseenter');
        
      <?php // also need to redraw and recalibrate if the multiple bookings are clicked ?>
      table.find('div.multiple_control')
          .click(function() {
              redrawClones(table);
              getTableData(table, tableData);
            });

    }  <?php // if (!lteIE8) ?>
      
    <?php
  } // if function_exists('json_encode')
  ?>
  
};

