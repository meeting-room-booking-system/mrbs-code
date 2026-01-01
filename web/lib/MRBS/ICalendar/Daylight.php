<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

class Daylight extends Timezone
{
  public const NAME = 'DAYLIGHT';

  protected function validateProperty(Property $property): void
  {
    // TODO: Implement validateProperty() method.
  }
}
