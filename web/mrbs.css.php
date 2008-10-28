<?php 

// $Id$

header("Content-type: text/css"); 
include "config.inc.php";

// IMPORTANT *************************************************************************************************
// In order to avoid problems in locales where the decimal point is represented as a comma, it is important to
//   (1) specify all PHP length variables as strings, eg $border_width = '1.5'; and not $border_width = 1.5;
//   (2) convert PHP variables after arithmetic using number_format
// ***********************************************************************************************************


?>


/* ------------ GENERAL -----------------------------*/
<?php

// ***** SETTINGS ***********************

$month_cell_scrolling = TRUE;   // set to TRUE if you want the cells in the month view to scroll if there are too
                                // many bookings to display; set to FALSE if you want the table cell to expand to
                                // accommodate the bookings.   (NOTE: (1) scrolling doesn't work in IE6 and so the table
                                // cell will always expand in IE6.  (2) In IE8 Beta 2 scrolling doesn't work either and
                                // the cell content is clipped when $month_cell_scrolling is set to TRUE.)


// ***** COLOURS ************************
// Colours used in MRBS.    All the colours are defined here as PHP variables

$body_background_color          = "#e7ebee";    // background colour for the main body
$standard_font_color            = "#0B263B";    // default font color
$header_font_color              = "#ffffff";    // font color for text in headers
$highlight_font_color           = "#ff0066";    // used for highlighting text (eg links, errors)

$banner_back_color              = "#4b667b";    // background colour for banner
$banner_border_color            = $body_background_color;    // border colour for banner
$company_name_color             = $header_font_color;        // colour for company name text

$header_back_color              = $banner_back_color;  // background colour for headers

$admin_table_header_back_color  = $header_back_color;     // background colour for header and also border colour for table cells
$admin_table_header_sep_color   = $body_background_color; // vertical separator colour in header
$admin_table_header_font_color  = $header_font_color;     // font colour for header

$main_table_header_border_color = $body_background_color; // border colour for day/week/month tables - header
$main_table_body_h_border_color = "#ffffff";              // border colour for day/week/month tables - body, horizontal
$main_table_body_v_border_color = $body_background_color; // border colour for day/week/month tables - body, vertical
$main_table_month_color         = "#ffffff";    // background colour for days in the month view
$main_table_month_invalid_color = "#d1d9de";    // background colour for invalid days in the month view

$report_table_border_color      = $standard_font_color;
$report_h2_border_color         = $banner_back_color;     // border colour for <h2> in report.php
$report_h3_border_color         = "#879AA8";              // border colour for <h2> in report.php
$report_entry_border_color      = "#C3CCD3";              // used to separate individual bookings in report.php

$search_table_border_color      = $standard_font_color;

$site_faq_entry_border_color    = $report_entry_border_color;    // used to separate individual FAQ's in help.php

$trailer_border_color           = $main_table_border_color;

$anchor_link_color              = $standard_font_color;        // link color
$anchor_visited_color           = $anchor_link_color;          // link color (visited)
$anchor_hover_color             = $highlight_font_color;       // link color (hover)

$anchor_link_color_banner       = $header_font_color;          // link color
$anchor_visited_color_banner    = $anchor_link_color_banner;   // link color (visited)
$anchor_hover_color_banner      = $anchor_link_color_banner;   // link color (hover)

$anchor_link_color_header       = $header_font_color;          // link color
$anchor_visited_color_header    = $anchor_link_color_header;   // link color (visited)
$anchor_hover_color_header      = $anchor_link_color_header;   // link color (hover)

$row_even_color                 = "#ffffff";        // even rows in the day and week views
$row_odd_color                  = "#f2f4f6";        // even rows in the day and week views
$row_highlight_color            = "#ffc0da";        // used for highlighting a row
                                                    // NOTE: this colour is also used in xbLib.js (in more than one place)and 
                                                    // if you change it here you will also need to change it there.

$help_highlight_color           = "#ffe6f0";        // highlighting text on the help page #ffffbb

// These are the colours used for distinguishing between the dfferent types of bookings in the main
// displays in the day, week and month views
$color_types = array(
    'A' => "#ffff99",
    'B' => "#99cccc",
    'C' => "#ffffcd",
    'D' => "#cde6e6",
    'E' => "#6dd9c4",
    'F' => "#82adad",
    'G' => "#ccffcc",
    'H' => "#d9d982",
    'I' => "#99cc66",
    'J' => "#e6ffe6");

    
    
    
// ***** FONTS ************************    
$standard_font_family  = 'Arial, Verdana, sans-serif';

 
?>
body {font-size: small;
    color:            <?php echo $standard_font_color ?>;
    font-family:      <?php echo $standard_font_family ?>;
    background-color: <?php echo $body_background_color ?>}

.current {color: <?php echo $highlight_font_color ?>}                        /* used to highlight the current item */
.error   {color: <?php echo $highlight_font_color ?>; font-weight: bold}     /* for error messages */

h1 {font-size: x-large}
h2 {font-size: large}

