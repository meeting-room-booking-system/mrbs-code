<?php 

// $Id$

require_once "systemdefaults.inc.php";
require_once "config.inc.php";
require_once "functions.inc";
require_once "theme.inc";

header("Content-type: text/css"); 
expires_header(60*30); // 30 minute expiry
                                
// IMPORTANT *************************************************************************************************
// In order to avoid problems in locales where the decimal point is represented as a comma, it is important to
//   (1) specify all PHP length variables as strings, eg $border_width = '1.5'; and not $border_width = 1.5;
//   (2) convert PHP variables after arithmetic using number_format
// ***********************************************************************************************************
                                
?>


/* ------------ GENERAL -----------------------------*/

body {font-size: small;
    color:            <?php echo $standard_font_color ?>;
    font-family:      <?php echo $standard_font_family ?>;
    background-color: <?php echo $body_background_color ?>}

.current {color: <?php echo $highlight_font_color ?>}                        /* used to highlight the current item */
.error   {color: <?php echo $highlight_font_color ?>; font-weight: bold}     /* for error messages */
.warning {color: <?php echo $highlight_font_color ?>}                        /* for warning messages */
.note    {font-style: italic}

h1 {font-size: x-large; clear: both}
h2 {font-size: large; clear: both}

img {border: 0}

a:link    {color: <?php echo $anchor_link_color ?>;    text-decoration: none; font-weight: bold}
a:visited {color: <?php echo $anchor_visited_color ?>; text-decoration: none; font-weight: bold}
a:hover   {color: <?php echo $anchor_hover_color ?>;   text-decoration: underline; font-weight: bold} 

tr.even_row {background-color: <?php echo $row_even_color ?>}
tr.odd_row {background-color: <?php echo $row_odd_color ?>}

td, th {vertical-align: top}

td form {margin: 0}     /* Prevent IE from displaying margins around forms in tables. */

legend {font-weight: bold; font-size: large;
    font-family: <?php echo $standard_font_family ?>;
    color: <?php echo $standard_font_color ?>}
fieldset {margin: 0; padding: 0; border: 0; 
    border-radius: 8px;
    -moz-border-radius: 8px;
    -webkit-border-radius: 8px}
fieldset.admin {width: 100%; padding: 0 1.0em 1.0em 1.0em;
    border: 1px solid <?php echo $admin_table_border_color ?>}
fieldset fieldset {position: relative; clear: left; width: 100%; padding: 0; border: 0; margin: 0}  /* inner fieldsets are invisible */
fieldset fieldset legend {font-size: 0}        /* for IE: even if there is no legend text, IE allocates space  */
  
.naked {margin: 0; padding: 0; border-width: 0} /* Invisible tables used for internal needs */
table.naked {width: 100%; height: 100%}
table:hover.naked {cursor: pointer}   /* set cursor to pointer; if you don't it doesn't show up when show_plus_link is false */

table.admin_table {border-spacing: 0px; border-collapse: collapse; border-color: <?php echo $admin_table_border_color ?>; border-style: solid;
    border-top-width: 0; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 0}
.admin_table th, .admin_table td {vertical-align: middle; text-align: left;
    padding: 0.1em 0.5em 0.1em 0.5em;
    border-top-width: 0; border-right-width: 0; border-bottom-width: 0; border-left-width: 1px; border-style: solid;}
.admin_table th {color: <?php echo $admin_table_header_font_color ?>; 
    background-color: <?php echo $admin_table_header_back_color ?>}
.admin_table td, .admin_table th {border-color: <?php echo $admin_table_border_color ?>}
.admin_table th:first-child {border-left-color: <?php echo $admin_table_header_back_color ?>}
.admin_table td.action {text-align: center}
.admin_table td.action div {display: inline-block}
.admin_table td.action div div {display: table-cell} 

select.room_area_select {margin-right: 0.5em}

/* ------------ ADMIN.PHP ---------------------------*/
<?php
// Adjust the label width to suit the longest label - it will depend on the translation being used
// The input width can normally be left alone
$admin_form_label_width       = '7.0';   // em
$admin_form_gap               = '1.0';   // em
$admin_form_input_width       = '10.5';   // em   (Also used in edit_area_room.php)

