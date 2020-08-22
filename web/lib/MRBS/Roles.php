<?php
namespace MRBS;


class Roles implements \Countable, \Iterator
{
  private $res;
  private $cursor;
  private $item;


  public function __construct()
  {
    $this->getRoles();
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
      $this->item = new Role();
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
      $this->getRoles();
    }
    $this->next();
  }


  public function count()
  {
    return $this->res->count();
  }


  private function getRoles()
  {
    $sql = "SELECT * FROM " . \MRBS\_tbl(Role::TABLE_NAME);
    $this->res = \MRBS\db()->query($sql);
    $this->cursor = -1;
    $this->item = null;
  }
}
