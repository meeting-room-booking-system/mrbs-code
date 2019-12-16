<?php
namespace MRBS;

use \DateTimeZone;
use PHPMailer\PHPMailer\PHPMailer;

require_once "mrbs_sql.inc";


// Tests whether the request has come via an Ajax call or not.
function is_ajax()
{
  global $server;

  return (isset($server['HTTP_X_REQUESTED_WITH']) &&
          (utf8_strtolower($server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'));
}

// Returns the contents of the URL, or else FALSE on failure
function url_get_contents($url)
{
  global $server;

  $connect_timeout  = 2;  // seconds
  $transfer_timeout = 2;  // seconds
  $curlopt_timeout  = 3;  // seconds

  // If we can we use cURL to download the page because it allows us to
  // set a timeout on establishing a connection as well as on the transfer time.
  // (We can only set a timeout on transfer time with file_get_contents()).  The
  // connection timeout is important because MRBS may be being used in sites
  // where direct access to the internet is not allowed.
  if (function_exists('curl_init'))
  {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_USERAGENT, $server['HTTP_USER_AGENT']);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connect_timeout);
    curl_setopt($ch, CURLOPT_LOW_SPEED_TIME, $transfer_timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $curlopt_timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $contents = curl_exec($ch);
    if ($contents === false)
    {
      trigger_error(curl_error($ch), E_USER_WARNING);
    }
    curl_close($ch);
  }

  // If there's no cURL then use file_get_contents()
  else
  {
    // Create the stream context so that we can (a) make sure the connection is closed,
    // because file_get_contents() does not return the contents when all the bytes
    // have been sent, but when the connection is closed, (b) set a timeout in case
    // the transfer is slow and (c) add a User-Agent header to prevent possible 403 errors
    // from some sites.
    $context = stream_context_create(
        array(
            'http' => array(
              'header'  => array(
                  'Connection: close',
                  'User-Agent: ' . $server['HTTP_USER_AGENT']
                ),
            'timeout' => $transfer_timeout)
          )
      );
    // Suppress any timeout warnings
    $contents = @file_get_contents($url, false, $context);
    if ($contents === false)
    {
      trigger_error("MRBS: could not get contents of $url", E_USER_WARNING);
    }
  }

  return $contents;
}


function get_rep_interval_units($rep_type, $rep_interval)
{
  switch ($rep_type)
  {
    case REP_DAILY:
      return ($rep_interval == 1) ? get_vocab('day') : get_vocab('days');
      break;
    case REP_WEEKLY:
      return ($rep_interval == 1) ? get_vocab('week') : get_vocab('weeks');
      break;
    case REP_MONTHLY:
      return ($rep_interval == 1) ? get_vocab('month') : get_vocab('months');
      break;
    case REP_YEARLY:
      return ($rep_interval == 1) ? get_vocab('year_lc') : get_vocab('years');
      break;
    default:
      return '';
      break;
  }
}


function get_rep_interval_units_mail($rep_type, $rep_interval)
{
  switch ($rep_type)
  {
    case REP_DAILY:
      return ($rep_interval == 1) ? get_mail_vocab('day') : get_mail_vocab('days');
      break;
    case REP_WEEKLY:
      return ($rep_interval == 1) ? get_mail_vocab('week') : get_mail_vocab('weeks');
      break;
    case REP_MONTHLY:
      return ($rep_interval == 1) ? get_mail_vocab('month') : get_mail_vocab('months');
      break;
    case REP_YEARLY:
      return ($rep_interval == 1) ? get_mail_vocab('year_lc') : get_mail_vocab('years');
      break;
    default:
      return '';
      break;
  }
}


// Returns the maximum length, in characters, of a column, or NULL if the maximum
// length is not known.   Should only be called after any database upgrades have been
// performed as the results are stored in a static variable and so the maximum lengths
// may change after an upgrade.
function maxlength($column)
{
  global $table_vars;

  static $maxlengths = null;

  if (!isset($maxlengths))
  {
    $maxlengths = array();

    foreach ($table_vars as $table => $var)
    {
      global $$var;

      // Check to see if the table exists, as it won't yet if it's about
      // to be created as part of an upgrade
      if (db()->table_exists($$var))
      {
        // Find the maximum length of the CHAR and VARCHAR fields (we won't
        // worry about TEXT fields)
        $field_info = db()->field_info($$var);

        foreach ($field_info as $field)
        {
          if (($field['nature'] == 'character') &&
              isset($field['length']) &&
              ($field['length'] < 256))
          {
            $maxlengths[$table . '.' . $field['name']] = (int) $field['length'];
          }
        }
      }
    }
  }

  return (isset($maxlengths[$column])) ? $maxlengths[$column] : null;
}


// Truncate any fields that have a maximum length as a precaution.
// Although the MAXLENGTH attribute is used in the <input> tag, this can
// sometimes be ignored by the browser, for example by Firefox when
// autocompletion is used.  The user could also edit the HTML and remove
// the MAXLENGTH attribute.    Another problem is that the <datalist> tag
// does not accept a maxlength attribute.  Passing an oversize string to some
// databases (eg some versions of PostgreSQL) results in an SQL error,
// rather than silent truncation of the string.
//
// We truncate to a maximum number of UTF8 characters rather than bytes.
// This is OK in current versions of MySQL and PostgreSQL, though in earler
// versions of MySQL (I haven't checked PostgreSQL) this could cause problems
// as a VARCHAR(n) was n bytes long rather than n characters.
function truncate($value, $column)
{
  if (null !== ($maxlength = maxlength($column)))
  {
    return utf8_substr($value, 0, $maxlength);
  }

  return $value;
}


// Formats a number taking into account the current locale.  (Could use
// the NumberFormatter class, but the intl extension isn't always installed.)
function number_format_locale($number, $decimals=0)
{
  $locale_info = localeconv();
  return number_format($number, $decimals, $locale_info['decimal_point'], $locale_info['thousands_sep']);
}


function get_microtime()
{
  if (function_exists('microtime'))
  {
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
  }
  else
  {
    return time();
  }
}


function function_disabled($name)
{
  $disabled = explode(', ', ini_get('disable_functions'));
  return in_array($name, $disabled);
}


// Set the default timezone.   If $tz is not set, then the default MRBS
// timezone from the config file is used.
function mrbs_default_timezone_set($tz=null)
{
  global $area_defaults;

  if (!isset($tz))
  {
    if (isset($area_defaults['timezone']))
    {
      $tz = $area_defaults['timezone'];
    }
    else
    {
      // This should have been picked up before now, but just in case ...

      // We don't just use a default default timezone such as UTC because then
      // people would start running into DST problems with their bookings.
      $message = 'MRBS configuration error: $timezone has not been set.';
      // Use die() rather than fatal_error() because unless we have set the timezone
      // PHP starts complaining bitterly if we try and do anything remotely complicated.
      die($message);
    }
  }

  if (!date_default_timezone_set($tz))
  {
    $message = "MRBS configuration error: invalid timezone '$tz'";
    die($message);  // See comment above about use of die()
  }

}


// Get the default timezone.
// The MRBS wrapper round date_default_timezone_get() is redundant now.  It used to
// cater for PHP servers that don't have date_default_timezone_get(), but now that
// the minimum PHP version is >= 5.1.0, there are none.
function mrbs_default_timezone_get()
{
  return date_default_timezone_get();
}


// Gets the default email address for $user.   Returns an empty
// string if one can't be found
function get_default_email($user)
{
  global $mail_settings;

  if (!isset($user) || $user === '')
  {
    return '';
  }

  $email = $user;

  // Remove the suffix, if there is one
  if (isset($mail_settings['username_suffix']) && ($mail_settings['username_suffix'] !== ''))
  {
    $suffix = $mail_settings['username_suffix'];
    if (substr($email, -strlen($suffix)) === $suffix)
    {
      $email = substr($email, 0, -strlen($suffix));
    }
  }

  // Add on the domain, if there is one
  if (isset($mail_settings['domain']) && ($mail_settings['domain'] !== ''))
  {
    // Trim any leading '@' character. Older versions of MRBS required the '@' character
    // to be included in $mail_settings['domain'], and we still allow this for backwards
    // compatibility.
    $domain = ltrim($mail_settings['domain'], '@');
    $email .= '@' . $domain;
  }

  return $email;
}


function get_cookie_path()
{
  global $cookie_path_override, $server;

  if (isset($cookie_path_override))
  {
    $cookie_path = $cookie_path_override;
  }
  else
  {
    // REQUEST_URI isn't set on all PHP systems, so fall back to PHP_SELF
    $cookie_path = isset($server['REQUEST_URI']) ? $server['REQUEST_URI'] : $server['PHP_SELF'];
    // Strip off everything after the last '/'
    $cookie_path = preg_replace('/[^\/]*$/', '', $cookie_path);
  }

  return $cookie_path;
}


// Gets the url path from either $_SERVER['PHP_SELF'] or $_SERVER['REQUEST_URI'], whichever
// happens to be non-empty.  (Some systems will not have one or the other variable set).
function url_path()
{
  global $server;

  if (isset($server['PHP_SELF']) && ($server['PHP_SELF'] !== ''))
  {
    $result = $server['PHP_SELF'];
  }
  elseif (isset($server['REQUEST_URI']) && ($server['REQUEST_URI'] !== ''))
  {
    // The REQUEST_URI includes the query string, so we need to strip it off
    $result = parse_url($server['REQUEST_URI'], PHP_URL_PATH);
    if ($result === false)
    {
      trigger_error('Cannot parse $_SERVER["REQUEST_URI"] of "' . $server['REQUEST_URI'] . '"', E_USER_NOTICE);
      $result = '';
    }
  }
  else
  {
    if (!is_cli())
    {
      trigger_error('$_SERVER["PHP_SELF"] and $_SERVER["REQUEST_URI"] are both empty.  Check your server configuration.', E_USER_NOTICE);
    }
    $result = '';
  }

  return $result;
}


// Returns the base URL for MRBS.  If the config variable $url_base is set then that
// overrides the automatic calculation.  A '/' will be appended to the end of the string,
// unless it is empty or already ends in a '/'.
function url_base()
{
  global $url_base, $server;

  if (isset($url_base))
  {
    $result = $url_base;
  }
  elseif (isset($server['HTTP_HOST']))
  {
    $result = $server['HTTP_HOST'] . dirname(url_path());
  }
  else
  {
    $result = '';
  }

  if (($result !== '') && (substr($result, -1) !== '/'))
  {
    $result .= '/';
  }

  return $result;
}


// Returns the current page.   If the page ends in $suffix this will be cut off.
// The query string is optionally added.
function this_page($with_query_string=false, $suffix=null)
{
  global $server;

  assert(is_bool($with_query_string), '$with_query_string should be a boolean');
  assert(is_null($suffix) || is_string($suffix), '$suffix should be null or a string');

  if ((!isset($server['SCRIPT_NAME']) || ($server['SCRIPT_NAME'] === '') && !is_cli()))
  {
    trigger_error('$_SERVER["SCRIPT_NAME"] is empty.  Check your server configuration.', E_USER_NOTICE);
  }

  // We use basename() because the full name causes problems when reverse proxies are being used.
  $result = basename($server['SCRIPT_NAME'], $suffix);

  if ($with_query_string &&
      isset($server['QUERY_STRING']) &&
     ($server['QUERY_STRING'] !== ''))
  {
    $result = $result . "?" . $server['QUERY_STRING'];
  }

  return $result;
}


// Deal with $private_xxxx overrides.  Simplifies
// logic related to private bookings.
global $private_override;
if ($private_override == "private" )
{
  $private_mandatory = true;
  $private_default = true;
}
elseif ($private_override == "public" )
{
  $private_mandatory = true;
  $private_default = false;
}


// Format a timestamp in RFC 1123 format, for HTTP headers
//
// e.g. Wed, 28 Jul 2010 12:43:58 GMT
function rfc1123_date($timestamp)
{
  return gmdate("D, d M Y G:i:s \\G\\M\\T",$timestamp);
}


// A little helper function to send an "Expires" header. Just one
// parameter, the number of seconds in the future to set the expiry.
// If $seconds is <= 0, then caching is disabled.
function expires_header($seconds)
{
  global $debug;

  if (!$debug && ($seconds > 0))
  {
    // We also send a couple of extra headers as the "Expires" header alone
    // does not always result in caching.
    header("Expires: " . rfc1123_date(time() + $seconds));
    header("Pragma: cache");
    header("Cache-Control: max-age=$seconds");
  }
  else
  {
    // Make sure that caching is disabled.   Setting the "Expires" header
    // alone doesn't always turn off caching.
    header("Pragma: no-cache");                          // HTTP 1.0
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
  }
}


// Outputs the HTTP headers, passed in the array $headers, followed by
// a set of headers to set the cache expiry date.  If $expiry_seconds <= 0
// then caching is disabled.
function http_headers(array $headers, $expiry_seconds=0)
{
  foreach ($headers as $header)
  {
    header($header);
  }

  expires_header($expiry_seconds);
}


// Prints a very simple header.  This may be necessary on occasions, such as
// during a database upgrade, when some of the features that the normal
// header uses are not yet available.
function print_simple_header()
{
  print_header($view=null, $view_all=null, $year=null, $month=null, $day=null, $area=null, $room=null, $simple=true);
}


// Print the page header
function print_header($view=null, $view_all=null, $year=null, $month=null, $day=null, $area=null, $room=null, $simple=false)
{
  global $theme, $default_view;

  static $done_header = false;

  if ($done_header)
  {
    return;
  }

  // Need to set the timezone before we can use date()
  if ($simple)
  {
    // We don't really care what timezone is being used
    mrbs_default_timezone_set();
  }
  else
  {
    // This will set the correct timezone for the area
    get_area_settings($area);
  }

  if (!isset($view))
  {
    $view = (isset($default_view)) ? $default_view : 'day';
  }

  // If we dont know the right date then use today's
  if (!isset($year))
  {
    $year = date('Y');
  }
  if (!isset($month))
  {
    $month = date('m');
  }
  if (!isset($day))
  {
    $day = date('d');
  }

  if (empty($area))
  {
    $area = get_default_area();
  }

  if (empty($room))
  {
    $room = get_default_room($area);
  }

  // Load the print_theme_header function appropriate to the theme.    If there
  // isn't one then fall back to the default header.
  if (is_readable("Themes/$theme/header.inc"))
  {
    include_once "Themes/$theme/header.inc";
  }
  if (!function_exists(__NAMESPACE__ . "\\print_theme_header"))
  {
    require_once "Themes/default/header.inc";
  }

  // Now go and do it
  print_theme_header($view, $view_all, $year, $month, $day, $area, $room, $simple);

  $done_header = true;
}


// Print the standard footer, currently very simple.  Pass $and_exit as
// TRUE to exit afterwards
function print_footer($and_exit=false)
{
  global $theme;

  // Load the print_theme_footer function appropriate to the theme.    If there
  // isn't one then fall back to the default footer.
  if (is_readable("Themes/$theme/footer.inc"))
  {
    include_once "Themes/$theme/footer.inc";
  }
  if (!function_exists(__NAMESPACE__ . "\\print_theme_footer"))
  {
    require_once "Themes/default/footer.inc";
  }

  print_theme_footer();

  if ($and_exit)
  {
    exit(0);
  }
}


// Converts a duration of $dur seconds into a duration of
// $dur $units
function toTimeString(&$dur, &$units, $translate=true)
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

        if((abs($dur) >= DAYS_PER_WEEK) && ($dur % DAYS_PER_WEEK == 0))
        {
          $dur /= DAYS_PER_WEEK;

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
      $units *= DAYS_PER_WEEK;
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


// Gets the interval in periods for a booking with $start_time and $end_time
// Takes account of DST
function get_period_interval($start_time, $end_time)
{
  global $periods;

  $periods_per_day = count($periods);

  // Need to use the MRBS version of DateTime to get round a bug in modify()
  // in PHP before 5.3.6.  As we are in the MRBS namespace we will get the
  // MRBS version.
  $startDate = new DateTime();
  $startDate->setTimestamp($start_time);
  $endDate = new DateTime();
  $endDate->setTimestamp($end_time);

  // Set both dates to noon so that we can compare them and get an integral
  // number of days difference.  Noon also happens to be when periods start,
  // so will be useful in a moment.
  $startDate->modify('12:00');
  $endDate->modify('12:00');

  // Calculate the difference in days
  $interval = $startDate->diff($endDate);
  $interval_days = $interval->format('%a');

  if ($interval_days == 0)
  {
    // If the interval starts and ends on the same day, the we just calculate the number
    // of periods by calculating the number of minutes between the start and end times.
    $result = ($end_time - $start_time)/60;
  }
  else
  {
    // Otherwise we calculate the number of periods on the first day
    $startDate->add(new \DateInterval('PT' . $periods_per_day . 'M'));
    $result = get_period_interval($start_time, $startDate->getTimestamp());
    // Add in the number of whole days worth of periods in between
    $result += ($interval_days - 1) * $periods_per_day;
    // And add in the number of periods on the last day
    $result += get_period_interval($endDate->getTimestamp(), $end_time);
  }

  return (int)$result;
}


function toPeriodString($start_period, &$dur, &$units, $translate=true)
{
  global $periods;

  $max_periods = count($periods);
  $dur /= 60;  // duration now in minutes
  $days = $dur / MINUTES_PER_DAY;
  $remainder = $dur % MINUTES_PER_DAY;
  // strip out any gap between the end of the last period on one day
  // and the beginning of the first on the next
  if ($remainder > $max_periods)
  {
    $remainder += $max_periods - MINUTES_PER_DAY;
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
      $units = (MINUTES_PER_DAY * floor($end_period/$max_periods)) + ($end_period%$max_periods) - $start_period;
    }
  }
  if ($dur_units == "days")
  {
    if ($start_period == 0)
    {
      $units = $max_periods + ($units-1)*MINUTES_PER_DAY;
    }
    else
    {
      $units = $units * MINUTES_PER_DAY;
    }
  }

  // Then convert into seconds
  $units = (int) $units;
  $units = 60 * $units;
}


// Splits a BYDAY string into its ordinal and day parts, returned as a simple array.
// For example "-1SU" is returned as array("-1", "SU");
function byday_split($byday)
{
  $result = array();
  $split_pos = strlen($byday) -2;
  $result[] = substr($byday, 0, $split_pos);
  $result[] = substr($byday, $split_pos, 2);
  return $result;
}


// Returns the BYDAY value for a given timestamp, eg 4SU for fourth Sunday in
// the month, or -1MO for the last Monday.
function date_byday($timestamp)
{
  global $RFC_5545_days;

  $dow = $RFC_5545_days[date('w', $timestamp)];
  $dom = date('j', $timestamp);
  $ord = intval(($dom - 1)/DAYS_PER_WEEK) + 1;
  if ($ord == 5)
  {
    $ord = -1;
  }
  return $ord . $dow;
}


// Convert an RFC 5545 day to an ordinal number representing the day of the week,
// eg "MO" returns "1"
function RFC_5545_day_to_ord($day)
{
  global $RFC_5545_days;

  $tmp = array_keys($RFC_5545_days, $day);
  return $tmp[0];
}


// Converts a BYDAY (eg "2SU") value for a given year and month into a
// day of the month.   Returns FALSE if the day does not exist (eg for "5SU"
// when there are only four Sundays in the month)
function byday_to_day($year, $month, $byday)
{
  // First of all normalise the month and year, as we allow $month > 12
  while ($month > 12)
  {
    $month -= 12;
    $year++;
  }
  // Get the ordinal number and the day of the week
  list($ord, $dow) = byday_split($byday);
  // Get the starting day of the month
  $start_dom = ($ord > 0) ? 1 : date('t', mktime(0, 0, 0, $month, 1, $year));
  // Get the starting day of the week
  $start_dow = date('w', mktime(0, 0, 0, $month, $start_dom, $year));
  // Turn the BYDAY day of the week into an integer
  $byday_dow = RFC_5545_day_to_ord($dow);
  // get the difference in days
  $diff = $byday_dow - $start_dow;
  $diff += ($ord > 0) ? DAYS_PER_WEEK : -DAYS_PER_WEEK;
  $diff = $diff %DAYS_PER_WEEK;
  // add in the weeks
  $diff += ($ord > 0) ? ($ord - 1) * DAYS_PER_WEEK : ($ord + 1) * DAYS_PER_WEEK;

  $day = $start_dom + $diff;

  if (checkdate($month, $day, $year))
  {
    return $day;
  }
  else
  {
    return false;
  }
}


// Returns TRUE if the time $hm1 is before $hm2
// $hm1 and $hm2 are associative arrays indexed by 'hours' and 'minutes'.
// The indices are chosen to allow the result of the PHP getdate() function
// to be passed as parameters
function hm_before($hm1, $hm2)
{
  return ($hm1['hours'] < $hm2['hours']) ||
         (($hm1['hours'] == $hm2['hours']) && ($hm1['minutes'] < $hm2['minutes']));
}


// Returns TRUE if the end of the last slot is on the day after the beginning
// of the first slot
function day_past_midnight()
{
  global $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes, $resolution;

  $start_first_slot = (($morningstarts * 60) + $morningstarts_minutes) * 60;
  $end_last_slot = ((($eveningends * 60) + $eveningends_minutes) * 60) + $resolution;
  $end_last_slot = $end_last_slot % SECONDS_PER_DAY;

  return ($end_last_slot <= $start_first_slot);
}


// Gets the UNIX timestamp for the start of the first slot on the given day
function get_start_first_slot($month, $day, $year)
{
  global $morningstarts, $morningstarts_minutes, $enable_periods;

  if ($enable_periods)
  {
    return mrbs_mktime(12, 0, 0, $month, $day, $year);
  }

  $t = mrbs_mktime($morningstarts, $morningstarts_minutes, 0,
                   $month, $day, $year);
  return $t;
}


// Gets the UNIX timestamp for the start of the last slot on the given day
function get_start_last_slot($month, $day, $year)
{
  global $morningstarts, $morningstarts_minutes, $eveningends, $eveningends_minutes, $enable_periods, $periods;

  if ($enable_periods)
  {
    return mrbs_mktime(12, count($periods) -1, 0, $month, $day, $year);
  }

  // Work out if $evening_ends is really on the next day
  if (hm_before(array('hours' => $eveningends, 'minutes' => $eveningends_minutes),
                array('hours' => $morningstarts, 'minutes' => $morningstarts_minutes)))
  {
    $day++;
  }
  $t = mrbs_mktime($eveningends, $eveningends_minutes, 0,
                   $month, $day, $year);
  return $t;
}


function get_end_last_slot($month, $day, $year)
{
  global $resolution;

  return get_start_last_slot($month, $day, $year) + $resolution;
}


// Determines with a given timestamp is within a booking day, ie between the start of
// the first slot and end of the last slot.   Returns a boolean.
function is_in_booking_day($t)
{
  global $morningstarts, $morningstarts_minutes,
         $eveningends, $eveningends_minutes,
         $resolution, $enable_periods;

  if ($enable_periods)
  {
    return true;
  }

  $start_day_secs = (($morningstarts * 60) + $morningstarts_minutes) * 60;
  $end_day_secs = (((($eveningends * 60) + $eveningends_minutes) * 60) + $resolution) % SECONDS_PER_DAY;

  $date = getdate($t);
  $t_secs = (($date['hours'] * 60) + $date['minutes']) * 60;

  if ($start_day_secs == $end_day_secs)
  {
    return true;
  }
  elseif (day_past_midnight())
  {
    return (($t_secs >= $start_day_secs) || ($t_secs <= $end_day_secs));
  }
  else
  {
    return (($t_secs >= $start_day_secs) && ($t_secs <= $end_day_secs));
  }
}


// Force a timestamp $t to be on a booking day, either by moving it back to the end
// of the previous booking day, or forward to the start of the next.
function fit_to_booking_day($t, $back=true)
{
  if (is_in_booking_day($t))
  {
    return $t;
  }

  $date = getdate($t);
  // Remember that we need to cater for days that stretch beyond midnight.
  if ($back)
  {
    $new_t = get_end_last_slot($date['mon'], $date['mday'], $date['year']);
    if ($new_t > $t)
    {
      $new_t = get_end_last_slot($date['mon'], $date['mday'] - 1, $date['year']);
    }
  }
  else
  {
    $new_t = get_start_first_slot($date['mon'], $date['mday'], $date['year']);
    if ($new_t < $t)
    {
      $new_t = get_start_first_slot($date['mon'], $date['mday'] + 1, $date['year']);
    }
  }

  return $new_t;
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
function get_duration($start_time, $end_time, $enable_periods, $area_id, $translate=true)
{
  $result = array();

  $period_names = get_period_names();

  if ($enable_periods)
  {
    $periods_per_day = count($period_names[$area_id]);
    $n_periods = get_period_interval($start_time, $end_time);  // this handles DST
    if (($n_periods % $periods_per_day) == 0)
    {
      $result['duration'] =  intval($n_periods/$periods_per_day);
      $result['dur_units'] = ($translate) ? get_vocab('days') : 'days';
    }
    else
    {
      $result['duration'] = $n_periods;
      $result['dur_units'] = ($translate) ? get_vocab('periods') : 'periods';
    }
  }
  else
  {
    $result['duration'] = $end_time - $start_time;
    // Need to make DST correct in opposite direction to entry creation
    // so that user see what he expects to see
    $result['duration'] -= cross_dst($start_time, $end_time);
    toTimeString($result['duration'], $result['dur_units'], $translate);
  }
  return $result;
}


// Escape a PHP string for use in JavaScript
//
// Based on a function contributed by kongaspar at gmail dot com at
// http://www.php.net/manual/function.addcslashes.php
function escape_js($str)
{
  return addcslashes($str, "\\\'\"&\n\r<>/");
}


// Return a default area; used if no area is already known. This returns the
// area that contains the default room (if it is set, valid and enabled) otherwise the
// first area in alphabetical order in the database (no guarantee there is an area 1).
// The area must be enabled for it to be considered.
// This could be changed to implement something like per-user defaults.
function get_default_area()
{
  global $tbl_area, $tbl_room, $default_room;

  // It's possible that this function is being called during
  // an upgrade process before the disabled columns existed.
  $disabled_field_exists = db()->field_exists($tbl_room, 'disabled');

  // If the $default_room is set and exists and is enabled and is visible to
  // the current user, then return the corresponding area
  if (isset($default_room) && is_visible($default_room))
  {
    $sql = "SELECT area_id
              FROM $tbl_room R, $tbl_area A
             WHERE R.id=:id
               AND R.area_id = A.id";

    if ($disabled_field_exists)
    {
      $sql .= " AND R.disabled = 0 AND A.disabled = 0";
    }

    $sql .= " LIMIT 1";
    $area = db()->query1($sql, array(':id' => $default_room));

    if ($area >= 0)
    {
      return $area;
    }
  }

  // Otherwise return the first enabled area in the database that has an
  // enabled and visible room
  $sql = "SELECT R.id, R.area_id
            FROM $tbl_room R, $tbl_area A
           WHERE R.area_id = A.id";

  if ($disabled_field_exists)
  {
    $sql .= " AND R.disabled = 0 AND A.disabled = 0";
  }

  $res = db()->query($sql);

  while (false !== ($row = $res->next_row_keyed()))
  {
    if (is_visible($row['id']))
    {
      return $row['area_id'];
    }
  }

  return 0;
}

// Return a default room given a valid area; used if no room is already known.
// If the area contains $default_room and $default_room is visible to the current
// user, then it returns $default_room. Otherwise it returns the first visible room
// in sort_key order in the database.
// This could be changed to implement something like per-user defaults.
function get_default_room($area)
{
  global $tbl_room, $default_room;

  // It's possible that this function is being called during
  // an upgrade process before the disabled columns existed.
  $disabled_field_exists = db()->field_exists($tbl_room, 'disabled');

  // Check to see whether this area contains $default_room
  if (isset($default_room))
  {
    $sql = "SELECT id FROM $tbl_room WHERE id=:id AND area_id=:area_id";

    if ($disabled_field_exists)
    {
      $sql .= " AND disabled=0";
    }

    $sql .= " LIMIT 1";
    $sql_params = array(':id' => $default_room, ':area_id' => $area);
    $room = db()->query1($sql, $sql_params);

    if (($room >= 0) && is_visible($room))
    {
      return $room;
    }
  }

  // Otherwise just return the first visible room in the area
  $rooms = get_rooms($area, !$disabled_field_exists);

  return (count($rooms) > 0) ? $rooms[0]['id'] : 0;
}

// Return an area id for a given room
function get_area($room)
{
  global $tbl_room;
  $area = db()->query1("SELECT area_id FROM $tbl_room WHERE id=? LIMIT 1", array($room));
  return ($area < 0 ? 0 : $area);
}


// Clean up a row from the area table, making sure there are no nulls, casting
// boolean fields into bools and doing some sanity checking
function clean_area_row($row)
{
  global $force_resolution, $area_defaults, $boolean_fields, $private_override_options;

  // This code can get called during the upgrade process and so must
  // not make any assumptions about the existence of extra columns in
  // the area table.
  foreach ($row as $key => $value)
  {
    if (array_key_exists($key, $area_defaults))
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
  global $min_create_ahead_enabled, $max_create_ahead_enabled, $min_create_ahead_secs, $max_create_ahead_secs;
  global $min_delete_ahead_enabled, $max_delete_ahead_enabled, $min_delete_ahead_secs, $max_delete_ahead_secs;
  global $max_duration_enabled, $max_duration_secs, $max_duration_periods;
  global $approval_enabled, $reminders_enabled, $enable_periods, $periods;
  global $confirmation_enabled, $confirmed_default, $timezone;
  global $max_per_interval_area_enabled, $max_per_interval_area;
  global $interval_types;

  // This code can get called during the upgrade process and so must
  // not make any assumptions about the existence of extra columns in
  // the area table.
  if (empty($area))
  {
    $area = get_default_area();
  }

  // Get all the "per area" config settings
  $columns = array('timezone', 'resolution', 'default_duration', 'default_duration_all_day',
                   'morningstarts', 'morningstarts_minutes',
                   'eveningends', 'eveningends_minutes',
                   'private_enabled', 'private_default', 'private_mandatory', 'private_override',
                   'min_create_ahead_enabled', 'max_create_ahead_enabled',
                   'min_create_ahead_secs', 'max_create_ahead_secs',
                   'min_delete_ahead_enabled', 'max_delete_ahead_enabled',
                   'min_delete_ahead_secs', 'max_delete_ahead_secs',
                   'max_duration_enabled', 'max_duration_secs', 'max_duration_periods',
                   'max_per_day_enabled', 'max_per_day',
                   'max_per_week_enabled', 'max_per_week',
                   'max_per_month_enabled', 'max_per_month',
                   'max_per_year_enabled', 'max_per_year',
                   'max_per_future_enabled', 'max_per_future',
                   'approval_enabled', 'reminders_enabled', 'enable_periods', 'periods',
                   'confirmation_enabled', 'confirmed_default');

  $sql = "SELECT *
            FROM $tbl_area
           WHERE id=?
           LIMIT 1";

  $res = db()->query($sql, array($area));
  if ($res->count() == 0)
  {
    // We still need to set the timezone even if the query didn't
    // return any results
    mrbs_default_timezone_set($timezone);
    return;
  }
  else
  {
    $row = $res->next_row_keyed();

    // Periods are stored as a JSON encoded string in the database
    if (isset($row['periods']))
    {
      $row['periods'] = json_decode($row['periods']);
    }

    $row = clean_area_row($row);
    foreach ($columns as $column)
    {
      if (array_key_exists($column, $row))
      {
        $$column = $row[$column];
      }
    }
  }
  // Set the timezone
  mrbs_default_timezone_set($timezone);

  // Set the $max_per_interval_area_enabled and $max_per_interval_area arrays,
  // which are handled slightly differently
  foreach ($interval_types as $interval_type)
  {
    $var = "max_per_${interval_type}_enabled";
    if (isset($$var))
    {
      $max_per_interval_area_enabled[$interval_type] = $$var;
    }

    $var = "max_per_${interval_type}";
    if (isset($$var))
    {
      $max_per_interval_area[$interval_type] = $$var;
    }
  }

  // Sanitise the settings
  if ($enable_periods)
  {
    $resolution = 60;
    $morningstarts = 12;
    $morningstarts_minutes = 0;
    $eveningends = 12;
    $eveningends_minutes = count($periods) - 1;
  }
  elseif (!isset($morningstarts_minutes))
  {
    // ensure that $morningstarts_minutes defaults to zero if not set
    $morningstarts_minutes = 0;
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
function some_area($field, $enabled=false)
{
  global $tbl_area;

  $predicate = some_area_predicate($field);
  $sql = "SELECT COUNT(*) FROM $tbl_area WHERE $predicate";
  $sql .= ($enabled) ? " AND disabled=0" : "";
  $sql .= " LIMIT 1";
  return (db()->query1($sql) > 0);
}


// Returns a date in ISO 8601 format ('yyyy-mm-dd'), having adjusted the date if necessary
// to make sure it's a valid date
function format_iso_date($year, $month, $day)
{
  if (!checkdate($month, $day, $year))
  {
    $date = getdate(mktime(12, 0, 0, $month, $day, $year));
    $year = $date['year'];
    $month = $date['mon'];
    $day = $date['mday'];
  }

  return sprintf("%04d-%02d-%02d", $year, $month, $day);
}


// Splits a date in ISO 8601 format ('yyyy-mm-dd') and returns an
// array of year, month, day with leading zeros removed.
function split_iso_date($date)
{
  // Split the date into components, sanitize the result and trim any leading zeros
  // If the date is malformed, pad it out with nulls so that three elements are returned.
  $result = array_pad(explode('-', $date, 3), 3, null);

  foreach ($result as &$value)
  {
    if (isset($value))
    {
      $value = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
      $value = ($value === '') ? null : (int) ltrim($value, '0');
    }
  }

  unset($value);  // Break the reference (not necessary here, but good practice)

  return $result;
}


// Get the local day name based on language. Note 2000-01-02 is a Sunday.
function day_name($daynumber, $format=null)
{
  global $strftime_format;

  if (!isset($format))
  {
    $format = $strftime_format['dayname'];
  }

  return utf8_strftime($format, mktime(0,0,0,1,2+$daynumber,2000));
}

// Returns a list of repeat days as a string (eg "Thursday Friday")
//
//    $rep_opt     an array of repeat days or
//                 a string of repeat days that can be used as an array
function get_rep_day_list($rep_opt)
{
  global $weekstarts;

  $rep_days = array();

  for ($i=0; $i<DAYS_PER_WEEK; $i++)
  {
    $daynum = ($i + $weekstarts) % DAYS_PER_WEEK;
    if ($rep_opt[$daynum])
    {
      $rep_days[] = day_name($daynum);
    }
  }

  return implode(' ', $rep_days);
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


// Returns a string representing the hour and minute for the nominal
// seconds since the start of the day, $s
function hour_min($s)
{
  $following_day = ($s >= SECONDS_PER_DAY);
  $s = $s % SECONDS_PER_DAY;  // in case $s is on the next day
  // Choose a day that doesn't have any DST transitions in any timezone
  $t = mktime(0, 0, $s, 1, 1, 2000);
  $result = utf8_strftime(hour_min_format(), $t);
  if ($following_day)
  {
    $result = "* " . $result;
  }
  return $result;
}


function period_date_string($t, $area_id, $mod_time=0)
{
  global $strftime_format;

  $period_names = get_period_names();

  $time = getdate($t);
  $p_num = $time["minutes"] + $mod_time;
  if( $p_num < 0 )
  {
    $p_num = 0;
  }
  if( $p_num >= count($period_names[$area_id]) - 1 )
  {
    $p_num = count($period_names[$area_id]) - 1;
  }
  // The separator is a ',' as a '-' leads to an ambiguous
  // display in report.php when showing end times.
  return array($p_num, $period_names[$area_id][$p_num] . utf8_strftime(", " . $strftime_format['date'], $t));
}


function period_time_string($t, $area_id, $mod_time=0)
{
  $period_names = get_period_names();

  $time = getdate($t);
  $p_num = $time["minutes"] + $mod_time;
  if ( $p_num < 0 )
  {
    $p_num = 0;
  }
  if ( $p_num >= count($period_names[$area_id]) - 1 )
  {
    $p_num = count($period_names[$area_id]) - 1;
  }
  return $period_names[$area_id][$p_num];
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


// Take a string of email addresses separated by commas
// and return a comma separated list with duplicates removed.
function clean_address_list($string)
{
  $array = explode(',', $string);
  array_walk($array, 'trim');
  return implode(', ', array_unique($array));
}


function validate_email($email)
{
  return PHPMailer::validateAddress($email);
}


// Validates a comma separated list of email addresses.  (The individual email
// addresses are 'trimmed' before validation, so spaces are allowed after the commas).
// Returns FALSE if any one of them is invalid, otherwise TRUE
function validate_email_list($list)
{
  if (isset($list) && ($list !== ''))
  {
    $emails = explode(',', $list);
    foreach ($emails as $email)
    {
      if (!validate_email(trim($email)))
      {
        return false;
      }
    }
  }

  return true;
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


// Returns the nominal (ie ignoring DST transitions) seconds since the start of
// the calendar day on the start of the booking day
function nominal_seconds($t)
{
  global $morningstarts, $morningstarts_minutes;

  $date = getdate($t);
  // check to see if the time is really on the next day
  if (hm_before($date,
                array('hours' => $morningstarts, 'minutes' => $morningstarts_minutes)))
  {
    $date['hours'] += 24;
  }
  return (($date['hours'] * 60) + $date['minutes']) * 60;
}


// Returns the index of the period represented by $s nominal seconds
function period_index($s)
{
  // Periods are counted as minutes from noon, ie 1200 is $period[0],
  // 1201 $period[1], etc.
  return intval($s/60) - (12*60);
}


// Returns the name of the period represented by nominal seconds $s
function period_name($s)
{
  global $periods;

  return $periods[period_index($s)];
}


// returns the numeric day of the week (0-6) in terms of the MRBS week as defined by
// $weekstarts.   For example if $weekstarts is set to 2 (Tuesday) and a $time for
// a Wednesday is given, then 1 is returned.
function day_of_MRBS_week($time)
{
  global $weekstarts;

  return (date('w', $time) - $weekstarts + DAYS_PER_WEEK) % DAYS_PER_WEEK;
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


// Compares two nominal dates which are indexed by'hours', 'minutes', 'seconds',
// 'mon', 'mday' and 'year', ie as in the output of getdate().  Returns -1 if the
// first date is before the second, 0 if they are equal and +1 if the first date
// is after the second.   NULL if a comparison can't be done.
//
// (Note that internally the function uses gmmktime() so the parameters do not
// have to represent valid values.   For example you could pass '32' for day and
// that would be interpreted as 4 days after '28'.)
function nominal_date_compare($d1, $d2)
{
  // We compare the dates using gmmktime() because we are trying to compare nominal
  // dates and so do not want DST transitions
  $t1 = gmmktime($d1['hours'], $d1['minutes'], $d1['seconds'],
                 $d1['mon'], $d1['mday'], $d1['year']);
  $t2 = gmmktime($d2['hours'], $d2['minutes'], $d2['seconds'],
                 $d2['mon'], $d2['mday'], $d2['year']);
  if ($t1 < $t2)
  {
    return -1;
  }
  elseif ($t1 == $t2)
  {
    return 0;
  }
  else
  {
    return 1;
  }
}


// Determines whether there's a possibility that the interval between the two Unix
// timestamps could contain nominal times that don't exist, for example from 0100 to
// 0159 in Europe/London when entering DST.
function is_possibly_invalid($start, $end)
{
  // We err on the side of caution by widening the interval by a day at each end.   This
  // allows for the possibility that the start or end times have been calculated by using
  // mktime() on an invalid time!
  return (cross_dst($start - 86400, $end + 86400) < 0);
}


// Checks whether the nominal time given is an invalid date and time with respect to
// DST transitions.   When entering DST there is a set of times that don't exist, for
// example from 0100 to 0159 in Europe/London.
// Returns NULL if MRBS is unable to determine an answer, otherwise TRUE or FALSE (so
// a simple equality test will default to a valid time if MRBS can't determine an answer)
function is_invalid_datetime($hour, $minute, $second, $month, $day, $year, $tz=null)
{
  global $timezone;

  // Do a quick check to see if there's a possibility of an invalid time by checking
  // whether there's a transition into DST from the day before to the day after
  if (!function_exists('date_default_timezone_set'))
  {
    return null;
  }

  if (empty($tz))
  {
    $tz = $timezone;  // default to the current timezone
  }

  $old_tz = date_default_timezone_get();  // save the current timezone
  date_default_timezone_set($tz);

  // If the day before is in DST then the datetime must be valid, because
  // you only get the gap when entering DST.
  $dayBefore = mktime($hour, $minute, $second, $month, $day-1, $year);
  if (date('I', $dayBefore))
  {
    $result = false;
  }
  else
  {
    // The day before is not in DST.   If the day after is also not in DST,
    // then there can have been no transition, so again the datetime must be valid.
    $dayAfter = mktime($hour, $minute, $second, $month, $day+1, $year);
    if (!date('I', $dayAfter))
    {
      $result = false;
    }
    else
    {
      // We are in a transition into DST, so we need to check more carefully.
      // However we can only do this efficiently in PHP 5.3.0 or greater
      if (version_compare(PHP_VERSION, '5.3.0') < 0)
      {
        $result = null;
      }
      else
      {
        $thisDateTimeZone = new DateTimeZone($tz);
        // Get the transition data (we assume there is one and only one transition),
        // in particular the time at which the transition happens and the new offset
        $transitions = $thisDateTimeZone->getTransitions($dayBefore, $dayAfter);
        // According to my reading of the PHP manual, getTransitions() should return
        // all transitions between the start and end date.   However what it seems to do
        // is return an array consisting of the time data for the start date followed by
        // the transition data.   So as a precaution we take the last element of the array
        // (we were only expecting one element, but seem to get two).
        $transition = array_pop($transitions);
        // If we failed for some reason to get any transition data, return NULL
        if (!isset($transition))
        {
          $result = null;
        }
        else
        {
          // Get the old offset and work out how many seconds the clocks change by
          $beforeDateTime = new DateTime(date('c', $dayBefore), $thisDateTimeZone);
          $change = $transition['offset'] - $beforeDateTime->getOffset();

          // See if the nominal date falls outside the gap
          $lastValidSecond = getdate($transition['ts'] - 1);
          $lastInvalidSecond = $lastValidSecond;
          $lastInvalidSecond['seconds'] += $change;
          $thisDate = array('hours' => $hour, 'minutes' => $minute, 'seconds' => $second,
                            'mon' => $month, 'mday' => $day, 'year' => $year);

          $result = ((nominal_date_compare($thisDate, $lastValidSecond) > 0) &&
                     (nominal_date_compare($thisDate, $lastInvalidSecond) <= 0));
        }
      }
    }
  }

  date_default_timezone_set($old_tz);  // restore the old timezone

  return $result;
}


// Returns TRUE if $t is in that part of daylight saving time that will have
// the same nominal time as another timestamp shortly after the DST transition.
// For example, will return TRUE for the $t equivalent to the first occurrence
// of 0230 on Sunday 27th October 2013 in timezone Europe/London
function is_dst_first_duplicate($t)
{
  if (date('I', $t))
  {
    $change = cross_dst($t, $t+SECONDS_PER_DAY);
    return !date('I', $t + $change);
  }
  return false;
}


// A PHP version independent version of mktime().   (The $is_dst parameter
// became deporecated at PHP 5.1.0)
function mrbs_mktime($hour, $minute, $second, $month, $day, $year)
{
  if (version_compare(PHP_VERSION, '5.1.0') >= 0)
  {
    return mktime($hour, $minute, $second, $month, $day, $year);
  }
  else
  {
    return mktime($hour, $minute, $second, $month, $day, $year,
                  is_dst($month, $day, $year, $hour));
  }
}

// returns the modification (in seconds) necessary on account of any DST
// transitions when going from $start to $end
function cross_dst($start, $end)
{
  global $timezone;

  // Ideally we calculate the modification using the DateTimeZone information
  // in PHP, because not all DST transitions are 1 hour.  For example, Lord Howe
  // Island in Australia has a 30 minute transition
  if (class_exists('DateTimeZone'))
  {
    $thisDateTimeZone = new DateTimeZone($timezone);
    $startDateTime = new DateTime(date('c', $start), $thisDateTimeZone);
    $endDateTime = new DateTime(date('c', $end), $thisDateTimeZone);
    $modification = $startDateTime->getOffset() - $endDateTime->getOffset();
  }
  // Otherwise we have to assume that the transition is 1 hour.
  else
  {
    // entering DST
    if (!date( "I", $start) &&  date( "I", $end))
    {
      $modification = -SECONDS_PER_HOUR;
    }
    // leaving DST
    else if (date( "I", $start) && !date( "I", $end))
    {
      $modification = SECONDS_PER_HOUR;
    }
    else
    {
      $modification = 0;
    }
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
    if ($skip_back == DAYS_PER_WEEK)
    {
      break;
    }
    $skip_back++;
    $dow = ($dow + 6) % DAYS_PER_WEEK;  // equivalent to skipping back a day
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
  if ($n_working_days == DAYS_PER_WEEK)
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
  $days_diff = (int) round(($last - $first)/SECONDS_PER_DAY);  // the difference in days
  $whole_weeks = (int) floor($days_diff/DAYS_PER_WEEK);  // the number of whole weeks between the two
  $non_working_days = $whole_weeks * (DAYS_PER_WEEK - $n_working_days);
  // Now (b), ie we just have to calculate how many non-working days there are between the two
  // days of the week that are left
  $last_dow = date('w', $last);
  $first_dow = date('w', $first);

  while ($first_dow != $last_dow)
  {
    $first_dow = ($first_dow + 1) % DAYS_PER_WEEK;
    if (!in_array($first_dow, $working_week))
    {
      $non_working_days++;
    }
  }

  // So now subtract the number of weekend seconds
  $diff = $diff - ($non_working_days * SECONDS_PER_DAY);

  // Finally reverse the difference if $now was in fact before $then
  if ($now < $then)
  {
    $diff = -$diff;
  }

  return (int) $diff;
}


// checks whether a given day of the week is supposed to be hidden in the display
function is_hidden_day($dow)
{
  global $hidden_days;

  return (isset($hidden_days) && in_array($dow, $hidden_days));
}


// checks whether a given day of the week is a weekend day
function is_weekend($dow)
{
  global $weekdays;

  return !in_array($dow, $weekdays);
}


// returns true if event should be considered private based on
// config settings and event's privacy status (passed to function)
function is_private_event($privacy_status)
{
  global $private_override;
  if ($private_override == "private" )
  {
    $privacy_status = true;
  }
  elseif ($private_override == "public" )
  {
    $privacy_status = false;
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
  global $server;

  $uid = uniqid('MRBS-');
  $uid .= "-" . substr(md5($str . rand(0,10000)), 0, 8);
  $uid .= "@";
  // Add on the domain name if possible, if not the server name,
  // otherwise 'MRBS'
  if (empty($server['SERVER_NAME']))
  {
    $uid .= 'MRBS';
  }
  elseif (strpos($server['SERVER_NAME'], 'www.') === 0)
  {
    $uid .= utf8_substr($server['SERVER_NAME'], 4);
  }
  else
  {
    $uid .= $server['SERVER_NAME'];
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
    return false;
  }

  if (defined('STDIN'))
  {
    return true;
  }
  elseif (is_cgi() && getenv('TERM'))
  {
    return true;
  }
  else
  {
    return false;
  }
}

// Set and restore ignore_user_abort.   The function is designed to be used to
// ensure a critical piece of code can't be aborted, and used in pairs of set and
// restore calls.  The function keeps track of outstanding set requests so that
// the original state isn't restored if there are other requests still outstanding.
//
// $set   TRUE    set ignore_user_abort
//        FALSE   restore to the original state, if no other requests outstanding
function mrbs_ignore_user_abort($set)
{
  static $original_state;
  static $outstanding_requests = 0;

  if (!isset($original_state))
  {
    $original_state = ignore_user_abort();
  }

  // Set ignore_user_abort
  if ($set)
  {
    if ($outstanding_requests == 0)
    {
      ignore_user_abort(1);
    }
    $outstanding_requests++;
  }
  else
  // Restore the original state, provided no other requests are outstanding
  {
    $outstanding_requests--;
    if ($outstanding_requests == 0)
    {
      ignore_user_abort($original_state);
    }
  }
}


// Gets the web server software type and version, if it can.
function get_server_software()
{
  global $server;

  if (function_exists('apache_get_version'))
  {
    return apache_get_version();
  }

  if (isset($server['SERVER_SOFTWARE']))
  {
    return $server['SERVER_SOFTWARE'];
  }

  return '';
}
