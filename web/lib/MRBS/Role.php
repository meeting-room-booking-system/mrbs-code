<?php
namespace MRBS;


class Role extends Table
{
  const TABLE_NAME = 'role';

  protected static $unique_columns = array('name');


  public function __construct($name=null)
  {
    parent::__construct();
    $this->name = $name;
  }


  public static function getByName($name)
  {
    return self::getByColumn('name', $name);
  }

}
