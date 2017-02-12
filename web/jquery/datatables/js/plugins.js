
// Selected plugins from http://datatables.net/plug-ins/api


// Sorting plugins

$.fn.dataTableExt.oSort['title-numeric-asc']  = function(a,b) {
  var x = a.match(/title="*(-?[0-9\.]+)/)[1];
  var y = b.match(/title="*(-?[0-9\.]+)/)[1];
  x = parseFloat( x );
  y = parseFloat( y );
  return ((x < y) ? -1 : ((x > y) ?  1 : 0));
  };

$.fn.dataTableExt.oSort['title-numeric-desc'] = function(a,b) {
  var x = a.match(/title="*(-?[0-9\.]+)/)[1];
  var y = b.match(/title="*(-?[0-9\.]+)/)[1];
  x = parseFloat( x );
  y = parseFloat( y );
  return ((x < y) ?  1 : ((x > y) ? -1 : 0));
  };

// Filtering plugins

$.fn.dataTableExt.ofnSearch['title-numeric'] = function ( sData ) {
   return sData.replace(/\n/g," ").replace( /<.*?>/g, "" );
   };

