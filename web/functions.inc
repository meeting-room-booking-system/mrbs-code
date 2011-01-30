<?php

// $Id$

/////////////////////////////////////////
// Set timezone, if one has been provided

global $timezone;

if (isset($timezone))
{
  if (function_exists("date_default_timezone_set"))
  {
    date_default_timezone_set($timezone) or die("Configuration error: invalid timezone.");
  }
  else
  {
    putenv("TZ=$timezone");
  }
}
else
{
  // to prevent people running into DST problems
  die('Configuration error: $timezone has not been set.');
}

// Deal with $private_xxxx overrides.  Simplifies
// logic related to private bookings.
global $private_override;
if ($private_override == "private" )
{
  $private_mandatory=TRUE;
  $private_default=TRUE;
}
elseif ($private_override == "public" )
{
  $private_mandatory=TRUE;
  $private_default=FALSE;
}

$done_header = FALSE;

// Prints a very simple header.  This may be necessary on occasions, such as
// during a database upgrade, when some of the features that the normal
// header uses are not yet available.
function print_simple_header()
{
  global $done_header;
 
  if ($done_header)
  {
    return;
  } 
  
  header("Content-Type: text/html; charset=" . get_charset());
  header("Pragma: no-cache");                          // HTTP 1.0
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
  echo DOCTYPE;
 ?>

<html>
  <head>
    <?php
      require_once "style.inc";
    ?>
    <title><?php echo get_vocab("mrbs") ?></title>
  </head>
  <body>
  <?php
  $done_header = TRUE; 
}

// Print the page header
function print_header($day, $month, $year, $area, $room)
{
  global $theme, $done_header;
  
  if ($done_header)
  {
    return;
  }
  
  // Load the print_theme_header function appropriate to the theme.    If there
  // isn't one then fall back to the default header.
  @include_once "Themes/$theme/header.inc";
  if (!function_exists("print_theme_header"))
  {
    require_once "Themes/default/header.inc";
  }
  // Now go and do it
  print_theme_header($day, $month, $year, $area, $room);
  $done_header = TRUE;
}



// Print the standard footer, currently very simple.  Pass $and_exit as
// TRUE to exit afterwards
function print_footer($and_exit)
{
  global $theme;
  @include_once "Themes/$theme/footer.inc";
  if (function_exists("print_theme_footer"))
  {
    print_theme_footer();
  }
  else {
?>
</body>
</html>
<?php
  }
  if ($and_exit)
  {
    exit(0);
  }
}


// Format a timestamp in RFC 1123 format, for HTTP headers
//
// e.g. Wed, 28 Jul 2010 12:43:58 GMT
function rfc1123_date($timestamp)
{
  return gmdate("D, d M Y G:i:s \\G\\M\\T",$timestamp);
}


// A little helper function to send an "Expires" header. Just one
// parameter, the number of seconds in the future to set the expiry
function expires_header($seconds)
{
  header("Expires: ".rfc1123_date(time()+$seconds));
}

// Converts a duration of $dur seconds into a duration of
// $dur $units
function toTimeString(&$dur, &$units, $translate=TRUE)
{
  if (abs($dur) >= 60)
  {
    $dur /= 60;

    if (abs($dur) >= 60)
    {
      $dur /= 60;

      if((abs($dur) >= 24) && ($dur % 24 == 0))
      {
        $dur /= 24;

        if((abs($dur) >= 7) && ($dur % 7 == 0))
        {
          $dur /= 7;

          if ((abs($dur) >= 52) && ($dur % 52 == 0))
          {
            $dur  /= 52;
            $units = "years";
          }
          else
          {
            $units = "weeks";
          }
        }
        else
        {
          $units = "days";
        }
      }
      else
      {
        $units = "hours";
      }
    }
    else
    {
      $units = "minutes";
    }
  }
  else
  {
    $units = "seconds";
  }
  
  // Limit any floating point values to three decimal places
  if (is_float($dur))
  {
    $dur = sprintf('%.3f', $dur);
    $dur = (string) (float) $dur;  // removes trailing zeros
  }
  
  // Translate into local language if required
  if ($translate)
  {
    $units = get_vocab($units);
  }
}


// Converts a time period of $units into seconds, when it is originally
// expressed in $dur_units.   (Almost the inverse of toTimeString(),
// but note that toTimeString() can do language translation)
function fromTimeString(&$units, $dur_units)
{
  if (!isset($units) || !isset($dur_units))
  {
    return;
  }
  
  switch($dur_units)
  {
    case "years":
      $units *= 52;
    case "weeks":
      $units *= 7;
    case "days":
      $units *= 24;
    case "hours":
      $units *= 60;
    case "periods":
    case "minutes":
      $units *= 60;
    case "seconds":
      break;
  }
  $units = (int) $units;
}


function toPeriodString($start_period, &$dur, &$units, $translate=TRUE)
{
  global $periods;

  $max_periods = count($periods);
  $dur /= 60;  // duration now in minutes
  $mins_in_day = 24*60;
  $days = $dur / $mins_in_day;
  $remainder = $dur % $mins_in_day;
  // strip out any gap between the end of the last period on one day
  // and the beginning of the first on the next
  if ($remainder > $max_periods)
  {
    $remainder += $max_periods - $mins_in_day;
  }
  
  // We'll express the duration as an integer, in days if possible, otherwise periods
  if (($remainder == 0) || (($start_period == 0) && ($remainder == $max_periods)))
  {
    $dur = (int) $days;
    if ($remainder == $max_periods)
    {
      $dur++;
    }
    $units = $translate ? get_vocab("days") : "days";
  }
  else
  {
    $dur = (intval($days) * $max_periods) + $remainder;
    $units = $translate ? get_vocab("periods") : "periods";
  }
}

// Converts a period of $units starting at $start_period into seconds, when it is
// originally expressed in $dur_units (periods or days).   (Almost the inverse of
// toPeriodString(), but note that toPeriodString() can do language translation)
function fromPeriodString($start_period, &$units, $dur_units)
{
  global $periods;
  
  if (!isset($units) || !isset($dur_units))
  {
    return;
  }
  
  // First get the duration in minutes
  $max_periods = count($periods);
  if ($dur_units == "periods")
  {
    $end_period = $start_period + $units;
    if ($end_period > $max_periods)
    {
      $units = (24*60*floor($end_period/$max_periods)) + ($end_period%$max_periods) - $start_period;
    }
  }
  if ($dur_units == "days")
  {
    if ($start_period == 0)
    {
      $units = $max_periods + ($units-1)*60*24;
    }
    else
    {
      $units = $units * 60 * 24;
    }
  }
  
  // Then convert into seconds
  $units = (int) $units;
  $units = 60 * $units;
}


// Get the duration of an interval given a start time and end time.  Corrects for
// DST changes so that the duration is what the user would expect to see.  For
// example 12 noon to 12 noon crossing a DST boundary is 24 hours.
//
// Returns an array indexed by 'duration' and 'dur_units'
//
//    $start_time   int     start time as a Unix timestamp
//    $end_time     int     end time as a Unix timestamp
//    $translate    boolean whether to translate into the browser language
function get_duration($start_time, $end_time, $translate=TRUE)
{
  global $enable_periods;
  
  $result = array();
  $result['duration'] = $end_time - $start_time;
  // Need to make DST correct in opposite direction to entry creation
  // so that user see what he expects to see
  $result['duration'] -= cross_dst($start_time, $end_time);
  if ($enable_periods)
  {
    $time = getdate($start_time);
    $start_period = $time['minutes'];
    toPeriodString($start_period, $result['duration'], $result['dur_units'], $translate);
  }
  else
  {
    toTimeString($result['duration'], $result['dur_units'], $translate);
  }
  return $result;
}


