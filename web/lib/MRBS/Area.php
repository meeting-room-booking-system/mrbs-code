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

}
