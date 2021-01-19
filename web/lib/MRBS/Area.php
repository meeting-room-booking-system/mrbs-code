<?php
namespace MRBS;


class Area extends Location
{
  const TABLE_NAME = 'area';

  protected static $unique_columns = array('area_name');


  public function __construct($area_name=null)
  {
    parent::__construct();
    $this->area_name = $area_name;
    $this->sort_key = $area_name;
    $this->disabled = false;
  }


  public static function getByName($name)
  {
    return self::getByColumn('area_name', $name);
  }


  // Returns an array of room names for the area indexed by area id.
  public function getRoomNames($include_disabled=false)
  {
    $rooms = new Rooms($this->id);
    return $rooms->getNames($include_disabled);
  }


  public function isDisabled()
  {
    return (bool) $this->disabled;
  }


  public function getRules(array $role_ids)
  {
    return AreaRule::getRulesByRoles($role_ids, $this->id);
  }


  // Function to decode any columns that are stored encoded in the database
  protected static function onRead($row)
  {
    // We cannot assume that any array keys exist as this may be called during an upgrade
    // process before the columns existed.

    // Decode the periods
    if (isset($row['periods']))
    {
      $row['periods'] = json_decode($row['periods']);
    }

    // Make sure the resolution is correct if we're using periods
    // TODO: Should this be necessary?  Shouldn't we just make sure the table
    // TODO: contains the correct value in the first place?
    if (!empty($row['enable_periods']))
    {
      $row['resolution'] = 60;
    }

    return $row;
  }


  // Function to encode any columns that are stored encoded in the database
  protected static function onWrite($row)
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
