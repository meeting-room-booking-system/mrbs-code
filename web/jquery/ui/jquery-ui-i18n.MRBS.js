// $Id$

// Extra regional settings for the jQuery UI datepicker

jQuery(function($){
  // The English (-en) and English-US (-en-US) files are non-standard additions,
  // which do not exist explicitly.   Although datepicker defaults to US English, it
  // helps when choosing the regional settings to have explicit settings.  A ticket
  // (#6682) has been raised to request their inclusion.
  
  /* US English initialisation for the jQuery UI date picker plugin. */
  /* Based on the en-GB initialisation */
  $.datepicker.regional['en-US'] = {
    closeText: 'Done',
    prevText: 'Prev',
    nextText: 'Next',
    currentText: 'Today',
    monthNames: ['January','February','March','April','May','June',
    'July','August','September','October','November','December'],
    monthNamesShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
    'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    dayNames: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
    dayNamesShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
    dayNamesMin: ['Su','Mo','Tu','We','Th','Fr','Sa'],
    weekHeader: 'Wk',
    dateFormat: 'mm/dd/yy',
    firstDay: 1,
    isRTL: false,
    showMonthAfterYear: false,
    yearSuffix: ''};

  $.datepicker.regional['en'] = $.datepicker.regional['en-US'];

  // The sr-RS-LATIN file is also a non-standard file and is provided to match the
  // MRBS aliases. 
  $.datepicker.regional['sr-RS-LATIN'] = $.datepicker.regional['sr-SR'];

});