// Generate an input field with an associated label
// Optional fifth parameter: $maxlength - the maximum length of input allowed
function generate_input($label_text, $name, $value, $disabled=FALSE)
{
  // get any optional fifth parameter
  if (func_num_args() > 4)
  {
    $maxlength = func_get_arg(4);
  }
  // generate the HTML
  $html  = "<label for=\"$name\">$label_text</label>\n";
  $html .= "<input id=\"$name\" name=\"$name\" type=\"text\"";
  $html .= ($disabled) ? " disabled=\"disabled\"" : '';
  $html .= (isset($maxlength)) ? " maxlength=\"$maxlength\"" : '';
  $html .= " value=\"" . htmlspecialchars($value) . "\">\n";
  // and a hidden input if the input box is disabled
  if ($disabled)
  {
    $html .= "<input type=\"hidden\" name=\"$name\" value=\"".
      htmlspecialchars($value)."\">\n";
  }
  echo $html;
}

// Generates a select box from $options, an array of options
// If $disabled is TRUE, then the select box is disabled and a hidden
// input is generated to pass through $value
function generate_select($label_text, $name, $value, $options, 
                         $mandatory = FALSE, $disabled=FALSE)
{
  // generate the HTML
  $html  = "<label for=\"$name\">$label_text</label>\n";
  $html .= "<select id=\"$name\" name=\"$name\"";
  $html .= ($disabled) ? " disabled=\"disabled\"" : '';
  $html .= ">\n";
  if ($mandatory)
  {
    $options = array_merge(array(""),$options);
  }
  foreach ($options as $option)
  {
    $html .= "<option";
    $html .= (isset($value) && ($value == $option)) ? " selected=\"selected\"" : '';
    $html .= ">".htmlspecialchars($option)."</option>\n";
  }
  $html .= "</select>\n";
  // and a hidden input if the select box is disabled
  if ($disabled)
  {
    $html .= "<input type=\"hidden\" name=\"$name\" value=\"".
      htmlspecialchars($value)."\">\n";
  }
  
  echo $html;
}

// Generate a textarea with an associated label
// If $disabled is TRUE, then the textarea is disabled and a hidden
// input is generated to pass through $value
function generate_textarea($label_text, $name, $value, $disabled=FALSE)
{
  // generate the HTML
  $html  = "<label for=\"$name\">$label_text</label>\n";
  // textarea rows and cols are overridden by CSS height and width
  $html .= "<textarea id=\"$name\" name=\"$name\" rows=\"8\" cols=\"40\"";
  $html .= ($disabled) ? " disabled=\"disabled\"" : '';
  $html .= ">" . htmlspecialchars($value) . "</textarea>\n";
  // and a hidden input if the textarea is disabled
  if ($disabled)
  {
    $html .= "<input type=\"hidden\" name=\"$name\" value=\"".
      htmlspecialchars($value)."\">\n";
  }
  echo $html;
}

// Generates a date selector for use on a form.   If JavaScript is enabled
// then it will generate a calendar picker using jQuery UI datepicker.   If not,
// it will generate three separate select boxes, one each for day, month and year.
//
// $form_id is an optional fifth parameter.   If set it specifies the id of
// a form to submit when the datepicker is closed.
//
// Whether or not JavaScript is enabled the date is passed back in three separate
// variables:  ${prefix}day, ${prefix}month and ${prefix}year
//
// The function passes back three separate variables, rather than a single date 
// variable, partly for compatibility with previous implementations of genDateSelector()
// and partly because it's easier to do this for the non-JavaScript version.
function genDateSelector($prefix, $day, $month, $year, $form_id='')
{
  global $strftime_format;
  
  // Make sure we've got a date
  if (empty($day) or empty($month) or empty($year))
  {
    $day   = date("d");
    $month = date("m");
    $year  = date("Y");
  }

  // Cast the dates to ints to remove any leading zeroes (otherwise
  // JavaScript will treat them as octal numbers)
  $day = (int) $day;
  $month = (int) $month;
  $year = (int) $year;
  
  // Get a month for use with JavaScript (where the months run from 0 to 11)
  $month_js = $month - 1;
  
  // First and last dates to show in year select
  $min = min($year, date("Y")) - 5;
  $max = max($year, date("Y")) + 5;
  
  $datepicker_baseid = "${prefix}datepicker";
  
  // We'll put the date selector in a span.    First of all we'll generate
  // day, month and year selectors.   These will be used if JavaScript is
  // disabled.    If JavaScript is enabled it will overwrite these with a
  // datepicker calendar.
  echo "<span id=\"${prefix}dateselector\">\n";
  // the day selector
  echo "<select name=\"${prefix}day\">\n";
  for ($i = 1; $i <= 31; $i++)
  {
    echo "<option" . ($i == $day ? " selected=\"selected\"" : "") . ">$i</option>\n";
  }
  echo "</select>\n";
  // the month selector
  echo "<select name=\"${prefix}month\">\n";
  for ($i = 1; $i <= 12; $i++)
  {
    $m = utf8_strftime($strftime_format['mon'], mktime(0, 0, 0, $i, 1, $year));  
    echo "<option value=\"$i\"" . ($i == $month ? " selected=\"selected\"" : "") . ">$m</option>\n";
  }
  echo "</select>\n";
  // the year selector
  echo "<select name=\"${prefix}year\">\n";
  for ($i = $min; $i <= $max; $i++)
  {
    echo "<option value=\"$i\"" . ($i == $year ? " selected=\"selected\"" : "") . ">$i</option>\n";
  }
  echo "</select>\n";
  echo "</span>\n";
  
  // Now use JavaScript to overwrite the contents of this span with a datepicker calendar
  ?>
  <script type="text/javascript">

	$(function() {
		$("#<?php echo $datepicker_baseid ?>").datepicker({yearRange: <?php echo "'$min:$max'" ?>,
		                                                   altField: '#<?php echo $datepicker_baseid ?>_alt'
		                                                   <?php
		                                                   if (!empty($form_id))
		                                                   {
		                                                     echo ", onClose: function(dateText, inst) {datepicker_close(dateText, inst, '$form_id');}";
		                                                   }
		                                                   ?>
		                                                  });
		<?php
	  // Populate the input fields with the initial values
	  ?>
	  var initial_date = new Date(<?php echo "$year, $month_js, $day" ?>);
	  var dateFormat = $("#<?php echo $datepicker_baseid ?>").datepicker( "option", "dateFormat" );
	  document.getElementById(<?php echo "'${datepicker_baseid}'" ?>).value = $.datepicker.formatDate(dateFormat, initial_date);
	  document.getElementById(<?php echo "'${datepicker_baseid}_alt_day'" ?>).value = <?php echo $day ?>;
    document.getElementById(<?php echo "'${datepicker_baseid}_alt_month'" ?>).value = <?php echo $month ?>;
    document.getElementById(<?php echo "'${datepicker_baseid}_alt_year'" ?>).value = <?php echo $year ?>;
	  $(".ui-datepicker").draggable();
	});
 
	
	var dateselector = document.getElementById(<?php echo "'${prefix}dateselector'" ?>);
	var datepicker_html = '<input class="date" type="text" id="<?php echo $datepicker_baseid ?>">\n';
	<?php
	// The next input is disabled because we don't need to pass the value through to
  // the form and we don't want the value cluttering up the URL (it's a GET).
  // It's just used as a holder for the date in a known format so that it can
  // then be used by datepicker_close() to populate the following three inputs.
  ?>
	datepicker_html += '<input type="hidden" id="<?php echo $datepicker_baseid ?>_alt"'
	datepicker_html += ' name="<?php echo $prefix ?>_alt"';
	datepicker_html += ' value="<?php echo "${year}-${month}-${day}" ?>"';
	datepicker_html += ' disabled="disabled">\n';
	<?php
	// These three inputs we do want
	?>
	datepicker_html += '<input type="hidden" id="<?php echo $datepicker_baseid ?>_alt_day" name="<?php echo $prefix ?>day">\n';
	datepicker_html += '<input type="hidden" id="<?php echo $datepicker_baseid ?>_alt_month" name="<?php echo $prefix ?>month">\n';
	datepicker_html += '<input type="hidden" id="<?php echo $datepicker_baseid ?>_alt_year" name="<?php echo $prefix ?>year">\n';
	dateselector.innerHTML = datepicker_html;
	
	</script>
  <?php
}

