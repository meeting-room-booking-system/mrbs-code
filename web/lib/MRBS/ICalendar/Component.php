<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

abstract class Component
{
  protected $properties = [];


  /**
   * Validate a property by checking that the name is valid for the component and
   * that it hasn't yet been added to the component if only one instance of a
   * property is allowed.
   */
  protected function validateProperty(Property $property) : void
  {

  }


  /**
   * Add a property to the component.
   */
  public function addProperty(Property $property) : self
  {
    $this->validateProperty($property);
    $this->properties[] = $property;
    return $this;
  }


  /**
   * Convert the component to a string.
   */
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
