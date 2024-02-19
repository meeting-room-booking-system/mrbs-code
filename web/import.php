<?php
namespace MRBS;

use DateTimeZone;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputHidden;
use MRBS\Form\FieldInputCheckbox;
use MRBS\Form\FieldInputCheckboxGroup;
use MRBS\Form\FieldInputFile;
use MRBS\Form\FieldInputRadioGroup;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;
use MRBS\Form\FieldInputUrl;
use MRBS\Form\FieldSelect;
use MRBS\Form\Form;
use MRBS\ICalendar\RFC5545;
use ReflectionClass;
use ZipArchive;


require "defaultincludes.inc";
require_once "functions_ical.inc";
require_once "mrbs_sql.inc";

$wrapper_mime_types = array('file'            => 'text/calendar',
                            'zip'             => 'application/zip',
                            'compress.zlib'   => 'application/x-gzip',
                            'compress.bzip2'  => 'application/x-bzip2');

$wrapper_descriptions = array('file'            => get_vocab('import_text_file'),
                              'zip'             => get_vocab('import_zip'),
                              'compress.zlib'   => get_vocab('import_gzip'),
                              'compress.bzip2'  => get_vocab('import_bzip2'));

// Get the available compression wrappers that we can use.
// Returns an array
function get_compression_wrappers() : array
{
  $result = array();
  if (function_exists('stream_get_wrappers'))
  {
    $wrappers = stream_get_wrappers();
    foreach ($wrappers as $wrapper)
    {
      if ((($wrapper == 'zip') && class_exists('ZipArchive')) ||
          (utf8_strpos($wrapper, 'compress.') === 0))
      {
        $result[] = $wrapper;
      }
    }
  }
  return $result;
}


// Gets the id of the area/room with the LOCATION property value of $location,
// creating an area and room if allowed.
// Returns FALSE if it can't find an id or create an id, with an error message in $error
function get_room_id($location, &$error)
{
  global $area_room_order, $area_room_delimiter, $area_room_create;

  // If there's no delimiter we assume we've just been given a room name (that will
  // have to be unique).   Otherwise we split the location into its area and room parts
  if (utf8_strpos($location, $area_room_delimiter) === false)
  {
    $location_area = '';
    $location_room = $location;
  }
  elseif ($area_room_order == 'area_room')
  {
    list($location_area, $location_room) = explode($area_room_delimiter, $location, 2);
  }
  else
  {
    list($location_room, $location_area) = explode($area_room_delimiter, $location, 2);
  }
  $location_area = trim($location_area);
  $location_room = trim($location_room);

  // Now search the database for the room

  // Case 1:  we've just been given a room name, in which case we hope it happens
  // to be unique, because if we find more than one we won't know which one is intended
  // and if we don't find one at all we won't be able to create it because we won't
  // know which area to put it in.
  if ($location_area == '')
  {
    $sql = "SELECT COUNT(*)
              FROM " . _tbl('room') . "
             WHERE room_name=?";
    $count = db()->query1($sql, array($location_room));

    if ($count == 0)
    {
      $error = "'$location_room': " . get_vocab("room_does_not_exist_no_area");
      return false;
    }
    elseif ($count > 1)
    {
      $error = "'$location_room': " . get_vocab("room_not_unique_no_area");
      return false;
    }
    else // we've got a unique room name
    {
      $sql = "SELECT id
                FROM " . _tbl('room') . "
               WHERE room_name=?
               LIMIT 1";
      $id = db()->query1($sql, array($location_room));
      return $id;
    }
  }

  // Case 2:  we've got an area and room name
  else
  {
    // First of all get the area id
    $sql = "SELECT id
              FROM " . _tbl('area') . "
             WHERE area_name=?
             LIMIT 1";
    $area_id = db()->query1($sql, array($location_area));
    if ($area_id < 0)
    {
      // The area does not exist - create it if we are allowed to
      if (!$area_room_create)
      {
        $error = get_vocab("area_does_not_exist") . " '$location_area'";
        return false;
      }
      else
      {
        echo get_vocab("creating_new_area") . " '$location_area'<br>\n";
        $error_add_area = '';
        $area_id = mrbsAddArea($location_area, $error_add_area);
        if ($area_id === false)
        {
          $error = get_vocab("could_not_create_area") . " '$location_area'";
          return false;
        }
      }
    }
  }
  // Now we've got the area_id get the room_id
  $sql = "SELECT id
            FROM " . _tbl('room') . "
           WHERE room_name=?
             AND area_id=?
           LIMIT 1";
  $room_id = db()->query1($sql, array($location_room, $area_id));
  if ($room_id < 0)
  {
    // The room does not exist - create it if we are allowed to
    if (!$area_room_create)
    {
      $error = get_vocab("room_does_not_exist") . " '$location_room'";
      return false;
    }
    else
    {
      echo get_vocab("creating_new_room") . " '$location_room'<br>\n";
      $error_add_room = '';
      $room_id = mrbsAddRoom($location_room, $area_id, $error_add_room);
      if ($room_id === false)
      {
        $error = get_vocab("could_not_create_room") . " '$location_room'";
        return false;
      }
    }
  }
  return $room_id;
}