?>
form.form_admin {float: left; clear: left; margin: 2em 0 0 0}
.form_admin fieldset {float: left; width: auto; border: 1px solid <?php echo $admin_table_border_color ?>; padding: 1em}
.form_admin legend {font-size: small}
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
.admin h2 {clear: left}
div#area_form, div#room_form {float: left; padding: 0 0 2em 1em}
div#area_form {width: auto}
div#room_form {width: 95%}
div#custom_html {float: left; padding: 0 0 3em 1em}
#area_form form {float: left; margin-right: 1em}
#area_form label#area_label {display: block; float: left; font-weight: bold; margin-right: <?php echo $admin_form_gap ?>em}
#areaChangeForm select {display: block; float: left; margin: -0.1em 1.5em 0 0}
#areaChangeForm input {float: left; margin: -0.2em 0.5em 0 0}
#areaChangeForm input.button {display: block; float: left; margin: 0 0.7em}

div.header_columns, div.body_columns {position: relative; float: left; overflow-x: scroll; overflow-y: hidden}
div.header_columns {max-width: 20%}
div.body_columns {max-width: 80%}

.body_columns .admin_table th:first-child {border-left-color: <?php echo $admin_table_border_color ?>}


/* ------------ DAY/WEEK/MONTH.PHP ------------------*/

<?php
$column_hidden_width  = 0;       // (%) width of the column for hidden days (set to 0 for no column at all; 1 for a narrow column);
                                 //     when $times_along_top is TRUE, hidden days (rows) are not shown at all
$column_row_labels_width   = 1;  // (%) width of the row label columns (will expand if necessary)

// week view:  work out what percentage of the width is available to
// normal columns (ie columns that are not hidden)
$n_hidden_days = count($hidden_days);
$column_week = 100 - $column_row_labels_width;                // subtract the width of the left hand column
if ($row_labels_both_sides)
{
  $column_week -= $column_row_labels_width;                   // and the right hand column if present
}
$column_week -= ($column_hidden_width * $n_hidden_days); // subtract the width of the hidden columns
if ($n_hidden_days < 7)                                  // (avoid the div by zero)
{
  $column_week = $column_week/(7 - $n_hidden_days);      // divide what's left between the number of days to display
}
$column_week = number_format($column_week, 1, '.', '');  // (%) tidy the number up and make sure it's valid for CSS (no commas)

// month view:  work out what percentage of the width is available to
// normal columns (ie columns that are not hidden)
$column_month = 100 - ($column_hidden_width *  $n_hidden_days);
if ($n_hidden_days < 7)                                  // (avoid the div by zero)
{
  $column_month = $column_month/(7 - $n_hidden_days);      // divide what's left between the number of days to display
}
$column_month = number_format($column_month, 1, '.', '');  // (%) tidy the number up and make sure it's valid for CSS (no commas)

?>
div#dwm_header {width: 100%; float: left; margin-top: 1.0em; margin-bottom: 0.5em}
div#dwm_areas  {float: left; margin-right: 2.0em}
div#dwm_rooms  {float: left; margin-right: 2.0em}
#dwm_header h3 {font-size: small; font-weight: normal; text-decoration: underline; 
    margin-top: 0; margin-bottom: 0.5em; padding-bottom: 0}
#dwm_header ul {list-style-type: none; padding-left: 0; margin-left: 0; margin-top: 0}
#dwm_header li {padding-left: 0; margin-left: 0}

div#dwm {margin-bottom: 0.5em}
#dwm {text-align: center}
#dwm h2 {margin-bottom: 0}
#dwm div.timezone {opacity: 0.8}

div.date_nav    {float: left;  width: 100%; margin-top: 0.5em; margin-bottom: 0.5em; font-weight: bold}
div.date_before {float: left;  width: 33%; text-align: left}
div.date_now    {float: left;  width: 33%; text-align: center}
div.date_after  {float: right; width: 33%; text-align: right}

table.dwm_main {clear: both; width: 100%; border-spacing: 0; border-collapse: separate;
    border-color: <?php echo $main_table_border_color ?>;
    border-width: <?php echo $main_table_border_width ?>px;
    border-style: solid}
.dwm_main td {padding: 0;
    border-top:  <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_body_h_border_color ?>;
    border-left: <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_body_v_border_color ?>;
    border-bottom: 0;
    border-right: 0}
.dwm_main td:first-child {border-left: 0}
.dwm_main th {font-size: small; font-weight: normal; vertical-align: top; padding: 0 2px;
    color: <?php echo $header_font_color ?>; 
    background-color: <?php echo $header_back_color ?>;
    border-left: <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_header_border_color ?>}
