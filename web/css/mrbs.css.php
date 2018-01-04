<?php 
namespace MRBS;

require_once "../systemdefaults.inc.php";
require_once "../config.inc.php";
require_once "../functions.inc";
require_once "../theme.inc";

http_headers(array("Content-type: text/css"),
             60*30);  // 30 minute cache expiry
                                
// IMPORTANT *************************************************************************************************
// In order to avoid problems in locales where the decimal point is represented as a comma, it is important to
//   (1) specify all PHP length variables as strings, eg $border_width = '1.5'; and not $border_width = 1.5;
//   (2) convert PHP variables after arithmetic using number_format
// ***********************************************************************************************************
                                
?>


/* ------------ GENERAL -----------------------------*/

body {
  font-size: small;
  margin: 0;
  padding: 0;
  color:            <?php echo $standard_font_color ?>;
  font-family:      <?php echo $standard_font_family ?>;
  background-color: <?php echo $body_background_color ?>;
}

.unsupported_browser body > * {
  display: none;
}

.unsupported_message {
  display: none;
}

.unsupported_browser body .unsupported_message {
  display: block;
}

.current {color: <?php echo $highlight_font_color ?>}                        /* used to highlight the current item */
.error   {color: <?php echo $highlight_font_color ?>; font-weight: bold}     /* for error messages */
.warning {color: <?php echo $highlight_font_color ?>}                        /* for warning messages */
.note    {font-style: italic}

input, textarea {
  box-sizing: border-box;
}

button.image {
  background-color: transparent;
  border: 0;
  padding: 0;
}

div.contents, div.trailer {
  float: left;
  width: 100%;
  box-sizing: border-box;
  padding: 0 2em;
}

h1 {font-size: x-large; clear: both}
h2 {font-size: large; clear: both}

img {border: 0}

a:link    {color: <?php echo $anchor_link_color ?>;    text-decoration: none; font-weight: bold}
a:visited {color: <?php echo $anchor_visited_color ?>; text-decoration: none; font-weight: bold}
a:hover   {color: <?php echo $anchor_hover_color ?>;   text-decoration: underline; font-weight: bold} 

tr.even_row td.new {background-color: <?php echo $row_even_color ?>}
tr.odd_row td.new {background-color: <?php echo $row_odd_color ?>}

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


label::after,
.trailer_label a::after,
.list td:first-child::after {
  content: ':';
}

label:empty::after, .group label::after {
  visibility: hidden;
}

[lang="fr"] label::after,
[lang="fr"] .trailer_label a::after,
[lang="fr"] .list td:first-child::after  {
  content: '\0000a0:';  <?php // &nbsp; before the colon ?>
}

label.no_suffix::after,
.dataTables_wrapper label::after,
.list td.no_suffix:first-child::after {
  content: '';
}

<?php
// DataTables don't work well with border-collapse: collapse and scrollX: 100%.   In fact they
// don't work well either with a border round the table.   So we put the left and right borders
// on the table cells.
?>


table.admin_table {
  border-collapse: separate;
  border-spacing: 0;
  border-color: <?php echo $admin_table_border_color ?>;
}

.admin_table th, .admin_table td,
table.dataTable thead th, table.dataTable thead td,
table.dataTable tbody th, table.dataTable tbody td {
  box-sizing: border-box;
  vertical-align: middle;
  text-align: left;
  padding: 0.1em 24px 0.1em 0.6em;
  border-style: solid;
  border-width: 0 1px 0 0;
}

.admin_table th:first-child, .admin_table td:first-child,
table.dataTable thead th:first-child, table.dataTable thead td:first-child {
  border-left-width: 1px;
}

.admin_table td, .admin_table th,
table.dataTable thead th, table.dataTable thead td {
  border-color: <?php echo $admin_table_border_color ?>;
}

.admin_table th:first-child,
table.dataTable thead th:first-child, table.dataTable thead td:first-child {
  border-left-color: <?php echo $admin_table_header_back_color ?>
}

.admin_table th:last-child {
  border-right-color: <?php echo $admin_table_header_back_color ?>
}

.admin_table.DTFC_Cloned th:last-child {
  border-right-color: <?php echo $admin_table_border_color ?>
}

.admin_table th,
table.dataTable thead .sorting,
table.dataTable thead .sorting_asc,
table.dataTable thead .sorting_desc {
  color: <?php echo $admin_table_header_font_color ?>; 
  background-color: <?php echo $admin_table_header_back_color ?>
}

.admin_table td.action {
  text-align: center
}

.admin_table td.action div {
  display: inline-block
}

.admin_table td.action div div {
  display: table-cell
}

table.display {
  width: 100%;
}

table.display tbody tr:nth-child(2n) {
  background-color: white;
}

table.display tbody tr:nth-child(2n+1) {
  background-color: #E2E4FF;
}

table.display th, table.display td {
  height: 2em;
  white-space: nowrap;
  overflow: hidden;
}

table.display th {
  padding: 3px 24px 3px 8px;
}

table.display span {
  display: none;
}

table.display span.normal {
  display: inline;
}

select.room_area_select {margin-right: 0.5em}