a:link    {color: <?php echo $anchor_link_color ?>;    text-decoration: none; font-weight: bold}
a:visited {color: <?php echo $anchor_visited_color ?>; text-decoration: none; font-weight: bold}
a:hover   {color: <?php echo $anchor_hover_color ?>;   text-decoration: underline; font-weight: bold} 


td, th {vertical-align: top}

td form {margin: 0}     /* Prevent IE from displaying margins around forms in tables. */

legend {font-weight: bold; font-size: large;
    font-family: <?php echo $standard_font_family ?>;
    color: <?php echo $standard_font_color ?>}
fieldset {margin: 0; padding: 0; border: 0}
fieldset.admin {width: 100%; padding: 0 1.0em 1.0em 1.0em;
    border: 1px solid <?php echo $admin_table_header_back_color ?>}
fieldset fieldset {position: relative; clear: left; width: 100%; padding: 0; border: 0; margin: 0}  /* inner fieldsets are invisible */
fieldset fieldset legend {font-size: 0}        /* for IE: even if there is no legend text, IE allocates space  */



table.admin_table {border-spacing: 0px; border-collapse: collapse; border-color: <?php echo $admin_table_header_back_color ?>; border-style: solid;
    border-top-width: 0; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 0}
.admin_table th {color: <?php echo $admin_table_header_font_color ?>; font-size: small;
    background-color: <?php echo $admin_table_header_back_color ?>; 
    padding: 0.1em 0.5em 0.1em 0.5em;
    border-top-width: 1px; border-right-width: 0; border-bottom-width: 0; border-left-width: 1px; border-style: solid;
    border-top-color: <?php echo $admin_table_header_back_color ?>; border-left-color: <?php echo $admin_table_header_sep_color ?>;
    vertical-align: middle; text-align: left}
.admin_table th:first-child {border-left-color: <?php echo $admin_table_header_back_color ?>}
.admin_table td {padding: 0.1em 0.5em 0.1em 0.5em; 
    border-top-width: 1px; border-right-width: 0; border-bottom-width: 0; border-left-width: 1px; border-style: solid;
    border-top-color: <?php echo $admin_table_header_back_color ?>; border-left-color: <?php echo $admin_table_header_back_color ?>;
    vertical-align: middle}
    
.naked {margin: 0; padding: 0; border-width: 0} /* Invisible tables used for internal needs */
table:hover.naked {cursor: pointer}   /* set cursor to pointer; if you don't it doesn't show up when show_plus_link is false */


/* ------------ ADMIN.PHP ---------------------------*/
<?php
// Adjust the label width to suit the longest label - it will depend on the translation being used
// The input width can normally be left alone
$admin_form_label_width       = '7.0';   // em
$admin_form_gap               = '1.0';   // em
$admin_form_input_width       = '10.5';   // em   (Also used in edit_area_room.php)
$admin_form_overheads         = '1.0';   // em   (fudge factor)
$admin_form_width             = $admin_form_label_width + $admin_form_gap + $admin_form_input_width + $admin_form_overheads;
$admin_form_width             = number_format($admin_form_width, 1, '.', '');   // get rid of any commas
?>
table#admin {margin-bottom: 1.0em}
#admin th {text-align: center}
#admin td {padding: 0.5em; vertical-align: top}
.form_admin fieldset {border: 0; padding-top: 1.0em; width: <?php echo $admin_form_width ?>em}  /* width necessary for Safari */
.form_admin div {float: left; clear: left} 
.form_admin label {
    display: block; float: left; clear: left; 
    width: <?php echo $admin_form_label_width ?>em; min-height: 2.0em; text-align: right;
}
.form_admin input {
    display: block; float: left; clear: right;
    width: <?php echo $admin_form_input_width ?>em;
    margin-top: -0.2em; margin-left: <?php echo $admin_form_gap ?>em;
    font-family: <?php echo $standard_font_family ?>; font-size: small;
}
.form_admin input.submit {
    width: auto; margin-top: 1.2em; margin-left: <?php echo number_format(($admin_form_gap + $admin_form_label_width), 1, '.', '')?>em
}


/* ------------ DAY/WEEK/MONTH.PHP ------------------*/
div#dwm_header {width: 100%; float: left; margin-top: 1.0em; margin-bottom: 0.5em}
div#dwm_areas  {float: left; margin-right: 2.0em}
div#dwm_rooms  {float: left; margin-right: 2.0em}
#dwm_header h3 {font-size: small; font-weight: normal; text-decoration: underline; 
    margin-top: 0; margin-bottom: 0.2em; padding-bottom: 0}
#dwm_header ul {list-style-type: none; padding-left: 0; margin-left: 0; margin-top: 0}
#dwm_header li {padding-left: 0; margin-left: 0}

h2#dwm {text-align: center}

div.date_nav    {float: left;  width: 100%; margin-top: 0.5em; margin-bottom: 0.5em; font-weight: bold}
div.date_before {float: left;  width: 33%; text-align: left}
div.date_now    {float: left;  width: 33%; text-align: center}
div.date_after  {float: right; width: 33%; text-align: right}

