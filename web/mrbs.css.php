<?php 

// $Id$

header("Content-type: text/css"); 
include "config.inc.php";

?>


/* ------------ GENERAL -----------------------------*/
<?php

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
$main_table_month_invalid_color = "#cccccc";    // background colour for invalid days in the month view

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
$row_odd_color                  = "#f8f8f8";        // even rows in the day and week views
$row_highlight_color            = "#ffc0da";        // used for highlighting a row
                                                    // NOTE: this colour is also used in xbLib.js (in more than one place)and 
                                                    // if you change it here you will also need to change it there.

$help_highlight_color           = "#ffe6f0";        // highlighting text on the help page #ffffbb

// These are the colours used for distinguishing between the dfferent types of bookings in the main
// displays in the day, week and month views
$color_types = array(
    'A' => "#FFFF99",
    'B' => "#99CCCC",
    'C' => "#ffffcd",
    'D' => "#cde6e6",
    'E' => "#99cc66",
    'F' => "#82adad",
    'G' => "#ccffcc",
    'H' => "#e6ffe6",
    'I' => "#d9d982",
    'J' => "#6dd9c4");

    
    
    
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
a:hover   {color: <?php echo $anchor_hover_color ?>;   text-decoration:underline; font-weight: bold}

td, th {vertical-align: top}