<?php
// Don't display anything with a class of js_none (used for example for hiding Submit
// buttons when we're submitting onchange).  The .js class is added to the <body> by JavaScript
?>
.js .js_none {display: none}
.js .js_hidden {visibility: hidden}

/* ------------ ADMIN.PHP ---------------------------*/
<?php
// Adjust the label width to suit the longest label - it will depend on the translation being used
// The input width can normally be left alone
$admin_form_label_width       = '10.0';   // em
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
    width: auto;
    margin-top: 1.2em; 
}

.admin h2 {clear: left}

div#area_form, div#room_form {
  width: 100%;
  float: left;
  padding: 0 0 2em 0;
}

div#custom_html {float: left; padding: 0 0 3em 1em}

#area_form form {
  width: 100%;
  float: left; 
  margin-right: 1em
}

#area_form label[for="area_select"] {
  display: block;
  float: left;
  font-weight: bold;
  margin-right: <?php echo $admin_form_gap ?>em;
}

#areaChangeForm div {
  float: left;
}
  
#roomChangeForm select, #areaChangeForm select {display: block; float: left; margin: -0.1em 1.5em 0 0}
#roomChangeForm input, #areaChangeForm input {float: left; margin: -0.2em 0.5em 0 0}

#roomChangeForm input.button, #areaChangeForm button.image {
  display: block;
  float: left;
  margin: 0 0.7em
}

div.header_columns, div.body_columns {position: relative; float: left; overflow-x: scroll; overflow-y: hidden}
div.header_columns {max-width: 20%}
div.body_columns {max-width: 80%}

.body_columns .admin_table th:first-child {border-left-color: <?php echo $admin_table_border_color ?>}


/* ------------ DAY/WEEK/MONTH.PHP ------------------*/

<?php
$column_hidden_width  = 0;       // (%) width of the column for hidden days (set to 0 for no column at all; 1 for a narrow column);
                                 //     when $times_along_top is TRUE, hidden days (rows) are not shown at all
$column_row_labels_width   = 1;  // (%) width of the row label columns (will expand if necessary)
$n_hidden_days = count($hidden_days);

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
div#dwm_areas, div#dwm_rooms  {float: left; margin-right: 2.0em}
#dwm_header h3 {font-size: small; font-weight: normal; text-decoration: underline; 
    margin-top: 0; margin-bottom: 0.5em; padding-bottom: 0}
#dwm_header ul {list-style-type: none; padding-left: 0; margin-left: 0; margin-top: 0}
#dwm_header li {padding-left: 0; margin-left: 0}

div#dwm {margin-bottom: 0.5em}
#dwm {text-align: center}
#dwm h2 {margin-bottom: 0}
#dwm div.timezone {opacity: 0.8}

.date_nav {
  float: left;
  width: 100%;
  margin-top: 0.5em;
  margin-bottom: 0.5em;
  font-weight: bold
}

.date_nav a {
  display: block;
  width: 33%;
}

.date_before {
  float: left;
  text-align: left;
}

.date_now {
  float: left;
  text-align: center;
}

.date_after {
  float: right;
  text-align: right;
}

.date_before::before {
  content: '<<\0000a0';
}

.date_after::after {
  content: '\0000a0>>';
}

table.dwm_main {
  float: left;
  clear: both; 
  width: 100%; 
  border-spacing: 0;
  border-collapse: separate;
  border-color: <?php echo $main_table_border_color ?>;
  border-width: <?php echo $main_table_border_width ?>px;
  border-style: solid
}

.dwm_main td {padding: 0;
    border-top:  <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_body_h_border_color ?>;
    border-left: <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_body_v_border_color ?>;
    border-bottom: 0;
    border-right: 0}
.dwm_main td:first-child {border-left: 0}
<?php
// Note that it is important to have zero padding-left and padding-top on the th cells and the celldiv divs.
// These elements are used to calculate the offset top and left of the position of bookings in
// the grid when using resizable bookings.   jQuery.offset() measures to the content.  If you
// need padding put it on the contained element.
?>
.dwm_main th {font-size: small; font-weight: normal; vertical-align: top; padding: 0;
    color: <?php echo $header_font_color ?>; 
    background-color: <?php echo $header_back_color ?>;
    border-left: <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_header_border_color ?>}
.dwm_main th.first_last, .dwm_main th span {padding: 0 2px}
.dwm_main th:first-child {border-left: 0}

.dwm_main a {
  display: block;
  min-height: inherit;
  word-break: break-all;
  word-break: break-word;  /* Better for those browsers, eg webkit, that support it */
  hyphens: auto;
}



.dwm_main tbody a {padding: 0 2px}
.dwm_main th a:link    {color: <?php echo $anchor_link_color_header ?>;    text-decoration: none; font-weight: normal}
.dwm_main th a:visited {color: <?php echo $anchor_visited_color_header ?>; text-decoration: none; font-weight: normal}
.dwm_main th a:hover   {color: <?php echo $anchor_hover_color_header ?>;   text-decoration:underline; font-weight: normal}

