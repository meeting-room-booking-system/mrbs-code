<?php 

// $Id$

header("Content-type: text/css"); 

?>

BODY {color:black; font-size: 10pt; font-family:arial,sans-serif;
background-color:#ffffed}

A:link {color:#5B69A6; font-weight: bold; text-decoration: none}
A:visited {color:#5B69A6; font-weight: bold; text-decoration: none}
A:hover {color:red; text-decoration:underline}
H1 {color:black; font-family:verdana,sans-serif; font-size:16pt}
H2 {color:black; font-family:verdana,sans-serif; font-size:14pt}
H3 {font-family:verdana,sans-serif}

TD {font-size:10pt; font-family: arial,sans-serif; border-width: 1px; vertical-align: top}
TD.header {color:black; font-family:verdana,sans-serif; border-width:0;
background-color:#ffffdd; font-size:26pt}
TD.CR { vertical-align: middle; text-align: right}
TD.CL { vertical-align: middle; text-align: left}
TD.BR { vertical-align: baseline; text-align: right}
TD.BL { vertical-align: baseline; text-align: left}
TD.TR { vertical-align: top; text-align: right}
TD.TL { vertical-align: top; text-align: left}

td form { margin:0; } /* Prevent IE from displaying margins around forms in tables. */

TD.unallocated {color:gray}
TD.allocated {color:black}
A:link.unallocated {color:#9BA9E6}
A:link.allocated {color:#5B69A6}

A:hover.unallocated {color:red}
A:hover.allocated {color:red}

A.blue {color:blue}
A:visited.blue {color:blue}
A:hover.blue {color:red}

TH {color:#eeeeee; font-size:10pt; font-family:verdana,sans-serif; background-color:#999999; border-width:1px; border-color:#999999; vertical-align:top}
TD.banner {text-align:center; vertical-align:middle; background-color:#C0E0FF;}

TD.blue {background-color:#F0F0FF}
TD.red  {background-color:#FFF0F0}
TD.green {background-color:#DDFFDD}
TD.A {background-color:#FFCCFF}
TD.B {background-color:#99CCCC}
TD.C {background-color:#FF9999}
TD.D {background-color:#FFFF99}
TD.E {background-color:#C0E0FF}
TD.F {background-color:#FFCC99}
TD.G {background-color:#FF6666}
TD.H {background-color:#66FFFF}
TD.I {background-color:#DDFFDD}
TD.J {background-color:#CCCCCC}
TD.white {background-color:#FFFFFF}

TD.calendar { border:0px; font-size: 8pt}
TD.calendarHeader {border:0px; font-size: 10pt}
FONT.calendarHighlight {color: red}

TD.even_row {background-color:#FFFFFF}	/* Even rows in the day view */
TD.odd_row {background-color:#EEEEEE}	/* Odd rows in the day view */

TD.highlight {background-color:#AABBFF; border-style: solid; border-width: 1px; border-color:#0000AA;} /* The highlighted cell under the cursor */
.naked { margin: 0; padding: 0; border-width:0} /* Invisible tables used for internal needs */

.sitename
{font-size: 18px;
font-style: normal;
font-weight: bold;
text-transform: none;
color:#ffffff;
position: absolute;
left:30px;
top:12px}

TD.month {font-size: 8pt; background-color:#FFFFFF}
.monthday {font-size: 12pt; vertical-align: top; text-align: left}



/* ------------ GENERAL -----------------------------*/

.current {color: red}		                    /* used to highlight the current item */
.error   {color: red; font-weight: bold}       /* for error messages */

legend {font-weight: bold; font-size: large}
fieldset {width: 100%; padding-left: 1.0em; padding-right: 1.0em}
fieldset fieldset {position: relative; clear: left; width: 100%; padding: 0; border: 0; margin: 0}  /* inner fieldsets are invisible */
fieldset fieldset legend {font-size: 0}        /* for IE: even if there is no legend text, IE allocates space  */

img.new_booking {display: block; margin-left: auto; margin-right: auto}



/* ------------ ADMIN.PHP ---------------------------*/
<?php
// Adjust the label width to suit the longest label - it will depend on the translation being used
// The input width can normally be left alone
$admin_form_input_width       = 8.3;   // em   (Also used in edit_area_room.php)
?>
table#admin {margin-bottom: 1em}
#admin td {border: 1px solid #999999}
#admin th {text-align: center; font-weight: bold}
form.form_admin {margin-top: 0.5em; margin-bottom: 0.5em}
.form_admin fieldset {width: auto; border: 0; padding-top: 1.0em}
.form_admin div {width: 100%}
.form_admin label {display: block; float: left; clear: left; width: 7.0em; min-height: 2.0em; text-align: right}
.form_admin input {
    display: block; position: relative; float: right; clear: right; 
    width: <?php echo $admin_form_input_width ?>em; margin-top: -0.2em; margin-left: 0.5em; margin-right: 0.5em
}
.form_admin input.submit {width: auto; margin-top: 1.2em; margin-left: 1.0em}



/* ------------ DAY.PHP -----------------------------*/
table#day_header {width: 100%}
#day_header h3 {color:black; font-size: 10pt; font-family: arial,sans-serif; text-decoration: underline; margin-bottom: 0.2em; padding-bottom: 0px}
td#day_header_areas {width: 60%}
#day_header_areas ul {list-style-type: none; padding-left: 0px; margin-top: 0px}
h2#day {text-align: center}

table#day_main {width: 100%; border-spacing: 0px; border-collapse: collapse}
#day_main td, #day_main th {border: 1px solid #555555}

div.date_nav    {position: relative; width: 100%; height: 2.5em}
div.date_before {width: 32%; position: absolute; top: 0.5em; left: 0; text-align: left}
div.date_now    {width: 32%; position: absolute; top: 0.5em; left: 50%; margin-left: -16%; text-align: center}
div.date_after  {width: 32%; position: absolute; top: 0.5em; right: 0; text-align: right}



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
.form_edit_area_room div {width: 100%}



/* ------------ EDIT_AREA_ROOM.PHP ------------------*/
<?php
$edit_entry_label_height          = 1.0;     // em
$edit_entry_left_col_max_width    = 10;      // em
$edit_entry_left_col_width        = 20;      // %
$edit_entry_right_col_width       = 80;      // %
$edit_entry_gap                   = 1.0;     // em  (gap between left and right columns)
$edit_entry_textarea_width        = 26;      // em
$edit_entry_form_min_width        = $edit_entry_left_col_max_width + $edit_entry_textarea_width + $edit_entry_gap;
?>
form#form_edit_entry {margin-top: 2.0em; width: 100%; min-width: <?php echo $edit_entry_form_min_width ?>em}
#form_edit_entry div {float: left; width: 100%}
#form_edit_entry div div {float: none; width: auto}
#form_edit_entry div.group {display: table-cell; float: left; width: <?php echo $edit_entry_right_col_width ?>%}
#form_edit_entry fieldset {width: auto; border: 0; padding-top: 2.0em}
#form_edit_entry label {
    display: block; float: left; 
    min-height: <?php echo $edit_entry_label_height ?>em; 
	 width: <?php echo $edit_entry_left_col_width ?>%; 
	 max-width: <?php echo $edit_entry_left_col_max_width ?>em; 
	 text-align: right; padding-bottom: 0.8em; font-weight: bold;
}
/* font family and size needs to be the same for input and textarea as their widths are defined in ems */
#form_edit_entry input {
    display: block; float: left; 
    width: <?php echo $edit_entry_textarea_width ?>em; 
    margin-left: <?php echo $edit_entry_gap ?>em; 
	 font-family: arial,sans-serif; font-size: 10pt
}
#form_edit_entry textarea {
    display: block; float: left; 
    width: <?php echo $edit_entry_textarea_width ?>em; height: 11em; 
    margin-left: <?php echo $edit_entry_gap ?>em; margin-bottom: 0.5em;
	 font-family: arial,sans-serif; font-size: 10pt
}
#form_edit_entry select {float: left; margin-left: <?php echo $edit_entry_gap ?>em; margin-right: -0.5em; margin-bottom: 0.5em}
#form_edit_entry input.radio {margin-top: 0.1em}
#form_edit_entry input.checkbox {margin-top: 0.1em}
#form_edit_entry #div_time input {width: 1.5em}
#form_edit_entry #div_time span + input {margin-left: 0}
#form_edit_entry #div_time span {display: block; float: left; margin-left: 0.2em; margin-right: 0.2em}
#form_edit_entry input#duration {width: 3.0em}
#form_edit_entry select#dur_units {margin-right: 1.0em}
#form_edit_entry div#ad {float: left}
#form_edit_entry #ad label {clear: none; text-align: left; font-weight: normal}
#form_edit_entry input#all_day {width: auto; margin-left: 1.0em; margin-right: 0.5em}
#form_edit_entry #div_rooms select {float: left; margin-right: 2.0em}
#form_edit_entry .group label {clear: none; width: auto; max-width: 100%; font-weight: normal}
#form_edit_entry .group input {clear: none; width: auto}
#form_edit_entry fieldset#rep_info {padding-top: 0}
#form_edit_entry #rep_info input {width: 13em}
#form_edit_entry input#rep_num_weeks {width: 1.5em}
#form_edit_entry #edit_entry_submit input {
    position: relative; clear: left; 
    left: <?php echo $edit_entry_left_col_max_width ?>em; width: auto; margin-top: 1.0em
}