<?php 
$table_dwm_main_border_width = '1';    // px
?>
table.dwm_main {clear: both; width: 100%; border-spacing: 0; border-collapse: separate; border: 0}
.dwm_main td {padding: 0;
   border-top:  <?php echo $table_dwm_main_border_width ?>px solid <?php echo $main_table_body_h_border_color ?>;
   border-left: <?php echo $table_dwm_main_border_width ?>px solid <?php echo $main_table_body_v_border_color ?>;
   border-bottom: 0;
   border-right: 0}
.dwm_main td:first-child {border-left: 0}
.dwm_main th {font-size: small; font-weight: normal; vertical-align: top; padding: 0 2px 0 2px;
    color: <?php echo $header_font_color ?>; 
    background-color: <?php echo $header_back_color ?>;
    border-left: <?php echo $table_dwm_main_border_width ?>px solid <?php echo $main_table_header_border_color ?>}
.dwm_main th:first-child {border-left: 0}
.dwm_main a {display: block; height: 100%}
.dwm_main th a:link    {color: <?php echo $anchor_link_color_header ?>;    text-decoration: none; font-weight: normal}
.dwm_main th a:visited {color: <?php echo $anchor_visited_color_header ?>; text-decoration: none; font-weight: normal}
.dwm_main th a:hover   {color: <?php echo $anchor_hover_color_header ?>;   text-decoration:underline; font-weight: normal}
.dwm_main#day_main th.first_last {width: 1%}
.dwm_main#day_main td, .dwm_main#week_main td {padding: 0 2px 0 2px}
.dwm_main#week_main th {width: 14%}
.dwm_main#week_main th.first_last {width: 1%; vertical-align: bottom}
.dwm_main#month_main th {width: 14%}                                                   /* 7 days in the week */
.dwm_main#month_main td {border-top:  <?php echo $table_dwm_main_border_width ?>px solid <?php echo $main_table_body_v_border_color ?>}
.dwm_main#month_main td.valid   {background-color: <?php echo $main_table_month_color ?>}
.dwm_main#month_main td.invalid {background-color: <?php echo $main_table_month_invalid_color ?>}
.dwm_main#month_main a {padding: 0 2px 0 2px}

a.new_booking {display: block; width: 100%; font-size: medium; text-align: center}
.new_booking img {margin: auto; border: 0; padding: 4px 0 2px 0}
<?php
if (!$show_plus_link)
{
  echo ".new_booking img {display: none}\n";
}
?>

<?php
// The following section deals with the contents of the table cells in the month view.    It is designed
// to ensure that the new booking link is active anywhere in the cell that there isn't another link, for 
// example the link to the day in question at the top left and the bookings themselves.   It works by using
// z-index levels and placing the new booking link at the bottom of the pile.
//
// [There is in fact one area where the new booking link is not active and that is to the right of the last
// booking when there is an odd number of bookings and the mode is 'slot' or 'description' (ie not 'both').
// This is because the list of bookings is in a div of its own which includes that bottom right hand corner.   One
// could do without the container div, and then you could solve the problem, but the container div is there to
// allow the bookings to scroll without moving the date and new booking space at the top of the cell.   Putting up
// with the small gap at the end of odd rows is probably a small price worth paying to ensure that the date and the 
// new booking link remain visible when you scroll.]
?>
div.cell_container {position: relative; float: left; width: 100%;        /* the containing div for a.new_booking and the naked table  */ 
<?php echo ($month_cell_scrolling ? 'height:' : 'min-height:') ?> 100px} /* NOTE:  if you change the value of (min-)height, make sure you */
                                                                         /* also change the value of height in mrbs-ielte6.css */
.month a.new_booking {position: absolute; top: 0; left: 0; z-index: 10}  /* needs to be above the base, but below the date (monthday) */

.dwm_main#month_main table.naked {position: absolute; top: 0; left: 0;  /* used when javascript cursor set - similar to new_booking  */
    width: 100%; height: 100%; z-index: 10}
       
div.cell_header {position: relative; width: 2.0em; z-index: 20;         /* needs to be above the new booking anchor */
     min-height: 20%; height: 20%; max-height: 20%; overflow: hidden}
                                                                                  
a.monthday {display: block; width: 100%; font-size: medium}             /* the date in the top left corner */

div.booking_list {position: relative; z-index: 20;                      /* contains the list of bookings */
    max-height: 80%; font-size: x-small;                                /* needs to be above new_booking and naked table */
    overflow: <?php echo ($month_cell_scrolling ? 'auto' : 'visible') ?>}
.booking_list a {font-size: x-small}


<?php
// Generate the classes to give the colour coding by booking type in the day/week/month views
foreach ($color_types as $type => $col)
{
  echo "td.$type {background-color: $col}\n";         // used in the day and week views
  echo ".month div.$type {float: left; max-height: 1.3em; height: 1.3em; min-height: 1.3em; overflow: hidden; background-color: $col}\n";   // used in the month view
}

?>