.dwm_main#week_main th.first_last {vertical-align: bottom}
.dwm_main td.invalid {background-color: <?php echo $main_table_slot_invalid_color ?>}
.dwm_main#month_main th {width: <?php echo $column_month ?>%}
.dwm_main#month_main td {border-top:  <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_body_v_border_color ?>}
.dwm_main#month_main td.valid   {background-color: <?php echo $main_table_month_color ?>}
.dwm_main#month_main td.invalid {background-color: <?php echo $main_table_month_invalid_color ?>}
.dwm_main#month_main a {height: 100%; width: 100%; padding: 0 2px 0 2px}

td.new a, a.new_booking {display: block; font-size: medium; text-align: center}
td.new img, .new_booking img {margin: auto; padding: 4px 0 2px 0}
img.repeat_symbol {float: right; padding: 3px}
.dwm_main#month_main img.repeat_symbol {padding: 2px}


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
div.cell_container {position: relative; float: left; width: 100%;        /* the containing div for a.new_booking */ 
<?php echo ($month_cell_scrolling ? 'height:' : 'min-height:') ?> 100px} /* NOTE:  if you change the value of (min-)height, make sure you */
                                                                         /* also change the value of height in mrbs-ielte6.css */
.month a.new_booking {position: absolute; top: 0; left: 0; z-index: 10}  /* needs to be above the base, but below the date (monthday) */
       
div.cell_header {position: relative; width: 100%; z-index: 20;         /* needs to be above the new booking anchor */
     min-height: 20%; height: 20%; max-height: 20%; overflow: hidden}

#month_main div.cell_header a {display: block; width: auto; float: left}                                                                               
#month_main div.cell_header a.monthday {font-size: medium}  /* the date in the top left corner */
#month_main div.cell_header a.week_number {opacity: 0.5; padding: 2px 4px 0 4px}

div.booking_list {
  position: relative;      /* contains the list of bookings */
  z-index: 20;             /* needs to be above new_booking */
  max-height: 80%;
  font-size: x-small;                                
  overflow: <?php echo ($month_cell_scrolling ? 'auto' : 'visible') ?>;
}

div.description, div.slot {
  width: 50%;
}

div.both {
  width: 100%;
}

.booking_list div {
  float: left;
  min-height: 1.3em;
  overflow: hidden;
}

<?php
if ($clipped_month)
{
  ?>
  .booking_list div {
    height: 1.3em;
    max-height: 1.3em;
  }
  <?php
}
?>


.booking_list a {
  font-size: x-small;
}


<?php
// Generate the classes to give the colour coding by booking type in the day/week/month views
foreach ($color_types as $type => $col)
{
  echo ".$type {background-color: $col}\n";
}

?>

.private_type {
  background-color: <?php echo $main_table_slot_private_type_color;?>;
}

/* For floating header in the day and week views */

.floatingHeader {
  position: fixed;
  top: 0;
  z-index: 2000;
  display: none;
}

.dwm_main#month_main th.hidden_day     
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
tr.row_highlight td.new {background-color: <?php echo $row_highlight_color ?>} /* used for highlighting a row */
.dwm_main td.row_labels     {background-color: <?php echo $main_table_labels_back_color ?>; white-space: nowrap}    /* used for the row labels column */
.row_labels a:link    {color: <?php echo $anchor_link_color_header ?>;    text-decoration: none; font-weight: normal}
.row_labels a:visited {color: <?php echo $anchor_visited_color_header ?>; text-decoration: none; font-weight: normal}
.row_labels a:hover   {color: <?php echo $anchor_hover_color_header ?>;   text-decoration: underline; font-weight: normal}

<?php
// HIGHLIGHTING:  Set styles for the highlighted cells under the cursor (the time/period cell and the current cell)
?>
.dwm_main td:hover.new, .dwm_main td.new_hover {background-color: <?php echo $row_highlight_color ?>}
.dwm_main tr:hover td.row_labels, .dwm_main td.row_labels_hover {background-color: <?php echo $row_highlight_color ?>; color: <?php echo $standard_font_color ?>}
.dwm_main#month_main td:hover.valid, .dwm_main#month_main td.valid_hover {background-color: <?php echo $row_highlight_color ?>}
<?php
// would be nicer to use color: inherit in the four rules below, but inherit is not supported by IE until IE8.   
// inherit would mean that (1) you didn't have to specify the colour again and (2) you needn't use the tbody selector to
// stop the header links changing colour.
?>

.dwm_main tbody tr:hover a:link,    td.row_labels_hover a:link    {color: <?php echo $anchor_link_color ?>}
.dwm_main tbody tr:hover a:visited, td.row_labels_hover a:visited {color: <?php echo $anchor_link_color ?>}
<?php // Disable the highlighting when we're in resize mode ?>
.resizing .dwm_main tr.even_row td:hover.new {background-color: <?php echo $row_even_color ?>}
.resizing .dwm_main tr.odd_row td:hover.new {background-color: <?php echo $row_odd_color ?>}
.resizing .dwm_main tr:hover td.row_labels {background-color: <?php echo $main_table_labels_back_color ?>; color: <?php echo $anchor_link_color_header ?>}
.resizing .row_labels a:hover {text-decoration: none}
.resizing .dwm_main tbody tr:hover td.row_labels a:link {color: <?php echo $anchor_link_color_header ?>}
.resizing .dwm_main tbody tr:hover td.row_labels a:visited {color: <?php echo $anchor_link_color_header ?>}
.resizing .dwm_main tr td.row_labels.selected {background-color: <?php echo $row_highlight_color ?>}
.resizing .dwm_main tr:hover td.row_labels.selected,
.resizing .dwm_main tr td.row_labels.selected a:link,
.resizing .dwm_main tr td.row_labels.selected a:visited {color: <?php echo $standard_font_color ?>}


