<?php
namespace MRBS;

require_once "../systemdefaults.inc.php";
require_once "../config.inc.php";
require_once "../site_config.inc";
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
  color: <?php echo $standard_font_color ?>;
  font-family: <?php echo $standard_font_family ?>;
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

.current {
  color: <?php echo $highlight_font_color ?>;  <?php // used to highlight the current item ?>
}

.error {
  color: <?php echo $highlight_font_color ?>;  <?php // for error messages ?>
  font-weight: bold;
}

.warning {
  color: <?php echo $highlight_font_color ?>;  <?php // for warning messages ?>
}

.note {
  font-style: italic;
}

input, textarea {
  box-sizing: border-box;
}

<?php
// <input> elements of type 'date' are converted by the JavaScript into datepickers.
// In order to prevent the display shifting about during the conversion process we set
// the widths of both to be the same.
?>
input.date,
.js input[type="date"],
input.form-control.input {
  width: 10em;
}

<?php
// Flatpickr lets mobile devices use their own date inputs, because these are usually
// superior.  In these cases set the width of the date input to 'auto' to allow the native
// date input display properly.   For example on iPad Safari, the native date input is
// displayed as dd-mmm-yyyy, eg 1 Jan 2019, which is wider than most date inputs.
?>
input.form-control.input.flatpickr-mobile {
  width: auto;
}

input.form-control.input {
  text-align: center;
}

input.date {
  text-align: center;
}

button.image {
  background-color: transparent;
  border: 0;
  padding: 0;
}

.contents, .banner {
  padding: 0 1.5rem;
}

.contents {
  float: left;
  width: 100%;
  box-sizing: border-box;
  padding-bottom: 3rem;
}

h1 {
  font-size: x-large;
  clear: both;
}

h2 {
  font-size: large;
  clear: both;
}

.minicalendars {
  padding-top: 0.8rem; <?php // same as margin-top on nav.main_calendar ?>
}

.minicalendars.formed {
  margin-right: 2em;
  display: none;
}

@media screen and (min-width: 80rem) {

  .minicalendars.formed {
    display: block;
  }
}

<?php
if ($display_mincals_above)
{
  ?>
  @media screen and (max-width: 80rem) and (min-width: 30rem) and (min-height: 55rem)  {

    .index :not(.simple) + .contents {
      flex-direction: column;
    }

    .minicalendars.formed {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      max-height: 18em;
      overflow: hidden;
      margin-right: 0;
    }

    .flatpickr-calendar.inline {
      margin-left: 1.5em;
      margin-right: 1.5em;
    }

  }
  <?php
}

// Make the inline minicalendars smaller than the pop-up calendars.
// The default width is 39px
?>
.flatpickr-calendar.inline {
  width: auto;
  font-size: 85%;
  margin-bottom: 1rem;
}

.flatpickr-calendar.inline .dayContainer {
  width: calc(7 * 25px);
  min-width: calc(7 * 25px);
  max-width: calc(7 * 25px);
}

.flatpickr-calendar.inline .flatpickr-days {
  width: calc(7 * 25px);
}

.flatpickr-calendar.inline .flatpickr-day {
  max-width: 25px;
  height: 25px;
  line-height: 25px;
}

.flatpickr-months .flatpickr-month {
  height: 40px;
}

.index :not(.simple) + .contents {
  display: -ms-flexbox;
  display: flex;
}

.view_container {
  -ms-flex-positive: 1;
  flex-grow: 1;
  width: 100%;
  overflow-x: auto;
}

img {
  border: 0;
}

a:link {
  color: <?php echo $anchor_link_color ?>;
  text-decoration: none;
  font-weight: bold;
}

a:visited {
  color: <?php echo $anchor_visited_color ?>;
  text-decoration: none;
  font-weight: bold
}

a:hover {
  color: <?php echo $anchor_hover_color ?>;
  text-decoration: underline;
  font-weight: bold;
}

tr:nth-child(odd) td.new,
.all_rooms tr:nth-child(odd) td {
  background-color: <?php echo $row_odd_color ?>;
}

tr:nth-child(even) td.new,
.all_rooms tr:nth-child(even) td {
  background-color: <?php echo $row_even_color ?>;
}

.all_rooms td {
  height: 100%; <?php // for Firefox ?>
}

.dwm_main.all_rooms td a {
  display: flex;
  height: 100%;
  padding: 0;
}

<?php
/* We use border-box sizing to keep the border inside the div therefore allow the div to
 * be as thin as 1px (useful if there are lots of slots as it saves screen space).  But as
 * the border would then obscure the background colour, we make the border dotted to allow
 * the background colour to show through.  Note however that box-sizing doesn't work quite
 * properly with flexbox - the sizing calculations are a bit out - but it's probably good
 * enough.
 *
 * 'width: 0' is essential to keep the table columns equal width when there's a lot of
 * text in the bookings.
 */
?>
.all_rooms td a div {
  box-sizing: border-box;
  min-width: 1px;
  width: 0;
  border-right: 1px dotted  <?php echo $main_table_body_v_border_color ?>;
  white-space: nowrap;
  overflow: hidden;
  padding: 0.2em 0;
}

<?php
/* We can't use padding-left because that would add a fixed amount of width
 * to every div, thus distorting their relative widths.  So add a non-breaking
 * space before, which will just be treated in the same way as the rest of the
 * text.
 */
 ?>
.all_rooms td a div:not(.free)::before {
  content: '\00a0';
}

.all_rooms td a:hover {
  text-decoration: none;
}

.all_rooms td a div:last-child,
.all_rooms td a div.free {
  border-right: 0;
}

td, th {
  vertical-align: top;
}

td form {
  margin: 0;  <?php // Prevent IE from displaying margins around forms in tables. ?>
}

legend {
  font-weight: bold;
  font-size: large;
  font-family: <?php echo $standard_font_family ?>;
  color: <?php echo $standard_font_color ?>;
}