.dwm_main th:first-child {border-left: 0}
.dwm_main a {display: block; min-height: inherit}
.dwm_main tbody a {padding: 0 2px}
.dwm_main th a:link    {color: <?php echo $anchor_link_color_header ?>;    text-decoration: none; font-weight: normal}
.dwm_main th a:visited {color: <?php echo $anchor_visited_color_header ?>; text-decoration: none; font-weight: normal}
.dwm_main th a:hover   {color: <?php echo $anchor_hover_color_header ?>;   text-decoration:underline; font-weight: normal}

.dwm_main#day_main th.first_last {width: <?php echo $column_row_labels_width ?>%}
.dwm_main#week_main th {width: <?php echo $column_week ?>%}
.dwm_main#week_main th.first_last {width: <?php echo $column_row_labels_width ?>%; vertical-align: bottom}
.dwm_main#month_main th {width: <?php echo $column_month ?>%}
.dwm_main#month_main td {border-top:  <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_body_v_border_color ?>}
.dwm_main#month_main td.valid   {background-color: <?php echo $main_table_month_color ?>}
.dwm_main#month_main td.invalid {background-color: <?php echo $main_table_month_invalid_color ?>}
.dwm_main#month_main a {height: 100%; width: 100%; padding: 0 2px 0 2px}

a.new_booking {display: block; font-size: medium; text-align: center}
.new_booking img {margin: auto; padding: 4px 0 2px 0}
img.repeat_symbol {float: right; padding: 3px}
.dwm_main#month_main img.repeat_symbol {padding: 2px}

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

.dwm_main#week_main th.hidden_day, .dwm_main#month_main th.hidden_day     
    {width: <?php echo $column_hidden_width ?>%; 
    <?php 
      echo (empty($column_hidden_width) ? " display: none" : ""); // if the width is set to zero, then don't display anything at all
    ?>
    }
td.hidden_day     {background-color: <?php echo $column_hidden_color ?>; /* hidden columns (eg weekends) in the week and month views */
    font-size: medium; font-weight: bold;
    border-top: <?php echo $main_table_cell_border_width ?>px solid <?php echo $column_hidden_color ?>;
    <?php 
      echo (empty($column_hidden_width) ? " display: none" : ""); // if the width is set to zero, then don't display anything at all
    ?>
    }
td.row_highlight  {background-color: <?php echo $row_highlight_color ?>} /* used for highlighting a row */
td.even_row       {background-color: <?php echo $row_even_color ?>}      /* even rows in the day view */
td.odd_row        {background-color: <?php echo $row_odd_color ?>}       /* odd rows in the day view */
td.row_labels     {background-color: <?php echo $main_table_labels_back_color ?>; white-space: nowrap}    /* used for the row labels column */
.row_labels a:link    {color: <?php echo $anchor_link_color_header ?>;    text-decoration: none; font-weight: normal}
.row_labels a:visited {color: <?php echo $anchor_visited_color_header ?>; text-decoration: none; font-weight: normal}
.row_labels a:hover   {color: <?php echo $anchor_hover_color_header ?>;   text-decoration:underline; font-weight: normal}

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
// The first two rules (both on the same line) highlight the cell that you are actually hovering over.
// 
// The next two disable this behaviour for multiple booking cells.   That's because we don't want the highlight colour showing
// through if one or more of the bookings are using an opacity setting of less than 1, eg if one of the bookings is a private
// booking.  This does not happen on the normal cells because they don't have a class of odd_row/even_row.   However we still need
// the odd_row/even_row classes for the multiple booking cells because we may need to display the odd/even row background - for
// example if you have a cell with two multiple bookings in it and another cell in the row has more than two.
//
// The fifth rule highlights the cell in the left-hand (and right-hand if present) column that shows the time/period for that row.
//
// The sixth rule highlights the cell being hovered over in the month view.
//
// Note that the first two rules only highlight empty cells in the day and week views.    They will not highlight 
// actual bookings (because they have a class other than odd_row or even_row), the header cells (because they 
// are <th> and not <td>) nor the empty cells in the month view (because odd_row and even_row are not used 
// in the month view).   However the fifth rule does have the useful effect of highlighting the time slot that
// corresponds to the start of a booking when you hover over a booked cell.    The sixth rule provides highlighting in the month view.
?>
.dwm_main td:hover.odd_row, .dwm_main td:hover.even_row {background-color: <?php echo $row_highlight_color ?>}
.dwm_main td:hover.multiple_booking.odd_row {background-color: <?php echo $row_odd_color ?>}
.dwm_main td:hover.multiple_booking.even_row {background-color: <?php echo $row_even_color ?>}
.dwm_main tr:hover td.row_labels {background-color: <?php echo $row_highlight_color ?>; color: <?php echo $standard_font_color ?>}
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
.dwm_main tbody tr:hover a:link    {color: <?php echo $anchor_link_color ?>}   /* used for CSS highlighting (but will also be used in JavaScript highlighting */
.dwm_main tbody tr:hover a:visited {color: <?php echo $anchor_link_color ?>}   /* used for CSS highlighting (but will also be used in JavaScript highlighting */
.month .highlight a:link    {font-weight: bold}
.month .highlight a:visited {font-weight: bold}