.dwm_main .ui-resizable-handle {z-index: 1000}
.dwm_main .ui-resizable-n {top: -1px}
.dwm_main .ui-resizable-e {right: -1px}
.dwm_main .ui-resizable-s {bottom: -1px}
.dwm_main .ui-resizable-w {left: -1px}
.dwm_main .ui-resizable-se {bottom: 0; right: 0}
.dwm_main .ui-resizable-sw {bottom: -2px; left: -1px}
.dwm_main .ui-resizable-ne {top: -2px; right: -1px}
.dwm_main .ui-resizable-nw {top: -2px; left: -1px}

<?php
// We make the position property !important because otherwise IE seems to give it an inline style
// of position: relative for some unknown reason
?>
div.outline {
  position: absolute !important;
  border: 1px dotted <?php echo $header_back_color ?>;
  z-index: 700;
}

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
div.celldiv {max-width: 100%; overflow: hidden; margin: 0; padding: 0}
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

<?php
// Over-rides for multiple bookings.  If JavaScript is enabled then we want to see the JavaScript controls.
// And we will need to extend the padding so that the controls don't overwrite the booking text
?>

.js div.multiple_control {
    display: block;   /* if JavaScript is enabled then we want to see the JavaScript controls */
  }
.js .multiple_booking .maxi a {padding-left: <?php echo $main_cell_height + $main_table_cell_border_width + 2 ?>px}

div.div_select {
  position: absolute;
  border: 0;
  opacity: 0.2;
  background-color: <?php echo $main_table_labels_back_color ?>;
}

div.div_select.outside {
  background-color: transparent;
}   

/* booking privacy status */
.private {
  opacity: 0.6;
  font-style: italic;
}

/* booking approval status */
.awaiting_approval {opacity: 0.6}
.awaiting_approval a::before {content: "? "}

/* booking confirmation status */
.tentative {opacity: 0.6}
.tentative a {font-weight: normal}



/* ------------ DEL.PHP -----------------------------*/
div#del_room_confirm {
  text-align: center;
  padding-bottom: 3em;
}

#del_room_confirm p, #del_room_confirm input[type="submit"] {
  font-size: large;
  font-weight: bold;
}

#del_room_confirm form {
  display: inline-block;
  margin: 1em 2em;
}



/* ------------ EDIT_AREA_ROOM.PHP ------------------*/
.edit_area_room .form_general fieldset fieldset {
  padding-top: 0.5em;
  padding-bottom: 0.5em
}

.edit_area_room .form_general fieldset fieldset fieldset {
  margin-bottom: 1em;
}

.edit_area_room .form_general fieldset fieldset legend {
  font-size: small;
  font-style: italic;
  font-weight: normal
}

.edit_area_room .form_general fieldset fieldset fieldset legend {
  padding-left: 2em;
}

.edit_area_room fieldset#time_settings {padding:0; margin: 0}
span#private_display_caution {display: block; margin-top: 1em; font-style: italic; font-weight: normal}
#book_ahead_periods_note span {display: block; float: left; width: 24em; margin: 0 0 1em 1em; font-style: italic}
.edit_area_room .form_general textarea {height: 6em; width: 25em}
.edit_area_room div#custom_html {margin-top: 8px}

.delete_period, #period_settings button {
  display: none;
}

.js .delete_period {
  display: inline-block;
  visibility: hidden; <?php // gets switched on by JavaScript ?>
  padding: 0 1em;
  opacity: 0.7;
}

.delete_period::after {
  content: '\002718';  <?php // cross ?>
  color: red;
}

.delete_period:hover {
  cursor: pointer;
  opacity: 1;
  font-weight: bold;
}

.js #period_settings button {
  display: inline-block;
  margin-left: 1em;
}


<?php // The standard form ?>

.standard {
  margin-top: 2.0em;
}

.standard fieldset {
  padding: 1em;
}

.standard fieldset fieldset {
  padding: 0.5em 0;
}

.standard fieldset fieldset legend{
  font-size: small;
  font-style: italic;
  font-weight: normal;
}

.standard fieldset > div {
  display: table-row;
}

.standard fieldset > div > :first-child, .standard fieldset > div > :nth-child(2) {
  display: table-cell;
  margin-bottom: 0.5em;
}

.standard fieldset > div > label {
  font-weight: bold;
  padding-left: 2em;
  padding-right: 1em;
  text-align: right;
}

.field_text_area label {
  vertical-align: top;
  padding-top: 0.2em;
}