fieldset {
  margin: 0;
  padding: 0;
  border: 0;
  -webkit-border-radius: 8px;
  -moz-border-radius: 8px;
  border-radius: 8px;
}

fieldset.admin {
  width: 100%; padding: 0 1.0em 1.0em 1.0em;
  border: 1px solid <?php echo $admin_table_border_color ?>;
}

fieldset fieldset {
  position: relative;
  clear: left;
  width: 100%;
  padding: 0;
  border: 0;
  margin: 0;
}

fieldset fieldset legend {
  font-size: 0;  <?php // for IE: even if there is no legend text, IE allocates space ?>
}

label:not(.link)::after,
label.link a::after,
.list td:first-child::after {
  content: ':';
}

[lang="fr"] label:not(.link)::after,
[lang="fr"] label.link a::after,
[lang="fr"] .list td:first-child::after  {
  content: '\0000a0:';  <?php // &nbsp; before the colon ?>
}

label:empty::after, .group label::after {
  visibility: hidden;
}

label.no_suffix::after,
[lang="fr"] label.no_suffix::after,
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
  border-left-color: <?php echo $admin_table_header_back_color ?>;
}

.admin_table th:last-child {
  border-right-color: <?php echo $admin_table_header_back_color ?>;
}

.admin_table.DTFC_Cloned th:last-child {
  border-right-color: <?php echo $admin_table_border_color ?>;
}

.admin_table th,
table.dataTable thead .sorting,
table.dataTable thead .sorting_asc,
table.dataTable thead .sorting_desc {
  color: <?php echo $admin_table_header_font_color ?>;
  background-color: <?php echo $admin_table_header_back_color ?>;
}

.admin_table td.action {
  text-align: center;
}

.admin_table td.action div {
  display: inline-block;
}

.admin_table td.action div div {
  display: table-cell;
}

<?php
// The width for JavaScript enabled browsers is set in js/datatables.js.php.  See the comment in that
// file for an explanation.
?>
body:not(.js) table.display {
  width: 100%;
}

table.display tbody tr:nth-child(2n) {
  background-color: <?php echo $zebra_even_color ?>;
}

table.display tbody tr:nth-child(2n+1) {
  background-color: <?php echo $zebra_odd_color ?>;
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

select.room_area_select,
nav.location .select2-container {
  margin: 0 0.5em;
}

.none {
  display: none;
}

<?php
// Don't display anything with a class of js_none (used for example for hiding Submit
// buttons when we're submitting onchange).  The .js class is added to the <body> by JavaScript
?>
.js .js_none {
  display: none;
}

.js .js_hidden {
  visibility: hidden;
}

h2.date, span.timezone {
  display: inline-block;
  width: 100%;
  text-align: center;
  margin-bottom: 0.1em;
}

span.timezone {
  opacity: 0.8;
  font-size: smaller;
}

nav.main_calendar {
  display: -ms-flexbox;
  display: flex;
  -ms-flex-align: center;
  align-items: center;
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
  width: 100%;
  margin-top: 0.8rem;  <?php // same as padding-top on minicalendars ?>
}

nav.main_calendar > nav {
  display: -ms-flexbox;
  display: flex;
  -ms-flex: 1;
  flex: 1;
  -ms-flex-pack: center;
  justify-content: center;
}

<?php
// Only allow the location element to wrap if the parent has already wrapped.
?>
nav.main_calendar.wrapped nav.location {
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
}

<?php
// Change the order if the element has wrapped (detected by JavaScript) in order
// to make it looker better and improve space utilisation.
?>
nav.main_calendar.wrapped nav.location {
  flex-basis: 100%;
}

nav.main_calendar.wrapped:nth-of-type(1) nav.location {
  order: -1;
}

<?php
// Put the margins on the children rather than the parent in case they are wrapped,
// because then we will want some vertical separation between the wrapped items.
?>
nav.main_calendar.wrapped:nth-of-type(1) nav.location > * {
  margin-bottom: 1em;
}

nav.main_calendar.wrapped:nth-of-type(2) nav.location {
  order: 1;
}

nav.main_calendar.wrapped:nth-of-type(2) nav.location > * {
  margin-top: 1em;
}

nav.main_calendar > nav:first-child {
  -ms-flex-pack: start;
  justify-content: flex-start;
}

nav.main_calendar > nav:last-child {
  -ms-flex-pack: end;
  justify-content: flex-end;
}

nav.view div.container {
  display: inline-grid;
  grid-template-columns: 1fr 1fr 1fr;
}

nav.view a, nav.arrow a {
  background: linear-gradient(<?php echo implode(', ', $button_color_stops)?>);
  border-right: thin solid <?php echo $body_background_color ?>;
  cursor: pointer;
  line-height: 1.8em;
  font-weight: normal;
  text-align: center;
}

nav.view a:first-child, nav.arrow a:first-child {
  border-top-left-radius: 5px;
  border-bottom-left-radius: 5px;
}

nav.view a:last-child, nav.arrow a:last-child {
  border-right: 0;
  border-top-right-radius: 5px;
  border-bottom-right-radius: 5px;
}

nav.view a {
  padding: 0.2em 0.5em;
}

nav.arrow a {
  padding: 0.2em 1.1em;
}

nav a.selected,
nav.view a:hover,
nav.view a:focus,
nav.arrow a:hover,
nav.arrow a:focus {
  background: <?php echo $banner_back_color ?>;
  box-shadow: inset 1px 1px <?php echo $button_inset_color ?>;
  color: #ffffff;
  text-decoration: none;
}

nav a.prev::before {
  content: '\00276e';  /* HEAVY LEFT-POINTING ANGLE QUOTATION MARK ORNAMENT */
}

nav a.next::after {
  content: '\00276f';  /* HEAVY RIGHT-POINTING ANGLE QUOTATION MARK ORNAMENT */
}


/* ------------ ADMIN.PHP ---------------------------*/

.form_admin fieldset {
  border: 1px solid <?php echo $admin_table_border_color ?>;
}

.admin h2 {
  clear: left
}

div#area_form, div#room_form {
  width: 100%;
  float: left;
  padding: 0 0 2em 0;
}