td.row_highlight  {background-color: <?php echo $row_highlight_color ?>} /* used for highlighting a row */
td.even_row       {background-color: <?php echo $row_even_color ?>}      /* even rows in the day view */
td.odd_row        {background-color: <?php echo $row_odd_color ?>}       /* odd rows in the day view */
td.times          {background-color: <?php echo $header_back_color ?>}   /* used for the column with times/periods */
.times a:link    {color: <?php echo $anchor_link_color_header ?>;    text-decoration: none; font-weight: normal}
.times a:visited {color: <?php echo $anchor_visited_color_header ?>; text-decoration: none; font-weight: normal}
.times a:hover   {color: <?php echo $anchor_hover_color_header ?>;   text-decoration:underline; font-weight: normal}

<?php
// HIGHLIGHTING:  Set styles for the highlighted cells under the cursor (the time/period cell and the current cell)
//
// There are two methods of highlighting:  (1) CSS Highlighting and (2) JavaScript highlighting.    Javascript highlighting
// has itself three different modes of highlighting:  'class', 'hybrid' and 'bgcolor'.    See xbLib.js for an explanation of
// the three modes.    JavaScript highlighting was originally the only method of highlighting cells, but now that support is
// common for the :hover pseudo-class used with elements other than <a>, CSS highlighting is used by default and JavaScript
// highlighting is only used for old browsers, eg IE6 and before, where the :hover pseudo-class is not supported.
// Note that CSS highlighting is essential for IE7 and IE8 where the performance of JavaScript highlighting is very poor.  This 
// is why CSS highlighting was introduced, though it is also simpler and produces smaller pages.
//
// (1) CSS HIGHLIGHTING
//
// The next four rules are used to implement CSS highlighting.    CSS highlighting is used because the performance 
// of JavaScript highlighting - both in 'class' and 'hybrid' modes - is very poor in IE7 and IE8Beta2 (the latest version of
// IE at the time of writing) when there are a large number of table rows, ie when $resolution is small compared to the length
// of the booking day.   As the performance of CSS highlighting is as good as JavaScript highlighting in recent versions of
// non-IE browsers, it is used as the default method of highlighting since it is simpler than the JavaScript method.
//
// The first two rules highlight the cell that you are actually hovering over.    The third rule highlights the cell in the 
// left-hand (and right-hand if present) column that shows the time/period for that row.   The fourth rule highlights the cell
// being hovered over in the month view.
//
// Note that the first two rules only highlight empty cells in the day and week views.    They will not highlight 
// actual bookings (because they have a class other than odd_row or even_row), the header cells (because they 
// are <th> and not <td>) nor the empty cells in the month view (because odd_row and even_row are not used 
// in the month view).   However the third rule does have the useful effect of highlighting the time slot that
// corresponds to the start of a booking when you hover over a booked cell.    The fourth rule provides highlighting in the month view.
?>
.dwm_main tr:hover td:hover.odd_row, .dwm_main tr:hover td:hover.even_row {background-color: <?php echo $row_highlight_color ?>}
.dwm_main tr:hover td.times {background-color: <?php echo $row_highlight_color ?>; color: <?php echo $standard_font_color ?>}
.dwm_main#month_main td:hover.valid {background-color: <?php echo $row_highlight_color ?>}


<?php
// (2) JAVASCRIPT HIGHLIGHTING
//
// See xbLib.js for an explanation.
?>

td.highlight         {background-color: <?php echo $row_highlight_color ?>; color: <?php echo $standard_font_color ?>}
<?php
// would be nicer to use color: inherit in the four rules below, but inherit is not supported by IE until IE8.   
// inherit would mean that (1) you didn't have to specify the colour again and (2) you needn't use the tbody selector to
// stop the header links changing colour.
?>
.highlight a:link    {font-weight: normal; color: <?php echo $standard_font_color ?>}       /* used for JavaScript highlighting  */
.highlight a:visited {font-weight: normal; color: <?php echo $standard_font_color ?>}       /* used for JavaScript highlighting  */
.dwm_main tbody tr:hover a:link    {color: <?php echo $standard_font_color ?>}   /* used for CSS highlighting (but will also be used in JavaScript highlighting */
.dwm_main tbody tr:hover a:visited {color: <?php echo $standard_font_color ?>}   /* used for CSS highlighting (but will also be used in JavaScript highlighting */
.month .highlight a:link    {font-weight: bold}
.month .highlight a:visited {font-weight: bold}


<?php