// Error handler - this is used to display serious errors such as database
// errors without sending incomplete HTML pages. This is only used for
// errors which "should never happen", not those caused by bad inputs.
// If $need_header!=0 output the top of the page too, else assume the
// caller did that. Alway outputs the bottom of the page and exits.
function fatal_error($need_header, $message)
{
  global $simple_trailer, $weekstarts, $view_week_number;
  
  if ($need_header)
  {
    print_header(0, 0, 0, 0, "");
  }
  error_log("MRBS: ".$message);
  if (!empty($_GET))
    error_log("MRBS GET: ".print_r($_GET, true));
  if (!empty($_POST))
    error_log("MRBS POST: ".print_r($_POST, true));
  if (!empty($_SESSION))
    error_log("MRBS SESSION: ".print_r($_SESSION, true));
  echo "<p>$message</p>";
  require_once "trailer.inc";
  exit;
}


// Escape a PHP string for use in JavaScript
//
// Based on a function contributed by kongaspar at gmail dot com at 
// http://www.php.net/manual/function.addcslashes.php
function escape_js($str)
{
  return addcslashes($str, "\\\'\"&\n\r<>");
}


// Remove backslash-escape quoting if PHP is configured to do it with
// magic_quotes_gpc. Use this whenever you need the actual value of a GET/POST
// form parameter (which might have special characters) regardless of PHP's
// magic_quotes_gpc setting.
function unslashes($s)
{
  if (get_magic_quotes_gpc())
  {
    return stripslashes($s);
  }
  else
  {
    return $s;
  }
}