div#div_custom_html {
  float: left;
  padding: 0 0 3em 1em;
}

#area_form form {
  width: 100%;
  float: left;
  margin-right: 1em
}

#area_form label[for="area_select"] {
  display: block;
  float: left;
  font-weight: bold;
  margin-right: 1em;
}

.areaChangeForm div {
  float: left;
}

.roomChangeForm select, .areaChangeForm select {
  font-size: larger;
}

.roomChangeForm input, .areaChangeForm input {
  float: left;
  margin: -0.2em 0.5em 0 0;
}

.roomChangeForm input.button, .areaChangeForm button.image {
  display: block;
  float: left;
  margin: 0 0.7em;
}


/* ------------ INDEX.PHP ------------------*/

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

.table_container {
  overflow: auto;
  position: relative;
  <?php
  // A height is necessary to make sticky headers work. Set the maximum height to be the viewport's,
  // less a fixed amount, which allows for a small space at the top and bottom, giving a little bit
  // of context and making it easier to position the table container in the viewport.
  ?>
  max-height: calc(100vh - 4em);
  <?php
  // For those browsers that support the max() function ensure that the maximum height is at least
  // a certain height, otherwise the element becomes too small to be meaningful.
  ?>
  max-height: max(calc(100vh - 4em), 8em);
  margin: 1em 0;
}

div.timeline {
  background-color: <?php echo $timeline_color ?>;
  position: absolute;
  width: 0;
  <?php
  // The JavaScript positions the top edge of the line at the current time, but intuitively we would expect
  // the centre of the line to represent the current time, so we need to shift the line up by half of its height.
  // Note that in some browsers, eg Firefox, translate(0, -50%) can make the line disappear completely if its height
  // is only 1px - probably something to do with rounding - so make the line at least 2px high.
  ?>
  height: 2px; <?php // Do not set to less than 2px.  See above. ?>
  transform: translate(0, -50%);
  z-index: 90;
  pointer-events: none;
}

div.timeline.times_along_top {
  height: 0;
  <?php
  // The JavaScript positions the left edge of the line at the current time, but intuitively we would expect
  // the centre of the line to represent the current time, so we need to shift the line left by half of its width.
  // Note that in some browsers, eg Firefox, translate(-50%, 0) can make the line disappear completely if its width
  // is only 1px - probably something to do with rounding - so make the line at least 2px wide.
  ?>
  width: 2px; <?php // Do not set to less than 2px.  See above. ?>
  transform: translate(-50%, 0);
}

table.dwm_main {
  float: left;
  clear: both;
  width: 100%;
  height: 100%;
  border-spacing: 0;
  border-collapse: separate;
  border-color: <?php echo $main_table_border_color ?>;
  border-width: <?php echo $main_table_border_width ?>px;
  border-style: solid;
  border-radius: 5px;
}

.dwm_main th, .dwm_main td {
  min-height: <?php echo $main_cell_height ?>;
  line-height: <?php echo $main_cell_height ?>;
}

.dwm_main tbody th {
  text-align: left;
}

.dwm_main td {
  position: relative;
  padding: 0;
  border-bottom: 0;
  border-right: 0;
}

.dwm_main td,
.dwm_main tbody td + th {
  border-right: <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_body_v_border_color ?>;
}

.series a::before {
  content: '\0021bb';  /* CLOCKWISE OPEN CIRCLE ARROW */
  margin-right: 0.5em;
}

.awaiting_approval a::before {
  content: '?';
  margin-right: 0.5em;
}

.awaiting_approval.series a:before {
  content: '?\002009\0021bb';  /* THIN SPACE, CLOCKWISE OPEN CIRCLE ARROW */
}

.awaiting_approval {
  opacity: 0.6
}

.private {
  opacity: 0.6;
  font-style: italic;
}

.tentative {
  opacity: 0.6;
}

.tentative a {
  font-weight: normal;
}

.capacity::before {
  content: ' (';
}

.capacity::after {
  content: ')';
}

.capacity.zero {
  display: none;
}

<?php
// Note that it is important to have zero padding-left and padding-top on the th and td cells.
// These elements are used to calculate the offset top and left of the position of bookings in
// the grid when using resizable bookings.   jQuery.offset() measures to the content.  If you
// need padding put it on the contained element.
?>
.dwm_main thead th,
.dwm_main tfoot th {
  font-size: small;
  font-weight: normal;
  vertical-align: top;
  padding: 0.2em;
  color: <?php echo $standard_font_color ?>;
  background-color: #ffffff;
  background-clip: padding-box; <?php // to keep Edge happy when using position: sticky ?>
}

.dwm_main th,
.dwm_main td {
  border-right: <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_header_border_color ?>;
}

.dwm_main thead tr:last-child th {
  border-bottom: 1px solid <?php echo $banner_back_color ?>;
}

.dwm_main tfoot tr:first-child th {
  border-top: 1px solid <?php echo $banner_back_color ?>;
}

.dwm_main thead tr:first-child th:first-child {
  border-top-left-radius: 5px;
}

.dwm_main thead tr:first-child th:last-child {
  border-top-right-radius: 5px;
}

<?php
// Note that although tfoot appears at the bottom of the table, it is not the last child
// of the table as the DOM is thead tfoot tbody.
 ?>
.dwm_main tfoot tr:last-child th:first-child,
.dwm_main thead + tbody tr:last-child th:first-child {
  border-bottom-left-radius: 5px;
}

.dwm_main tfoot tr:last-child th:last-child,
.dwm_main thead + tbody tr:last-child > *:last-child {
  border-bottom-right-radius: 5px;
}


.dwm_main a,
.dwm_main .booked span.saving {
  display: block;
  height: 100%;
  width: 100%;
  min-height: inherit;
  word-break: break-all;
  word-break: break-word; <?php // Better for those browsers, eg webkit, that support it ?>
  hyphens: auto;
}

