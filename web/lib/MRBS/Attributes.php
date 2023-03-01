<?php
namespace MRBS;


abstract class Attributes extends TableIterator
{

  // Returns an array of role names indexed by id.
  abstract public function getNames() : array;


  // Converts an array of ids to an array of names indexed by id
  public static function idsToNames(array $ids) : array
  {
    static $names = array();
    $class = get_called_class();

    if (!isset($names[$class]))
    {
      $instance = new static();
      $names[$class] = $instance->getNames();
    }

    $result = array();

    foreach ($ids as $id)
    {
      if (isset($names[$class][$id]))
      {
        $result[$id] = $names[$class][$id];
      }
      else
      {
        trigger_error("Id $id does not exist");
      }
    }

    asort($result, SORT_LOCALE_STRING | SORT_FLAG_CASE);

    return $result;
  }
}