/* CELLDIV CLASSES

The next section generates the celldiv classes (i.e. celldiv1, celldiv2, etc.).   We need
enough of them so that they cover a booking spanning all the slots.

These classes are used to control the styling of the main div in a cell in the main display table.
By editing $clipped the styling can be set to be either 
(1) CLIPPED.
The cells are all a standard height and any content that does not fit in the cell is clipped.
The height is a multiple of the height for a single cell, defined by $main_cell_height.   For 
example if you define the main cell height to be 1.1em high, then a booking that is only one slot long
will be 1.1 em high and a booking two slots long will be 2.2em high, etc.
(2) NOT CLIPPED
The cells expand to fit the content.

The cell height can be specified in pixels or ems.    Specifying it in pixels has the advantage that we 
are able to calculate the true height of merged cells and make them clickable for the entire height.  If
the units are in ems, we cannot calculate this and there will be an area at the bottom that is not clickable -
the effect will be most noticeable on long bookings.   However specifying pixels may cause zooming problems
on older browsers.

(Although the style information could be put in an inline style declaration, this would mean that every
cell in the display would carry the extra size of the style declaration, whereas the classes here mean
that we only need the style declaration for each row.) 

In the classes below
- celldivN is the class for displaying a booking N slots long
- height is the height of N slots (ie N * $main_cell_height)
- you need to specify max-height so that clipping works correctly in Firefox
- you need to specify height so that clipping works correctly in IE and also
  to force min-height to work correctly in IE
- you need to specify min-height to force the box to be the min-height in
  IE (Firefox works OK without min-height)

*/

$main_cell_height = '17';        // Units specified below
$main_cell_height_units = 'px';  // Set to "em" or "px" as desired
$clipped = TRUE;                 // Set to TRUE for clipping, FALSE if not   

if ($clipped)
{
  // find the max. number of slots in a day
  if ($enable_periods)
  {
    $n_slots = count($periods);    // if we're using periods it's just the number of periods
  }
  else
  {
    $n_slots = ($eveningends*60) + $eveningends_minutes;
    $n_slots = $n_slots - (($morningstarts*60) + $morningstarts_minutes);  // day duration in minutes
    $n_slots = (($n_slots*60)/$resolution) + 1;                            // number of slots
  }
  for ($i=1; $i<=$n_slots; $i++) 
  {
    $div_height = $main_cell_height * $i;
    
    // need to add the height of the inter-cell borders to the height of the div, but
    // we can only do this if the cell height is specified in pixels otherwise we end
    // up with a mixture of ems and pixels
    if ('px' == $main_cell_height_units)
    {
      $div_height = $div_height + (($i-1)*$table_dwm_main_border_width);
      $div_height = (int) $div_height;    // Make absolutely sure it's an int to avoid generating invalid CSS
    }
    
    // need to make sure the height is formatted with a '.' as the decimal point,
    // otherwise in some locales you will get invalid CSS (eg 1,1em will not work as CSS).
    // This step isn't necessary if the cell height is in pixels and
    // therefore guaranteed to be an integer.
    else
    {
      $div_height = number_format($div_height, 2, '.', '');
    }
    
    echo "div.celldiv" . $i . " {" . 
      "display: block; overflow: hidden; margin: 0; padding: 0; " . 
    	"height:"      . $div_height . $main_cell_height_units . "; " . 
    	"max-height: " . $div_height . $main_cell_height_units . "; " . 
    	"min-height: " . $div_height . $main_cell_height_units . ";}\n";
  }
}
?>



/* ------------ DEL.PHP -----------------------------*/
div#del_room_confirm {padding-bottom: 3em}
#del_room_confirm p {text-align: center; font-size: large; font-weight: bold}
div#del_room_confirm_links {position: relative; margin-left: auto; margin-right: auto}
span#del_yes {display:block; position: absolute; right: 50%; margin-right: 1em; font-size: large}
span#del_no  {display:block; position: absolute; left: 50%; margin-left: 1em; font-size: large}
#del_room_confirm_links a:hover {cursor: pointer}                  /* for IE */
#del_room_confirm_links span:hover {text-decoration: underline}    /* for Firefox */


/* ------------ EDIT_AREA_ROOM.PHP ------------------*/
<?php
// Ideally the label text will fit on a single line, but as the text
// is editable in the lang.* files and there are so many translations, we cannot
// be sure how long the longest line will be.    Once you've chosen your preferred language and you can see what it looks like,
// you may want to adjust the width of the label below so that the longest label just fits on one line.  

$edit_area_room_label_width       = '11.0';    // em
$edit_area_room_input_margin_left = '1.0';
$edit_area_room_input_width       = $admin_form_input_width;
$edit_area_room_width_overheads   = '1.0';     // borders around inputs etc.    Konqueror seems to be the most extreme
$edit_area_room_form_width        = $edit_area_room_label_width + $edit_area_room_input_margin_left + $edit_area_room_input_width + $edit_area_room_width_overheads;
$edit_area_room_form_width        = number_format($edit_area_room_form_width, 1, '.', '');   // get rid of any commas
      
?>
form.form_edit_area_room {
    position: relative; width: <?php echo $edit_area_room_form_width ?>em;
    margin-top: 2em; margin-bottom: 2em; margin-left: auto; margin-right: auto
}
.form_edit_area_room label {
    display: block; float: left; clear: left; min-height: 2.0em;
    width: <?php echo $edit_area_room_label_width ?>em; text-align: right
}
.form_edit_area_room input {
    display: block; position: relative; float: right; clear: right; 
    width: <?php echo $edit_area_room_input_width ?>em; 
    margin-top: -0.2em; margin-left: <?php echo $edit_area_room_input_margin_left ?>em
}
.form_edit_area_room .submit_buttons input {width: auto; clear: none; margin-top: 1.2em; margin-left: 1.0em}
.form_edit_area_room span.error {display: block; width: 100%; margin-bottom: 0.5em}
.form_edit_area_room div {float: left; clear: left; width: 100%}


