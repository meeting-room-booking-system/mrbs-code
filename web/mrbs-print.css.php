<?php 

// $Id$

require_once "systemdefaults.inc.php";
require_once "config.inc.php";
require_once "functions.inc";
require_once "theme.inc"; 

header("Content-type: text/css");
expires_header(60*30); // 30 minute expiry

?>

div.screenonly { display: none; }

td.new a, a.new_booking img { display: none; }

<?php
// redefine table and cell border colours so that they are visible in the print view
// (in the screen view the boundaries are visible due to the different background colours)
?>
table.dwm_main {
    border-width: 1px;
    border-color: <?php echo $main_table_border_color_print ?>;}

.dwm_main th {
    border-left-color: <?php echo $main_table_header_border_color_print ?>}
    
.dwm_main td {
    border-top-color:  <?php echo $main_table_body_h_border_color_print ?>;
    border-left-color: <?php echo $main_table_body_v_border_color_print ?>}

.dwm_main#month_main td {
    border-top-color:  <?php echo $main_table_body_h_border_color_print ?>}
    
<?php
// add a top margin to the colour key table to separate it from the main table
// (in the screen view the separation is provided by the Goto Prev/This/Next links
?>
table#colour_key {margin-top: 1em}

<?php
// Generate the classes to give the colour coding by booking type in the day/week/month views
foreach ($color_types as $type => $col)
{
  echo "td.$type, .$type .celldiv {border: 2px solid $col}\n";         // used in the day and week views
}

// hide DataTable buttons in print
?>

.ColVis_Button, .dataTables_filter, .dataTables_length, .dataTables_paginate {display: none; }

