<?php
namespace MRBS;


// Holds information about table columns
class Columns
{
  private static $instances = array();
  private $data;


  private function __construct($table_name)
  {
    // Get the column info
    $this->data = array();
    $info = db()->field_info($table_name);
    // And rearrange the array so that it is indexed by name
    foreach ($info as $field)
    {
      $name = $field['name'];
      $this->data[$name] = array();
      foreach ($field as $key => $value)
      {
        if ($key != 'name')
        {
          $this->data[$name][$key] = $value;
        }
      }
    }
  }


  public static function getInstance($table_name)
  {
    if (!isset(self::$instances[$table_name]))
    {
      self::$instances[$table_name] = new self($table_name);
    }

    return self::$instances[$table_name];
  }


  public function getNames()
  {
    return array_keys($this->data);
  }
}
