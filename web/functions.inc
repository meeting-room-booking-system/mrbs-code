<?php

// $Id$


// Set the default timezone.   Caters for PHP servers that don't
// have date_default_timezone_set()
function mrbs_default_timezone_set($timezone)
{
  global $area_defaults;
  
  if (!isset($timezone))
  {
    // to prevent people running into DST problems
    $message = 'Configuration error: $timezone has not been set.';
    trigger_error($message, E_USER_WARNING);
    fatal_error(FALSE, $message);
  }
  else
  {
    if (function_exists("date_default_timezone_set"))
    {
      // We try and set the timezone to $timezone, which is the timezone setting
      // for the area.   If that fails we try the default for new areas.   And if
      // that fails we try the server timezone.
      if (!date_default_timezone_set($timezone))
      {
        $fallback_timezone = $area_defaults['timezone'];
        if (!date_default_timezone_set($fallback_timezone))
        {
          // date_default_timezone_get() was introduced at the same time as
          // date_default_timezone_set(), so it will exist
          $fallback_timezone = date_default_timezone_get();
          if (!date_default_timezone_set($fallback_timezone))
          {
            $message = "Could not set any timezone";
            trigger_error($message, E_USER_WARNING);
            fatal_error(TRUE, $message);
          }
        }
        trigger_error("Could not set timezone to '$timezone'; using '$fallback_timezone' instead", E_USER_WARNING);
      }
    }
    else
    {
      putenv("TZ=$timezone");
    }
  }
}