td.highlight {background-color: <?php echo $row_highlight_color ?>; border-style: solid; border-width: 1px; border-color:#0000AA;} /* The highlighted cell under the cursor */

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


/* ------------ ADMIN.PHP ---------------------------*/
<?php
// Adjust the label width to suit the longest label - it will depend on the translation being used
// The input width can normally be left alone
$admin_form_label_width       = 7.0;   // em
$admin_form_gap               = 1.0;   // em
$admin_form_input_width       = 8.3;   // em   (Also used in edit_area_room.php)
?>
table#admin {margin-bottom: 1.0em}
#admin th {text-align: center}
#admin td {padding: 0.5em; vertical-align: top}
.form_admin fieldset {border: 0; padding-top: 1.0em}   
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
    width: auto; margin-top: 1.2em; margin-left: <?php echo ($admin_form_gap + $admin_form_label_width)?>em
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
$table_dwm_main_border_width = 1;    // px
?>
table.dwm_main {clear: both; width: 100%; border-spacing: 0; border-collapse: separate; border: 0}
.dwm_main td {padding: 0;
   border-top:  <?php echo $table_dwm_main_border_width ?>px solid <?php echo $main_table_body_h_border_color ?>;
   border-left: <?php echo $table_dwm_main_border_width ?>px solid <?php echo $main_table_body_v_border_color ?>;
   border-bottom: 0;
   border-right: 0}
.dwm_main td:first-child {border-left: 0}
.dwm_main th {font-size: small; font-weight: normal; vertical-align: top; padding: 0;
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
div.cell_container {float: left; min-height: 100px; height: 100px; width: 100%}    /* the containing div for the td cell contents */ 
div.cell_header  {height: 20%; min-height: 20%; max-height: 20%; overflow: hidden; position: relative}
div.booking_list {height: 80%; min-height: 80%; max-height: 80%; overflow: auto; font-size: x-small}                                                         /* contains the list of bookings */
a.monthday {display: block; position: absolute; top: 0; left: 0; font-size: medium}                                                   /* the date in the top left corner */
a.new_booking {display: block; width: 100%; font-size: medium; text-align: center}
.new_booking img {margin: auto; border: 0; padding: 4px 0 2px 0}
.booking_list a {font-size: x-small}
.dwm_main#month_main a {padding: 0 2px 0 2px}

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

$main_cell_height = 17;          // Units specified below
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
    $n_slots = ($n_slots*60)/$resolution;                                  // number of slots
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

$edit_area_room_label_width       = 10.0;    // em
$edit_area_room_input_margin_left = 1.0;
$edit_area_room_input_width       = $admin_form_input_width;
$edit_area_room_width_overheads   = 0.7;     // borders around inputs etc.    Konqueror seems to be the most extreme
$edit_area_room_form_width        = $edit_area_room_label_width + $edit_area_room_input_margin_left + $edit_area_room_input_width + $edit_area_room_width_overheads;
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
$general_label_height          = 1.0;     // em
$general_left_col_width        = 20;      // %
$general_right_col_width       = 80;      // %
$general_gap                   = 1.0;     // em  (gap between left and right columns)

// Specific to the "edit_entry" form
$edit_entry_left_col_max_width = 10;      // em
$edit_entry_textarea_width     = 26;      // em
$edit_entry_form_min_width     = $edit_entry_left_col_max_width + $edit_entry_textarea_width + $general_gap;

// Specific to the "report" form
$report_left_col_max_width     = 12;      // em
$report_input_width            = 12;      // em
$report_form_min_width         = $report_left_col_max_width + $report_input_width + $general_gap;

// Specific to the "search" form
$search_left_col_max_width     = 8;       // em
$search_input_width            = 12;      // em
$search_form_min_width         = $search_left_col_max_width + $search_input_width + $general_gap;

// Specific to the "logon" form
$logon_left_col_max_width      = 8;       // em
$logon_input_width             = 12;      // em
$logon_form_min_width          = $logon_left_col_max_width + $logon_input_width + $general_gap;
?>
form.form_general {margin-top: 2.0em; width: 100%}
.edit_entry form.form_general {min-width: <?php echo $edit_entry_form_min_width ?>em}
.report     form.form_general {min-width: <?php echo $report_form_min_width ?>em}
.search     form.form_general {min-width: <?php echo $search_form_min_width ?>em}
form.form_general#logon       {min-width: <?php echo $logon_form_min_width ?>em}

.form_general div {float: left; clear: left; width: 100%}
.form_general div div {float: none; clear: none; width: auto}
.form_general div.group {display: table-cell; float: left; width: <?php echo $general_right_col_width ?>%}
.form_general fieldset {width: auto; border: 0; padding-top: 2.0em}

.form_general label {
    display: block; float: left; 
    min-height: <?php echo $general_label_height ?>em; 
    width: <?php echo $general_left_col_width ?>%; 
    text-align: right; padding-bottom: 0.8em; font-weight: bold;
}
.edit_entry .form_general label {max-width: <?php echo $edit_entry_left_col_max_width ?>em;
                                     width: <?php echo $edit_entry_left_col_max_width ?>em}
.report     .form_general label {max-width: <?php echo $report_left_col_max_width ?>em;
                                     width: <?php echo $report_left_col_max_width ?>em}
.search     .form_general label {max-width: <?php echo $search_left_col_max_width ?>em;
                                     width: <?php echo $search_left_col_max_width ?>em}
#logon                    label {max-width: <?php echo $logon_left_col_max_width ?>em;
                                     width: <?php echo $logon_left_col_max_width ?>em}
.form_general .group      label {clear: none; width: auto; max-width: 100%; font-weight: normal}

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
.edit_entry .form_general input.submit {margin-left: <?php echo ($edit_entry_left_col_max_width + $general_gap) ?>em}
.report     .form_general input.submit {margin-left: <?php echo ($report_left_col_max_width + $general_gap) ?>em}
.search     .form_general input.submit {margin-left: <?php echo ($search_left_col_max_width + $general_gap) ?>em}
#logon                    input.submit {margin-left: <?php echo ($logon_left_col_max_width + $general_gap) ?>em; width: auto}

.form_general #div_time input {width: 1.5em}
.form_general #div_time span + input {margin-left: 0}
.form_general #div_time span {display: block; float: left; margin-left: 0.2em; margin-right: 0.2em}
.form_general input#duration {width: 3.0em}
.form_general select#dur_units {margin-right: 1.0em}
.form_general div#ad {float: left}
.form_general #ad label {clear: none; text-align: left; font-weight: normal}
.form_general input#all_day {width: auto; margin-left: 1.0em; margin-right: 0.5em}
.form_general #div_rooms select, .form_general #div_typematch select {float: left; margin-right: 2.0em}
.form_general fieldset#rep_info {padding-top: 0}
.form_general #rep_info input {width: 13em}
.form_general input#rep_num_weeks {width: 1.5em}


/* ------------ EDIT_USERS.PHP ------------------*/
<?php
$edit_users_label_height     = 2.0;   // em
$edit_users_label_width      = 10.0;   // em
$edit_users_gap              = 1.0;   // em
$edit_users_input_width      = 10.0;   // em
$edit_users_form_width = $edit_users_label_width + $edit_users_gap + $edit_users_input_width + 5;
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
span#search_str {color: blue}
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
             margin-top: 1.0em; padding: 0.3em 0 0.3em 0}
#trailer span.label {font-weight: bold; padding-right: 1.0em}
#trailer span.current {font-weight: bold}
#trailer .current a {color: <?php echo $highlight_font_color ?>}


/* ------------ VIEW_ENTRY.PHP ------------------*/
.view_entry #entry td:first-child {text-align: right; font-weight: bold; padding-right: 1.0em}
.view_entry div#view_entry_nav {margin-top: 1.0em}