<?php

/* SLOTS CLASSES

The next section generates the slot classes (i.e. slots1, slots2, etc.).   We need
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

(Although the style information could be put in an inline style declaration, this would mean that every
cell in the display would carry the extra size of the style declaration, whereas the classes here mean
that we only need the style declaration for each row.) 

In the classes below
- slotsN is the class for displaying a booking N slots long
- height is the height of N slots (ie N * $main_cell_height)
- you need to specify max-height so that clipping works correctly in Firefox
- you need to specify height so that clipping works correctly in IE and also
  to force min-height to work correctly in IE
- you need to specify min-height to force the box to be the min-height in
  IE (Firefox works OK without min-height)

*/


// work out how many classes we'll need.   If we're transposing the table then we'll only need one, since all
// cells are the same height (it's the width that varies, controlled by the colspan attribute).   For a normal
// table we'll need at least as many as we've got slots, since a booking could span as many as all the slots
// (in this case controlled by a rowspan).
$classes_required = ($times_along_top) ? 1 : $max_slots;
for ($i=1; $i<=$classes_required; $i++) 
{
  $div_height = $main_cell_height * $i;
  $div_height = $div_height + (($i-1)*$main_table_cell_border_width);
  $div_height = (int) $div_height;    // Make absolutely sure it's an int to avoid generating invalid CSS
  
  $rule = "div.slots" . $i . " {min-height: " . $div_height . "px";
  if ($clipped)
  {
    $rule .= "; max-height: " . $div_height . "px"; 
    $rule .= "; height: "     . $div_height . "px";
  }
  $rule .= "}";
  echo $rule . "\n";
}

?>
div.celldiv {overflow: hidden; margin: 0; padding: 0}
.row_labels div.celldiv {overflow: visible}  /* we want to see the content in the row label columns */
<?php


// Multiple bookings.  These rules control the styling of the cells and controls when there is more than
// one booking in a time slot.
?>
div.mini, div.maxi {position: relative}     /* establish a relative position for the absolute position to follow */
div.multiple_control {
    display: none;       /* will be over-ridden by JavaScript if enabled */
    position: absolute; z-index: 20;
    width: <?php echo $main_cell_height ?>px;
    text-align: center;
    padding: 0;
    border-right: <?php echo $main_table_cell_border_width . "px solid " . $main_table_body_v_border_color ?>;
    background-color: <?php echo $multiple_control_color ?>}
.mini div.multiple_control {                /* heights for maxi are set using in-line styles */
    height: <?php echo $main_cell_height ?>px;
    max-height: <?php echo $main_cell_height ?>px;
    min-height: <?php echo $main_cell_height ?>px}
div:hover.multiple_control {cursor: pointer}
.multiple_booking table {height: 100%; width: 100%; border-spacing: 0; border-collapse: collapse}
.multiple_booking td {border-left: 0}

/* used for toggling multiple bookings from mini to maxi size */
.maximized div.mini {display: none}
.maximized div.maxi {display: block}
.minimized div.mini {display: block}
.minimized div.maxi {display: none}

/* booking privacy status */
.private {opacity: 0.6; font-style: italic}

/* booking approval status */
.awaiting_approval {opacity: 0.6}
.awaiting_approval a:before {content: "? "}

/* booking confirmation status */
.tentative {opacity: 0.6}
.tentative a {font-weight: normal}