.standard fieldset > div > div {
  display: inline-block;
  text-align: left;
  padding-bottom: 0.5em
}

.standard div.group {
  display: inline-block;
}

.standard input[type="text"], .standard input[type="email"], .standard textarea {
  width: 20em;
}
.standard input[type="number"] {
  width: 4em;
}

.standard input[type="radio"], .standard input[type="checkbox"] {
  vertical-align: middle;
  margin: -0.17em 0.4em 0 0;
}

.standard textarea {
  height: 6em;
  margin-bottom: 0.5em;
}

.standard .group label {
  margin-right: 0.5em;
}


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
$input_width                   = '20';      // em

// Specific to the "edit_entry" form
$edit_entry_left_col_max_width = '10';      // em
$edit_entry_textarea_width     = '20';      // em
$edit_entry_ampm_width         = '16';      // em
$edit_entry_form_min_width     = $edit_entry_left_col_max_width + $edit_entry_textarea_width + $general_gap;
$edit_entry_form_min_width     = number_format($edit_entry_form_min_width, 1, '.', '');   // get rid of any commas

// Specific to the "import" form
$import_left_col_max_width     = '12';      // em

// Specific to the "report" form
$report_left_col_max_width     = '12';      // em
$report_form_min_width         = $report_left_col_max_width + $input_width + $general_gap;
$report_form_min_width         = number_format($report_form_min_width, 1, '.', '');   // get rid of any commas

// Specific to the "search" form
$search_left_col_max_width     = '8';       // em
$search_form_min_width         = $search_left_col_max_width + $input_width + $general_gap;
$search_form_min_width         = number_format($search_form_min_width, 1, '.', '');   // get rid of any commas

// Specific to the "logon" form
$logon_left_col_max_width      = '8';       // em
$logon_form_min_width          = $logon_left_col_max_width + $input_width + $general_gap;
$logon_form_min_width          = number_format($logon_form_min_width, 1, '.', '');   // get rid of any commas

// Specific to the "db_logon" form
$db_logon_left_col_max_width   = '12';      // em
$db_logon_form_min_width       = $db_logon_left_col_max_width + $input_width + $general_gap;
$db_logon_form_min_width       = number_format($db_logon_form_min_width, 1, '.', '');   // get rid of any commas

// Specific to the "edit_area_room" form
$edit_area_room_left_col_width      = '17';      // em
$edit_area_room_left_col_max_width  = '30';      // em
$edit_area_room_form_min_width      = $edit_area_room_left_col_width + $input_width + $general_gap;
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

.edit_entry     .form_general label {
    width: <?php echo $edit_entry_left_col_max_width ?>em;
    max-width: <?php echo $edit_entry_left_col_max_width ?>em;
}

.import         .form_general label {max-width: <?php echo $import_left_col_max_width ?>em}
.report         .form_general label {max-width: <?php echo $report_left_col_max_width ?>em}
.search         .form_general label {max-width: <?php echo $search_left_col_max_width ?>em}
.edit_area_room .form_general label {max-width: <?php echo $edit_area_room_left_col_max_width ?>em; width: <?php echo $edit_area_room_left_col_width ?>em}
#logon                    label {max-width: <?php echo $logon_left_col_max_width ?>em}
#db_logon                 label {max-width: <?php echo $db_logon_left_col_max_width ?>em}

.form_general .group      label {clear: none; width: auto; max-width: none; font-weight: normal; overflow: visible; text-align: left}
.form_general #rep_type .group label {clear: left}
div#rep_type {
    width: auto;
    border-right: 1px solid <?php echo $site_faq_entry_border_color ?>;
    margin-right: 1em;
    margin-bottom: 0.5em;
    padding-right: 1em;
}
fieldset.rep_type_details {clear: none; padding-top: 0}
fieldset.rep_type_details fieldset {padding-top: 0}

.rep_type_details label {text-align: left}

.form_general input, .form_general textarea, .form_general select {
  float: left;
  margin-left: <?php echo $general_gap ?>em; 
}

/* font family and size needs to be the same for input and textarea as their widths are defined in ems */
.form_general input, .form_general textarea {
  display: block;
  font-family: <?php echo $standard_font_family ?>;
  font-size: small;
}

.form_general input {
  width: <?php echo $input_width ?>em;
}

.form_general .group input {
  clear: none;
  width: auto;
  margin-right: 0.5em;
}

.form_general input.date {
  width: 6em;
}

.form_general textarea {
    width: <?php echo $edit_entry_textarea_width ?>em;
    height: 11em; 
    margin-bottom: 0.5em;
}

.form_general select {
  margin-right: -0.5em;
  margin-bottom: 0.5em;
}

.form_general label.radio {font-weight: normal; width: auto}
.form_general input.radio {
  margin-top: 0.1em;
  margin-right: 0.4em;
  width: auto
}
.form_general input.checkbox {width: auto; margin-top: 0.2em}
.edit_area_room .form_general input.checkbox {margin-left: <?php echo $general_gap ?>em}
.edit_area_room .form_general #booking_policies input.text {width: 4em}

.form_general input.submit {
  clear: left;
}