.dwm_main .booked a,
.dwm_main .booking,
.dwm_main .booked span.saving {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 50;
  overflow: hidden;
}

.dwm_main.times-along-top .booked.multiply a {
  max-height: <?php echo $main_cell_height ?>;
  box-sizing: content-box;
}

<?php
// Catch IE10 and IE11 only
// IE10 and IE11 don't support position: relative on the <td> so we have to do it on
// the wrapper.  The downside is that when resizing to make the booking larger the
// table has to expand to accommodate the relative div.
?>
@media all and (-ms-high-contrast: none), all and (-ms-high-contrast: active)
{
  .dwm_main .booking {
    position: relative;
  }
}

.dwm_main .booking {
  width: 100%;
  height: 100%; <?php // overridden if .booked.multiply ?>
  min-height: <?php echo $main_cell_height ?>;
}

.dwm_main .booked.multiply a,
.dwm_main .booked.multiply .booking {
  position: relative;
}

.dwm_main .booked.multiply .booking {
  height: <?php echo $main_cell_height ?>; <?php // overrides .dwm_main .booking { height: 100%; } ?>
}

.dwm_main .booked a {
  box-sizing: border-box;
}

.dwm_main .booked a,
.all_rooms td a div:not(.free) {
  border-bottom: 1px solid <?php echo $main_table_body_v_border_color ?>;
}

.dwm_main .booked a.saving {
  opacity: 0.4;
  color: transparent;
  pointer-events: none;
}

.dwm_main .booked span.saving {
  font-weight: bold;
}

.dwm_main .booked span.saving::after,
.loading::after {
  content: '\002026'; <?php // HORIZONTAL ELLIPSIS ?>
}

.dwm_main .booked span.saving,
.dwm_main .booked span.saving::after {
  z-index: 60;
  animation-name: pulsate;
  animation-duration: 2s;
  animation-timing-function: ease-in-out;
  animation-iteration-count: infinite;
}

@keyframes pulsate {
  from {
    opacity: 0;
  }

  50% {
    opacity: 1;
  }

  to {
    opacity: 0;
  }
}

.dwm_main tbody a {
  padding: 0.2em;
  box-sizing: border-box;
}

.dwm_main th a {
  text-decoration: none;
  font-weight: normal;
}

.dwm_main th a:link {
  color: <?php echo $anchor_link_color_header ?>;
}

.dwm_main th a:visited {
  color: <?php echo $anchor_visited_color_header ?>;
}

.dwm_main th a:hover {
  color: <?php echo $anchor_hover_color_header ?>;
  text-decoration:underline;
}

.dwm_main thead th.first_last,
.dwm_main tfoot th.first_last {
  width: 1px;
}

.dwm_main#week_main thead th.first_last,
.dwm_main#week_main tfoot th.first_last {
  vertical-align: bottom;
}

.dwm_main td.invalid {
  background-color: <?php echo $main_table_slot_invalid_color ?>;
}

.dwm_main#month_main:not(.all_rooms) tbody tr:not(:first-child) td {
  border-top:  <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_body_v_border_color ?>;
}

.dwm_main#month_main td.valid {
  background-color: <?php echo $main_table_month_color ?>;
}

.dwm_main#month_main td.invalid {
  background-color: <?php echo $main_table_month_invalid_color ?>;
}

.dwm_main#month_main:not(.all_rooms) a {
  height: 100%;
  width: 100%;
  padding: 0 2px 0 2px;
}

td.new a, a.new_booking {
  font-size: medium;
  text-align: center;
}

td.new img, .new_booking img {
  margin: auto;
  padding: 4px 0 2px 0;
}

<?php
// We use outline instead of border because jQuery UI Resizable has problems with border-box.
// Note that on Chrome (at least up until Chrome 68.0.3416.0) outline position and width can
// be 1px off when the browser zoom level is not 100%.
?>
.resizable-helper {
  outline: 2px solid #666666;
  outline-offset: -2px;
  position: absolute;
  top: 0;
  left: 0;
  z-index: 100 !important;
}


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

<?php // The containing div for a.new_booking. ?>
div.cell_container {
  position: relative;
  float: left;
  width: 100%;
  <?php echo ($month_cell_scrolling ? 'height:' : 'min-height:') ?> 100px;
}

#month_main a.new_booking {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 10;  <?php // needs to be above the base, but below the date (monthday) ?>
}

div.cell_header {
  position: relative;
  width: 100%;
  z-index: 20;  <?php // needs to be above the new booking anchor ?>
  min-height: 20%;
  height: 20%;
  max-height: 20%;
  overflow: hidden;
}

#month_main div.cell_header a {
  display: block;
  width: auto;
  float: left;
}

<?php // the date in the top left corner ?>
#month_main div.cell_header a.monthday {
  font-size: medium;
}

#month_main div.cell_header a.week_number {
  opacity: 0.5;
  padding: 2px 4px 0 4px;
}