// Turn an EXDATE property into an array of timestamps
function get_skip_list(string $values, array $params) : array
{
  $result = array();

  $tzid = $params['TZID'] ?? 'UTC';
  $date_time_zone = new DateTimeZone($tzid);
  $exdates = explode(',', $values);

  foreach ($exdates as $exdate)
  {
    $date = new DateTime($exdate, $date_time_zone);
    $result[] = $date->getTimestamp();
  }

  return $result;
}

// Get the next line, after unfolding, from the stream.
// Returns FALSE when EOF is reached
function get_unfolded_line($handle)
{
  static $buffer_line;

  // If there's something in the buffer line left over
  // from the last call, then start with that.
  if (isset($buffer_line))
  {
    $unfolded_line = $buffer_line;
    $buffer_line = null;
  }

  // Theoretically the line should be folded if it's longer than 75 octets
  // but just in case the file has been created without using folding we
  // will read a large number (4096) of bytes to make sure that we get as
  // far as the CRLF.
  while (false !== ($line = stream_get_line($handle, 4096, "\r\n")))
  {
    if (!isset($unfolded_line))
    {
      $unfolded_line = $line;
    }
    else
    {
      $first_char = utf8_substr($line, 0, 1);
      // If the first character of the line is a space or tab then it's
      // part of a fold
      if (($first_char == " ") || ($first_char == "\t"))
      {
        $unfolded_line .= utf8_substr($line, 1);
      }
      // Otherwise we've reached the start of the next unfolded line, so
      // save it for next time and finish
      else
      {
        $buffer_line = $line;
        break;
      }
    }
  }

  return (isset($unfolded_line)) ? $unfolded_line : false;
}


// Get the next event from the stream.
// Returns FALSE if EOF has been reached, or else an array
// of lines for the event.  The BEGIN:VEVENT and END:VEVENT
// lines are not included in the array.
function get_event($handle)
{
  // Advance to the beginning of the event
  while ((false !== ($ical_line = get_unfolded_line($handle))) && ($ical_line != 'BEGIN:VEVENT'))
  {
  }

  // No more events
  if ($ical_line === false)
  {
    return false;
  }
  // Get the event
  $vevent = array();
  while ((false !== ($ical_line = get_unfolded_line($handle))) && ($ical_line != 'END:VEVENT'))
  {
    $vevent[] = $ical_line;
  }

  return $vevent;
}


