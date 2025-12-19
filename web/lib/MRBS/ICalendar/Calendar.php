<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

use function MRBS\get_mrbs_version;

require_once MRBS_ROOT . '/version.inc';

class Calendar
{
  private const NAME = 'VCALENDAR';

  private $components = [];
  private $properties = [];

  public function __construct(?string $method=null)
  {
    $this->properties[] = new Property('PRODID', '-//MRBS//NONSGML ' . get_mrbs_version() . '//EN');
    $this->properties[] = new Property('VERSION', '2.0');
    $this->properties[] = new Property('CALSCALE', 'GREGORIAN');
    if (isset($method))
    {
      $this->properties[] = new Property('METHOD', $method);
    }
  }


  public function addComponent(Component $component)
  {
    $this->components[] = $component;
  }


  public function addComponents(array $components)
  {
    foreach ($components as $component)
    {
      $this->addComponent($component);
    }
  }

  public function toString(): string
  {
    $result = 'BEGIN:' . self::NAME . RFC5545::EOL;

    foreach ($this->properties as $property)
    {
      $result .= $property->toString();
    }

    foreach ($this->components as $component)
    {
      $result .= $component->toString();
    }

    $result .= 'END:' . self::NAME . RFC5545::EOL;
    return RFC5545::fold($result);
  }
}