// Get the default timezone.  Caters for PHP servers that don't
// have date_default_timezone_get()
function mrbs_default_timezone_get()
{
  if (function_exists("date_default_timezone_get"))
  {
    return date_default_timezone_get();
  }
  else
  {
    return getenv('TZ');
  }
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


// Outputs the HTTP headers
function http_headers()
{
  header("Content-Type: text/html; charset=" . get_charset());
  // We want to prevent IE8 offering "Compatability View" as that really
  // messes up MRBS, especially on the Report page
  header("X-UA-Compatible: IE=Edge");
  header("Pragma: no-cache");                          // HTTP 1.0
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the pastt
}


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
  
  http_headers();
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
  if (file_exists("Themes/$theme/header.inc"))
  {
    include_once "Themes/$theme/header.inc";
  }
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
  if (file_exists("Themes/$theme/footer.inc"))
  {
    include_once "Themes/$theme/footer.inc";
  }
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
// (We also send a couple of extra headers as the "Expires" header alone
// does not always result in caching.)
function expires_header($seconds)
{
  header("Expires: ".rfc1123_date(time()+$seconds));
  header("Pragma: cache");
  header("Cache-Control: max-age=$seconds");
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
    $dur = rtrim($dur, '0');  // removes trailing zeros
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
//    $start_time     int     start time as a Unix timestamp
//    $end_time       int     end time as a Unix timestamp
//    $enable_periods boolean whether we are using periods
//    $translate      boolean whether to translate into the browser language
function get_duration($start_time, $end_time, $enable_periods, $translate=TRUE)
{
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

//   $maxlength   the maximum length of input allowed

//   $attributes  additional attributes, allowing HTML5 input types such as number and
//                email to be used.   Note that additional attributes such as min, etc.
//                can also be included in the string, eg 'type="number" min="1" step="1"'.
function generate_input($label_text, $name, $value, $disabled=FALSE, $maxlength=NULL, $attributes=NULL)
{
  // generate the HTML
  $html  = "<label for=\"$name\">$label_text</label>\n";
  $html .= "<input" . ((isset($attributes)) ? " $attributes" : "") . " id=\"$name\" name=\"$name\"";
  $html .= ($disabled) ? " disabled=\"disabled\"" : '';
  $html .= (isset($maxlength)) ? " maxlength=\"$maxlength\"" : '';
  // Don't give an empty string if it's a number as that's not a valid floating point number
  // and will fail HTML5 validation
  if (!isset($attributes) || (strpos($attributes, 'type="number"') === FALSE) || ($value != '') )
  {
    $html .= " value=\"" . htmlspecialchars($value) . "\"";
  }
  $html .= ">\n";
  // and a hidden input if the input box is disabled
  if ($disabled)
  {
    $html .= "<input type=\"hidden\" name=\"$name\" value=\"".
      htmlspecialchars($value)."\">\n";
  }
  echo $html;
}


// Generate a group of radio buttons
// $buttons is an associative array where the key is the value of the button
// and the value is the button text
function generate_radio_group($label_text, $name, $current, $buttons)
{
  echo "<label>$label_text:</label>\n";
  echo "<div class=\"group\">\n";
  // Output each radio button
  foreach ($buttons as $value => $token)
  {
    echo "<label>";
    echo "<input class=\"radio\" type=\"radio\" name=\"$name\" value=\"$value\"";          
    if ($current == $value)
    {
      echo " checked=\"checked\"";
    }
    echo ">" . get_vocab($token);
    echo "</label>\n";
  }
  echo "</div>\n";
}


// Generates a select box from $options, an array of options
// If $disabled is TRUE, then the select box is disabled and a hidden
// input is generated to pass through $current
function generate_select($label_text, $name, $current, $options, 
                         $mandatory = FALSE, $disabled=FALSE)
{
  // generate the HTML
  $html  = "<label for=\"$name\">$label_text</label>\n";
  $html .= "<select id=\"$name\" name=\"$name\"";
  $html .= ($disabled) ? " disabled=\"disabled\"" : "";
  $html .= ($mandatory) ? " required" : "";
  $html .= ">\n";

  // We can cope with both associative and ordinary arrays
  if (is_assoc($options))
  {
    foreach ($options as $key => $value)
    {
      $html .= "<option value=\"$key\"";
      $html .= (isset($current) && ($current == $key)) ? " selected=\"selected\"" : '';
      $html .= ">".htmlspecialchars($value)."</option>\n";
    }
  }
  else
  {
    foreach ($options as $option)
    {
      $html .= "<option";
      $html .= (isset($current) && ($current == $option)) ? " selected=\"selected\"" : '';
      $html .= ">".htmlspecialchars($option)."</option>\n";
    }
  }
  $html .= "</select>\n";
  // and a hidden input if the select box is disabled
  if ($disabled)
  {
    $html .= "<input type=\"hidden\" name=\"$name\" value=\"".
      htmlspecialchars($current)."\">\n";
  }
  
  echo $html;
}

// Generate a textarea with an associated label
// If $disabled is TRUE, then the textarea is disabled and a hidden
// input is generated to pass through $value
//
//   $attributes  additional attributes, allowing HTML5 input types such as required
function generate_textarea($label_text, $name, $value, $disabled=FALSE, $attributes=NULL)
{
  // generate the HTML
  $html  = "<label for=\"$name\">$label_text</label>\n";
  // textarea rows and cols are overridden by CSS height and width
  $html .= "<textarea id=\"$name\" name=\"$name\" rows=\"8\" cols=\"40\"";
  $html .= (isset($attributes)) ? " $attributes" : "";
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
// $disabled:  TRUE if the SELECT is to be disabled (Note: hidden inputs will be
// created if the SELECT is disabled)
//
// Whether or not JavaScript is enabled the date is passed back in three separate
// variables:  ${prefix}day, ${prefix}month and ${prefix}year
//
// The function passes back three separate variables, rather than a single date 
// variable, partly for compatibility with previous implementations of genDateSelector()
// and partly because it's easier to do this for the non-JavaScript version.
function genDateSelector($prefix, $day, $month, $year, $form_id='', $disabled=FALSE)
{
  global $strftime_format, $year_range;
  
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
  $min = min($year, date("Y")) - $year_range['back'];
  $max = max($year, date("Y")) + $year_range['ahead'];
  
  $datepicker_baseid = "${prefix}datepicker";
  
  // We'll put the date selector in a span.    First of all we'll generate
  // day, month and year selectors.   These will be used if JavaScript is
  // disabled.    If JavaScript is enabled it will overwrite these with a
  // datepicker calendar.
  echo "<span id=\"${prefix}dateselector\">\n";
  // the day selector
  echo "<select name=\"${prefix}day\"" . 
       ($disabled ? " disabled=\"disabled\"" : "") .
       ">\n"; 
  for ($i = 1; $i <= 31; $i++)
  {
    echo "<option" . ($i == $day ? " selected=\"selected\"" : "") . ">$i</option>\n";
  }
  echo "</select>\n";
  // the month selector
  echo "<select name=\"${prefix}month\"" . 
       ($disabled ? " disabled=\"disabled\"" : "") .
       ">\n"; 
  for ($i = 1; $i <= 12; $i++)
  {
    $m = utf8_strftime($strftime_format['mon'], mktime(0, 0, 0, $i, 1, $year));  
    echo "<option value=\"$i\"" . ($i == $month ? " selected=\"selected\"" : "") . ">$m</option>\n";
  }
  echo "</select>\n";
  // the year selector
  echo "<select name=\"${prefix}year\"" . 
       ($disabled ? " disabled=\"disabled\"" : "") .
       ">\n";
  for ($i = $min; $i <= $max; $i++)
  {
    echo "<option value=\"$i\"" . ($i == $year ? " selected=\"selected\"" : "") . ">$i</option>\n";
  }
  echo "</select>\n";
  // If the select is disabled we'll still pass through the values as hidden inputs
  if ($disabled)
  {
    echo "<input type=\"hidden\" name=\"${prefix}day\" value=\"$day\">\n";
    echo "<input type=\"hidden\" name=\"${prefix}month\" value=\"$month\">\n";
    echo "<input type=\"hidden\" name=\"${prefix}year\" value=\"$year\">\n";
  }
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
                                                       if ($disabled)
                                                       {
                                                         echo ", disabled: true";
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
  // the form and we don't want the value cluttering up the URL (if it's a GET).
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
function fatal_error($need_header, $message, $show_form_data = true)
{
  global $simple_trailer, $weekstarts, $view_week_number, $strftime_format;

  if ($need_header)
  {
    print_header(0, 0, 0, 0, "");
  }
  error_log("MRBS: ".$message);

  if ($show_form_data)
  {
    if (!empty($_GET))
    {
      error_log("MRBS GET: ".print_r($_GET, true));
    }
    if (!empty($_POST))
    {
      error_log("MRBS POST: ".print_r($_POST, true));
    }
  }
  if (!empty($_SESSION))
  {
    error_log("MRBS SESSION: ".print_r($_SESSION, true));
  }
  echo "<p>$message</p>";
  output_trailer();
  exit;
}


// Escape a PHP string for use in JavaScript
//
// Based on a function contributed by kongaspar at gmail dot com at 
// http://www.php.net/manual/function.addcslashes.php
function escape_js($str)
{
  return addcslashes($str, "\\\'\"&\n\r<>/");
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


// Clean up a row from the area table, making sure there are no nulls, casting
// boolean fields into bools and doing some sanity checking
function clean_area_row($row)
{
  global $force_resolution, $area_defaults, $boolean_fields, $private_override_options;
  
  foreach ($row as $key => $value)
  {
    // If the "per area" setting is in the database, then use that.   Otherwise
    // just stick with the default setting from the config file.
    // (don't use the database setting if $force_resolution is TRUE 
    // and we're looking at the resolution field)
    if (($key != 'resolution') || empty($force_resolution))
    {
      $row[$key] = (isset($row[$key])) ? $value : $area_defaults[$key];
    }
    // Cast those fields which are booleans into booleans
    if (in_array($key, $boolean_fields['area']))
    {
      $row[$key] = (bool) $row[$key];
    }
  }
  // Do some sanity checking in case the area table is somehow messed up
  // (1) 'private_override' must be a valid value
  if (array_key_exists('private_override', $row) &&
      !in_array($row['private_override'], $private_override_options))
  {
    $row['private_override'] = 'private';  // the safest default
    $message = "Invalid value for 'private_override' in the area table.  Using '${row['private_override']}'.";
    trigger_error($message, E_USER_WARNING);
  }
  // (2) 'resolution' must be positive
  if (array_key_exists('resolution', $row) &&
      (empty($row['resolution']) || ($row['resolution'] < 0)))
  {
    $row['resolution'] = 30*60;  // 30 minutes, a reasonable fallback
    $message = "Invalid value for 'resolution' in the area table.   Using ${row['resolution']} seconds.";
    trigger_error($message, E_USER_WARNING);
  }
  
  return $row;
}


// Update the default area settings with the ones specific to this area.
// If no value is set in the database, use the value from the config file.
// If $area is empty, use the default area
function get_area_settings($area)
{
  global $tbl_area;
  global $resolution, $default_duration, $default_duration_all_day;
  global $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes;
  global $private_enabled, $private_default, $private_mandatory, $private_override;
  global $min_book_ahead_enabled, $max_book_ahead_enabled, $min_book_ahead_secs, $max_book_ahead_secs;
  global $approval_enabled, $reminders_enabled, $enable_periods;
  global $confirmation_enabled, $confirmed_default, $timezone;
  global $max_per_interval_area_enabled, $max_per_interval_area;
  global $interval_types;
  
  if (empty($area))
  {
    $area = get_default_area();
  }
  
  // Get all the "per area" config settings                  
  $sql = "SELECT timezone, resolution, default_duration, default_duration_all_day,
                 morningstarts, morningstarts_minutes,
                 eveningends, eveningends_minutes,
                 private_enabled, private_default, private_mandatory, private_override,
                 min_book_ahead_enabled, max_book_ahead_enabled,
                 min_book_ahead_secs, max_book_ahead_secs,
                 max_per_day_enabled, max_per_day,
                 max_per_week_enabled, max_per_week,
                 max_per_month_enabled, max_per_month,
                 max_per_year_enabled, max_per_year,
                 max_per_future_enabled, max_per_future,
                 approval_enabled, reminders_enabled, enable_periods,
                 confirmation_enabled, confirmed_default
          FROM $tbl_area 
          WHERE id=$area 
          LIMIT 1";
  $res = sql_query($sql);
  if (!$res || (sql_count($res) == 0))
  {
    // We still need to set the timezone even if the query didn't
    // return any results
    mrbs_default_timezone_set($timezone);
    return;
  }
  else
  {
    $row = clean_area_row(sql_row_keyed($res, 0));
    foreach ($row as $key => $value)
    {
      $$key = $value;
    }
  }
  // Set the timezone
  mrbs_default_timezone_set($timezone);
  
  // Set the $max_per_interval_area_enabled and $max_per_interval_area arrays,
  // which are handled slightly differently
  foreach ($interval_types as $interval_type)
  {
    $var = "max_per_${interval_type}_enabled";
    $max_per_interval_area_enabled[$interval_type] = $$var;
    $var = "max_per_${interval_type}";
    $max_per_interval_area[$interval_type] = $$var;
  }
  
  // If we're using periods then set the rsolution to 60 seconds
  if ($enable_periods)
  {
    $resolution = 60;
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
//
// Note: the charset defaults to UTF-8.  This is slightly different from the
// behaviour in the PHP html_entity_decode() function where the default charset
// was only changed from ISO-8859-1 to UTF-8 in PHP 5.4.0
function mrbs_entity_decode($string, $quote_style=ENT_COMPAT, $charset = 'UTF-8')
{
  if (function_exists('version_compare') && version_compare(PHP_VERSION, '5.0.0', 'ge'))
  {
    $string = html_entity_decode($string, $quote_style, $charset);
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


// Display the entry-type color key. This has up to 2 rows, up to 5 columns.
function show_colour_key()
{
  global $booking_types;
  // set the table width.   Default is 5, but try and avoid rows of unequal length
  switch (count($booking_types))
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
  $n = 0;
  foreach ($booking_types as $key)
  {
    $value = get_type_vocab($key);
    if (++$n > $table_width)
    {
      $n = 1;
      echo "</tr><tr>";
    }
    echo tdcell($key, 1);
    echo "<div class=\"celldiv slots1\" " .  // put the description inside a div which will give clipping in case of long names
    "title=\"$value\">\n";        // but put the name in the title so you can still read it all if you hover over it
    echo "$value</div></td>\n";
  }
  // If there is more than one row and the bottom row isn't complete then 
  // pad it out with a single merged cell
  if ((count($booking_types) > $table_width) && ($n < $table_width))
  {
    echo "<td colspan=\"" . ($table_width - $n) . "\"" .
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


// returns the numeric day of the week (0-6) in terms of the MRBS week as defined by 
// $weekstarts.   For example if $weekstarts is set to 2 (Tuesday) and a $time for
// a Wednesday is given, then 1 is returned.
function day_of_MRBS_week($time)
{
  global $weekstarts;
  
  return (date('w', $time) - $weekstarts + 7) % 7;
}


// This will return the appropriate value for isdst for mktime().
// The order of the arguments was chosen to match those of mktime.
// hour is added so that this function can when necessary only be
// run if the time is between midnight and 3am (all DST changes
// occur in this period.
function is_dst($month, $day, $year, $hour="-1")
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
function cross_dst($start, $end)
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

// Tests whether an array is associative
//
// Thanks to magentix at gmail dot com at http://php.net/manual/function.is-array.php
function is_assoc($arr)
{
  return (is_array($arr) && count(array_filter(array_keys($arr),'is_string')) == count($arr));
}


// Checks whether we are running as a CLI module
//
// Based on code from mniewerth at ultimediaos dot com at 
// http://php.net/manual/features.commandline.php
function is_cgi()
{
  return (substr(PHP_SAPI, 0, 3) == 'cgi');
}


// Checks whether we are running from the CLI
//
// Based on code from mniewerth at ultimediaos dot com at 
// http://php.net/manual/features.commandline.php
function is_cli()
{
  global $allow_cli;
  
  if (!$allow_cli)
  {
    return FALSE;
    
  }
  if (defined('STDIN'))
  {
    return TRUE;
  }
  elseif (is_cgi() && getenv('TERM'))
  {
    return TRUE;
  }
  else
  {
    return FALSE;
  }
}
?>
