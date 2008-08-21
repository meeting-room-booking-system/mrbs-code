<?php 

// $Id$

header("Content-type: text/css"); 

?>

body {color:black; font-size: 10pt; font-family:arial,sans-serif;
background-color:#ffffed}


h1 {color:black; font-family:verdana,sans-serif; font-size:16pt}
h2 {color:black; font-family:verdana,sans-serif; font-size:14pt}
/* H3 {font-family:verdana,sans-serif} */

td {font-size:10pt; font-family: arial,sans-serif; border-width: 1px; vertical-align: top}
td.header {color:black; font-family:verdana,sans-serif; border-width:0;
background-color:#ffffdd; font-size:26pt}

td.TR { vertical-align: top; text-align: right}
td.TL { vertical-align: top; text-align: left}

td form { margin:0; } /* Prevent IE from displaying margins around forms in tables. */

th {color:#eeeeee; font-size:10pt; font-family:verdana,sans-serif; background-color:#999999; border-width:1px; border-color:#999999; vertical-align:top}


td.red  {background-color:#FFF0F0}
td.green {background-color:#DDFFDD}
td.A {background-color:#FFCCFF}
td.B {background-color:#99CCCC}
td.C {background-color:#FF9999}
td.D {background-color:#FFFF99}
td.E {background-color:#C0E0FF}
td.F {background-color:#FFCC99}
td.G {background-color:#FF6666}
td.H {background-color:#66FFFF}
td.I {background-color:#DDFFDD}
td.J {background-color:#CCCCCC}
td.white {background-color:#FFFFFF}

td.even_row {background-color:#FFFFFF}   /* Even rows in the day view */
td.odd_row {background-color:#EEEEEE}   /* Odd rows in the day view */

td.highlight {background-color:#AABBFF; border-style: solid; border-width: 1px; border-color:#0000AA;} /* The highlighted cell under the cursor */


.sitename
{font-size: 18px;
font-style: normal;
font-weight: bold;
text-transform: none;
color:#ffffff;
position: absolute;
left:30px;
top:12px}






/* ------------ GENERAL -----------------------------*/
<?php
$banner_back_color              = "#c0e0ff";    // background colour for banner
$banner_border_color            = "#5b69a6";    // border colour for banner

$admin_table_header_back_color  = "#999999";    // background colour for header and also border colour for table cells
$admin_table_header_sep_color   = "#eeeeee";    // vertical separator colour in header
$admin_table_header_font_color  = "#eeeeee";    // font colour for header

$main_table_border_color        = "#555555";    // border colour for day/week/month tables
$main_table_month_color         = "#ffffff";    // background colour for days in the month view
$main_table_month_invalid_color = "#cccccc";    // background colour for invalid days in the month view

$report_table_border_color      = $main_table_border_color;
$report_h2_border_color         = "#474747";    // border colour for <h2> in report.php
$report_h3_border_color         = "#808080";    // border colour for <h2> in report.php
$report_entry_border_color      = "#D0D0D0";    // used to separate individual bookings in report.php

$search_table_border_color      = $main_table_border_color;

$site_faq_entry_border_color    = $report_entry_border_color;    // used to separate individual FAQ's in help.php

$trailer_border_color           = $main_table_border_color;

$anchor_link_color              = "#5b69a6";            // link color
$anchor_visited_color           = $anchor_link_color;   // link color (visited)
$anchor_hover_color             = "red";                // link color (hover)

?>

.current {color: red}                          /* used to highlight the current item */
.error   {color: red; font-weight: bold}       /* for error messages */

a:link    {color: <?php echo $anchor_link_color ?>;    font-weight: bold; text-decoration: none}
a:visited {color: <?php echo $anchor_visited_color ?>; font-weight: bold; text-decoration: none}
a:hover   {color: <?php echo $anchor_hover_color ?>;   text-decoration:underline}

legend {font-weight: bold; font-size: large; color: black}
fieldset {margin: 0; padding: 0; border: 0}
fieldset.admin {width: 100%; padding: 0 1.0em 1.0em 1.0em;
  border: 1px solid <?php echo $admin_table_header_back_color ?>}
fieldset fieldset {position: relative; clear: left; width: 100%; padding: 0; border: 0; margin: 0}  /* inner fieldsets are invisible */
fieldset fieldset legend {font-size: 0}        /* for IE: even if there is no legend text, IE allocates space  */

img.new_booking {display: block; margin-left: auto; margin-right: auto; border: 0}

table.admin_table {border-spacing: 0px; border-collapse: collapse; border-color: <?php echo $admin_table_header_back_color ?>; border-style: solid;
    border-top-width: 0; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 0}
.admin_table th {color: <?php echo $admin_table_header_font_color ?>; font-size:10pt; ; font-weight: bold; font-family: verdana, sans-serif;
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
    font-family: arial,sans-serif; font-size: 10pt;
}
.form_admin input.submit {
    width: auto; margin-top: 1.2em; margin-left: <?php echo ($admin_form_gap + $admin_form_label_width)?>em
}


/* ------------ DAY/WEEK/MONTH.PHP ------------------*/
div#dwm_header {width: 100%; float: left; margin-top: 1.0em; margin-bottom: 0.5em}
div#dwm_areas  {float: left; margin-right: 2.0em}
div#dwm_rooms  {float: left; margin-right: 2.0em}
#dwm_header h3 {color:black; font-size: small; font-weight: normal; font-family: arial, sans-serif; text-decoration: underline; 
    margin-top: 0; margin-bottom: 0.2em; padding-bottom: 0}
#dwm_header ul {list-style-type: none; padding-left: 0; margin-left: 0; margin-top: 0}
#dwm_header li {padding-left: 0; margin-left: 0}

h2#dwm {text-align: center}

div.date_nav    {float: left;  width: 100%; margin-top: 0.5em; margin-bottom: 0.5em}
div.date_before {float: left;  width: 33%; text-align: left}
div.date_now    {float: left;  width: 33%; text-align: center}
div.date_after  {float: right; width: 33%; text-align: right}

table.dwm_main {clear: both; width: 100%; border-spacing: 1px; border-collapse: collapse}
.dwm_main td, .dwm_main th {border: 1px solid <?php echo $main_table_border_color ?>; padding: 0}
.dwm_main#day_main th.first_last {width: 1%}
.dwm_main#day_main td, .dwm_main#week_main td {padding: 2px}
.dwm_main#month_main th {width: 14%}                                                   /* 7 days in the week */
.dwm_main#month_main td.valid   {background-color: <?php echo $main_table_month_color ?>}
.dwm_main#month_main td.invalid {background-color: <?php echo $main_table_month_invalid_color ?>}
div.cell_container {float: left; min-height: 100px; width: 100%}                       /* the containing div for the td cell contents */
div.cell_container div {width: 100%; float: left; clear: left}                         /* each of the sections in the cell is wrapped in another div */                        
a.monthday {display: block; font-size: medium; padding: 0 2px 0 2px}                   /* first section: the date in the top left corner */
.dwm_main#month_main span {display: block; font-size: x-small; padding: 0 2px 0 2px}   /* then details of any bookings */
.dwm_main#month_main img {border: 0; padding: 5px 0 5px 2px}                           /* finally the new booking image */
.dwm_main#week_main th {width: 14%}
.dwm_main#week_main th.first_last {width: 1%; vertical-align: bottom}  


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
    font-family: arial,sans-serif; font-size: 10pt
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
    font-family: arial,sans-serif; font-size: 10pt
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
    border-top-width: 0; border-right-width: 2px; border-bottom-width: 2px; border-left-width: 0}
#banner td {text-align: center; vertical-align: middle; background-color: <?php echo $banner_back_color ?>;
    border-color: <?php echo $banner_border_color ?>; border-style: solid;
    border-top-width: 2px; border-right-width: 0; border-bottom-width: 0; border-left-width: 2px}
#banner td#company {font-size: large; font-weight: bold}
#banner #company span {display: block; width: 100%}
table#colour_key {clear: both; border-spacing: 0; border-collapse: collapse}
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
.calendar th {min-width: 2.0em; text-align: center; font-family: arial,sans-serif;
    font-weight: normal; color: black; background-color: transparent}
.calendar td {text-align: center; font-size: x-small; font-weight: bold}
.calendar a.current {color: red}


/* ------------ REPORT.PHP ----------------------*/
.div_report h2, #div_summary h1 {border-top: 2px solid <?php echo $report_h2_border_color ?>;
    padding-top: 0.5em; margin-top: 2.0em}
.div_report h3 {border-top: 1px solid <?php echo $report_h3_border_color ?>;
    padding-top: 0.5em; margin-bottom: 0}
.div_report table {clear: both; width: 100%; margin-top: 0.5em}
.div_report col.col1 {width: 8em}
.div_report td:first-child {text-align: right; font-weight: bold}
.div_report .createdby td, .div_report .lastupdate td {font-size: x-small}
div.report_entry_title {width: 100%; float: left; font-family: verdana, sans-serif;
    border-top: 1px solid <?php echo $report_entry_border_color ?>; margin-top: 0.8em}
div.report_entry_name  {width: 40%;  float: left; font-weight: bold}
div.report_entry_when  {width: 60%;  float: right; text-align: right}
#div_summary table {border-spacing: 1px; border-collapse: collapse;
    border-color: <?php echo $report_table_border_color ?>; border-style: solid;
    border-top-width: 1px; border-right-width: 0px; border-bottom-width: 0px; border-left-width: 1px}
#div_summary td, #div_summary th {padding: 0.1em 0.2em 0.1em 0.2em;
    border-color: <?php echo $report_table_border_color ?>; border-style: solid;
    border-top-width: 0; border-right-width: 1px; border-bottom-width: 1px; border-left-width: 0}
#div_summary th {background-color: transparent; color: black; font-weight: bold; text-align: center}
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
#site_faq_body :target {background-color: #ffffbb}


/* ------------ TRAILER.INC ---------------------*/
div#trailer {border-top: 1px solid <?php echo $trailer_border_color ?>; 
             border-bottom: 1px solid <?php echo $trailer_border_color ?>; 
             margin-top: 1.0em; padding: 0.3em 0 0.3em 0}
#trailer span.label {font-weight: bold}
#trailer span.current {font-weight: bold; color: black}

/* ------------ VIEW_ENTRY.PHP ------------------*/
.view_entry #entry td:first-child {text-align: right; font-weight: bold; padding-right: 1.0em}
.view_entry div#view_entry_nav {margin-top: 1.0em}
