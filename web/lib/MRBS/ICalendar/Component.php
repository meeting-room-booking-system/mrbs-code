<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

abstract class Component
{
  // Self-referential 'abstract' declaration.  Must be overridden by subclasses.
  protected const NAME = self::NAME;

  protected $content = null;
  protected $properties = [];

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

  public function addProperty(Property $property) : void
  {
    $this->validateProperty($property);
    $this->properties[] = $property;
  }

  abstract public function validateProperty(Property $property) : void;

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