// Add a VEVENT to MRBS.   Returns TRUE on success, FALSE if the event wasn't added
function process_event(array $vevent) : bool
{
  global $import_default_room, $import_default_type, $import_past, $skip;
  global $morningstarts, $morningstarts_minutes, $resolution;
  global $booking_types;
  global $ignore_location, $add_location;
  global $default_type;

  // We are going to cache the settings ($resolution etc.) for the rooms
  // in order to avoid lots of database lookups
  static $room_settings = array();

  $registration_keys = array(
    'allow_registration',
    'registrant_limit',
    'registrant_limit_enabled',
    'registration_opens',
    'registration_opens_enabled',
    'registration_closes',
    'registration_closes_enabled'
  );

  // Set up the booking with some defaults
  $registrants = array();
  $booking = array();
  $booking['awaiting_approval'] = false;
  $booking['private'] = false;
  $booking['tentative'] = false;
  $repeat_rule = new RepeatRule();
  $repeat_rule->setType(RepeatRule::NONE);
  $booking['repeat_rule'] = $repeat_rule;
  $booking['type'] = $import_default_type;
  $booking['room_id'] = $import_default_room;

  // Parse all the lines first because we'll need to get the start date
  // for calculating some of the other settings
  $properties = array();
  $problems = array();

  $line = current($vevent);
  while ($line !== false)
  {
    $property = RFC5545::parseProperty($line);
    // Ignore any sub-components (eg a VALARM inside a VEVENT) as MRBS does not
    // yet handle things like reminders.  Skip through to the end of the sub-
    // component.   Just in case you can have sub-components at a greater depth
    // than 1 (not sure if you can), make sure we've got to the matching END.
    if ($property['name'] != 'BEGIN')
    {
      $properties[] = $property;
      // Get the start time because we'll need it later
      if ($property['name'] == 'DTSTART')
      {
        $booking['start_time'] = RFC5545::getTimestamp($property['value'], $property['params']);
      }
    }
    else
    {
      $component = $property['value'];
      while (!(($property['name'] == 'END') && ($property['value'] == $component)) &&
             ($line = next($vevent)))
      {
        $property = RFC5545::parseProperty($line);
      }
    }
    $line = next($vevent);
  }

  if (!isset($booking['start_time']))
  {
    trigger_error("No DTSTART", E_USER_WARNING);
  }

  // Now go through the properties
  foreach($properties as $property)
  {
    switch ($property['name'])
    {
      case 'ORGANIZER':
        $booking['create_by'] = get_create_by($property['value']);
        $booking['modified_by'] = '';
        break;

      case 'SUMMARY':
        $booking['name'] = $property['value'];
        break;

      case 'DESCRIPTION':
        $booking['description'] = $property['value'];
        break;

      case 'LOCATION':
        $location = $property['value']; // We may need the original LOCATION later
        if ($ignore_location)
        {
          $booking['room_id'] = $import_default_room;
        }
        else
        {
          $error = '';
          $booking['room_id'] = get_room_id($location, $error);
          if ($booking['room_id'] === false)
          {
            $problems[] = $error;
          }
        }
        break;

      case 'DTSTART':
        // We've already handled this
        break;

      case 'DTEND':
        $booking['end_time'] = RFC5545::getTimestamp($property['value'], $property['params']);
        break;

      case 'DURATION':
        trigger_error("DURATION not yet supported by MRBS", E_USER_WARNING);
        break;

      case 'RRULE':
        $rrule_errors = array();
        $repeat_rule = get_repeat_rule($property['value'], $booking['start_time'], $rrule_errors);
        if ($repeat_rule === false)
        {
          $problems = array_merge($problems, $rrule_errors);
        }
        else
        {
          $booking['repeat_rule'] = $repeat_rule;
        }
        break;

      case 'EXDATE':
        try
        {
          $booking['skip_list'] = get_skip_list($property['value'], $property['params']);
        }
        catch (\Exception $e)
        {
          // If it's a bad timezone, flag that as a problem, otherwise rethrow the exception
          if (str_contains($e->getMessage(), 'Unknown or bad timezone'))
          {
            $problems[] = get_vocab('bad_timezone', $property['params']['TZID'] ?? 'UTC');
          }
          else
          {
            throw new \Exception($e);
          }
        }
        break;

      case 'CLASS':
        $booking['private'] = in_array($property['value'], array('PRIVATE', 'CONFIDENTIAL'));
        break;

      case 'STATUS':
        $booking['tentative'] = ($property['value'] == 'TENTATIVE');
        break;

      case 'UID':
        $booking['ical_uid'] = $property['value'];
        break;

      case 'SEQUENCE':
        $booking['ical_sequence'] = $property['value'];
        break;

      case 'LAST-MODIFIED':
        // We probably ought to do something with LAST-MODIFIED and use it
        // for the timestamp field
        break;

      default:
        // MRBS specific properties
        $mrbs_prefix = 'X-MRBS-';
        if (str_starts_with($property['name'], $mrbs_prefix))
        {
          $key = substr($property['name'], strlen($mrbs_prefix));
          $key = strtolower($key);
          $key = str_replace('-', '_', $key);
          // Type
          if ($key == 'type')
          {
            if (!empty($booking_types))
            {
              foreach ($booking_types as $type)
              {
                if ($property['value'] == get_type_vocab($type))
                {
                  $booking['type'] = $type;
                  break;
                }
              }
            }
          }
          // Registration keys
          elseif (in_array($key, $registration_keys))
          {
            $booking[$key] = $property['value'];
          }
          // Registrants
          elseif ($key == 'registrant')
          {
            $registrants[] = array(
              'username'    => $property['value'],
              'registered'  => $property['params']['X-MRBS-REGISTERED'],
              'create_by'   => RFC5545::unescapeQuotedString($property['params']['X-MRBS-CREATE-BY'])
            );
          }
          // TODO: custom fields
        }
        break;
    }
  }

  if (!$import_past && ($booking['end_time'] < time()))
  {
    return false;
  }

  // If we didn't manage to work out a username then just put the booking
  // under the name of the current user
  if (!isset($booking['create_by']))
  {
    $mrbs_user = session()->getCurrentUser();
    $mrbs_username = (isset($mrbs_user)) ? $mrbs_user->username : null;
    $booking['create_by'] = $mrbs_username;
  }

  // On the other hand a UID is mandatory in RFC 5545.   We'll be lenient and
  // provide one if it is missing
  if (!isset($booking['ical_uid']))
  {
    $booking['ical_uid'] = generate_global_uid($booking['name']);
    $booking['sequence'] = 0;  // and we'll start the sequence from 0
  }

  // Modify the brief and/or full descriptions
  if (!empty($add_location) && isset($location) && ($location !== ''))
  {
    // Brief description (SUMMARY)
    if (in_array('summary', $add_location))
    {
      if (isset($booking['name']) && ($booking['name'] !== ''))
      {
        $booking['name'] = get_vocab('expanded_name',
                                     $booking['name'],
                                     $location);
      }
      else
      {
        $booking['name'] = get_vocab('expanded_empty_name', $location);
      }
    }
    // Full description (DESCRIPTION)
    if (in_array('description', $add_location))
    {
      if (isset($booking['description']) && ($booking['description'] !== ''))
      {
        $booking['description'] = get_vocab('expanded_description',
                                            $booking['description'],
                                            $location);
      }
      else
      {
        $booking['description'] = get_vocab('expanded_empty_description', $location);
      }
    }
  }

  // A SUMMARY is optional in RFC 5545, however a brief description is mandatory
  // in MRBS.   So if the VEVENT didn't include a name, we'll give it one
  if (!isset($booking['name']) || ($booking['name']) === '')
  {
    $tag = 'import_no_SUMMARY';
    $booking['name'] = get_vocab($tag);
    // Throw an exception if it is still empty - probably because the vocab string has
    // been overridden in the config file by an empty string.
    if (!isset($booking['name']) || ($booking['name']) === '')
    {
      throw new Exception("Vocab string for '$tag' is empty");
    }
  }

  // LOCATION is optional in RFC 5545 but is obviously mandatory in MRBS.
  // If there is no LOCATION property we use the default_room specified on
  // the form, but if there is no default room (most likely because no rooms
  // have been created) then this error message is created).
  if (!isset($booking['room_id']))
  {
    $problems[] = get_vocab("no_LOCATION");
  }

  if (empty($problems))
  {
    // Get the area settings for this room, if we haven't got them already
    if (!isset($room_settings[$booking['room_id']]))
    {
      get_area_settings(get_area($booking['room_id']));
      $room_settings[$booking['room_id']]['morningstarts'] = $morningstarts;
      $room_settings[$booking['room_id']]['morningstarts_minutes'] = $morningstarts_minutes;
      $room_settings[$booking['room_id']]['resolution'] = $resolution;
    }
    // Round the start and end times to slot boundaries
    $date = getdate($booking['start_time']);
    $m = $date['mon'];
    $d = $date['mday'];
    $y = $date['year'];
    $am7 = mktime($room_settings[$booking['room_id']]['morningstarts'],
                  $room_settings[$booking['room_id']]['morningstarts_minutes'],
                  0, $m, $d, $y);
    $booking['start_time'] = round_t_down($booking['start_time'],
                                          $room_settings[$booking['room_id']]['resolution'],
                                          $am7);
    $booking['end_time'] = round_t_up($booking['end_time'],
                                      $room_settings[$booking['room_id']]['resolution'],
                                      $am7);
    // Make the bookings
    $bookings = array($booking);
    $result = mrbsMakeBookings($bookings, null, false, $skip);

    if ($result['valid_booking'])
    {
      // If the bookings been made then add the registrants
      add_registrants($result['new_details'][0]['id'], $registrants);
      return true;
    }
  }
  // There were problems - list them
  echo "<div class=\"problem_report\">\n";
  echo htmlspecialchars(get_vocab("could_not_import", $booking['name'], $booking['ical_uid']));
  echo "<ul>\n";
  foreach ($problems as $problem)
  {
    echo "<li>" . htmlspecialchars($problem) . "</li>\n";
  }
  if (!empty($result['violations']['errors']))
  {
    echo "<li>" . get_vocab("rules_broken") . "\n";
    echo "<ul>\n";
    foreach ($result['violations']['errors'] as $rule)
    {
      echo "<li>$rule</li>\n";
    }
    echo "</ul></li>\n";
  }
  if (!empty($result['conflicts']))
  {
    echo "<li>" . get_vocab("conflict"). "\n";
    echo "<ul>\n";
    foreach ($result['conflicts'] as $conflict)
    {
      echo "<li>$conflict</li>\n";
    }
    echo "</ul></li>\n";
  }
  echo "</ul>\n";
  echo "</div>\n";

  return false;
}


