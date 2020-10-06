<?php
namespace MRBS;


class Group extends Table
{
  const TABLE_NAME = 'group';

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