/* ------------ FORM_GENERAL ------------------------*/
/*                                                   */
/*   used in EDIT_ENTRY.PHP, REPORT.PHP              */
/*   and SEARCH.PHP                                  */

<?php
// Common to all forms in the class "form_general"
$general_label_height          = '1.0';     // em
$general_left_col_width        = '20';      // %
$general_right_col_width       = '79';      // %  (79 to avoid rounding problems)
$general_gap                   = '1.0';     // em  (gap between left and right columns)

// Specific to the "edit_entry" form
$edit_entry_left_col_max_width = '10';      // em
$edit_entry_textarea_width     = '26';      // em
$edit_entry_ampm_width         = '16';      // em
$edit_entry_form_min_width     = $edit_entry_left_col_max_width + $edit_entry_textarea_width + $general_gap;
$edit_entry_form_min_width     = number_format($edit_entry_form_min_width, 1, '.', '');   // get rid of any commas

// Specific to the "report" form
$report_left_col_max_width     = '12';      // em
$report_input_width            = '12';      // em
$report_form_min_width         = $report_left_col_max_width + $report_input_width + $general_gap;
$report_form_min_width         = number_format($report_form_min_width, 1, '.', '');   // get rid of any commas

// Specific to the "search" form
$search_left_col_max_width     = '8';       // em
$search_input_width            = '12';      // em
$search_form_min_width         = $search_left_col_max_width + $search_input_width + $general_gap;
$search_form_min_width         = number_format($search_form_min_width, 1, '.', '');   // get rid of any commas

// Specific to the "logon" form
$logon_left_col_max_width      = '8';       // em
$logon_input_width             = '12';      // em
$logon_form_min_width          = $logon_left_col_max_width + $logon_input_width + $general_gap;
$logon_form_min_width          = number_format($logon_form_min_width, 1, '.', '');   // get rid of any commas
?>
form.form_general {margin-top: 2.0em; width: 100%}
.edit_entry form.form_general {min-width: <?php echo $edit_entry_form_min_width ?>em}
.report     form.form_general {min-width: <?php echo $report_form_min_width ?>em}
.search     form.form_general {min-width: <?php echo $search_form_min_width ?>em}
form.form_general#logon       {min-width: <?php echo $logon_form_min_width ?>em}

.form_general div {float: left; clear: left; width: 100%}
.form_general div div {float: none; clear: none; width: auto}
.form_general div.group {float: left; width: <?php echo $general_right_col_width ?>%}
.form_general div.group#ampm {width: <?php echo $edit_entry_ampm_width ?>em}
.form_general fieldset {width: auto; border: 0; padding-top: 2.0em}

.form_general label {
    display: block; float: left; overflow: hidden;
    min-height: <?php echo $general_label_height ?>em; 
    width: <?php echo $general_left_col_width ?>%; 
    text-align: right; padding-bottom: 0.8em; font-weight: bold;
}

.edit_entry .form_general label {max-width: <?php echo $edit_entry_left_col_max_width ?>em}
.report     .form_general label {max-width: <?php echo $report_left_col_max_width ?>em}
.search     .form_general label {max-width: <?php echo $search_left_col_max_width ?>em}
#logon                    label {max-width: <?php echo $logon_left_col_max_width ?>em}

.form_general .group      label {clear: none; width: auto; max-width: 100%; font-weight: normal; overflow: visible}

.form_general input {
    display: block; float: left; margin-left: <?php echo $general_gap ?>em; 
    font-family: <?php echo $standard_font_family ?>; font-size: small
}
.edit_entry .form_general input {width: <?php echo $edit_entry_textarea_width ?>em}
.report     .form_general input {width: <?php echo $report_input_width ?>em}
.search     .form_general input {width: <?php echo $search_input_width ?>em}
#logon                    input {width: <?php echo $logon_input_width ?>em}
.form_general .group      input {clear: none; width: auto}

/* font family and size needs to be the same for input and textarea as their widths are defined in ems */
.form_general textarea {
    display: block; float: left; 
    width: <?php echo $edit_entry_textarea_width ?>em; height: 11em; 
    margin-left: <?php echo $general_gap ?>em; margin-bottom: 0.5em;
    font-family: <?php echo $standard_font_family ?>; font-size: small
}
.form_general select {float: left; margin-left: <?php echo $general_gap ?>em; margin-right: -0.5em; margin-bottom: 0.5em}
.form_general input.radio {margin-top: 0.1em}
.form_general input.checkbox {margin-top: 0.1em}
.form_general input.submit {display: block; width: auto; float: left; clear: left; margin-top: 1.0em}