function get_file_details_url(string $file) : array
{
  $files = array();
  list( , $tmp_name) = explode('://', $file, 2);
  $files[] = array('name'     => $file,
                   'tmp_name' => $tmp_name,
                   'size'     => null);
  return $files;
}


function get_file_details_calendar($file) : array
{
  $files = array();
  $files[] = array('name'     => $file['name'],
                   'tmp_name' => $file['tmp_name'],
                   'size'     => filesize($file['tmp_name']));
  return $files;
}


function get_file_details_bzip2($file) : array
{
  // It's not possible to get the uncompressed size of a bzip2 file without first
  // decompressing the whole file
  $files = array();
  $files[] = array('name'     => $file['name'],
                   'tmp_name' => $file['tmp_name'],
                   'size'     => null);
  return $files;
}

function get_file_details_gzip($file) : array
{
  // Get the uncompressed size of the gzip file which is stored in the last four
  // bytes of the file, little-endian
  if (false !== ($handle = fopen($file['tmp_name'], 'rb')))
  {
    fseek($handle, -4, SEEK_END);
    $buffer = fread($handle, 4);
    $size_array = unpack('V', $buffer);
    $size = end($size_array);
    fclose($handle);
  }
  else
  {
    $size = null;
  }
  $files = array();
  $files[] = array('name'     => $file['name'],
                   'tmp_name' => $file['tmp_name'],
                   'size'     => $size);
  return $files;
}


