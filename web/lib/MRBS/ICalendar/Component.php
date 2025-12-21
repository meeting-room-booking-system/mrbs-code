<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

abstract class Component
{
  protected $name;
  protected $content = null;
  protected $properties = [];


  public function __construct()
  {
    $this->name = static::NAME;
  }


  protected function validateProperty(Property $property)
  {

  }


  public function addProperty(Property $property) : self
  {
    $this->validateProperty($property);
    $this->properties[] = $property;
    return $this;
  }


  public function toString(): string
  {
    $result = 'BEGIN:' . static::NAME . RFC5545::EOL;

    foreach ($this->properties as $property)
    {
      $result .= $property->toString();
    }

    $result .= 'END:' . static::NAME . RFC5545::EOL;
    return $result;
  }

}