<?php // contains the list of bookings ?>
div.booking_list {
  position: relative;
  z-index: 20;  <?php // needs to be above new_booking ?>
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
  line-height: 1.3em;
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
// Don't go for hard stops on the linear gradient because it doesn't do anti-aliasing.  Instead leave
// a pixel gap to allow the linear gradient to do some blurring.  (Unfortunately calc() doesn't
// work with linear-gradient in IE11).
foreach ($color_types as $type => $col)
{
  echo ".$type {background: $col}\n";
  echo ".full.$type {background: linear-gradient(to right bottom, $col calc(50% - 1.5px), $row_even_color calc(50% - 0.5px) calc(50% + 0.5px), $col calc(50% + 1.5px))}\n";
  echo ".spaces.$type {background: linear-gradient(to right bottom, $row_even_color calc(50% - 0.5px), $col calc(50% + 0.5px))}\n";
}

?>

.full a,
.spaces a {
  background: transparent;
}

<?php
// Put a line at the top of the div to separate the rows when the div is below an even row.
?>
tbody tr:nth-child(odd) .spaces {
  box-shadow: 0 1px 0 <?php echo $row_odd_color ?> inset;
}

.private_type {
  background: <?php echo $main_table_slot_private_type_color;?>;
}

.dwm_main thead th,
.dwm_main th:first-child {
  position: -webkit-sticky;
  position: sticky;
  z-index: 600;
}

.dwm_main thead th {
  top: 0;
}

.dwm_main th:first-child {
  left: 0;
}

.dwm_main thead th:first-child {
  z-index: 610;
}

<?php // hidden columns (eg weekends) in the week and month views ?>
.hidden_day {
  display: none;
}

td.hidden_day {
  background-color: <?php echo $column_hidden_color ?>;
  font-size: medium;
  font-weight: bold;
}

tr.row_highlight td.new {
  background-color: <?php echo $row_highlight_color ?>;
}

th {
  white-space: nowrap;
  vertical-align: middle;
}

tbody tr:nth-child(odd) th {
  background-color: <?php echo $row_odd_color ?>;
}

tbody tr:nth-child(even) th {
  background-color: <?php echo $row_even_color ?>;
}

tbody th a {
  display: inline-block;
  text-decoration: none;
  font-weight: normal
}

tbody th a:link {
  color: <?php echo $anchor_link_color_header ?>;
}

tbody th a:visited {
  color: <?php echo $anchor_visited_color_header ?>;
}

tbody th a:hover {
  color: <?php echo $anchor_hover_color_header ?>;
  text-decoration: underline;
}

<?php
// HIGHLIGHTING:  Set styles for the highlighted cells under the cursor (the time/period cell and the current cell)
?>
.dwm_main td:hover.new, .dwm_main td.new_hover {
  background-color: <?php echo $row_highlight_color ?>;
}

.dwm_main tbody tr:hover th {
  background-color: <?php echo $row_highlight_color ?>;
}

.dwm_main tbody tr:hover th a {
  color: #ffffff;
}

.dwm_main#month_main td:hover.valid,
.dwm_main#month_main td.valid_hover {
  background-color: <?php echo $row_highlight_color ?>;
}

<?php // Disable the highlighting when we're in resize mode ?>
.dwm_main.resizing tbody tr:nth-child(odd) td:hover.new {
  background-color: <?php echo $row_odd_color ?>;
}

.dwm_main.resizing tbody tr:nth-child(even) td:hover.new {
  background-color: <?php echo $row_even_color ?>;
}

.dwm_main.resizing tbody tr:hover th {
  background-color: <?php echo $main_table_labels_back_color ?>;
  color: <?php echo $anchor_link_color_header ?>;
}

.resizing tbody th a:hover {
  text-decoration: none;
}

.dwm_main.resizing tbody tr:hover th a:link {
  color: <?php echo $anchor_link_color_header ?>;
}

.dwm_main.resizing tbody tr:hover th a:visited {
  color: <?php echo $anchor_link_color_header ?>;
}

.dwm_main.resizing tbody tr th.selected {
  background-color: <?php echo $row_highlight_color ?>;
}

.dwm_main.resizing tbody tr:hover t.selected,
.dwm_main.resizing tbody tr th.selected a:link,
.dwm_main.resizing tbody tr th.selected a:visited {
  color: #ffffff;
}


.dwm_main .ui-resizable-n {
  top: -1px;
}

.dwm_main .ui-resizable-e {
  right: -1px;
}

.dwm_main .ui-resizable-s {
  bottom: -1px;
}

.dwm_main .ui-resizable-w {
  left: -1px;
}

.dwm_main .ui-resizable-se {
  bottom: 0;
  right: 0;
}

.dwm_main .ui-resizable-sw {
  bottom: -2px;
  left: -1px;
}

.dwm_main .ui-resizable-ne {
  top: -2px;
  right: -1px;
}

.dwm_main .ui-resizable-nw {
  top: -2px;
  left: -1px;
}


div.div_select {
  position: absolute;
  border: 0;
  opacity: 0.2;
  background-color: <?php echo $main_table_labels_back_color ?>;
}

div.div_select.outside {
  background-color: transparent;
}


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



/* ------ EDIT_AREA.PHP AND EDIT_ROOM.PHP ----------*/

#book_ahead_periods_note span {
  display: block;
  float: left;
  width: 24em;
  margin: 0 0 1em 1em;
  font-style: italic;
}

div#div_custom_html {
  margin-top: 2em;
}

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
  float:left;
  margin-top: 2.0em;
}

.standard fieldset {
  display: table;
  float: left;
  clear: left;
  width: auto;
  border-spacing: 0 0.75em;
  border-collapse: separate;
  padding: 1em 1em 1em 0;
}

.standard#logon fieldset {
  padding-bottom: 0;
}

.standard fieldset > div {
  display: table-row;
}

.standard fieldset > div > *:not(.none) {
  display: table-cell;
  vertical-align: middle;
}

.standard fieldset .multiline label {
  vertical-align: top;
}

.standard fieldset .field_text_area label {
  vertical-align: top;
  padding-top: 0.2em;
}

.standard fieldset > div > div > * {
  float: left;
  margin-top: 0;
}

.standard ul {
  clear: left;
}

.reset_password .standard p {
  float: left;
  clear: left;
}

.standard fieldset fieldset {
  padding: 1em 0;
}

.standard fieldset fieldset legend{
  font-size: small;
  font-style: italic;
  font-weight: normal;
}

.standard fieldset fieldset fieldset legend {
  padding-left: 2em;
}

.standard fieldset > div > label {
  font-weight: bold;
  padding-left: 2em;
  padding-right: 1em;
  text-align: right;
}

.standard fieldset > div > div {
  text-align: left;
}

.standard div.group {
  display: inline-block;
  float: left;
}

