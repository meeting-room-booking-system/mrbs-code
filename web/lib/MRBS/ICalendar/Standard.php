<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

class Standard extends Timezone
{
  public const NAME = 'STANDARD';

  protected function validateProperty(Property $property): void
  {
    // TODO: Implement validateProperty() method.
  }
}