function get_file_details_zip($file) : array
{
  $files = array();

  if (class_exists('ZipArchive'))
  {
    $zip = new ZipArchive();

    if (true === ($result = $zip->open($file['tmp_name'])))
    {
      for ($i=0; $i<$zip->numFiles; $i++)
      {
        $details = array();
        $stats = $zip->statIndex($i);
        $details['name']     = $stats['name'];
        $details['tmp_name'] = $file['tmp_name'] . '#' . $stats['name'];
        $details['size']     = $stats['size'];
        $files[] = $details;
      }
    }
    else
    {
      // Try and convert the error code into something a bit more
      // meaningful.
      //
      // It's safe to use ReflectionClass (PHP 5) as we already know that
      // ZipArchive (PHP 5.2.0) exists
      $reflection = new ReflectionClass('ZipArchive');
      $constants = $reflection->getConstants();
      foreach ($constants as $key => $value)
      {
        if (($result === $value) && (utf8_strpos($key, 'ER_') === 0))
        {
          $error_code = $key;
          break;
        }
      }
      $message = "ZipArchive::open() failed with ";
      if (isset($error_code))
      {
        $message .= "error code $error_code.";
        // There is a problem on IIS when opening a ZipArchive file that is in
        // C:\Windows\Temp.   ER_OPEN will be returned unless the user IUSR_XXXX
        // has 'List Folder' permission.
        // See the user notes at http://php.net/manual/en/ziparchive.open.php
        // and also https://bugs.php.net/bug.php?id=54128
        if ($result == ZipArchive::ER_OPEN)
        {
          $message .= " If your server is running Windows, check the permissions on " .
                      dirname($file['tmp_name']) . ". 'List Folder' permission is required " .
                      "for user IUSR_XXXX.";
        }
      }
      else
      {
        $message .= "unknown error code '$result'";
      }
      trigger_error($message, E_USER_WARNING);
    }
  }
  else
  {
    trigger_error("Could not open zip archive - the ZipArchive class does not exist on this system", E_USER_WARNING);
  }
  return $files;
}