/* ------------ DEL.PHP -----------------------------*/
div#del_room_confirm {padding-bottom: 3em}
#del_room_confirm p {text-align: center; font-size: large; font-weight: bold}
div#del_room_confirm_links {position: relative; margin-left: auto; margin-right: auto}
span#del_yes {display:block; position: absolute; right: 50%; margin-right: 1em; font-size: large}
span#del_no  {display:block; position: absolute; left: 50%; margin-left: 1em; font-size: large}
#del_room_confirm_links a:hover {cursor: pointer}                  /* for IE */
#del_room_confirm_links span:hover {text-decoration: underline}    /* for Firefox */


/* ------------ EDIT_AREA_ROOM.PHP ------------------*/
.edit_area_room .form_general fieldset fieldset {padding-top: 0.5em; padding-bottom: 0.5em}
.edit_area_room .form_general fieldset fieldset legend {font-size: small; font-style: italic; font-weight: normal}
.edit_area_room fieldset#time_settings {padding:0; margin: 0}
span#private_display_caution {display: block; margin-top: 1em; font-style: italic; font-weight: normal}
#book_ahead_periods_note span {display: block; float: left; width: 24em; margin: 0 0 1em 1em; font-style: italic}
.edit_area_room .form_general textarea {height: 6em; width: 25em}
.edit_area_room div#custom_html {margin-top: 8px}


/* ------------ FORM_GENERAL ------------------------*/
/*                                                   */
/*   used in EDIT_ENTRY.PHP, REPORT.PHP,             */
/*   SEARCH.PHP and EDIT_AREA_ROOM.PHP               */

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

// Specific to the "db_logon" form
$db_logon_left_col_max_width   = '12';      // em
$db_logon_input_width          = '12';      // em
$db_logon_form_min_width       = $db_logon_left_col_max_width + $db_logon_input_width + $general_gap;
$db_logon_form_min_width       = number_format($db_logon_form_min_width, 1, '.', '');   // get rid of any commas

// Specific to the "edit_area_room" form
$edit_area_room_left_col_width      = '15';      // em
$edit_area_room_left_col_max_width  = '30';      // em
$edit_area_room_input_width         = '12';      // em
$edit_area_room_form_min_width      = $edit_area_room_left_col_width + $edit_area_room_input_width + $general_gap;
$edit_area_room_form_min_width      = number_format($edit_area_room_form_min_width, 1, '.', '');   // get rid of any commas


?>
form.form_general {margin-top: 2.0em; width: 100%}
.edit_entry     form.form_general {min-width: <?php echo $edit_entry_form_min_width ?>em}
.report         form.form_general {min-width: <?php echo $report_form_min_width ?>em}
.search         form.form_general {min-width: <?php echo $search_form_min_width ?>em}
.edit_area_room form.form_general {min-width: <?php echo $edit_area_room_form_min_width ?>em}
form.form_general#logon       {min-width: <?php echo $logon_form_min_width ?>em}
form.form_general#db_logon    {min-width: <?php echo $db_logon_form_min_width ?>em}
form#edit_room {float: left; width: auto; margin: 0 2em 1em 1em}

.form_general div {float: left; clear: left; width: 100%}
.form_general div div {float: none; clear: none; width: auto}
.form_general div.group {float: left}
.form_general div.group_container {float: left}
.form_general .group_container div.group {clear: left}
.form_general div.group.ampm {width: <?php echo $edit_entry_ampm_width ?>em}
.edit_area_room div.group {clear: none; width: auto}
.edit_area_room div.group#private_override div {clear: left}
.form_general fieldset {width: auto; border: 0; padding-top: 2.0em}
#edit_room fieldset {width: 100%; float: left; padding: 0; margin: 0}
#edit_room fieldset.submit_buttons {margin-top: 1em}

.form_general label {
    display: block; float: left; overflow: hidden;
    min-height: <?php echo $general_label_height ?>em; 
    width: <?php echo $general_left_col_width ?>%; 
    text-align: right; padding-bottom: 0.8em; font-weight: bold;
}

.edit_entry     .form_general label {max-width: <?php echo $edit_entry_left_col_max_width ?>em}
.report         .form_general label {max-width: <?php echo $report_left_col_max_width ?>em}
.search         .form_general label {max-width: <?php echo $search_left_col_max_width ?>em}
.edit_area_room .form_general label {max-width: <?php echo $edit_area_room_left_col_max_width ?>em; width: <?php echo $edit_area_room_left_col_width ?>em}
#logon                    label {max-width: <?php echo $logon_left_col_max_width ?>em}
#db_logon                 label {max-width: <?php echo $db_logon_left_col_max_width ?>em}