// Return a default area; used if no area is already known. This returns the
// area that contains the default room (if it is set, valid and enabled) otherwise the
// first area in alphabetical order in the database (no guarantee there is an area 1).
// The area must be enabled for it to be considered.
// This could be changed to implement something like per-user defaults.
function get_default_area()
{
  global $tbl_area, $tbl_room, $default_room;
  
  // If the $default_room is set and exists and enabled, then return the
  // corresponding area
  if (isset($default_room))
  {
    $area = sql_query1("SELECT area_id
                          FROM $tbl_room R, $tbl_area A
                         WHERE R.id=$default_room
                           AND R.area_id = A.id
                           AND R.disabled = 0
                           AND A.disabled = 0
                         LIMIT 1");
    if ($area >= 0)
    {
      return $area;
    }
  }
  // Otherwise return the first enabled area in alphabetical order in the database
  $area = sql_query1("SELECT id
                        FROM $tbl_area
                       WHERE disabled=0
                    ORDER BY area_name
                       LIMIT 1");
  return ((!isset($area) || ($area < 0)) ? 0 : $area);
}

// Return a default room given a valid area; used if no room is already known.
// If the area contains $default_room, then it returns $default_room,
// otherwise the first room in sort_key order in the database.
// This could be changed to implement something like per-user defaults.
function get_default_room($area)
{
  global $tbl_room, $default_room;
  // Check to see whether this area contains $default_room
  if (isset($default_room))
  {
    $room = sql_query1("SELECT id
                          FROM $tbl_room
                         WHERE id=$default_room
                           AND area_id=$area
                           AND disabled=0
                         LIMIT 1");
    if ($room >= 0)
    {
      return $room;
    }
  }
  // Otherwise just return the first room (in sortkey order) in the area
  $room = sql_query1("SELECT id
                        FROM $tbl_room
                       WHERE area_id=$area
                         AND disabled=0
                    ORDER BY sort_key
                       LIMIT 1");
  return ($room < 0 ? 0 : $room);
}

// Return an area id for a given room
function get_area($room)
{
  global $tbl_room;
  $area = sql_query1("SELECT area_id FROM $tbl_room WHERE id=$room LIMIT 1");
  return ($area < 0 ? 0 : $area);
}

// Update the default area settings with the ones specific to this area.
// If no value is set in the database, use the value from the config file
function get_area_settings($area)
{
  global $tbl_area, $force_resolution;
  global $resolution, $default_duration, $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes;
  global $private_enabled, $private_default, $private_mandatory, $private_override;
  global $min_book_ahead_enabled, $max_book_ahead_enabled, $min_book_ahead_secs, $max_book_ahead_secs;
  global $approval_enabled, $reminders_enabled, $enable_periods, $boolean_fields;
  global $confirmation_enabled, $confirmed_default;
  
  // Get all the "per area" config settings                  
  $sql = "SELECT resolution, default_duration, morningstarts, morningstarts_minutes,
                 eveningends, eveningends_minutes,
                 private_enabled, private_default, private_mandatory, private_override,
                 min_book_ahead_enabled, max_book_ahead_enabled,
                 min_book_ahead_secs, max_book_ahead_secs,
                 approval_enabled, reminders_enabled, enable_periods,
                 confirmation_enabled, confirmed_default
          FROM $tbl_area 
          WHERE id=$area 
          LIMIT 1";
  $res = sql_query($sql);
  if (!$res || (sql_count($res) == 0))
  {
    return;
  }
  else
  {
    $row = sql_row_keyed($res, 0);
    foreach ($row as $field => $value)
    {
      // If the "per area" setting is in the database, then use that.   Otherwise
      // just stick with the default setting from the config file.
      // (don't use the database setting if $force_resolution is TRUE 
      // and we're looking at the resolution field)
      if (($field != 'resolution') || empty($force_resolution))
      {
        $$field = (isset($row[$field])) ? $value : $$field;
      }
      // Cast those fields which are booleans into booleans
      if (in_array($field, $boolean_fields['area']))
      {
        $$field = (bool) $$field;
      }
    }
  }
}

// generate the predicate for use in an SQL query to test whether
// an area has $field set
function some_area_predicate($field)
{
  global $area_defaults;
  
  $predicate = "(($field IS NOT NULL) AND ($field > 0))";
  if ($area_defaults[$field])
  {
    $predicate = "(" . $predicate . " OR ($field IS NULL))";
  }
  return $predicate;
}

// Determines whether there is at least one area with the relevant $field
// set (eg 'approval_enabled' or 'confirmation_enabled').   If $enabled
// is TRUE then the search is limited to enabled areas
//
// Returns: boolean
function some_area($field, $enabled=FALSE)
{
  global $tbl_area;
  
  $predicate = some_area_predicate($field);   
  $sql = "SELECT COUNT(*) FROM $tbl_area WHERE $predicate";
  $sql .= ($enabled) ? " AND disabled=0" : "";
  $sql .= " LIMIT 1";                                
  return (sql_query1($sql) > 0);
}

// Get the local day name based on language. Note 2000-01-02 is a Sunday.
function day_name($daynumber)
{
  global $strftime_format;
  
  return utf8_strftime($strftime_format['dayname'],
                       mktime(0,0,0,1,2+$daynumber,2000));
}

// Returns a list of repeat days as a string (eg "Thursday Friday")
//
//    $rep_opt     an array of repeat days or
//                 a string of repeat days that can be used as an array
function get_rep_day_list($rep_opt)
{
  global $weekstarts;
  
  $rep_day_list = "";
  for ($i = 0; $i < 7; $i++)
  {
    $daynum = ($i + $weekstarts) % 7;
    if ($rep_opt[$daynum])
    {
      $rep_day_list .= day_name($daynum) . " ";
    }
  }
  return $rep_day_list;
}


function hour_min_format()
{
  global $twentyfourhour_format, $strftime_format;
  
  if ($twentyfourhour_format)
  {
    return $strftime_format['time24'];
  }
  else
  {
    return $strftime_format['time12'];
  }
}

function period_date_string($t, $mod_time=0)
{
  global $periods, $strftime_format;

  $time = getdate($t);
  $p_num = $time["minutes"] + $mod_time;
  if( $p_num < 0 )
  {
    $p_num = 0;
  }
  if( $p_num >= count($periods) - 1 )
  {
    $p_num = count($periods ) - 1;
  }
  // I have made the separator a ',' as a '-' leads to an ambiguious
  // display in report.php when showing end times.
  return array($p_num, $periods[$p_num] . utf8_strftime(", " . $strftime_format['date'], $t));
}

function period_time_string($t, $mod_time=0)
{
  global $periods;

  $time = getdate($t);
  $p_num = $time["minutes"] + $mod_time;
  if ( $p_num < 0 )
  {
    $p_num = 0;
  }
  if ( $p_num >= count($periods) - 1 )
  {
    $p_num = count($periods ) - 1;
  }
  return $periods[$p_num];
}

function time_date_string($t)
{
  global $twentyfourhour_format, $strftime_format;

  if ($twentyfourhour_format)
  {
    return utf8_strftime($strftime_format['datetime24'], $t);
  }
  else
  {
    return utf8_strftime($strftime_format['datetime12'], $t);
  }
}

// version of the standard PHP function nl2br() that takes account of the fact
// that the optional second parameter is only available from PHP 5.3.0 onwards.
function mrbs_nl2br($string)
{
  if (function_exists('version_compare') && version_compare(PHP_VERSION, '5.3.0', 'ge'))
  {
    return nl2br($string, IS_XHTML);
  }
  else
  {
    return nl2br($string);
  }
}

// Version of the standard PHP function html_entity_decode()
// Although html_entity_decode() was introduced in PHP 4.3.0, support for
// multi-byte character sets was only introduced in PHP 5.0.0.   
// So if we're running PHP5 or higher we'll use the standard
// PHP function; otherwise we'll do the best we can.   At the moment
// we just replace &nbsp; with an ordinary space, which
// should be sufficient in most MRBS circumstances.   This could
// always be extended later to do something more sophisticated if
// necessary.
function mrbs_entity_decode($string)
{
  $n_args = func_num_args();
  if ($n_args > 1)
  {
    $quote_style = func_get_arg(1);
  }
  if ($n_args > 2)
  {
    $charset = func_get_arg(2);
  }
  
  if (function_exists('version_compare') && version_compare(PHP_VERSION, '5.0.0', 'ge'))
  {
    switch ($n_args)
    {
      case 3:
        $string = html_entity_decode($string, $quote_style, $charset);
        break;
      case 2:
        $string = html_entity_decode($string, $quote_style);
        break;
      default:
        $string = html_entity_decode($string);
        break;
    }  
  }
  else
  {
    $string = str_replace('&nbsp;', ' ', $string);
  }
  return $string;
}

// validates a comma separated list of email addresses
// returns FALSE if any one of them is invalid, otherwise TRUE
function validate_email_list($list)
{
  require_once 'Mail/RFC822.php';
  (!isset($list)) ? $list = '': '';
  $emails = explode(',', $list);
  $email_validator = new Mail_RFC822();
  foreach ($emails as $email)
  {
    // if no email address is entered, this is OK, even if isValidInetAddress
    // does not return TRUE
    if ( !$email_validator->isValidInetAddress($email, $strict = FALSE)
         && ('' != $list) )
    {
      return FALSE;
    }
  }
  return TRUE;
}

// Output a start table cell tag <td> with color class.
// $colclass is an entry type (A-J), zebra stripes if
// empty or row_highlight if highlighted.
// $slots is the number of time slots high that the cell should be
function tdcell($colclass, $slots)
{
  global $times_along_top;
  echo "<td class=\"$colclass\"";
  if ($slots > 1)
  // No need to output more HTML than necessary
  {
    echo " " . (($times_along_top) ? "colspan" : "rowspan") . "=\"$slots\"";
  }
  echo ">\n";
}

// Display the entry-type color key. This has up to 2 rows, up to 5 columns.
function show_colour_key()
{
  global $typel;
  // set the table width.   Default is 5, but try and avoid rows of unequal length
  switch (count($typel))
  {
    case '6':
      $table_width = 3;
      break;
    case '8':
    case '12':
      $table_width = 4;
      break;
    default:
      $table_width = 5;
  }
  echo "<table id=\"colour_key\"><tr>\n";
  $nct = 0;
  for ($ct = "A"; $ct <= "Z"; $ct++)
  {
    if (!empty($typel[$ct]))
    {
      if (++$nct > $table_width)
      {
        $nct = 1;
        echo "</tr><tr>";
      }
      tdcell($ct, 1);
      echo "<div class=\"celldiv slots1\" " .  // put the description inside a div which will give clipping in case of long names
      "title=\"$typel[$ct]\">\n";        // but put the name in the title so you can still read it all if you hover over it
      echo "$typel[$ct]</div></td>\n";
    }
  }
  // If there is more than one row and the bottom row isn't complete then 
  // pad it out with a single merged cell
  if ((count($typel) > $table_width) && ($nct < $table_width))
  {
    echo "<td colspan=\"" . ($table_width - $nct) . "\"" .
        " id=\"row_padding\">&nbsp;</td>\n";
  }
  echo "</tr></table>\n";
}

// Round time down to the nearest resolution
function round_t_down($t, $resolution, $am7)
{
  return (int)$t - (int)abs(((int)$t-(int)$am7)
                            % $resolution);
}

// Round time up to the nearest resolution
function round_t_up($t, $resolution, $am7)
{
  if (($t-$am7) % $resolution != 0)
  {
    return $t + $resolution - abs(((int)$t-(int)
                                   $am7) % $resolution);
  }
  else
  {
    return $t;
  }
}

// generates some html that can be used to select which area should be
// displayed.
function make_area_select_html($link, $current, $year, $month, $day)
{
  global $tbl_area, $area_list_format;
  
  $out_html = '';
  $sql = "SELECT id, area_name 
            FROM $tbl_area
           WHERE disabled=0
        ORDER BY area_name";
  $res = sql_query($sql);
  // Only show the areas if there's more than one of them, otherwise
  // there's no point
  if ($res  && (sql_count($res) > 1))
  {
    $out_html .= "<div id=\"dwm_areas\">\n";
    $out_html .= "<h3>" . get_vocab("areas") . "</h3>\n";
    if ($area_list_format == "select")
    {
      $out_html .= "<form id=\"areaChangeForm\" method=\"get\" action=\"$link\">\n" .
                   "<div>\n" .
                   "<select class=\"room_area_select\" id=\"area_select\" name=\"area\" onchange=\"this.form.submit()\">";
      for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
      {
        $selected = ($row['id'] == $current) ? "selected=\"selected\"" : "";
        $out_html .= "<option $selected value=\"". $row['id']. "\">" . htmlspecialchars($row['area_name']) . "</option>\n";
      }
      // Note:  the submit button will not be displayed if JavaScript is enabled
      $out_html .= "</select>\n" .
                   "<input type=\"hidden\" name=\"day\"   value=\"$day\">\n" .
                   "<input type=\"hidden\" name=\"month\" value=\"$month\">\n" .
                   "<input type=\"hidden\" name=\"year\"  value=\"$year\">\n" .
                   "<input type=\"submit\" class=\"js_none\" value=\"".get_vocab("change")."\">\n" .
                   "</div>\n" .
                   "</form>\n";
    }
    else // list format
    {
      $out_html .= "<ul>\n";
      for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
      {
        $out_html .= "<li><a href=\"$link?year=$year&amp;month=$month&amp;day=$day&amp;area=${row['id']}\">";
        $out_html .= "<span" . (($row['id'] == $current) ? ' class="current"' : '') . ">";
        $out_html .= htmlspecialchars($row['area_name']) . "</span></a></li>\n";
      }
      $out_html .= "</ul>\n";
    }
    $out_html .= "</div>\n";
  }
  return $out_html;
} // end make_area_select_html


function make_room_select_html ($link, $area, $current, $year, $month, $day)
{
  global $tbl_room, $tbl_area, $area_list_format;
  
  $out_html = '';
  $sql = "SELECT R.id, R.room_name
            FROM $tbl_room R, $tbl_area A
           WHERE R.area_id=$area
             AND R.area_id=A.id
             AND R.disabled=0
             AND A.disabled=0
        ORDER BY R.sort_key";
  $res = sql_query($sql);
  // Only show the rooms if there's more than one of them, otherwise
  // there's no point
  if ($res && (sql_count($res) > 1))
  {
    $out_html .= "<div id=\"dwm_rooms\">\n";
    $out_html .= "<h3>" . get_vocab("rooms") . "</h3>";
    if ($area_list_format == "select")
    {
      $out_html .= "<form id=\"roomChangeForm\" method=\"get\" action=\"$link\">\n" .
                   "<div>\n" .
                   "<select class=\"room_area_select\" name=\"room\" onchange=\"this.form.submit()\">\n";
  
      for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
      {
        $selected = ($row['id'] == $current) ? "selected=\"selected\"" : "";
        $out_html .= "<option $selected value=\"". $row['id']. "\">" . htmlspecialchars($row['room_name']) . "</option>\n";
      }
      // Note:  the submit button will not be displayed if JavaScript is enabled
      $out_html .= "</select>\n" .
                   "<input type=\"hidden\" name=\"day\"   value=\"$day\">\n" .
                   "<input type=\"hidden\" name=\"month\" value=\"$month\">\n" .
                   "<input type=\"hidden\" name=\"year\"  value=\"$year\">\n" .
                   "<input type=\"hidden\" name=\"area\"  value=\"$area\">\n" .
                   "<input type=\"submit\" class=\"js_none\" value=\"".get_vocab("change")."\">\n" .
                   "</div>\n" .
                   "</form>\n";
    }
    else  // list format
    {
      $out_html .= "<ul>\n";
      for ($i = 0; ($row = sql_row_keyed($res, $i)); $i++)
      {
        $out_html .= "<li><a href=\"$link?year=$year&amp;month=$month&amp;day=$day&amp;area=$area&amp;room=".$row['id']."\">";
        $out_html .= "<span" . (($row['id'] == $current) ? ' class="current"' : '') . ">";
        $out_html .= htmlspecialchars($row['room_name']) . "</span></a></li>\n";
      }
      $out_html .= "</ul>\n";
    }
    $out_html .= "</div>\n";
  }
  return $out_html;
} // end make_room_select_html


// This will return the appropriate value for isdst for mktime().
// The order of the arguments was chosen to match those of mktime.
// hour is added so that this function can when necessary only be
// run if the time is between midnight and 3am (all DST changes
// occur in this period.
function is_dst ( $month, $day, $year, $hour="-1" )
{
  if ( $hour != -1  && $hour > 3)
  {
    return( -1 );
  }
   
  // entering DST
  if( !date( "I", mktime(12, 0, 0, $month, $day-1, $year)) && 
      date( "I", mktime(12, 0, 0, $month, $day, $year)))
  {
    return( 0 ); 
  }

  // leaving DST
  else if( date( "I", mktime(12, 0, 0, $month, $day-1, $year)) && 
           !date( "I", mktime(12, 0, 0, $month, $day, $year)))
  {
    return( 1 );
  }
  else
  {
    return( -1 );
  }
}

// if crossing dst determine if you need to make a modification
// of 3600 seconds (1 hour) in either direction
function cross_dst ( $start, $end )
{
  // entering DST
  if ( !date( "I", $start) &&  date( "I", $end))
  {
    $modification = -3600;
  }

  // leaving DST
  else if(  date( "I", $start) && !date( "I", $end))
  {
    $modification = 3600;
  }
  else
  {
    $modification = 0;
  }

  return $modification;
}

// If $time falls on a non-working day, shift it back to the end of the last 
// working day before that
function shift_to_workday($time)
{
  global $working_days;
  
  $dow = date('w', $time);  // get the day of the week
  $skip_back = 0;           // number of days to skip back
  // work out how many days to skip back to get to a working day
  while (!in_array($dow, $working_days))
  {
    if ($skip_back == 7)
    {
      break;
    }
    $skip_back++;
    $dow = ($dow + 6) % 7;  // equivalent to skipping back a day
  }
  if ($skip_back != 0)
  {
    // set the time to the end of the working day
    $d = date('j', $time) - $skip_back;
    $m = date('n', $time);
    $y  = date('Y', $time);
    $time = mktime(23, 59, 59, $m, $d, $y);
  }
  return $time;
}
  
// Returns the difference in seconds between two timestamps, $now and $then
// It gives $now - $then, less any seconds that were part of a non-working day
function working_time_diff($now, $then)
{
  global $working_days;
  
  // Deal with the easy case
  if ($now == $then)
  {
    return 0;
  }
  // Sanitise the $working_days array in case it was malformed
  $working_week = array_unique(array_intersect(array(0,1,2,3,4,5,6), $working_days));
  $n_working_days = count($working_week);
  // Deal with the special case where there are no working days
  if ($n_working_days == 0)
  {
    return 0;
  }
  // and the special case where there are no holidays
  if ($n_working_days == 7)
  {
    return ($now - $then);
  }

  // For the rest we're going to assume that $last comes after $first
  $last = max($now, $then);
  $first = min($now, $then);
  
  // first of all, if $last or $first fall on a non-working day, shift
  // them back to the end of the last working day
  $last = shift_to_workday($last);
  $first = shift_to_workday($first);
  // So calculate the difference
  $diff = $last - $first;
  // Then we have to deduct all the non-working days in between.   This will be
  // (a) the number of non-working days in the whole weeks between them +
  // (b) the number of non-working days in the part week
  
  // First let's calculate (a)
  $last = mktime(12, 0, 0, date('n', $last), date('j', $last), date('Y', $last));
  $first = mktime(12, 0, 0, date('n', $first), date('j', $first), date('Y', $first));
  $days_diff = (int) round(($last - $first)/(60*60*24));  // the difference in days
  $whole_weeks = (int) floor($days_diff/7);  // the number of whole weeks between the two
  $non_working_days = $whole_weeks * (7 - $n_working_days);
  // Now (b), ie we just have to calculate how many non-working days there are between the two
  // days of the week that are left
  $last_dow = date('w', $last);
  $first_dow = date('w', $first);
  
  while ($first_dow != $last_dow)
  {
    $first_dow = ($first_dow + 1) % 7;
    if (!in_array($first_dow, $working_week))
    {
      $non_working_days++;
    }
  }

  // So now subtract the number of weekend seconds
  $diff = $diff - ($non_working_days * 60*60*24);
  
  // Finally reverse the difference if $now was in fact before $then
  if ($now < $then)
  {
    $diff = -$diff;
  }
  
  return (int) $diff;
}

// checks whether a given day of the week is supposed to be hidden in the display
function is_hidden_day ($dow)
{
  global $hidden_days;
  return (isset($hidden_days) && in_array($dow, $hidden_days));
}

// returns true if event should be considered private based on
// config settings and event's privacy status (passed to function)
function is_private_event($privacy_status) 
{
  global $private_override;
  if ($private_override == "private" )
  {
    $privacy_status = TRUE;
  }
  elseif ($private_override == "public" )
  {
    $privacy_status = FALSE;
  }

  return $privacy_status;
}


function map_add_booking ($row, &$column, $am7, $pm7, $format)
{
  // Enters the contents of the booking found in $row into $column, which is
  // a column of the map of the bookings being prepared ready for display.
  //
  // $column    the column of the map that is being prepared (see below)
  // $row       a booking from the database
  // $am7       the start of the first slot of the booking day (Unix timestamp)
  // $pm7       the start of the last slot of the booking day (Unix timestamp)
  // $format    time format used for indexing the $map array
  
  // $row is expected to have the following field names, when present:
  //       room_id
  //       start_time
  //       end_time
  //       name
  //       repeat_id
  //       entry_id
  //       type
  //       entry_description
  //       entry_create_by
  //       status
  
  // $column is a column of the map of the screen that will be displayed
  // It looks like:
  //     $column[Time][n][id]
  //                     [is_repeat]
  //                     [color]
  //                     [data]
  //                     [long_descr]
  //                     [start_time]
  //                     [slots]
  //                     [status]
  
  // slots records the duration of the booking in number of time slots.
  // Used to calculate how high to make the block used for clipping
  // overflow descriptions.

  // Fill in the map for this meeting. Start at the meeting start time,
  // or the day start time, whichever is later. End one slot before the
  // meeting end time (since the next slot is for meetings which start then),
  // or at the last slot in the day, whichever is earlier.
  // Time is of the format HHMM without leading zeros.
  //
  // [n] exists because it's possible that there may be multiple bookings
  // in the same time slot.   Normally this won't be the case.   However it
  // can arise legitimately if you increase the resolution, or shift the 
  // displayed day.   For example if you previously had a resolution of 1800 
  // seconds you might have a booking (A) for 1000-1130 and another (B) for 1130-1230.
  // If you then increase the resolution to 3600 seconds, these two bookings 
  // will both occupy the 1100-1200 time slot.   [n] starts at 0.   For
  // the example above the map for the room would look like this
  //
  //       0  1
  // 1000  A
  // 1100  A  B
  // 1200  B
  //
  // Note: int casts on database rows for max may be needed for PHP3.
  // Adjust the starting and ending times so that bookings which don't
  // start or end at a recognized time still appear.
  
  global $resolution;
  global $is_private_field;
  
  $user = getUserName();
  if (is_private_event($row['status'] & STATUS_PRIVATE) &&
         !getWritable($row['entry_create_by'], $user, $row['room_id']))
  {
    $row['status'] |= STATUS_PRIVATE;   // Set the private bit
    if ($is_private_field['entry.name'])
    {
      $row['name']= "[".get_vocab('unavailable')."]";
    }
    if ($is_private_field['entry.description'])
    {
      $row['entry_description']= "[".get_vocab('unavailable')."]";
    }
  }
  else
  {
    $row['status'] &= ~STATUS_PRIVATE;  // Clear the private bit
  }

  $start_t = max(round_t_down($row['start_time'], $resolution, $am7), $am7);
  $end_t = min(round_t_up($row['end_time'], $resolution, $am7) - $resolution, $pm7);
  // calculate the times used for indexing
  $time_start_t = date($format,$start_t);
  $time_end_t = date($format,$end_t);
  

  for ($t = $start_t; $t <= $end_t; $t += $resolution)
  { 
    $time_t = date($format,$t);
    
    // find the first free index (in case there are multiple bookings in a timeslot)
    $n = 0;
    while (!empty($column[$time_t][$n]["id"]))
    {
      $n++;
    }
    
    // fill in the id, type and start time
    $column[$time_t][$n]["id"] = $row['entry_id'];
    $column[$time_t][$n]["is_repeat"] = !empty($row['repeat_id']);
    $column[$time_t][$n]["status"] = $row['status'];
    $column[$time_t][$n]["color"] = $row['type'];
    $column[$time_t][$n]["start_time"] = utf8_strftime(hour_min_format(), $row['start_time']);
    $column[$time_t][$n]["slots"] = null;  // to avoid undefined index NOTICE errors
    // if it's a multiple booking also fill in the name and description
    if ($n > 0)
    {
      $column[$time_t][$n]["data"] = $row['name'];
      $column[$time_t][$n]["long_descr"] = $row['entry_description'];
    }
    // otherwise just leave them blank (we'll fill in the first whole slot later)
    else
    {
      $column[$time_t][$n]["data"] = "";
      $column[$time_t][$n]["long_descr"] = ""; 
    }
  } // end for
  
  
  // Show the name of the booker, the description and the number of complete
  // slots in the first complete slot that the booking happens in, or at the 
  // start of the day if it started before today.

  // Find the number of time slots that the booking occupies, and the index
  // of the first slot that this booking has entirely to itself
  $n_slots = intval(($end_t - $start_t)/$resolution) + 1;
  $first_slot = $start_t;
  
  // If the last time slot is already occupied, we have a multiple
  // booking in the last slot, so decrement the number of slots that
  // we will display for this booking
  if (isset($column[$time_end_t][1]["id"]))
  {
    $n_slots--;
    // If we're only the second booking to land on this time slot
    // then we'll have to adjust the information held for the first booking
    // (unless it's just one slot long in the first place, when it 
    // doesn't matter as it will now be part of a multiple booking).
    // If we are the third booking or more, then it will have already
    // been adjusted.
    if (!isset($column[$time_end_t][2]["id"]))
    {
      if ($column[$time_end_t][0]["slots"] > 1)
      {
        // Move the name and description into the new first slot and decrement the number of slots
        $column[date($format, $end_t + $resolution)][0]["data"] = $column[$time_end_t][0]["data"];
        $column[date($format, $end_t + $resolution)][0]["long_descr"] = $column[$time_end_t][0]["long_descr"];
        $column[date($format, $end_t + $resolution)][0]["slots"] = $column[$time_end_t][0]["slots"] - 1;
      }
    }
  }
  
  // and if the first time slot is already occupied, decrement
  // again, adjust the first slot for this booking
  if (isset($column[$time_start_t][1]["id"]))
  {
    $n_slots--;
    $first_slot += $resolution;
    // If we're only the second booking to land on this time slot
    // then we'll have to adjust the information held for the first booking
    if (!isset($column[$time_start_t][2]["id"]))
    {
      // Find the first slot ($s) of the first booking
      $first_booking_id = $column[$time_start_t][0]["id"];
      $s = $start_t;
      // If you've got to the first slot of the day then that must be the
      // first slot of the first booking
      while ($s > $am7)
      {
        // Otherwise, step back one slot.
        $s -= $resolution;
        // If that slot contains the first booking, then step back again
        if (isset($column[date($format,$s)]))
        {
          foreach ($column[date($format,$s)] as $booking)
          {
            if ($booking["id"] == $first_booking_id)
            {
              continue 2;  // next iteration of the while loop
            }
          }
        }
        // If not, then we've stepped back one slot past the start of
        // the first booking, so step forward again and finish
        $s += $resolution;
        break;
      } // end while
      
      // Now we've found the time ($s) of the first slot of the first booking
      // we need to find its index ($i)
      foreach ($column[date($format,$s)] as $i => $booking)
      {
        if ($booking["id"] == $first_booking_id)
        {
          break;
        }
      }

      // Finally decrement the slot count for the first booking
      // no need to worry about count going < 1: the multiple booking display
      // does not use the slot count.
      $column[date($format,$s)][$i]["slots"]--;
      // and put the name and description in the multiply booked slot
      $column[$time_start_t][0]["data"] = $column[date($format,$s)][$i]["data"];
      $column[$time_start_t][0]["long_descr"] = $column[date($format,$s)][$i]["long_descr"];

    }
  }
 
  // now we've got all the information we can enter it in the first complete
  // slot for the booking (provided it's not a multiple booking slot)
  if (!isset($column[date($format,$first_slot)][1]["id"]))
  {
    $column[date($format,$first_slot)][0]["data"] = $row['name'];
    $column[date($format,$first_slot)][0]["long_descr"] = $row['entry_description'];
    $column[date($format,$first_slot)][0]["slots"] = $n_slots;
  }

} // end function map_add_booking()



function draw_cell($cell, $query_strings, $cell_class)
{
  // draws a single cell in the main table of the day and week views
  //
  // $cell is a two dimensional array that is part of the map of the whole
  // display and looks like this:
  // 
  // $cell[n][id]
  //         [is_repeat]
  //         [color]
  //         [data]
  //         [long_descr]
  //         [start_time]
  //         [slots]
  //         [status]
  //
  // where n is the number of the booking in the cell.    There can be none, one or many 
  // bookings in a cell.    If there are no bookings then a blank cell is drawn with a link
  // to the edit entry form.     If there is one booking, then the booking is shown in that
  // cell.    If there is more than one booking then all the bookings are shown, but they can 
  // be shown in two different ways: minimised and maximised.   By default they are shown
  // minimised, so that the standard row height is preserved.   By clicking a control
  // the cell can be maximised.   (Multiple bookings can arise in a cell if the resolution
  // of an existing database in increased or the booking day is shifted).
  
  // $query_strings is an array containg the query strings (or partial query strings) to be
  // appended to the link used for the cell.    It is indexed as follows:
  //    ['new_periods']   the string to be used for an empty cell if using periods
  //    ['new_times']     the string to be used for an empty cell if using times
  //    ['booking']       the string to be used for a full cell
  //
  // $cell_class specifies the default class for the cell (odd_row, even_row or row_highlight)
  // so that the zebra stripes can be drawn, or else to allow the whole row to be highlighted.
  
  global $main_cell_height, $main_table_cell_border_width;
  global $area, $year, $month, $timetohighlight;
  global $javascript_cursor, $enable_periods, $times_along_top;
  global $approval_enabled, $confirmation_enabled;
  
  // if the time slot has got multiple bookings, then draw a mini-table
  if(isset($cell[1]["id"]))
  {
    // Find out how many bookings there are (needed to calculate heights)
    $n_bookings = 0;
    while (isset($cell[$n_bookings]["id"]))
    {
      $n_bookings++;
    }
    
    // Give the cell a unique id so that the JavaScript can use it
    $td_id = uniqid('td', true);
    
    // Make the class maximized by default so that if you don't have JavaScript then
    // you can still see all the bookings.    If you have JavaScript it will overwrite
    // the class and make it minimized.
    echo "<td class=\"multiple_booking maximized\" id=\"$td_id\">\n";
    ?>
    <script type="text/javascript">
    //<![CDATA[
      document.getElementById('<?php echo $td_id ?>').className = "multiple_booking minimized <?php echo $cell_class ?>";
    //]]>
    </script>
    <?php
    
    // First draw the mini table
    echo "<div class=\"celldiv slots1 mini\">\n";
    echo "<div class=\"multiple_control\" onClick=\"document.getElementById('$td_id').className = 'multiple_booking maximized $cell_class'\">+</div>\n";
    echo "<table>\n";
    echo "<tbody>\n";
    
    $row_height = $main_cell_height - (($n_bookings-1) * $main_table_cell_border_width);   // subtract the borders (first row has no top border)
    $row_height = $row_height/$n_bookings;  // split what's left between the bookings
    $row_height = (int) ceil($row_height);  // round up, so that (a) there's no empty space at the bottom
                                            // and (b) each stripe is at least 1 unit high
    for ($n=0; $n<$n_bookings; $n++)
    {
      $id         = $cell[$n]["id"];
      $is_repeat  = $cell[$n]["is_repeat"];
      $status     = $cell[$n]["status"];
      $color      = $cell[$n]["color"];
      $descr      = htmlspecialchars($cell[$n]["data"]);
      $long_descr = htmlspecialchars($cell[$n]["long_descr"]);
      $class = $color;
      if ($status & STATUS_PRIVATE)
      {
        $class .= " private";
      }
      if ($approval_enabled && ($status & STATUS_AWAITING_APPROVAL))
      {
        $class .= " awaiting_approval";
      }
      if ($confirmation_enabled && ($status & STATUS_TENTATIVE))
      {
        $class .= " tentative";
      }
      echo "<tr>\n";
      echo "<td class=\"$class\"" .
           (($n==0) ? " style=\"border-top-width: 0\"" : "") .   // no border for first row
           ">\n";
      echo "<div style=\"overflow: hidden; " .
           "height: " . $row_height . "px; " .
           "max-height: " . $row_height . "px; " .
           "min-height: " . $row_height . "px\">\n";
      echo "&nbsp;\n";
      echo "</div>\n";
      echo "</td>\n";
      echo "</tr>\n";
    }
    echo "</tbody>\n";
    echo "</table>\n";
    echo "</div>\n";
    
    // Now draw the maxi table
    echo "<div class=\"maxi\">\n";
    $total_height = $n_bookings * $main_cell_height;
    $total_height += ($n_bookings - 1) * $main_table_cell_border_width;  // (first row has no top border)
    echo "<div class=\"multiple_control\" " .
         "onClick=\"document.getElementById('$td_id').className = 'multiple_booking minimized $cell_class'\" " .
         "style =\"height: " . $total_height . "px; " .
                  "min-height: " . $total_height . "px; " .
                  "max-height: " . $total_height . "px; " .
         "\">-</div>\n";  
    echo "<table>\n";
    echo "<tbody>\n";
    for ($n=0; $n<$n_bookings; $n++)
    {
      $id         = $cell[$n]["id"];
      $is_repeat  = $cell[$n]["is_repeat"];
      $status     = $cell[$n]["status"];
      $color      = $cell[$n]["color"];
      $descr      = htmlspecialchars($cell[$n]["start_time"] . " " . $cell[$n]["data"]);
      $long_descr = htmlspecialchars($cell[$n]["long_descr"]);
      $class = $color;
      if ($status & STATUS_PRIVATE)
      {
        $class .= " private";
      }
      if ($approval_enabled && ($status & STATUS_AWAITING_APPROVAL))
      {
        $class .= " awaiting_approval";
      }
      if ($confirmation_enabled && ($status & STATUS_TENTATIVE))
      {
        $class .= " tentative";
      }    
      echo "<tr>\n";
      echo "<td class=\"$class\"" .
           (($n==0) ? " style=\"border-top-width: 0\"" : "") .   // no border for first row
           ">\n";
      echo "<div class=\"celldiv slots1\">\n";     // we want clipping of overflow
      echo "<a href=\"view_entry.php?id=$id&amp;". $query_strings['booking'] . "\" title=\"$long_descr\">";
      echo ($is_repeat) ? "<img class=\"repeat_symbol\" src=\"images/repeat.png\" alt=\"" . get_vocab("series") . "\" title=\"" . get_vocab("series") . "\" width=\"10\" height=\"10\">" : '';
      echo "$descr</a>\n";
      echo "</div>\n";
     
      echo "</td>\n";
      echo "</tr>\n";
    }
    echo "</tbody>\n";
    echo "</table>\n";
    echo "</div>\n";
    
    echo "</td>\n";
  }  // end of if isset ( ...[1]..)
  
  // otherwise draw a cell, showing either the booking or a blank cell
  else
  {  
    if(isset($cell[0]["id"]))
    {       
      $id         = $cell[0]["id"];
      $is_repeat  = $cell[0]["is_repeat"];
      $status     = $cell[0]["status"];
      $color      = $cell[0]["color"];
      $descr      = htmlspecialchars($cell[0]["data"]);
      $long_descr = htmlspecialchars($cell[0]["long_descr"]);
      $slots      = $cell[0]["slots"];
    }
    else  // id not set
    {
      unset($id);
      $slots = 1;
    }

    // $c is the colour of the cell that the browser sees. Zebra stripes normally,
    // row_highlight if we're highlighting that line and the appropriate colour if
    // it is booked (determined by the type).
    // We tell if its booked by $id having something in it
    if (isset($id))
    {
      $c = $color;
      if ($status & STATUS_PRIVATE)
      {
        $c .= " private";
      }
      if ($approval_enabled && ($status & STATUS_AWAITING_APPROVAL))
      {
        $c .= " awaiting_approval";
      }
      if ($confirmation_enabled && ($status & STATUS_TENTATIVE))
      {
        $c .= " tentative";
      } 
    }
    else
    {
      $c = $cell_class; // Use the default color class (zebra stripes or highlighting).
    }
    
    // Don't put in a <td> cell if the slot is booked and there's no description.
    // This would mean that it's the second or subsequent slot of a booking and so the
    // <td> for the first slot would have had a rowspan that extended the cell down for
    // the number of slots of the booking.

    if (!(isset($id) && ($descr == ""))) 
    {
      tdcell($c, $slots);

      // If the room isn't booked then allow it to be booked
      if (!isset($id))
      {
        echo "<div class=\"celldiv slots1\">\n";  // a bookable slot is only one unit high

        if ($javascript_cursor)
        {
          echo "<script type=\"text/javascript\">\n";
          echo "//<![CDATA[\n";
          echo "BeginActiveCell();\n";
          echo "//]]>\n";
          echo "</script>\n";
        }
        
        if( $enable_periods )
        {
          echo "<a class=\"new_booking\" href=\"edit_entry.php?" . $query_strings['new_periods'] . "\">\n";
          echo "<img src=\"images/new.gif\" alt=\"New\" width=\"10\" height=\"10\">\n";
          echo "</a>\n";
        }
        else
        {
          echo "<a class=\"new_booking\" href=\"edit_entry.php?" . $query_strings['new_times'] . "\">\n";
          echo "<img src=\"images/new.gif\" alt=\"New\" width=\"10\" height=\"10\">\n";
          echo "</a>\n";
        }
        
        if ($javascript_cursor)
        {
          echo "<script type=\"text/javascript\">\n";
          echo "//<![CDATA[\n";
          echo "EndActiveCell();\n";
          echo "//]]>\n";
          echo "</script>\n";
        }
        echo "</div>\n";
      }
      else                 // if it is booked then show the booking
      {
        if ($times_along_top)
        {
          echo "<div class=\"celldiv slots1\">\n";
        }
        else
        {
          echo "<div class=\"celldiv slots" . $slots . "\">\n";     // we want clipping of overflow
        }
        echo "<a href=\"view_entry.php?id=$id&amp;". $query_strings['booking'] . "\" title=\"$long_descr\">";
        echo ($is_repeat) ? "<img class=\"repeat_symbol $c\" src=\"images/repeat.png\" alt=\"" . get_vocab("series") . "\" title=\"" . get_vocab("series") . "\" width=\"10\" height=\"10\">" : '';
        echo "$descr</a>\n";
        echo "</div>\n";
      }
      echo "</td>\n";
    }
  }
}  // end function draw_cell


// Draw a time cell to be used in the first and last columns of the day and week views
//    $t                 the timestamp for the start of the slot
//    $time_t            the time converted to HHMM format without leading zeros
//    $time_t_stripped   a stripped version of the time for use with periods
//    $hilite_url        the url to form the basis of the link in the time cell
function draw_time_cell($t, $time_t, $time_t_stripped, $hilite_url)
{
  global $enable_periods, $periods;
  
  tdcell("row_labels", 1);
  echo "<div class=\"celldiv slots1\">\n";
  if ( $enable_periods )
  {
    
    echo "<a href=\"$hilite_url=$time_t\"  title=\""
      . get_vocab("highlight_line") . "\">"
      . $periods[$time_t_stripped] . "</a>\n";
  }
  else
  {
    echo "<a href=\"$hilite_url=$time_t\" title=\""
      . get_vocab("highlight_line") . "\">"
      . utf8_strftime(hour_min_format(),$t) . "</a>\n";
  }
  echo "</div></td>\n";
}

// Draw a room cell to be used in the first and last columns of the day view
//    $row     contains the room details; comes from an SQL query
//    $link    the href to be used for the link
function draw_room_cell($row, $link)
{
  tdcell("row_labels", 1);
  echo "<div class=\"celldiv slots1\">\n";
  echo "<a href=\"$link\" title=\"" . get_vocab("viewweek") . " &#10;&#10;" . $row['description'] . "\">";
  echo htmlspecialchars($row['room_name']) . ($row['capacity'] > 0 ? "(".$row['capacity'].")" : "");
  echo "</a>\n";
  echo "</div></td>\n";
}

// Draw a day cell to be used in the first and last columns of the week view
//    $text    contains the date, formatted as a string
//    $link    the href to be used for the link
function draw_day_cell($text, $link)
{
  tdcell("row_labels", 1);
  echo "<div class=\"celldiv slots1\">\n";
  echo "<a href=\"$link\" title=\"" . get_vocab("viewday") . "\">$text</a>\n";
  echo "</div></td>\n";
}


// Generate a globally unique id
//
// We will generate a uid of the form "MRBS-uniqid-MD5hash@domain_name" 
// where uniqid is time based and is generated by uniqid() and the
// MD5hash is the first 8 characters of the MD5 hash of $str concatenated
// with a random number.
function generate_global_uid($str)
{
  $uid = uniqid('MRBS-');
  $uid .= "-" . substr(md5($str . rand(0,10000)), 0, 8);
  $uid .= "@";
  // Add on the domain name if possible, if not the server name,
  // otherwise 'MRBS'
  if (empty($_SERVER['SERVER_NAME']))
  {
    $uid .= 'MRBS';
  }
  elseif (strpos($_SERVER['SERVER_NAME'], 'www.') === 0)
  {
    $uid .= substr($_SERVER['SERVER_NAME'], 4);
  }
  else
  {
    $uid .= $_SERVER['SERVER_NAME'];
  }

  return $uid;
}

?>
