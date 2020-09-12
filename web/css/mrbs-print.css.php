<?php
namespace MRBS;

require_once "../systemdefaults.inc.php";
require_once "../config.inc.php";
require_once "../functions.inc";
require_once "../theme.inc";

http_headers(array("Content-type: text/css"),
             60*30);  // 30 minute cache expiry
?>

.screenonly, .banner, div.minicalendars.formed,
nav:not(.main_calendar):not(.arrow):not(.location):not(.view) {
  display: none;
}

nav.arrow, nav.view {
  visibility: hidden;
}

td.new a, a.new_booking img { display: none; }

.dwm_main :not(tbody) th {
  color: <?php echo $header_font_color_print ?>;
}

.dwm_main th a:link {
  color: <?php echo $anchor_link_color_header_print ?>;
}

<?php
// redefine table and cell border colours so that they are visible in the print view
// (in the screen view the boundaries are visible due to the different background colours)
?>
table.dwm_main {
  border-width: 1px;
  border-color: <?php echo $main_table_border_color_print ?>;
}

.dwm_main :not(tbody) th {
  border-left-color: <?php echo $main_table_header_border_color_print ?>;
}

.dwm_main td,
.dwm_main tbody th {
  border-top-color:  <?php echo $main_table_body_h_border_color_print ?>;
  border-left-color: <?php echo $main_table_body_v_border_color_print ?>;
}

.dwm_main#month_main td {
  border-top-color:  <?php echo $main_table_body_h_border_color_print ?>;
}


<?php
// In the month view, get rid of horizontal and vertical scrollbars.   Make
// horizontal overflow hidden and allow the table cell to grow to accommodate
// vertical overflow.
?>

div.cell_container {
  min-height: 100px;
  height: auto;
}

div.cell_header {
  min-height: 1.4em;
  height: 1.4em;
  max-height: 1.4em;
}

div.booking_list {
  overflow: hidden;
  max-height: none;
}

div.booking_list div {
  box-sizing: border-box;
}


<?php
// Generate the rules to give the colour coding by booking type in the day/week/month views
// and the colour key
foreach ($color_types as $type => $col)
{
  echo "td.$type, div.$type {border: 2px solid $col}\n";
}

// hide DataTable buttons in print
?>

.ColVis_Button, .dataTables_filter, .dataTables_length, .dataTables_paginate {
  display: none;
}

.ui-resizable-handle {
  display: none;
}