.form_general input[type="submit"] {
  width: auto;
  margin-top: 1em;
}

div#import_submit     {width: <?php echo $general_left_col_width ?>%; max-width: <?php echo $import_left_col_max_width ?>em}
div#report_submit     {width: <?php echo $general_left_col_width ?>%; max-width: <?php echo $report_left_col_max_width ?>em}
div#search_submit     {width: <?php echo $general_left_col_width ?>%; max-width: <?php echo $search_left_col_max_width ?>em}
div#db_logon_submit   {width: <?php echo $general_left_col_width ?>%; max-width: <?php echo $db_logon_left_col_max_width ?>em}
#import_submit input, #report_submit input, #search_submit input, #db_logon_submit input
    {position: relative; left: 100%; width: auto}
div#edit_area_room_submit_back {float: left; width: <?php echo $edit_area_room_left_col_width ?>em; max-width: <?php echo $edit_area_room_left_col_max_width ?>em}
div#edit_area_room_submit_save {float: left; clear: none; width: auto}
#edit_area_room_submit_back input {float: right}
div#edit_entry_submit_back {float: left; width: <?php echo $general_left_col_width ?>em; max-width: <?php echo $edit_entry_left_col_max_width ?>em}
div#edit_entry_submit_save {float: left; clear: none; width: auto}
#edit_entry_submit_back input {float: right}


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
.form_general input.all_day, .form_general input#area_def_duration_all_day {width: auto; margin-left: 1em; margin-right: 0.5em}
.form_general select#start_seconds, input#area_def_duration_mins {margin-right: 2em}
.form_general #div_rooms select, .form_general #div_typematch select {float: left; margin-right: 2.0em}

fieldset#rep_info, fieldset#booking_controls {
  border-top: 1px solid <?php echo $site_faq_entry_border_color ?>;
  padding-top: 0.7em;
}

.form_general input#rep_num_weeks, .form_general input#month_absolute {width: 4em}

.edit_entry span#end_time_error {display: block; float: left; margin-left: 2em; font-weight: normal}
.edit_area_room span.error {display: block; width: 100%; margin-bottom: 0.5em}

.form_general label.secondary {font-weight: normal; width: auto}

div#checks {
  float: left; 
  clear: none; 
  width: auto;
  white-space: nowrap;
  letter-spacing: 0.9em;
  padding: 1em 0;
  margin-left: 3em;
}

div#checks span {
  cursor: pointer;
}

.good::after {
  content: '\002714';  <?php // checkmark ?>
  color: green;
}

.notice::after {
  content: '!';
  font-weight: bold;
  color: #ff5722;
}

.bad::after {
  content: '\002718';  <?php // cross ?>
  color: red;
}

.form_general table {border-collapse: collapse}
.form_general table, .form_general tr, .form_general th, .form_general td {padding: 0; margin: 0; border: 0}
.form_general th {font-weight: normal; font-style: italic; text-align: left; padding: 0.2em 0 0.2em 1em}


/* ------------ EDIT_ENTRY_HANDLER.PHP ------------------*/
.edit_entry_handler div#submit_buttons {float: left}
.edit_entry_handler #submit_buttons form {float: left; margin: 1em 2em 1em 0}


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
div#user_list {padding: 2em 0}
form#add_new_user {margin-left: 1em}
#users_table td {text-align: right}
#users_table td div.string {text-align: left}



/* ------------ FUNCTIONS.INC -------------------*/

.banner {
  height: 100%;
  width: 100%;
  background-color: <?php echo $banner_back_color ?>;
  color: <?php echo $banner_font_color ?>;
  border-color: <?php echo $banner_border_color ?>;
  border-width: <?php echo $banner_border_width ?>px;
  border-style: solid;
}

.banner.simple, .banner.simple nav {
  height: auto;
}

.banner .company {
  font-size: large;
  padding: 0.3em 1em;
  text-align: center;
  vertical-align: middle;
}

.banner .company div {
  width: 100%;
}

.banner nav {
  display: table;
  height: 100%;
  width: 100%;
}

.banner ul {
  list-style: none;
  display: table;
  width: 100%;
  height: 100%;
  margin: 0;
  padding-left: 0;
}

.banner li {
  display: table-cell;
  height: 100%;
  text-align: center;
  vertical-align: middle;
  border-color: <?php echo $banner_border_color ?>;
  border-style: solid;
  border-width: 0 0 0 <?php echo $banner_border_cell_width ?>px;
  padding: 0.3em 0.5em;
}

.banner li:first-child {
  border-left-width: 0;
}

#logon_box a {
  display: block;
  width: 100%;
  padding-top: 0.3em;
  padding-bottom: 0.3em;
}

.banner a:link, .banner a:visited, .banner a:hover {
  text-decoration: none;
  font-weight: normal;
}

.banner a:link {
  color: <?php echo $anchor_link_color_banner ?>;
}

.banner a:visited {
  color: <?php echo $anchor_visited_color_banner ?>;
}

.banner a:hover {
  color: <?php echo $anchor_hover_color_banner ?>;
}

