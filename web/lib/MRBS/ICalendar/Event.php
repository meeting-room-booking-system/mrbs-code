<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

class Event extends Component
{
  /**
   * The following are REQUIRED, but MUST NOT occur more than once.
   */
  private const REQUIRED_PROPERTIES_ONCE_ONLY = ['DTSTAMP', 'UID'];

  /**
   * The following is REQUIRED if the component appears in an iCalendar object that doesn't specify the
   * "METHOD" property; otherwise, it is OPTIONAL; in any case, it MUST NOT occur more than once.
   */
  private const SPECIAL_PROPERTIES_ONCE_ONLY = ['DTSTART'];

  /**
   * The following are OPTIONAL, but MUST NOT occur more than once.
   */
  private const OPTIONAL_PROPERTIES_ONCE_ONLY = [
    'CLASS',
    'CREATED',
    'DESCRIPTION',
    'GEO',
    'LAST-MODIFIED',
    'LOCATION',
    'ORGANIZER',
    'PRIORITY',
    'SEQUENCE',
    'STATUS',
    'SUMMARY',
    'TRANSP',
    'URL',
    'RECURRENCE-ID'
  ];

  /**
   * The following is OPTIONAL, but SHOULD NOT occur more than once.
   */
  private const OPTIONAL_PROPERTIES_ONCE_ONLY_SHOULD = [
    'RRULE'
  ];

  /**
   * Either 'dtend' or 'duration' MAY appear in a 'eventprop', but 'dtend' and 'duration' MUST NOT occur in
   * the same 'eventprop'.
   */
  private const OPTIONAL_PROPERTIES_MUTUALLY_EXCLUSIVE = [
    'DTEND',
    'DURATION'
  ];

  private const OPTIONAL_PROPERTIES = [
    'ATTACH',
    'ATTENDEE',
    'CATEGORIES',
    'COMMENT',
    'CONTACT',
    'EXDATE',
    'REQUEST-STATUS',
    'RELATED-TO',
    'RESOURCES',
    'RDATE'
  ];

  private $property_names = [];


  public function __construct()
  {
    $this->name = 'VEVENT';
  }


  public function addProperty(Property $property) : void
  {
    $name = $property->getName();

    $once_only_properties = array_merge(
      self::REQUIRED_PROPERTIES_ONCE_ONLY,
      self::OPTIONAL_PROPERTIES_ONCE_ONLY,
      self::SPECIAL_PROPERTIES_ONCE_ONLY,
      self::OPTIONAL_PROPERTIES_MUTUALLY_EXCLUSIVE
    );

    $valid_properties = array_merge(
      $once_only_properties,
      self::OPTIONAL_PROPERTIES_ONCE_ONLY_SHOULD,
      self::OPTIONAL_PROPERTIES
    );

    // Check that the property is valid for an event
    if (!in_array($name, $valid_properties, true) && !str_starts_with($name, 'X-'))
    {
      throw new RFC5545Exception("Property '$name' is not valid for an event");
    }

    // Check that the property is not set more than once if it's in the set that can only be set once.
    if (in_array($name, $once_only_properties, true) && in_array($name, $this->property_names, true))
    {
      throw new RFC5545Exception("Property '$name' can only be set once");
    }

    // Check that we'll only have one of the mutually exclusive properties.
    if (in_array($name, self::OPTIONAL_PROPERTIES_MUTUALLY_EXCLUSIVE, true) &&
        in_array($name, array_intersect($this->property_names, self::OPTIONAL_PROPERTIES_MUTUALLY_EXCLUSIVE), true))
    {
      throw new RFC5545Exception("Only one of the following properties may be set: " . implode(', ', self::OPTIONAL_PROPERTIES_MUTUALLY_EXCLUSIVE));
    }

    // Check that we're not using a property twice that is not recommended to be used twice.
    if (in_array($name, self::OPTIONAL_PROPERTIES_ONCE_ONLY_SHOULD, true) &&
        in_array($name, $this->property_names, true))
    {
      trigger_error("Property '$name' is recommended to be set only once", E_USER_WARNING);
    }

    parent::addProperty($property);
  }

}