function get_details($file) : array
{
  $result = array();

  if (is_string($file))
  {
    list($result['wrapper']) = explode('://', $file, 2);
    $result['files'] = get_file_details_url($file);
  }
  else
  {
    switch ($file['type'])
    {
      case 'text/calendar':
      case 'text/html':
      case 'text/plain':
      case 'application/x-download':
        $result['wrapper'] = 'file';
        $result['files'] = get_file_details_calendar($file);
        break;
      case 'application/x-bzip2':
        $result['wrapper'] = 'compress.bzip2';
        $result['files'] = get_file_details_bzip2($file);
        break;
      case 'application/x-gzip':
        $result['wrapper'] = 'compress.zlib';
        $result['files'] = get_file_details_gzip($file);
        break;
      case 'application/x-zip-compressed':
      case 'application/zip':
        $result['wrapper'] = 'zip';
        $result['files'] = get_file_details_zip($file);
        break;
      default:
        $result = false;
        trigger_error("Unknown file type '" . $file['type'] . "'", E_USER_NOTICE);
        break;
    }
  }

  return $result;
}


function get_fieldset_source(array $compression_wrappers) : ElementFieldset
{
  global $wrapper_mime_types;
  global $source_type, $url;

  $fieldset = new ElementFieldset();

  $fieldset->addLegend(get_vocab('source'));

  // Source type
  $field = new FieldInputRadioGroup();
  $options = array('file' => get_vocab('file'),
                   'url' => get_vocab('url'));
  $field->setLabel(get_vocab('source_type'))
        ->addRadioOptions($options, 'source_type', $source_type, true);
  $fieldset->addElement($field);

  // File
  $field = new FieldInputFile();

  $accept_mime_types = array();
  foreach ($compression_wrappers as $compression_wrapper)
  {
    $accept_mime_types[] = $wrapper_mime_types[$compression_wrapper];
  }
  // 'file' will always be available.  Put it at the beginning of the array.
  array_unshift($accept_mime_types, $wrapper_mime_types['file']);

  $field->setLabel(get_vocab('file_name'))
        ->setAttribute('id', 'field_file')
        ->setControlAttributes(array(
              'accept' => implode(',', $accept_mime_types),
              'name'   => 'upload_file',
              'id'     => 'upload_file')
            );

  $fieldset->addElement($field);

  // URL
  $field = new FieldInputUrl();
  $field->setLabel(get_vocab('url'))
        ->setAttribute('id', 'field_url')
        ->setControlAttributes(array(
            'name'      => 'url',
            'id'        => 'url',
            'required'  => true,
            'value'     => $url)
          );
  $fieldset->addElement($field);

  return $fieldset;
}


function get_fieldset_location_parsing() : ElementFieldset
{
  global $area_room_order, $area_room_delimiter, $area_room_create;

  $fieldset = new ElementFieldset();
  $fieldset->setAttribute('id', 'location_parsing');

  // Area-room order
  $field = new FieldInputRadioGroup();
  $options = array('area_room' => get_vocab('area_room'),
    'room_area' => get_vocab('room_area'));
  $field->setLabel(get_vocab('area_room_order'))
        ->setLabelAttribute('title', get_vocab('area_room_order_note'))
        ->addRadioOptions($options, 'area_room_order', $area_room_order, true);
  $fieldset->addElement($field);

  // Area-room delimiter
  $field = new FieldInputText();
  $field->setLabel(get_vocab('area_room_delimiter'))
        ->setLabelAttribute('title', get_vocab('area_room_delimiter_note'))
        ->setControlAttributes(array('name'     => 'area_room_delimiter',
          'value'    => $area_room_delimiter,
          'class'    => 'short',
          'required' => true));
  $fieldset->addElement($field);

  // Area/room create
  $field =new FieldInputCheckbox();
  $field->setLabel(get_vocab('area_room_create'))
        ->setControlAttribute('name', 'area_room_create')
        ->setChecked($area_room_create);
  $fieldset->addElement($field);

  return $fieldset;
}


function get_fieldset_ignore_location_settings() : ElementFieldset
{
  global $add_location;

  $fieldset = new ElementFieldset();
  $fieldset->setAttribute('id', 'ignore_location_settings');

  // Add the location to the brief and/or full description
  $field =new FieldInputCheckboxGroup();
  $options = array(
      'summary' => get_vocab('namebooker'),
      'description' => get_vocab('fulldescription_short')
    );
  $field->setLabel(get_vocab('add_location'))
        ->setControlAttribute('name', 'add_location')
        ->addCheckboxOptions($options, 'add_location[]', $add_location);
  $fieldset->addElement($field);

  return $fieldset;
}


