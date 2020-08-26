<?php
namespace MRBS;


class Areas extends TableIterator
{

  public function __construct()
  {
    parent::__construct(__NAMESPACE__ . '\\Area');
  }


  protected function getRes()
  {
    $class_name = $this->base_class;
    $sql = "SELECT *
              FROM " . _tbl($class_name::TABLE_NAME) . "
          ORDER BY sort_key";
    $this->res = db()->query($sql);
    $this->cursor = -1;
    $this->item = null;
  }
}
