<?php
namespace MRBS;


class Group extends Table
{
  const TABLE_NAME = 'group';

  protected static $unique_columns = array('name');


  public function __construct($name=null)
  {
    global $auth;
    
    parent::__construct();
    $this->name = $name;
    // Set some default properties
    $this->auth_type = $auth['type'];
  }


  public static function getByName($name)
  {
    return self::getByColumn('name', $name);
  }

}
