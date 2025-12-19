<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

class Timezone extends Component
{
  protected const NAME = 'VTIMEZONE';

  public function validateProperty(Property $property): void
  {
    // TODO: Implement validateProperty() method.
  }
}
