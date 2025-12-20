<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

abstract class Component
{
  // Self-referential 'abstract' declaration.  Must be overridden by subclasses.
  protected const NAME = self::NAME;

  protected $content = null;
  protected $properties = [];


  /**
   * Constructor. Can either be called with no arguments, and then properties can be added
   * later, or with a string containing the component content.
   */
  public function __construct(?string $content = null)
  {
    if (isset($content))
    {
      if (!str_ends_with($content, RFC5545::EOL))
      {
        $content .= RFC5545::EOL;
      }
      $this->content = $content;
    }
  }


  abstract protected function validateProperty(Property $property) : void;


  public function addProperty(Property $property) : self
  {
    if (isset($this->content))
    {
      throw new \LogicException('Cannot add properties to a component once it has been converted to a string');
    }

    $this->validateProperty($property);
    $this->properties[] = $property;
    return $this;
  }


  public function toString(): string
  {
    if (!isset($this->content))
    {
      $this->content = 'BEGIN:' . static::NAME . RFC5545::EOL;

      foreach ($this->properties as $property)
      {
        $this->content .= $property->toString();
      }

      $this->content .= 'END:' . static::NAME . RFC5545::EOL;
    }

    return $this->content;
  }

}
