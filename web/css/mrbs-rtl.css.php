<?php
namespace MRBS;

// Modifications to the standard CSS when using RTL languages (eg Hebrew)

require_once "../systemdefaults.inc.php";
require_once "../config.inc.php";
require_once "../functions.inc";
require_once "../theme.inc";

http_headers(array("Content-type: text/css"),
             60*30);  // 30 minute cache expiry
?>


/* ------------ GENERAL -----------------------------*/

h1, h2, td, th {
  direction: rtl;
}

legend {
  float: right;
}

/* ------------ ADMIN.PHP ---------------------------*/

form.form_admin, .form_admin div, div#div_custom_html,
#area_form form, #area_form label[for="area_select"],
#areaChangeForm select, #areaChangeForm input, #areaChangeForm input.button,
div.header_columns, div.body_columns {
  float: right;
}

.form_admin label, .form_admin fieldset, .form_admin input, 
div#area_form, div#room_form {
  float: inherit;
}

.form_admin label {
  padding-bottom: 5px;
}

.form_admin fieldset {
  width: 700px;
}

.form_admin legend {
  padding-left: 36px;
}

.admin h2 {
  clear: right;
}

div#area_form, div.header_columns, div.body_columns {
  direction: rtl;
}

/* ------------ DAY/WEEK/MONTH.PHP ------------------*/

div#dwm_header, div#dwm_areas, div#dwm_rooms, div.cell_container {
  float: right;
}

.date_before {
  float: right;
  text-align: right;
}

.date_now {
  float: right;
}

.date_after {
  float: left;
  text-align: left;
}

div#dwm_areas, div#dwm_rooms, .date_before, .date_after, table.dwm_main {
  direction: rtl;
}

#dwm_header ul {
  margin-left: 30px;
}

<?php
foreach ($color_types as $type => $col)
{
  echo ".month div.$type {float: right}\n";   // used in the month view
}
?>

/* ------------ EDIT_AREA.PHP ------------------*/

#book_ahead_periods_note span {
  float: right;
}

/* ------------ FUNCTIONS.INC -------------------*/

.banner {
  direction: rtl;
}

.banner li {
  border-width: 0 <?php echo $banner_border_cell_width ?>px 0 0;
}

/* ------------ MINCALS.PHP ---------------------*/

div#cal_last, div#cal_this, div#cal_next {
  float: right;
}

div#cal_last {
  margin-left: 1.0em;
}

table.calendar {
  margin-right: 30px
}

.calendar th {
  font-weight: bold;
}

/* ------------ PENDING.PHP ------------------*/

table#pending_list {
  direction: rtl;
}

#pending_list form {
  float: right;
}

#pending_list td, #pending_list td.control + td,
#pending_list th.header_name, #pending_list th.header_create, #pending_list th.header_area,
#pending_list th.header_room, #pending_list th.header_action {
  text-align: right;
}

#pending_list th.control + th, #pending_list td.control + td {
  border-left-width: 1
}

/* ------------ REPORT.PHP ----------------------*/

.div_report h3, .div_report table,
div.report_entry_name {
  direction: rtl;
}

div.report_entry_title, div.report_entry_name, p.report_entries {
  float: right;
}

/* ------------ TRAILER.INC ---------------------*/

div#trailer, #trailer div, #trailer div.trailer_label,
#trailer div.trailer_links {
  float: right;
}

div#trailer {
  direction: rtl;
}

/* ------------ VIEW_ENTRY.PHP ------------------*/

.view_entry div#view_entry_nav {
  direction: rtl;
}

.view_entry #approve_buttons form {
  float: right;
}