.standard div.group.long label {
  float: left;
  clear: left;
  margin-bottom: 0.5em;
}

.standard input[type="text"]:not(.date):not(.form-control),
.standard input[type="email"],
.standard input[type="password"],
.standard input[type="search"],
.standard textarea {
  width: 17rem;  <?php // Use rem not em because fonts may be different ?>
}

.standard input[type="text"].short {
  width: 4em;
}

.standard input[type="number"] {
  width: 4em;
}

.standard input[type="number"][step="0.01"] {
  width: 6em;
}

.standard input[type="radio"], .standard input[type="checkbox"] {
  vertical-align: middle;
  margin: -0.17em 0.4em 0 0;
}

.standard input, .standard input.enabler, .standard select {
  margin-right: 1em;
}

.standard textarea {
  height: 6em;
}

.standard .group label {
  margin-right: 0.5em;
}

<?php
// On narrow devices put the form labels above the form inputs, instead
// of to the left.
?>
@media (max-width: 30rem) {
  .standard fieldset {
    display: block;
  }

  .standard fieldset > div {
    display: block;
    float: left;
    clear: left;
  }

  .standard fieldset div > * {
    margin-bottom: 1em;
  }

  .standard fieldset > div > *:not(.none) {
    display: block;
    vertical-align: middle;
  }

  .standard fieldset > div > label {
    text-align: left;
    padding-left: 0;
    padding-right: 0;
    margin-bottom: 0.2em;
  }

  .standard fieldset.rep_type_details {
    padding-bottom: 0;
    margin-bottom: 0;
  }

  .standard input#rep_interval {
    float: left;
  }

  .standard div#rep_type div.long {
    border-right: 0;
  }

  .standard .submit_buttons > * {
    float: left;
  }
}

<?php
// The max number of bookings policy fieldset, where we want to display the
// controls in tabular form
?>

.standard fieldset.max_limits > div > label {
  vertical-align: top;
  margin-bottom: 0.5em;
}

.max_limits div:first-of-type span {
  display: inline-block;
  width: 50%;
}

.max_limits div div div {
  float: left;
  margin-bottom: 0.5em;
}

.max_limits div:first-of-type span {
  white-space: normal;
  font-style: italic;
}

.max_limits div div {
  white-space: nowrap;
}

.max_limits input {
  display: inline-block;
}

.max_limits input[type="checkbox"] {
  margin-right: 1em;
}

div#rep_type div.long{
  border-right: 1px solid <?php echo $site_faq_entry_border_color ?>;
  padding-right: 1em;
}

fieldset.rep_type_details fieldset {
  padding-top: 0
}

#rep_monthly input[type="radio"] {
  margin-left: 2em;
}

.standard fieldset fieldset.rep_type_details {
  padding-top: 0;
  clear: none;
}

fieldset#rep_info,
fieldset#booking_controls {
  border-top: 1px solid <?php echo $site_faq_entry_border_color ?>;
  border-radius: 0;
  padding-top: 0.7em;
}

span#interval_units {
  display: inline-block;
}

.edit_entry span#end_time_error {
  display: block;
  float: left;
  margin-left: 2em;
  font-weight: normal;
}

div#checks {
  white-space: nowrap;
  letter-spacing: 0.9em;
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


/* ------------ EDIT_ENTRY_HANDLER.PHP ------------------*/

.edit_entry_handler div#submit_buttons {
  float: left;
}

.edit_entry_handler #submit_buttons form {
  float: left;
  margin: 1em 2em 1em 0;
}


/* ------------ EDIT_USERS.PHP ------------------*/

div#user_list {
  padding: 2em 0;
}

form#add_new_user {
  margin-left: 1em;
}

#users_table td {
  text-align: right;
}

#users_table td div.string {
  text-align: left;
}


/* ------------ FUNCTIONS.INC -------------------*/

.banner {
  display: -ms-flexbox;
  display: flex;
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
  -ms-flex-direction: row;
  flex-direction: row;
  -ms-flex-pack: start;
  justify-content: flex-start;
  background-color: <?php echo $banner_back_color ?>;
  color: <?php echo $banner_font_color ?>;
  border-color: <?php echo $banner_border_color ?>;
  border-width: <?php echo $banner_border_width ?>px;
  border-style: solid;
}

.banner .logo {
  display: -ms-flexbox;
  display: flex;
  -ms-flex-align: center;
  align-items: center;
  -ms-flex-pack: start;
  justify-content: flex-start;
}

.banner .logo img {
  display: block;
  margin: 1em 2em 1em 0;
}

.banner .company {
  display: -ms-flexbox;
  display: flex;
  -ms-flex-direction: column;
  flex-direction: column;
  -ms-flex-align: center;
  align-items: center;
  -ms-flex-pack: center;
  justify-content: center;
  font-size: large;
  padding: 0.5rem 2rem 0.5rem 0;
  margin-right: 2rem;
  white-space: nowrap;
}

.banner .company > * {
  display: -ms-flexbox;
}

.banner a:link, .banner a:visited, .banner a:hover {
  text-decoration: none;
  font-weight: normal;
}

.banner a:link, nav.logon input {
  color: <?php echo $anchor_link_color_banner ?>;
}

.banner a:visited {
  color: <?php echo $anchor_visited_color_banner ?>;
}

.banner a:hover {
  color: <?php echo $anchor_hover_color_banner ?>;
}

.banner nav.container {
  width: 50%;
  -ms-flex-positive: 1;
  flex-grow: 1;
  display: -ms-flexbox;
  display: flex;
  -ms-flex-direction: row-reverse;
  flex-direction: row-reverse;
  justify-content: space-between;
  align-items: stretch;
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
  padding: 0.75em 0;
}

.banner nav.container > nav {
  display: -ms-flexbox;
  display: flex;
  -ms-flex-direction: row;
  flex-direction: row;
  -ms-flex-align: stretch;
  align-items: stretch;
  justify-content: flex-end;
  padding: 0.3em 0;
}