.form_general .group      label {clear: none; width: auto; max-width: 100%; font-weight: normal; overflow: visible; text-align: left}

.form_general input {
    display: block; float: left; margin-left: <?php echo $general_gap ?>em; 
    font-family: <?php echo $standard_font_family ?>; font-size: small
}
.edit_entry     .form_general input {width: <?php echo $edit_entry_textarea_width ?>em}
.report         .form_general input {width: <?php echo $report_input_width ?>em}
.search         .form_general input {width: <?php echo $search_input_width ?>em}
.edit_area_room .form_general input {width: <?php echo $edit_area_room_input_width ?>em}
#logon                    input {width: <?php echo $logon_input_width ?>em}
#db_logon                 input {width: <?php echo $db_logon_input_width ?>em}
.form_general .group      input {clear: none; width: auto}
.form_general input.date {width: 6em}

/* font family and size needs to be the same for input and textarea as their widths are defined in ems */
.form_general textarea {
    display: block; float: left; 
    width: <?php echo $edit_entry_textarea_width ?>em; height: 11em; 
    margin-left: <?php echo $general_gap ?>em; margin-bottom: 0.5em;
    font-family: <?php echo $standard_font_family ?>; font-size: small
}
.form_general select {float: left; margin-left: <?php echo $general_gap ?>em; margin-right: -0.5em; margin-bottom: 0.5em}
.form_general input.radio {margin-top: 0.1em; margin-right: 2px}
.form_general input.checkbox {width: auto; margin-top: 0.2em}
.edit_area_room .form_general input.checkbox {margin-left: <?php echo $general_gap ?>em}
.edit_area_room .form_general #booking_policies input.text {width: 2.0em}
.form_general input.submit {display: block; width: auto; float: left; clear: left; margin-top: 1.0em}

div#edit_entry_submit {width: <?php echo $general_left_col_width ?>%; max-width: <?php echo $edit_entry_left_col_max_width ?>em}
div#report_submit     {width: <?php echo $general_left_col_width ?>%; max-width: <?php echo $report_left_col_max_width ?>em}
div#search_submit     {width: <?php echo $general_left_col_width ?>%; max-width: <?php echo $search_left_col_max_width ?>em}
div#logon_submit      {width: <?php echo $general_left_col_width ?>%; max-width: <?php echo $logon_left_col_max_width ?>em}
div#db_logon_submit   {width: <?php echo $general_left_col_width ?>%; max-width: <?php echo $db_logon_left_col_max_width ?>em}
#edit_entry_submit input, #report_submit input, #search_submit input, #logon_submit input, #db_logon_submit input
    {position: relative; left: 100%; width: auto}
div#edit_area_room_submit_back {float: left; width: <?php echo $edit_area_room_left_col_width ?>em; max-width: <?php echo $edit_area_room_left_col_max_width ?>em}
div#edit_area_room_submit_save {float: left; clear: none; width: auto}
#edit_area_room_submit_back input {float: right}

.form_general .div_dur_mins input{width: 4.0em}
.form_general .div_time input {width: 2.0em}
.form_general .div_time input.time_hour {text-align: right}
.form_general .div_time input.time_minute {text-align: left; margin-left: 0}
.form_general .div_time span + input {margin-left: 0}
.form_general .div_time span {display: block; float: left; width: 0.5em; text-align: center}
.form_general input#duration {width: 2.0em; text-align: right}
.form_general select#dur_units {margin-right: 1.0em; margin-left: 0.5em}
.form_general div#ad {float: left}
.form_general #ad label {clear: none; text-align: left; font-weight: normal}
.form_general input#all_day, .form_general input#area_def_duration_all_day {width: auto; margin-left: 3.0em; margin-right: 0.5em}
.form_general #div_rooms select, .form_general #div_typematch select {float: left; margin-right: 2.0em}
fieldset#rep_info {border-top: 1px solid <?php echo $site_faq_entry_border_color ?>; padding-top: 0.7em}
.form_general input#rep_num_weeks {width: 2.0em}

.edit_area_room span.error {display: block; width: 100%; margin-bottom: 0.5em}

.form_general label.secondary {font-weight: normal; width: auto}
    

