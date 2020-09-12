<?php
namespace MRBS;


class Area extends Table
{
  const TABLE_NAME = 'area';

  protected static $unique_columns = array('area_name');


  public function __construct($area_name=null)
  {
    parent::__construct();
    $this->area_name = $area_name;
  }


  public static function getById($id)
  {
    return self::getByColumn('id', $id);
  }


  public static function getByName($area_name)
  {
    return self::getByColumn('area_name', $area_name);
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


  public function getPermissions(array $role_ids)
  {
    return array();
  }

}
