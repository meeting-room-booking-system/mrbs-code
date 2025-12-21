<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

abstract class Component
{
  /**
   * @var Property[]
   */
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
   * Get the values of a property.
   */
  public function getPropertyValues(string $name) : array
  {
    $result = [];

    foreach ($this->properties as $property)
    {
      if ($property->getName() == $name)
      {
        // There could be more than one property with the same name
        $result = array_merge($result, $property->getValues());
      }
    }

    return $result;
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