/* ------------ EDIT_USERS.PHP ------------------*/
<?php
$edit_users_label_height     = '2.0';    // em
$edit_users_label_width      = '10.0';   // em
$edit_users_gap              = '1.0';    // em
$edit_users_input_width      = '10.0';   // em
// This CSS works by using absolute positioning to bring the Delete button up into the main form.
// Logically the HTML for the Delete button is implemented and because you can't nest a form within
// a form it appears as a second form after the main form.    However, to the user it is more logical to
// see it within the main form, which we achieve through CSS.    [Actually it would probably be better
// to have the Delete button in a column on the User List page, just like the Edit button is.  However
// if you put it there you probably also need a confirmation screen, otherwise it is too easy to delete
// users by mistake.    Having it on the edit form at least means that you have to press two buttons to
// delete a user (the Edit button followed by the Delete button)]
?>
div#form_container {width: auto; position: relative; float: left}    /* this is the containing block against which the absolute positioning works */
#form_container input.submit {width: auto; position: absolute; bottom: 2.0em}  /* bring both buttons up          */
form#form_edit_users {width: auto; margin-top: 2.0em}
#form_edit_users fieldset {float: left; width: auto}  
#form_edit_users div {float: left; clear: left; width: auto}
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
#form_edit_users select, #form_edit_users textarea {
    margin-left: <?php echo $edit_users_gap ?>em;
}
#form_edit_users textarea {margin-bottom: 0.5em}

#form_edit_users p {display: block; float: left; clear: left; padding: 0.5em 0 0.7em 0; margin: 0;
                    width: <?php echo $edit_users_label_width + $edit_users_gap + $edit_users_input_width + 5?>em}
#form_edit_users ul {clear: left}
#form_edit_users input.submit {right: 2.0em}                                   /* and put the OK on the right     */
#form_delete_users input.submit {left: 2.0em}                                  /* and put the Delete on the left */
#form_edit_users input.checkbox {width: auto; margin-left: <?php echo $edit_users_gap ?>em}
form.edit_users_error {width: 10em; margin-top: 2.0em}
div#user_list {position: relative; float: left; min-width: 50%; max-width: 98%; padding: 2em 0 2em 1em}
form#add_new_user {margin-left: 1em}



/* ------------ FUNCTIONS.INC -------------------*/
#logon_box a {display: block; width: 100%; padding-top: 0.3em; padding-bottom: 0.3em}
table#banner {width: 100%; border-spacing: 0; border-collapse: collapse;
    border-color: <?php echo $banner_border_color ?>;
    border-width: <?php echo $banner_border_width ?>px;
    border-style: solid}
#banner td {text-align: center; vertical-align: middle; background-color: <?php echo $banner_back_color ?>;
    border-color: <?php echo $banner_border_color ?>; border-style: solid;
    border-top-width: 0; border-right-width: 0; border-bottom-width: 0; border-left-width: <?php echo $banner_border_cell_width ?>px;
    padding: 6px; color: <?php echo $banner_font_color ?>}
#banner td:first-child {border-left-width: 0}
#banner td#company {font-size: large}
#banner #company div {width: 100%}
#banner a:link    {color: <?php echo $anchor_link_color_banner ?>;    text-decoration: none; font-weight: normal}
#banner a:visited {color: <?php echo $anchor_visited_color_banner ?>; text-decoration: none; font-weight: normal}
#banner a:hover   {color: <?php echo $anchor_hover_color_banner ?>;   text-decoration:underline; font-weight: normal}
#banner input.date {width: 6.5em; text-align: center}

table#colour_key {clear: both; border-spacing: 0; border-collapse: collapse}
#colour_key td {width: 7.0em; padding: 2px; font-weight: bold;
    color: <?php echo $colour_key_font_color ?>;
    border: <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_body_h_border_color ?>}
#colour_key td#row_padding {border-right: 0; border-bottom: 0}
#header_search input {width: 6.0em}
div#n_outstanding {margin-top: 0.5em}
#banner .outstanding a {color: <?php echo $outstanding_color ?>}

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
<?php
// set the styling for the "hidden" days in the mini-cals
?>
.calendar th.hidden {background-color: <?php echo $calendar_hidden_color ?>} 
.calendar td.hidden {background-color: <?php echo $calendar_hidden_color ?>; font-weight: bold} 
.calendar a.current {font-weight: bold; color: <?php echo $highlight_font_color ?>}
td#sticky_day {border: 1px dotted <?php echo $highlight_font_color ?>}
td.mincals_week_number { opacity: 0.5; font-size: 60%; }

