<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

use GuzzleHttp\Client;
use MRBS\DB\DBException;
use function MRBS\_tbl;
use function MRBS\db;
use function MRBS\row_cast_columns;

class Timezone extends Component
{
  protected const NAME = 'VTIMEZONE';


  protected function validateProperty(Property $property): void
  {
    // TODO: Implement validateProperty() method.
  }


  /**
   * Create an instance of the class based on a given timezone name.
   *
   * This method caches the latest VTIMEZONE component in the database.  If it has expired,
   * it goes to the web for the latest version, or, if there's nothing in the database in the
   * first place, it tries to populate it from the VTIMEZONE definitions in the filesystem.
   *
   * @param string $tz The name of the timezone for which the instance should be created.
   * @return self|false Returns an instance of the class representing the timezone,
   *                    or false if the timezone could not be determined or is invalid.
   */
  public static function createFromTimezoneName (string $tz)
  {
    global $zoneinfo_update, $zoneinfo_expiry;

    static $vtimezones = array();  // Cache the components for performance

    if (!isset($vtimezones[$tz]))
    {
      // Look for a timezone definition in the database
      $vtimezone_db = self::getFromDb($tz);
      if (isset($vtimezone_db['vtimezone']))
      {
        $vtimezones[$tz] = new self($vtimezone_db['vtimezone']);
        // If the definition has expired, and we're updating it, then get a fresh definition from the URL
        if ($zoneinfo_update && ((time() - $vtimezone_db['last_updated']) >= $zoneinfo_expiry))
        {
          $vtimezone = self::getFromUrl($vtimezone_db['vtimezone']);
          if (isset($vtimezone))
          {
            // We've got a valid VTIMEZONE, so we can update the database and the static variable
            self::updateDb($tz, $vtimezone);
            $vtimezones[$tz] = new self($vtimezone);
          }
          else
          {
            // If we didn't manage to get a new VTIMEZONE, update the last_updated field
            // so that MRBS will not try again until after the expiry interval has passed.
            // This will mean that we don't keep encountering a timeout delay. (The most
            // likely reason that we couldn't get a new VTIMEZONE is that the site doesn't
            // have external internet access, so there's no point in retrying for a while).
            self::touchDb($tz);
          }
        }
      }
      else
      {
        // If there's nothing in the database, get one from the filesystem
        $vtimezone = self::getFromFile($tz);
        if (isset($vtimezone))
        {
          // And put it in the database if it's valid
          self::insertIntoDb($tz, $vtimezone);
          $vtimezones[$tz] = new self($vtimezone);
        }
        else
        {
          // Everything has failed
          $vtimezones[$tz] = false;
        }
      }
    }

    return $vtimezones[$tz];
  }


  /**
   * Fetch timezone information from the database based on the given timezone identifier.
   *
   * @param string $tz The timezone identifier to retrieve data for.
   * @return array{'vtimezone': string, 'last_updated': int} Returns an associative array containing the
   *    timezone information and last updated timestamp, or null if no matching record is found.
   */
  private static function getFromDb(string $tz) : ?array
  {
    global $zoneinfo_outlook_compatible;

    $sql = "SELECT vtimezone, last_updated
              FROM " . _tbl('zoneinfo') . "
             WHERE timezone=:timezone
               AND outlook_compatible=:outlook_compatible
             LIMIT 1";

    $sql_params = array(
      ':timezone' => $tz,
      ':outlook_compatible' => ($zoneinfo_outlook_compatible) ? 1 : 0
    );

    $res = db()->query($sql, $sql_params);

    if (false === ($row = $res->next_row_keyed()))
    {
      return null;
    }

    row_cast_columns($row, 'zoneinfo');

    return $row;
  }


  /**
   * Fetch the VTIMEZONE component from a file for a given timezone identifier.
   */
  private static function getFromFile(string $tz) : ?string
  {
    global $zoneinfo_outlook_compatible;

    $tz_dir = ($zoneinfo_outlook_compatible) ? TZDIR_OUTLOOK : TZDIR;
    $tz_file = "$tz_dir/$tz.ics";

    if (!is_readable($tz_file))
    {
      return null;
    }

    $vcalendar = file_get_contents($tz_file);

    if (empty($vcalendar))
    {
      return null;
    }

    $vtimezone = RFC5545::extractVtimezone($vcalendar);

    return (empty($vtimezone)) ? null : $vtimezone;
  }


