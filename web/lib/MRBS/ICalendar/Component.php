<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

abstract class Component
{
  // Self-referential 'abstract' declaration
  public const NAME = self::NAME;

  /**
   * Components can contain other components, eg a VEVENT can contain a VALARM, or a VTIMEZONE can contain
   * STANDARD and DAYLIGHT components.
   *
   * @var Component[]
   */
  protected $components = [];
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
   * Add a subcomponent to the component.
   */
  public function addComponent(Component $component) : self
  {
    $this->components[] = $component;
    return $this;
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
    // TODO: Make this more efficient, so that it doesn't have to loop through all the properties
    // TODO: if we know that there is only one of a particular property.  Either add a limit
    // TODO: parameter?  Or check the Component class for once-only properties?
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
   * Get properties in the component, optionally filtered by name.  Note that there could be more than one
   * property with the same name, so the result is an array of Property objects.
   *
   * @param string|null $name  The name of the property to return.  If null, return all properties.
   * @param int|null    $limit The maximum number of properties to return.  If null, return all properties.
   * @return Property[]
   */
  public function getProperties(?string $name=null, ?int $limit=null) : array
  {
    if (!isset($name) && !isset($limit))
    {
      return $this->properties;
    }

    $result = [];

    foreach ($this->properties as $property)
    {
      if (($name === null) || ($property->getName() === $name))
      {
        $result[] = $property;
      }

      if (isset($limit) && count($result) >= $limit)
      {
        break;
      }
    }

    return $result;
  }


  /**
   * Convert the component to a string.
   */
  public function toString(): string
  {
    $result = 'BEGIN:' . static::NAME . Calendar::EOL;

    foreach ($this->properties as $property)
    {
      $result .= $property->toString();
    }

    foreach ($this->components as $component)
    {
      $result .= $component->toString();
    }

    $result .= 'END:' . static::NAME . Calendar::EOL;
    return $result;
  }

}