.banner input.date {
  width: 6.5em;
  text-align: center
}

form#show_my_entries input[type="submit"] {
  display: inline;
  border: none;
  background: none;
  color: <?php echo $anchor_link_color_banner ?>;
  cursor: pointer;
  padding: 0.3em 0;
}

<?php
// The rules below are concerned with keeping the date selector in the header a constant width, so
// that the header doesn't jump around when the page is loaded
?>

.js .banner #Form1 select {
  display: none;
}

.js .banner #Form1 span {
  display: inline-block;
  min-width: 7.5em;
}

.js .banner #Form1 input[type=submit] {
  visibility: hidden;
}

table#colour_key {clear: both; float: left; border-spacing: 0; border-collapse: collapse; margin-bottom: 0.5em}
#colour_key td {width: 7.0em; padding: 2px; font-weight: bold;
    color: <?php echo $colour_key_font_color ?>;
    border: <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_body_h_border_color ?>}
#colour_key td#row_padding {border-right: 0; border-bottom: 0}
#header_search input {width: 6.0em}
div#n_outstanding {margin-top: 0.5em}
.banner .outstanding a {color: <?php echo $outstanding_color ?>}

/* ------------ HELP.PHP ------------------------*/

table.details {
  border-spacing: 0;
  border-collapse: collapse;
  margin-bottom: 1.5em;
}

table.details:first-child {
  margin-bottom: 0;
}

table.details.has_caption {
  margin-left: 2em;
}

.details caption {
  text-align: left;
  font-weight: bold;
  margin-left: -2em;
  margin-bottom: 0.2em;
}

.details td {
  padding: 0 1.0em 0 0;
  vertical-align: bottom;
}

.details td:first-child {
  text-align: right;
}


/* ------------ IMPORT.PHP ------------------------*/
.import .form_general fieldset fieldset legend {font-size: small; font-style: italic; font-weight: normal}
div.problem_report {border-bottom: 1px solid <?php echo $site_faq_entry_border_color ?>; margin-top: 1em}

/* ------------ MINCALS.PHP ---------------------*/
#cals {float: right}
div#cal_last {float: left}
div#cal_this {float: left; margin-left: 1.0em}
div#cal_next {float: left; margin-left: 1.0em}

table.calendar {
  border-spacing: 0;
  border-collapse: collapse;
}

.calendar th {
  min-width: 2.0em;
  text-align: center;
  font-weight: normal;
  background-color: transparent;
  color: <?php echo $standard_font_color ?>;
}

.calendar td {
  text-align: center;
  font-size: x-small;
}

<?php
// set the styling for the "hidden" days in the mini-cals
?>
.calendar th.hidden {background-color: <?php echo $calendar_hidden_color ?>} 
.calendar td.hidden {background-color: <?php echo $calendar_hidden_color ?>; font-weight: bold} 
.calendar a.current {font-weight: bold; color: <?php echo $highlight_font_color ?>}
td#sticky_day {border: 1px dotted <?php echo $highlight_font_color ?>}
td.mincals_week_number { opacity: 0.5; font-size: 60%; }

/* ------------ PENDING.PHP ------------------*/
#pending_list form {
  display: inline-block;
}

#pending_list td.table_container, #pending_list td.sub_table {
  padding: 0;
  border: 0;
  margin: 0;
}

#pending_list .control {
  padding-left: 0;
  padding-right: 0;
  text-align: center;
  color: <?php echo $standard_font_color ?>;
}

.js #pending_list td.control {
  background-color: <?php echo $pending_control_color ?>;
}

#pending_list td:first-child {width: 1.2em}
#pending_list #pending_table td.sub_table {width: auto}
table.admin_table.sub {border-right-width: 0}
table.sub th {background-color: #788D9C}
.js .admin_table table.sub th:first-child {background-color: <?php echo $pending_control_color ?>;
    border-left-color: <?php echo $admin_table_border_color ?>}
#pending_list form {margin: 2px 4px}


/* ------------ REPORT.PHP ----------------------*/
div#div_summary {padding-top: 3em}
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
p.report_entries {font-weight: bold}
.report .form_general fieldset fieldset {padding-top: 0.5em; padding-bottom: 0.5em}
.report .form_general fieldset fieldset legend {font-size: small; font-style: italic; font-weight: normal}
button#delete_button {float: left; clear: left; margin: 1em 0 3em 0}


/* ------------ SEARCH.PHP ----------------------*/
span#search_str {color: <?php echo $highlight_font_color ?>}
p#nothing_found {font-weight: bold}
div#record_numbers {font-weight: bold}
div#record_nav {font-weight: bold; margin-bottom: 1.0em}

/* ------------ SITE_FAQ ------------------------*/
.help q {font-style: italic}
.help dfn {font-style: normal; font-weight: bold}
#site_faq_contents li a {text-decoration: underline}
div#site_faq_body {margin-top: 2.0em}
#site_faq_body h4 {border-top: 1px solid <?php echo $site_faq_entry_border_color ?>; padding-top: 0.5em; margin-top: 0} 
#site_faq_body div {padding-bottom: 0.5em}
#site_faq_body :target {background-color: <?php echo $help_highlight_color ?>}


/* ------------ TRAILER.INC ---------------------*/
div#trailer {
  border-top: 1px solid <?php echo $trailer_border_color ?>; 
  border-bottom: 1px solid <?php echo $trailer_border_color ?>; 
  float: left;
  clear: left;
  margin-top: 1.0em; margin-bottom: 1.5em;
  padding-top: 0.3em; padding-bottom: 0.3em;
}

