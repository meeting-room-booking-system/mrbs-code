<?php
namespace MRBS;


class Roles extends TableIterator
{

  public function __construct()
  {
    parent::__construct(__NAMESPACE__ . '\\Role');
  }

}
