<?php 

// $Id$

require_once "systemdefaults.inc.php";
require_once "config.inc.php";
require_once "functions.inc";
require_once "theme.inc"; 

header("Content-type: text/css");
expires_header(60*30); // 30 minute expiry

?>

/* Fixes for Internet Explorer 7 and less */

/* ------------ ADMIN.PHP ---------------------------*/
<?php
// Alignment slightly different in IE7 and below
?>
#areaChangeForm button {margin-top: -0.1em}


/* ------------ FORM_GENERAL ------------------------*/

.form_general#edit_room legend {font-size: 0}   /* no legend in edit_room, so stop IE allocating space */
.form_general#edit_room select {margin-bottom: 0.2em}
.form_general textarea {margin-top: 0.25em} /* IE7 and below don't understand margin-bottom */
                                            /* so use the top margin instead */
div#mode {margin-top: 0.5em} /* this div comes just below a textarea */

/* ------------ DAY/WEEK/MONTH.PHP ------------------*/

<?php
// IE7 and below do not support the value of inherit for min-height (and
// indeed support for min-height is patchy) so we cannot support $clipped = FALSE
// as that relies on it.    If $clipped is FALSE, we'll just use the same rule as
// we do when $clipped is TRUE.
?>

.dwm_main a {height: 100%}  /* for IE7 */

<?php
if (!$clipped)
{
  $classes_required = ($times_along_top) ? 1 : $max_slots;
  for ($i=1; $i<=$classes_required; $i++) 
  {
    $div_height = $main_cell_height * $i;
    $div_height = $div_height + (($i-1)*$main_table_cell_border_width);
    $div_height = (int) $div_height;    // Make absolutely sure it's an int to avoid generating invalid CSS
  
    $rule = "div.slots" . $i . " {";
    $rule .= "max-height: " . $div_height . "px";
    $rule .= "; height: "   . $div_height . "px";
    $rule .= "}";
    echo $rule . "\n";
  }
}
?>

/* ------------ PENDING.PHP ---------------------*/
<?php
// Not quite sure why this is necessary for IE7.   Without it
// the subtable is clipped.   With it it's not perfect:  the table and
// the sub-table expand to the right
?>

#pending_list form {display: inline}

/* ------------ TRAILER.INC ---------------------*/

/* opacity for IE7 and below is implemented with filter, but only works if the */
/* element is positioned;  you can also get filter to work by using zoom.      */
#trailer span.hidden {
    zoom: 1;                   /* to force the filter to work */
    filter: alpha(opacity=50); /* keep the value in step with the main stylesheet */
}
