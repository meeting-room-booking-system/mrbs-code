<?php
namespace MRBS;


class Area extends Location
{
  const TABLE_NAME = 'area';

  protected static $unique_columns = array('area_name');


  public function __construct($area_name=null)
  {
    global $area_defaults;

    parent::__construct();
    $this->area_name = $area_name;
    $this->sort_key = $area_name;
    $this->disabled = false;

    // TODO: Handle defaults differently: get rid of $area_defaults
    foreach ($area_defaults as $key => $value)
    {
      $this->$key = $value;
    }
  }


  public static function getByName($name) : ?object
  {
    return self::getByColumn('area_name', $name);
  }


  // Returns an array of room names for the area indexed by area id.
  public function getRoomNames($include_disabled=false) : array
  {
    $rooms = new Rooms($this->id);
    return $rooms->getNames($include_disabled);
  }


  public function isDisabled() : bool
  {
    return (bool) $this->disabled;
  }


  public function getRules(array $role_ids) : array
  {
    return AreaRule::getRulesByRoles($role_ids, $this->id);
  }


  // TODO: is this necessary?
  private static function sanitize(array $row) : array
  {
    global $periods, $private_override_options;
    global $area_defaults;

    // If there isn't a value in the database use the area default
    foreach ($row as $key => $value)
    {
      if (!isset($row[$key]) && array_key_exists($key, $area_defaults))
      {
        $row[$key] = $area_defaults[$key];
      }
    }

    // Make sure the slot settings are correct if we're using periods
    if (!empty($row['enable_periods']))
    {
      $row['resolution'] = 60;
      $row['morningstarts'] = 12;
      $row['morningstarts_minutes'] = 0;
      $row['eveningends'] = 12;
      if (array_key_exists('periods', $row))
      {
        $row['eveningends_minutes'] = count($row['periods']) - 1;
      }
      else
      {
        $row['eveningends_minutes'] = count($periods) - 1;
      }
    }
    // Otherwise check that the resolution is set and > 0
    elseif (array_key_exists('resolution', $row) &&
            (empty($row['resolution']) || ($row['resolution'] < 0)))
    {
      $row['resolution'] = 30*60;  // 30 minutes, a reasonable fallback
      $message = "Invalid value for 'resolution' in the area table.   Using {$row['resolution']} seconds.";
      trigger_error($message, E_USER_WARNING);
    }

    // Do some sanity checking in case the area table is somehow messed up
    // (1) 'private_override' must be a valid value
    if (array_key_exists('private_override', $row) &&
        !in_array($row['private_override'], $private_override_options))
    {
      $row['private_override'] = 'private';  // the safest default
      $message = "Invalid value for 'private_override' in the area table.  Using '{$row['private_override']}'.";
      trigger_error($message, E_USER_WARNING);
    }

    return $row;
  }


  // Function to decode any columns that are stored encoded in the database
  protected static function onRead(array $row) : array
  {
    global $force_resolution, $area_defaults, $auth;

    // Cast the pseudo-booleans to bools
    $columns = Columns::getInstance(_tbl(self::TABLE_NAME));
    foreach ($columns as $column)
    {
      if (($column->getNature() == Column::NATURE_INTEGER) && ($column->getLength() <= 2))
      {
        $row[$column->name] = (bool)$row[$column->name];
      }
    }

    // We cannot assume that any array keys exist as this may be called during an upgrade
    // process before the columns existed.

    // Decode the periods
    if (isset($row['periods']))
    {
      $row['periods'] = json_decode($row['periods']);
    }

    // TODO: Should this be necessary?  Shouldn't we just make sure the table
    // TODO: contains the correct values in the first place?
    $row = self::sanitize($row);

    // Some special config settings ...

    // If $force_resolution is set then use the default value of $resolution
    // instead of the area setting.
    if ($force_resolution &&
        array_key_exists('enable_periods', $row) &&
        !$row['enable_periods'])
    {
      $row['resolution'] = $area_defaults['resolution'];
    }

    // If it's been set in the config settings then force all bookings to be shown
    // as private for users who haven't logged in.
    if ($auth['force_private_for_guests'] && (null === session()->getCurrentUser()))
    {
      $row['private_override'] = 'private';
    }

    return $row;
  }


  // Function to encode any columns that are stored encoded in the database
  protected static function onWrite(array $row) : array
  {
    // We cannot assume that any array keys exist as this may be called during an upgrade
    // process before the columns existed.

    // Encode the periods
    if (isset($row['periods']))
    {
      $row['periods'] = json_encode($row['periods']);
    }

    return $row;
  }
}