#trailer div {
  float: left;
  width: 100%;
}

#trailer div.trailer_label {
  float: left;
  clear: left;
  width: 20%;
  max-width: 9.0em;
  font-weight: bold;
}

#trailer div.trailer_links {
  float: left;
  width: 79%;  /* 79 to avoid rounding problems */
  padding-left: 1em;
}

.trailer_label span {
  margin-right: 1.0em;
}

#trailer span.current {
  font-weight: bold;
}

#trailer span.hidden {
  font-weight: normal; 
  background-color: <?php echo $body_background_color ?>;  /* hack: only necessary for IE6 to prevent blurring with opacity */
  opacity: 0.5;  /* if you change this value, change it in the IE sheets as well */
}

#trailer .current a {
  color: <?php echo $highlight_font_color ?>;
}

div#simple_trailer {
  clear: both;
  text-align: center;
  padding-top: 1.0em;
  padding-bottom: 2.0em;
}

#simple_trailer a {
  padding: 0 1.0em 0 1.0em;
}


/* ------------ VIEW_ENTRY.PHP ------------------*/
.view_entry #entry td:first-child {text-align: right; font-weight: bold; padding-right: 1.0em}

.view_entry div#view_entry_nav {
  display: table;
  margin-top: 1em;
  margin-bottom: 1em;
}

div#view_entry_nav > div {
  display: table-row;
}

div#view_entry_nav > div > div {
  display: table-cell;
  padding: 0.5em 1em;
}

#view_entry_nav input[type="submit"] {
  width: 100%;
}

.view_entry #approve_buttons form {
  float: left;
  margin-right: 2em;
}

.view_entry #approve_buttons form {
  float: left;
}

div#returl {
  margin-bottom: 1em;
}

#approve_buttons td {vertical-align: middle; padding-top: 1em}
#approve_buttons td#caption {text-align: left}
#approve_buttons td#note {padding-top: 0}
#approve_buttons td#note form {width: 100%}

#approve_buttons td#note textarea {
  width: 100%;
  height: 6em;
  margin-bottom: 0.5em;
}


/*-------------DataTables-------------------------*/

div.datatable_container {
  float: left;
  width: 100%;
}

div.ColVis_collection {
  float: left;
  width: auto;
}

div.ColVis_collection button.ColVis_Button {
  float: left;
  clear: left;
}

.dataTables_wrapper .dataTables_length {
  clear: both;
}

.dataTables_wrapper .dataTables_filter {
  clear: right;
  margin-bottom: 1em;
}

span.ColVis_radio {
  display: block;
  float: left;
  width: 30px;
}

span.ColVis_title {
  display: block;
  float: left;
  white-space: nowrap;
}

table.dataTable.display tbody tr.odd {
  background-color: #E2E4FF;
}

table.dataTable.display tbody tr.even {
  background-color: white;
}

table.dataTable.display tbody tr.odd > .sorting_1,
table.dataTable.order-column.stripe tbody tr.odd > .sorting_1 {
  background-color: #D3D6FF;
}

table.dataTable.display tbody tr.odd > .sorting_2,
table.dataTable.order-column.stripe tbody tr.odd > .sorting_2 {
  background-color: #DADCFF;
}

table.dataTable.display tbody tr.odd > .sorting_3,
table.dataTable.order-column.stripe tbody tr.odd > .sorting_3 {
  background-color: #E0E2FF;
}

table.dataTable.display tbody tr.even > .sorting_1,
table.dataTable.order-column.stripe tbody tr.even > .sorting_1  {
  background-color: #EAEBFF;
}

table.dataTable.display tbody tr.even > .sorting_2,
table.dataTable.order-column.stripe tbody tr.even > .sorting_2 {
  background-color: #F2F3FF;
}

table.dataTable.display tbody tr.even > .sorting_3,
table.dataTable.order-column.stripe tbody tr.even > .sorting_3 {
  background-color: #F9F9FF;
}

.dataTables_wrapper.no-footer .dataTables_scrollBody {
  border-bottom-width: 0;
}

div.dt-buttons {
  float: right;
  margin-bottom: 0.4em;
}

a.dt-button {
  margin-right: 0;
}


/* ------------ jQuery UI additions -------------*/

.ui-autocomplete {
  max-height: 150px;
  overflow-y: auto;
  /* prevent horizontal scrollbar */
  overflow-x: hidden;
  /* add padding to account for vertical scrollbar */
  padding-right: 20px;
}


#check_tabs {border:0}
div#check_tabs {background-image: none}
.edit_entry #ui-tab-dialog-close {position:absolute; right:0; top:23px}
.edit_entry #ui-tab-dialog-close a {float:none; padding:0}
