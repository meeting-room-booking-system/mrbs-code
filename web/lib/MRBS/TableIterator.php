<?php
namespace MRBS;


abstract class TableIterator implements \Countable, \Iterator
{
  protected $res;
  protected $cursor;
  protected $item;
  protected $base_class;
  protected $names;


  public function __construct($base_class)
  {
    $this->base_class = $base_class;
    $this->getRes();
  }


  public function current()
  {
    return $this->item;
  }


  public function next()
  {
    $this->cursor++;
    if (false !== ($row = $this->res->next_row_keyed()))
    {
      $this->item = new $this->base_class();
      $this->item->load($row);
    }
  }


  public function key()
  {
    return $this->cursor;
  }


  public function valid()
  {
    return ($this->cursor < $this->count());
  }


  public function rewind()
  {
    if ($this->cursor >= 0)
    {
      $this->getRes();
    }
    $this->next();
  }


  public function count()
  {
    return $this->res->count();
  }


  protected function getRes($sort_column=null)
  {
    $class_name = $this->base_class;
    $sql = "SELECT * FROM " . _tbl($class_name::TABLE_NAME);
    if (isset($sort_column) && ($sort_column !== ''))
    {
      $sql .= " ORDER BY " . db()->quote($sort_column);
    }
    $this->res = db()->query($sql);
    $this->cursor = -1;
    $this->item = null;
  }


  // Converts the result of db()->syntax_group_array_as_string() queries
  // back into arrays.
  protected function stringsToArrays(&$row)
  {
    foreach (array('groups', 'roles') as $key)
    {
      // Convert the string of ids into an array and also add an
      // array of names
      if (array_key_exists($key, $row))
      {
        $names = array();

        // If there are no groups/roles, MySQL will return NULL and PostgreSQL ''.
        if (isset($row[$key]) && ($row[$key] !== ''))
        {
          $row[$key] = explode(',', $row[$key]);
          foreach ($row[$key] as $id)
          {
            $names[] = $this->names[$key][$id];
          }
        }
        else
        {
          $row[$key] = array();
        }

        // Sort the names
        sort($names, SORT_LOCALE_STRING | SORT_FLAG_CASE);

        // Add the names to the result
        switch ($key)
        {
          case 'groups':
            $row['group_names'] = $names;
            break;
          case 'roles':
            $row['role_names'] = $names;
            break;
          default:
            throw new \Exception("Unknown key '$key'");
            break;
        }
      }
    }
  }

}