function get_fieldset_location_settings() : ElementFieldset
{
  global $import_default_room;
  global $ignore_location;

  $fieldset = new ElementFieldset();

  $fieldset->addLegend(get_vocab('area_room_settings'));

  // Default room
  $areas = get_area_names(true);
  if (count($areas) > 0)
  {
    $options = array();

    foreach($areas as $area_id => $area_name)
    {
      $rooms = get_room_names($area_id, $all=true);
      if (count($rooms) > 0)
      {
        $options[$area_name] = array();
        foreach($rooms as $room_id => $room_name)
        {
          $options[$area_name][$room_id] = $room_name;
        }
      }
    }

    if (count($options) > 0)
    {
      $field = new FieldSelect();

      $field->setLabel(get_vocab('default_room'))
            ->setLabelAttribute('title', get_vocab('default_room_note'))
            ->setControlAttribute('name', 'import_default_room')
            ->addSelectOptions($options, $import_default_room, true);

      $fieldset->addElement($field);
    }
  }

  // Ignore location
  $field =new FieldInputCheckbox();
  $field->setLabel(get_vocab('ignore_location'))
        ->setControlAttribute('name', 'ignore_location')
        ->setChecked($ignore_location);
  $fieldset->addElement($field);

  // Location parsing fieldset
  $fieldset->addElement(get_fieldset_location_parsing());

  // Settings when we are ignoring the location
  $fieldset->addElement(get_fieldset_ignore_location_settings());

  return $fieldset;
}


function get_fieldset_other_settings() : ElementFieldset
{
  global $booking_types;
  global $import_default_type, $import_past, $skip;

  $fieldset = new ElementFieldset();

  $fieldset->addLegend(get_vocab('other_settings'));

  // Default type
  $field = new FieldSelect();
  $options = get_type_options(true);
  if (count($options) > 1)
  {
    $field->setLabel(get_vocab('default_type'))
          ->setControlAttribute('name', 'import_default_type')
          ->addSelectOptions($options, $import_default_type, true);
    $fieldset->addElement($field);
  }

  // Import past bookings
  // Add a hidden element so that if the checkbox is not checked we
  // get 0 instead of NULL passed to the server and so the default
  // can be used.
  // TODO: need a better way of doing this
  $hidden = new ElementInputHidden();
  $hidden->setAttributes(array(
      'name' => 'import_past',
      'value' => 0
    ));
  $fieldset->addElement($hidden);
  $field = new FieldInputCheckbox();
  $field->setLabel(get_vocab('import_past'))
        ->setControlAttribute('name', 'import_past')
        ->setChecked($import_past);
  $fieldset->addElement($field);

  // Skip conflicts
  // Add a hidden element (see comment above)
  $hidden = new ElementInputHidden();
  $hidden->setAttributes(array(
      'name' => 'skip',
      'value' => 0
    ));
  $fieldset->addElement($hidden);
  $field = new FieldInputCheckbox();
  $field->setLabel(get_vocab('skip_conflicts'))
        ->setControlAttribute('name', 'skip')
        ->setChecked($skip);
  $fieldset->addElement($field);

  return $fieldset;
}


function get_fieldset_submit_button() : ElementFieldset
{
  $fieldset = new ElementFieldset();

  // The Submit button
  $field = new FieldInputSubmit();
  $field->setControlAttributes(array('name'  => 'import',
                                     'value' => get_vocab('import')));
  $fieldset->addElement($field);

  return $fieldset;
}


$import = get_form_var('import', 'string');
$source_type = get_form_var('source_type', 'string', $default_import_source);
$url = get_form_var('url', 'string');
$import_default_room = get_form_var('import_default_room', 'int');
$ignore_location = get_form_var('ignore_location', 'string', '0');
$add_location = get_form_var('add_location', 'array');
$area_room_order = get_form_var('area_room_order', 'string', 'area_room');
$area_room_delimiter = get_form_var('area_room_delimiter', 'string', $default_area_room_delimiter);
$area_room_create = get_form_var('area_room_create', 'string', '0');
$import_default_type = get_form_var('import_default_type', 'string', $default_type);
$import_past = get_form_var('import_past', 'string', ((empty($default_import_past)) ? '0' : '1'));
$skip = get_form_var('skip', 'string', ((empty($skip_default)) ? '0' : '1'));