/* ------------ PENDING.PHP ------------------*/
table#pending_list {width: 100%}
#pending_list form {float: left; margin: 0 0.5em}
#pending_list table {width: 100%; border-spacing: 0px; border-collapse: collapse; border: 0}
#pending_list td.sub_table {padding: 0; margin: 0}
#pending_list table.minimised tbody {display: none}
#pending_list table th {border-top: 1px solid <?php echo $admin_table_header_sep_color ?>;
                        background-color: <?php echo $pending_header_back_color ?>}
#pending_list td {border-top-width: 1px}
#pending_list .control {padding-left: 0; padding-right: 0; text-align: center;
                        color: <?php echo $standard_font_color ?>}
#pending_list th.control + th {border-left-width: 0}
#pending_list td.control + td {border-left-width: 0}
#pending_list table th.control{background-color: <?php echo $pending_control_color ?>; cursor: default}
#pending_list table th a {color: <?php echo $admin_table_header_font_color ?>}
#pending_list table td {border-color: <?php echo $admin_table_border_color ?>;
                        background-color: <?php echo $series_entry_back_color ?>}
#pending_list .control             {width: 1.2em}
#pending_list th.header_name       {width: 10%}
#pending_list th.header_create     {width: 10%}
#pending_list th.header_area       {width: 10%}
#pending_list th.header_room       {width: 10%}
#pending_list th.header_action     {width: 20em}
#pending_list table th.header_start_time {text-transform: uppercase}

/* ------------ REPORT.PHP ----------------------*/
#div_summary table {border-spacing: 1px; border-collapse: collapse;
    border-color: <?php echo $report_table_border_color ?>; border-style: solid;
    border-top-width: 1px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 1px}
#div_summary td, #div_summary th {padding: 0.1em 0.2em 0.1em 0.2em;
    border-color: <?php echo $report_table_border_color ?>; border-style: solid;
    border-top-width: 0; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 0}
#div_summary th {background-color: transparent; font-weight: bold; text-align: center}
#div_summary thead tr:nth-child(2) th {font-weight: normal; font-style: italic}
#div_summary th:first-child {text-align: right}
#div_summary tfoot th {text-align: right}
#div_summary td {text-align: right}
#div_summary tbody td:nth-child(even), #div_summary tfoot th:nth-child(even) {border-right-width: 0}
#div_summary td:first-child {font-weight: bold}
div#report_output {width: 98%}
p.report_entries {font-weight: bold}
.report .form_general fieldset fieldset {padding-top: 0.5em; padding-bottom: 0.5em}
.report .form_general fieldset fieldset legend {font-size: small; font-style: italic; font-weight: normal}


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
#trailer span.hidden {font-weight: normal; 
    background-color: <?php echo $body_background_color ?>;  /* hack: only necessary for IE6 to prevent blurring with opacity */
    opacity: 0.5}  /* if you change this value, change it in the IE sheets as well */
#trailer .current a {color: <?php echo $highlight_font_color ?>}

div#simple_trailer {clear: both; width: 100%; text-align: center; padding-top: 1.0em; padding-bottom: 2.0em}
#simple_trailer a {padding: 0 1.0em 0 1.0em}


/* ------------ VIEW_ENTRY.PHP ------------------*/
.view_entry #entry td:first-child {text-align: right; font-weight: bold; padding-right: 1.0em}
.view_entry div#view_entry_nav {margin-top: 1.0em}
.view_entry #approve_buttons form {float: left; margin-right: 2em}
.view_entry #approve_buttons legend {font-size: 0}
.view_entry div#returl {margin-top: 1em}
#approve_buttons td {vertical-align: middle; padding-top: 1em}
#approve_buttons td#caption {text-align: left}
#approve_buttons td#note {padding-top: 0}
#approve_buttons td#note form {width: 100%}
#approve_buttons td#note textarea {width: 100%; height: 6em}

/* ------------ jQuery UI additions -------------*/

.ui-autocomplete {
  max-height: 150px;
  overflow-y: auto;
  /* prevent horizontal scrollbar */
  overflow-x: hidden;
  /* add padding to account for vertical scrollbar */
  padding-right: 20px;
}