div#edit_entry_submit {width: <?php echo $general_left_col_width ?>%; max-width: <?php echo $edit_entry_left_col_max_width ?>em}
div#report_submit     {width: <?php echo $general_left_col_width ?>%; max-width: <?php echo $report_left_col_max_width ?>em}
div#search_submit     {width: <?php echo $general_left_col_width ?>%; max-width: <?php echo $search_left_col_max_width ?>em}
div#logon_submit      {width: <?php echo $general_left_col_width ?>%; max-width: <?php echo $logon_left_col_max_width ?>em}
#edit_entry_submit input, #report_submit input, #search_submit input, #logon_submit input
    {position: relative; left: 100%; width: auto}

.form_general #div_time input {width: 2.0em}
.form_general #div_time input#time_hour {text-align: right}
.form_general #div_time input#time_minute {text-align: left; margin-left: 0}
.form_general #div_time span + input {margin-left: 0}
.form_general #div_time span {display: block; float: left; width: 0.5em; text-align: center}
.form_general input#duration {width: 2.0em; text-align: right}
.form_general select#dur_units {margin-right: 1.0em}
.form_general div#ad {float: left}
.form_general #ad label {clear: none; text-align: left; font-weight: normal}
.form_general input#all_day {width: auto; margin-left: 1.0em; margin-right: 0.5em}
.form_general #div_rooms select, .form_general #div_typematch select {float: left; margin-right: 2.0em}
.form_general fieldset#rep_info {padding-top: 0}
.form_general #rep_info input {width: 13em}
.form_general input#rep_num_weeks {width: 2.0em}


/* ------------ EDIT_USERS.PHP ------------------*/
<?php
$edit_users_label_height     = '2.0';    // em
$edit_users_label_width      = '10.0';   // em
$edit_users_gap              = '1.0';    // em
$edit_users_input_width      = '10.0';   // em
$edit_users_form_width       = $edit_users_label_width + $edit_users_gap + $edit_users_input_width + 5;
$edit_users_form_width       = number_format($edit_users_form_width, 1, '.', '');   // get rid of any commas
// This CSS works by using absolute positioning to bring the Delete button up into the main form.
// Logically the HTML for the Delete button is implemented and because you can't nest a form within
// a form it appears as a second form after the main form.    However, to the user it is more logical to
// see it within the main form, which we achieve through CSS.    [Actually it would probably be better
// to have the Delete button in a column on the User List page, just like the Edit button is.  However
// if you put it there you probably also need a confirmation screen, otherwise it is too easy to delete
// users by mistake.    Having it on the edit form at least means that you have to press two buttons to
// delete a user (the Edit button followed by the Delete button)]
?>
div#form_container {position: relative; float: left}    /* this is the containing block against which the absolute positioning works */
#form_container input.submit {width: auto; position: absolute; bottom: 2.0em}  /* bring both buttons up          */
form#form_edit_users {width: <?php echo $edit_users_form_width ?>em; margin-top: 2.0em}
#form_edit_users fieldset {width: auto}  
#form_edit_users div {float: left; width: 100%}
#form_edit_users div#edit_users_input_container {padding-bottom: 4.0em}    /* padding-bottom leaves room for the submit buttons. */
                                                                           /* Apply it to the div because applying it to the     */
                                                                           /* fieldset does not work in all browsers (eg Safari) */
#form_edit_users label{
    display: block; float: left;
    min-height: <?php echo $edit_users_label_height ?>em; 
    width: <?php echo $edit_users_label_width ?>em;  
    text-align: right;
}
#form_edit_users input {
    display: block; float: left;
    width: <?php echo $edit_users_input_width ?>em; 
    margin-left: <?php echo $edit_users_gap ?>em; 
}
#form_edit_users input.submit {right: 2.0em}                                   /* and put the OK on the right     */
#form_delete_users input.submit {left: 2.0em}                                  /* and put the Delete on the left */
form.edit_users_error {width: 10em; margin-top: 2.0em}
table#edit_users_list {margin-top: 1.0em; margin-bottom: 1.0em}

/* ------------ FUNCTIONS.INC -------------------*/
#logon_box a {display: block; width: 100%; padding-top: 0.3em; padding-bottom: 0.3em}
table#banner {width: 100%; border-spacing: 0; border-collapse: collapse;
    border-color: <?php echo $banner_border_color ?>; border-style: solid;
    border-top-width: 0; border-right-width: 0; border-bottom-width: 0; border-left-width: 0}
#banner td {text-align: center; vertical-align: middle; background-color: <?php echo $banner_back_color ?>;
    border-color: <?php echo $banner_border_color ?>; border-style: solid;
    border-top-width: 0; border-right-width: 0; border-bottom-width: 0; border-left-width: 1px;
    padding: 6px}
#banner td:first-child {border-left-width: 0}
#banner td#company {font-size: large}
#banner a:link    {color: <?php echo $anchor_link_color_banner ?>;    text-decoration: none; font-weight: normal}
#banner a:visited {color: <?php echo $anchor_visited_color_banner ?>; text-decoration: none; font-weight: normal}
#banner a:hover   {color: <?php echo $anchor_hover_color_banner ?>;   text-decoration:underline; font-weight: normal}
#banner #company span {display: block; color: <?php echo $company_name_color ?>; width: 100%}
table#colour_key {clear: both; border-spacing: 0; border-collapse: collapse}
#colour_key td {width: 7.0em; padding: 2px; font-weight: bold;
    border: <?php echo $table_dwm_main_border_width ?>px solid <?php echo $main_table_body_h_border_color ?>}