  // Gets a VTIMEZONE definition from the TZURL defined in the $vtimezone component
  private static function getFromUrl(string $vtimezone) : ?string
  {
    // (Note that a VTIMEZONE component can contain a TZURL property which
    // gives the URL of the most up-to-date version.  Calendar applications
    // should be able to check this themselves, but we might as well give them
    // the most up-to-date version in the first place).
    $properties = explode("\r\n", RFC5545::unfold($vtimezone));
    foreach ($properties as $property)
    {
      if (mb_strpos($property, "TZURL:") === 0)
      {
        $tz_url = mb_substr($property, 6);  // 6 is the length of "TZURL:"
        break;
      }
    }

    if (!isset($tz_url))
    {
      trigger_error("The VTIMEZONE component didn't contain a TZURL property.", E_USER_NOTICE);
      return null;
    }

    try {
      $vcalendar = (new Client())->get($tz_url)->getBody()->getContents();
    }
    catch (\Exception $e) {
      trigger_error(get_class($e) . ': ' . $e->getMessage(), E_USER_WARNING);
      trigger_error("MRBS: failed to download a new timezone definition from $tz_url", E_USER_WARNING);
      return null;
    }

    $new_vtimezone = RFC5545::extractVtimezone($vcalendar);
    if (empty($new_vtimezone))
    {
      trigger_error("MRBS: $tz_url did not contain a valid VTIMEZONE", E_USER_WARNING);
      return null;
    }

    return $new_vtimezone;
  }


  // Insert a new VTIMEZONE definition for a timezone into the database
  private static function insertIntoDb(string $tz, string $vtimezone) : void
  {
    global $zoneinfo_outlook_compatible;

    try
    {
      $sql = "INSERT INTO " . _tbl('zoneinfo') . "
                          (vtimezone, last_updated, timezone, outlook_compatible)
                   VALUES (:vtimezone, :last_updated, :timezone, :outlook_compatible)";

      $sql_params = array(
        ':vtimezone' => $vtimezone,
        ':last_updated' => time(),
        ':timezone' => $tz,
        ':outlook_compatible' => ($zoneinfo_outlook_compatible) ? 1 : 0
      );

      db()->command($sql, $sql_params);
    }
    catch (DBException $e)
    {
      // Catch any database exception because it's just possible that someone else has inserted a row just
      // before us and then we'll get an error because of the unique constraint.   If we do then that's
      // fine and we can ignore it, but trigger an error anyway just in case it's some other kind of error.
      // (Ideally we'd want to be more specific about looking for constraint errors, but we have to consider
      // both MySQL and PostgreSQL).
      //
      // We could have used an INSERT IGNORE, but there isn't an easy PostgreSQL alternative until
      // PostgreSQL 9.5.
      //
      // We can't put a mutex lock round the SELECT - INSERT operation because we're normally already
      // inside another lock from edit_entry_handler and you can only, in the MYSQL implementation, have
      // one active lock.
      trigger_error($e->getMessage(), E_USER_NOTICE);
    }
  }


  // Update the last_updated time for a timezone in the database
  private static function touchDb(string $tz) : void
  {
    global $zoneinfo_outlook_compatible;

    $sql = "UPDATE " . _tbl('zoneinfo') . "
               SET last_updated=:last_updated
             WHERE timezone=:timezone
               AND outlook_compatible=:outlook_compatible";

    $sql_params = array(
      ':last_updated' => time(),
      ':timezone' => $tz,
      ':outlook_compatible' => ($zoneinfo_outlook_compatible) ? 1 : 0
    );

    db()->command($sql, $sql_params);
  }


  // Update the database with a new VTIMEZONE definition for a timezone
  private static function updateDb(string $tz, string $vtimezone) : void
  {
    global $zoneinfo_outlook_compatible;

    $sql = "UPDATE " . _tbl('zoneinfo') . "
               SET vtimezone=:vtimezone,
                   last_updated=:last_updated
             WHERE timezone=:timezone
               AND outlook_compatible=:outlook_compatible";

    $sql_params = array(
      ':vtimezone' => $vtimezone,
      ':last_updated' => time(),
      ':timezone' => $tz,
      ':outlook_compatible' => ($zoneinfo_outlook_compatible) ? 1 : 0
    );

    db()->command($sql, $sql_params);
  }

}
