<?php
namespace MRBS;


class Column
{
  const NATURE_BINARY     = 0;
  const NATURE_BOOLEAN    = 1;
  const NATURE_CHARACTER  = 2;
  const NATURE_DECIMAL    = 3;
  const NATURE_INTEGER    = 4;
  const NATURE_REAL       = 5;

  public $name;

  private $nature;
  private $length;


  public function __construct($name)
  {
    $this->name = $name;
  }


  public function getLength()
  {
    return $this->length;
  }


  public function setLength($length)
  {
    $this->length = intval($length);
  }


  public function getNature()
  {
    return $this->nature;
  }


  public function setNature($nature)
  {
    $reflectionClass = new \ReflectionClass($this);
    $constants = $reflectionClass->getConstants();
    if (!in_array($nature, array_values($constants), true))
    {
      throw new \Exception("Invalid nature '$nature'");
    }
    $this->nature = $nature;
  }


}