#colour_key td#row_padding {border-right: 0; border-bottom: 0}
#header_search input {width: 6.0em}

/* ------------ HELP.PHP ------------------------*/
table#version_info {border-spacing: 0; border-collapse: collapse}
#version_info td {padding: 0 1.0em 0 0; vertical-align: bottom}
#version_info td:first-child {text-align: right}

/* ------------ MINCALS.PHP ---------------------*/
div#cals {float: right}
div#cal_last {float: left}
div#cal_this {float: left; margin-left: 1.0em}
div#cal_next {float: left; margin-left: 1.0em}

table.calendar {border-spacing: 0; border-collapse: collapse}
.calendar th {min-width: 2.0em; text-align: center; font-weight: normal; background-color: transparent; color: <?php echo $standard_font_color ?>}
.calendar td {text-align: center; font-size: x-small}
.calendar a.current {font-weight: bold; color: <?php echo $highlight_font_color ?>}
td#sticky_day {border: 1px dotted <?php echo $highlight_font_color ?>}


/* ------------ REPORT.PHP ----------------------*/
.div_report h2, #div_summary h1 {border-top: 2px solid <?php echo $report_h2_border_color ?>;
    padding-top: 0.5em; margin-top: 2.0em}
.div_report h3 {border-top: 1px solid <?php echo $report_h3_border_color ?>;
    padding-top: 0.5em; margin-bottom: 0}
.div_report table {clear: both; width: 100%; margin-top: 0.5em}
.div_report col.col1 {width: 8em}
.div_report td:first-child {text-align: right; font-weight: bold}
.div_report .createdby td, .div_report .lastupdate td {font-size: x-small}
div.report_entry_title {width: 100%; float: left;
    border-top: 1px solid <?php echo $report_entry_border_color ?>; margin-top: 0.8em}
div.report_entry_name  {width: 40%;  float: left; font-weight: bold}
div.report_entry_when  {width: 60%;  float: right; text-align: right}
#div_summary table {border-spacing: 1px; border-collapse: collapse;
    border-color: <?php echo $report_table_border_color ?>; border-style: solid;
    border-top-width: 1px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 1px}
#div_summary td, #div_summary th {padding: 0.1em 0.2em 0.1em 0.2em;
    border-color: <?php echo $report_table_border_color ?>; border-style: solid;
    border-top-width: 0; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 0}
#div_summary th {background-color: transparent; font-weight: bold; text-align: center}
#div_summary td {text-align: right}
#div_summary td.count {border-right-width: 0}
#div_summary td:first-child {font-weight: bold}
p.report_entries {font-weight: bold}

/* ------------ SEARCH.PHP ----------------------*/
span#search_str {color: <?php echo $highlight_font_color ?>}
p#nothing_found {font-weight: bold}
div#record_numbers {font-weight: bold}
div#record_nav {font-weight: bold; margin-bottom: 1.0em}
table#search_results {border-spacing: 1px; border-collapse: collapse}
#search_results td, #search_results th {border: 1px solid <?php echo $search_table_border_color ?>; padding: 0.1em 0.2em 0.1em 0.2em}

/* ------------ SITE_FAQ ------------------------*/
.help q {font-style: italic}
.help dfn {font-style: normal; font-weight: bold}
#site_faq_contents li a {text-decoration: underline}
div#site_faq_body {margin-top: 2.0em}
#site_faq_body h4 {border-top: 1px solid <?php echo $site_faq_entry_border_color ?>; padding-top: 0.5em; margin-top: 0} 
#site_faq_body div {padding-bottom: 0.5em}
#site_faq_body :target {background-color: <?php echo $help_highlight_color ?>}


/* ------------ TRAILER.INC ---------------------*/
div#trailer {border-top: 1px solid <?php echo $trailer_border_color ?>; 
             border-bottom: 1px solid <?php echo $trailer_border_color ?>; 
             float: left; width: 100%; 
             margin-top: 1.5em; margin-bottom: 1.5em;
             padding: 0.3em 0 0.3em 0}
#trailer div {float: left; width: 100%}
#trailer div.trailer_label {float: left; clear: left; width: 20%; max-width: 9.0em; font-weight: bold}
#trailer div.trailer_links {float: left;              width: 79%}  /* 79 to avoid rounding problems */
.trailer_label span {margin-right: 1.0em}

#trailer span.current {font-weight: bold}
#trailer .current a {color: <?php echo $highlight_font_color ?>}

div#simple_trailer {width: 100%; text-align: center; padding-top: 1.0em; padding-bottom: 2.0em}
#simple_trailer a {padding: 0 1.0em 0 1.0em}


/* ------------ VIEW_ENTRY.PHP ------------------*/
.view_entry #entry td:first-child {text-align: right; font-weight: bold; padding-right: 1.0em}
.view_entry div#view_entry_nav {margin-top: 1.0em}