.banner nav.container > nav > nav {
  -ms-flex-align: center;
  align-items: center;
}

.banner nav.container > nav:first-child {
  -ms-flex-wrap: wrap-reverse;
  flex-wrap: wrap-reverse;
}

.banner nav.container > nav:last-child > * {
  display: -ms-flexbox;
  display: flex;
  -ms-flex-align: center;
  align-items: center;
}

nav.menu, nav.logon {
  margin-left: 1rem;
  padding-left: 1rem;
}

nav.menu {
  display: -ms-flexbox;
  display: flex;
}

nav.logon {
  display: -ms-flexbox;
  display: flex;
  -ms-flex-align: center;
  align-items: center;
}

nav.logon input {
  background: none;
  border: none;
}

.banner nav a,
nav.logon input,
nav.logon span {
  display: inline-block;
  text-align: center;
  padding: 0.3rem 1rem;
  line-height: 1.5em;
  border-radius: 0.8em;
}

.banner a.attention {
  background-color: <?php echo $attention_color ?>;
}

.banner nav a:hover,
nav.logon input:hover {
  background-color: <?php echo $banner_nav_hover_color ?>;
  color: #ffffff;
}

#form_nav {
  padding-right: 1rem;
  margin-right: 1rem;
}

input.link[type="submit"] {
  display: inline;
  border: none;
  background: none;
  cursor: pointer;
  font-weight: bold;
  padding: 0;
}

form#show_my_entries input.link[type="submit"] {
  color: <?php echo $anchor_link_color_banner ?>;
  padding: 0.3em 0;
  font-weight: normal;
}

@media (max-width: 30rem) {
  .banner .logo {
    display: none;
  }

  .banner .company {
    align-items: flex-start;
  }

  .banner #more_info {
    display: none;
  }

  <?php
  // Save room on the index page by not showing the "Goto" button.  However
  // it's useful on other pages as a means of returning to the index page.
  ?>
  .index #form_nav input[type="submit"] {
    display: none;
  }
}

<?php

// THE COLOR KEY
//
// Displays as a grid for those browsers that support it, falling back to a flexbox.  The
// differences between the two levels of support are:
//
//  Grid:             If there is some spare space, the divs expand so that the grid rows
//                    nicely fill 100% of the container width.
//  Flexbox:          As Grid, but the divs are of fixed width and won't expand, but will
// (IE10 and IE11)    wrap onto the next row.
?>

.color_key {
  display: -ms-flexbox;
  display: flex;
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
}

.color_key {
  display: inline-grid;
  grid-template-columns: repeat(auto-fill, minmax(20ch, 1fr));
  width: 100%;
  margin-top: 1em;
}

.color_key > div {
  width: 12em;
  color: <?php echo $color_key_font_color ?>;
  word-wrap: break-word;
  padding: 0.3em;
  margin: -1px 0 0 -1px; <?php // to collapse the borders ?>
  font-weight: bold;
  border: <?php echo $main_table_cell_border_width ?>px solid <?php echo $main_table_body_h_border_color ?>
}

@supports (display: grid) {
  .color_key > div {
    width: auto;
  }
}



header input[type="search"] {
  width: 10em;
}

.banner .outstanding a {
  color: <?php echo $outstanding_color ?>;
}


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
}

.details td:first-child {
  text-align: right;
  white-space: nowrap;
}


/* ------------ IMPORT.PHP ------------------------*/

div.problem_report {
  border-bottom: 1px solid <?php echo $site_faq_entry_border_color ?>;
  margin-top: 1em;
}


/* ------------ MINCALS.PHP ---------------------*/

table.minicalendar {
  border-spacing: 0;
  border-collapse: collapse;
}

.minicalendar th {
  min-width: 2.0em;
  text-align: center;
  font-weight: normal;
  background-color: transparent;
}

.minicalendar thead tr:first-child th {
  text-align: center;
  vertical-align: middle;
  line-height: 1.5em;
}

.minicalendar thead tr:first-child th:first-child {
  text-align: left;
}

.minicalendar thead tr:first-child th:last-child {
  text-align: right;
}

.minicalendar td {
  text-align: center;
  font-size: x-small;
}

.minicalendar a.arrow {
  display: block;
  width: 100%;
  height: 100%;
  text-align: center;
}

.minicalendar td > * {
  display: block;
  width: 2em;
  height: 2em;
  line-height: 2em;
  margin: auto;
  border-radius: 50%;
}

.minicalendar td.today a,
.minicalendar td a:hover {
  background-color: <?php echo $minical_today_color ?>;
  color: <?php echo $standard_font_color ?>
}

.minicalendar .view {
  background-color: <?php echo $minical_view_color ?>;
}

.minicalendar .hidden {
  opacity: 0.7
}

.minicalendar a.current {
  font-weight: bold;
  color: <?php echo $highlight_font_color ?>;
}


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

#pending_list td:first-child {
  width: 1.2em;
}

#pending_list #pending_table td.sub_table {
  width: auto;
}

table.admin_table.sub {
  border-right-width: 0;
}

table.sub th {
  background-color: #788D9C;
}

.js .admin_table table.sub th:first-child {
  background-color: <?php echo $pending_control_color ?>;
  border-left-color: <?php echo $admin_table_border_color ?>;
}

#pending_list form {
  margin: 2px 4px;
}


/* ------------ REPORT.PHP ----------------------*/

div#div_summary {
  padding-top: 3em;
}

#div_summary table {
  border-spacing: 1px;
  border-collapse: collapse;
  border-color: <?php echo $report_table_border_color ?>;
  border-style: solid;
  border-top-width: 1px;
  border-right-width: 0;
  border-bottom-width: 0;
  border-left-width: 1px;
}

#div_summary td, #div_summary th {
  padding: 0.1em 0.2em 0.1em 0.2em;
  border-color: <?php echo $report_table_border_color ?>;
  border-style: solid;
  border-top-width: 0;
  border-right-width: 1px;
  border-bottom-width: 1px;
  border-left-width: 0;
}

