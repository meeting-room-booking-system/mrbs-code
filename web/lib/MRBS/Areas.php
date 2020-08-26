<?php
namespace MRBS;


class Areas implements \Countable, \Iterator
{
  private $res;
  private $cursor;
  private $item;


  public function __construct()
  {
    $this->getAreas();
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
      $this->item = new Area();
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
      $this->getAreas();
    }
    $this->next();
  }


  public function count()
  {
    return $this->res->count();
  }


  private function getAreas()
  {
    $sql = "SELECT *
              FROM " . _tbl(Area::TABLE_NAME) . "
          ORDER BY sort_key";
    $this->res = db()->query($sql);
    $this->cursor = -1;
    $this->item = null;
  }
}
