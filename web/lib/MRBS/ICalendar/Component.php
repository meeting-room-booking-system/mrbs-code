<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

abstract class Component
{
  protected $name;

  protected $properties = [];

  public function addProperty(Property $property) : void
  {
    $this->validateProperty($property);
    $this->properties[] = $property;
  }

  abstract public function validateProperty(Property $property) : void;

  public function toString(): string
  {
    $result = 'BEGIN:' . $this->name . RFC5545::EOL;

    foreach ($this->properties as $property)
    {
      $result .= $property->toString();
    }

    $result .= 'END:' . $this->name . RFC5545::EOL;
    return $result;
  }

}