#div_summary th {
  background-color: transparent;
  font-weight: bold;
  text-align: center;
}

#div_summary thead tr:nth-child(2) th {
  font-weight: normal;
  font-style: italic;
}

#div_summary th:first-child {
  text-align: right;
}

#div_summary tfoot th {
  text-align: right;
}

#div_summary td {
  text-align: right;
}

#div_summary tbody td:nth-child(even),
#div_summary tfoot th:nth-child(even) {
  border-right-width: 0;
}

#div_summary td:first-child {
  font-weight: bold;
}

p.report_entries {
  float: left;
  clear: left;
  font-weight: bold
}

button#delete_button {
  float: left;
  clear: left;
  margin: 1em 0 3em 0;
}


/* ------------ SEARCH.PHP ----------------------*/

h3.search_results {
  clear: left;
  margin-bottom: 0;
  padding-top: 2em;
}

.search p.error {
  clear: left;
}

p#nothing_found {
  font-weight: bold;
}

div#record_numbers {
  font-weight: bold;
}

div#record_nav {
  float: left;
  margin: 0.5em 0;
}

div#record_nav form {
  float: left;
}

div#record_nav form:first-child {
  margin-right: 1em;
}


/* ------------ SITE_FAQ ------------------------*/

.help q {
  font-style: italic;
}

.help dfn {
  font-style: normal;
  font-weight: bold;
}

#site_faq_contents li a {
  text-decoration: underline;
}

div#site_faq_body {
  margin-top: 2.0em;
}

#site_faq_body h4 {
  border-top: 1px solid <?php echo $site_faq_entry_border_color ?>;
  padding-top: 0.5em;
  margin-top: 0;
}

#site_faq_body div {
  padding-bottom: 0.5em;
}

#site_faq_body :target {
  background-color: <?php echo $help_highlight_color ?>;
}


/* ------------ VIEW_ENTRY.PHP ------------------*/

.view_entry #entry td:first-child,
.view_entry #registration .list td:first-child {
  text-align: right;
  font-weight: bold;
  padding-right: 1.0em;
}

.view_entry #registration .list {
  margin-bottom: 1em;
}

.view_entry #entry,
.view_entry #registration {
  padding-left: 1em;
}

.view_entry #registration {
  margin-bottom: 2em;
}

.view_entry #registration p {
  margin-top: 0;
  margin-bottom: 0;
}

.view_entry #registration .standard {
  display: inline-block;
  float: none;
  margin-top: 0;
}

.view_entry #registration .standard fieldset {
  padding-top: 0;
  padding-bottom: 0;
}

.view_entry #registration .standard fieldset > div > label {
  padding-left: 0;
}

.view_entry #registrant_list {
  padding-left: 1em;
}

.view_entry #registrants thead th {
  text-align: left;
}

.view_entry #registrants th,
.view_entry #registrants td {
  vertical-align: middle;
  padding: 0.4em 0.8em;
}

.view_entry #registrants td:not(:first-child) {
  width: 33%;
}

.view_entry h4 {
  margin-top: 2em;
  margin-bottom: 1em;
  clear: left;
}

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

#approve_buttons td {
  vertical-align: middle;
  padding-top: 1em;
}

#approve_buttons td#caption {
  text-align: left;
}

#approve_buttons td#note {
  padding-top: 0;
}

#approve_buttons td#note form {
  width: 100%;
}

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

.js .datatable_container {
  visibility: hidden;
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



<?php
// Modify the flatpickr blue
?>
.flatpickr-day.selected,
.flatpickr-day.startRange,
.flatpickr-day.endRange,
.flatpickr-day.selected.inRange,
.flatpickr-day.startRange.inRange,
.flatpickr-day.endRange.inRange,
.flatpickr-day.selected:focus,
.flatpickr-day.startRange:focus,
.flatpickr-day.endRange:focus,
.flatpickr-day.selected:hover,
.flatpickr-day.startRange:hover,
.flatpickr-day.endRange:hover,
.flatpickr-day.selected.prevMonthDay,
.flatpickr-day.startRange.prevMonthDay,
.flatpickr-day.endRange.prevMonthDay,
.flatpickr-day.selected.nextMonthDay,
.flatpickr-day.startRange.nextMonthDay,
.flatpickr-day.endRange.nextMonthDay {
  background: <?php echo $flatpickr_highlight_color ?>;
  border-color: <?php echo $flatpickr_highlight_color ?>;
}

.flatpickr-day.selected.startRange + .endRange,
.flatpickr-day.startRange.startRange + .endRange,
.flatpickr-day.endRange.startRange + .endRange {
  -webkit-box-shadow: -10px 0 0 <?php echo $flatpickr_highlight_color ?>;
  box-shadow: -10px 0 0 <?php echo $flatpickr_highlight_color ?>;
}

.flatpickr-day.week.selected {
  -webkit-box-shadow:-5px 0 0 <?php echo $flatpickr_highlight_color ?>, 5px 0 0 <?php echo $flatpickr_highlight_color ?>;
  box-shadow:-5px 0 0 <?php echo $flatpickr_highlight_color ?>, 5px 0 0 <?php echo $flatpickr_highlight_color ?>;
}

.flatpickr-day.mrbs-hidden {
  color: rgba(57,57,57,0.1);
}

<?php
// Fix to stop calendars being 1px wide when the screen is widened.
// See https://github.com/flatpickr/flatpickr/issues/1300
?>
.flatpickr-calendar, .flatpickr-days {
    width: auto !important;
}

h2.date.loading,
h2.date.loading::after {
  animation-name: pulsate;
  animation-duration: 2s;
  animation-timing-function: ease-in-out;
  animation-iteration-count: infinite;
}

.ajax-loading input.select2-search__field {
  background-image: url(../images/ajax-loader.gif);
  background-position: center right 5px;
  background-repeat: no-repeat;
}

