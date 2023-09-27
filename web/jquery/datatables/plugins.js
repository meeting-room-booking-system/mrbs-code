
// Selected plugins from http://datatables.net/plug-ins/api


// Sorting plugins

jQuery.extend( jQuery.fn.dataTableExt.oSort, {
    "title-string-pre": function ( a ) {
        return a.match(/title="(.*?)"/)[1].toLowerCase();
    },

    "title-string-asc": function ( a, b ) {
        return ((a < b) ? -1 : ((a > b) ? 1 : 0));
    },

    "title-string-desc": function ( a, b ) {
        return ((a < b) ? 1 : ((a > b) ? -1 : 0));
    }
} );


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

// Localised sorting
$.fn.dataTable.ext.order.intl = function ( locales, options ) {
  if ( window.Intl ) {
    var collator = new window.Intl.Collator( locales, options );
    var types = $.fn.dataTable.ext.type;

    delete types.order['string-pre'];
    types.order['string-asc'] = collator.compare;
    types.order['string-desc'] = function ( a, b ) {
      return collator.compare( a, b ) * -1;
    };
  }
};


// Filtering plugins

$.fn.dataTableExt.ofnSearch['title-numeric'] = function ( sData ) {
   return sData.replace(/\n/g," ").replace( /<.*?>/g, "" );
   };
