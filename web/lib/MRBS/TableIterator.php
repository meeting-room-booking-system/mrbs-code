<?php
namespace MRBS;

use Countable;
use Iterator;

abstract class TableIterator implements Countable, Iterator
{
  protected $res;
  protected $cursor;
  protected $item;
  protected $base_class;


  public function __construct($base_class)
  {
    $this->base_class = $base_class;
    $this->getRes();
  }


  public function current() : object
  {
    return $this->item;
  }


  public function next() : void
  {
    $this->cursor++;
    if (false !== ($row = $this->res->next_row_keyed()))
    {
      $this->item = new $this->base_class();
      $this->item->load($row);
    }
  }


  public function key() : int
  {
    return $this->cursor;
  }


  public function valid() : bool
  {
    return ($this->cursor < $this->count());
  }


  public function rewind() : void
  {
    if ($this->cursor >= 0)
    {
      $this->getRes();
    }
    $this->next();
  }


  public function count() : int
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

}