// Check the CSRF token if we're being asked to import data
if (!empty($import))
{
  Form::checkToken();
}

// Check the user is authorised for this page
checkAuthorised(this_page());

$context = array(
  'view'      => $view,
  'view_all'  => $view_all,
  'year'      => $year,
  'month'     => $month,
  'day'       => $day,
  'area'      => $area,
  'room'      => $room ?? null
);

print_header($context);


// PHASE 2 - Process the files
// ---------------------------

if (!empty($import))
{
  if ($source_type == 'url')
  {
    if (!isset($url) || !filter_var($url, FILTER_VALIDATE_URL))
    {
      echo "<p>\n";
      echo get_vocab("invalid_url");
      echo "</p>\n";
    }
    else
    {
      $details = get_details($url);
    }
  }
  else
  {
    if (($_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) ||
        !is_uploaded_file($_FILES['upload_file']['tmp_name']))
    {
      echo "<p>\n";
      echo get_vocab("upload_failed");

      if ($_FILES['upload_file']['error'] !== UPLOAD_ERR_OK)
      {
        try
        {
          throw new UploadException($_FILES['upload_file']['error']);
        }
        catch (UploadException $e)
        {
          switch ($e->getCode())
          {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
              echo "<br>\n" . get_vocab("max_allowed_file_size", ini_get('upload_max_filesize'));
              break;
            case UPLOAD_ERR_NO_FILE:
              echo "<br>\n" . get_vocab("no_file");
              break;
            default:
              // None of the other possible errors would make much sense to the user, but should be reported
              trigger_error($e->getMessage(), E_USER_WARNING);
              break;
          }
        }
      }
      // Check this last, as it will be true if there is an error
      elseif (!is_uploaded_file($_FILES['upload_file']['tmp_name']))
      {
        // This should not happen and if it does may mean that somebody is messing about
        trigger_error("Attempt to import a file that has not been uploaded", E_USER_WARNING);
      }

      echo "</p>\n";
    }
    else
    {
      // We've got a file
      $details = get_details($_FILES['upload_file']);
    }
  }

  if (isset($details))
  {
    if ($details === false)
    {
      echo "<p>" . get_vocab("could_not_process") . "</p>\n";
    }
    else
    {
      foreach ($details['files'] as $file)
      {
        echo "<h3>" . $file['name'] . "</h3>";

        $n_success = 0;
        $n_failure = 0;

        $handle = fopen($details['wrapper'] . '://' . $file['tmp_name'], 'rb');

        if ($handle === false)
        {
          echo "<p>" . get_vocab("could_not_process") . "</p>\n";
        }
        else
        {
          while (false !== ($vevent = get_event($handle)))
          {
            (process_event($vevent)) ? $n_success++ : $n_failure++;
          }

          fclose($handle);

          echo "<p>\n";
          echo "$n_success " . get_vocab("events_imported");
          if ($n_failure > 0)
          {
            echo "<br>\n$n_failure " . get_vocab("events_not_imported");
          }
          echo "</p>\n";
        }
      }
    }
  }
}

// PHASE 1 - Get the user input
// ----------------------------

echo "<h2>" . get_vocab('import_icalendar') . "</h2>\n";

$compression_wrappers = get_compression_wrappers();

echo "<p>\n" . get_vocab('import_intro') . "</p>\n";
echo "<p>\n" . get_vocab('supported_file_types') . "</p>\n";
echo "<ul>\n";
echo "<li>" . $wrapper_descriptions['file'] . "</li>\n";
foreach ($compression_wrappers as $compression_wrapper)
{
  echo "<li>" . $wrapper_descriptions[$compression_wrapper] . "</li>\n";
}
echo "</ul>\n";

$form = new Form();

$form->setAttributes(array('class'   => 'standard',
                           'method'  => 'post',
                           'enctype' => 'multipart/form-data',
                           'action'  => multisite(this_page())));

$fieldset = new ElementFieldset();

$fieldset->addElement(get_fieldset_source($compression_wrappers))
         ->addElement(get_fieldset_location_settings())
         ->addElement(get_fieldset_other_settings())
         ->addElement(get_fieldset_submit_button());

$form->addElement($fieldset);

$form->render();


print_footer();
