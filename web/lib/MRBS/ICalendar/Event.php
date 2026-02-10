<?php
declare(strict_types=1);
namespace MRBS\ICalendar;

use DateInterval;
use DateTimeZone;
use MRBS\DateTime;
use MRBS\Exception;
use function MRBS\get_mail_vocab;
use function MRBS\get_period_data;
use function MRBS\get_registrants;
use function MRBS\get_type_vocab;
use function MRBS\parse_addresses;

class Event extends Component
{
  public const NAME = 'VEVENT';

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


  /**
   * Create an array of Event components given the booking data.
   *
   * @param string $method Specifies the calendar method, such as 'CANCEL', which determines the event status.
   * @param array $data The event data, which must include keys for 'room_id', 'room_name' and 'area_name'.
   * @param string|null $tzid The timezone identifier.  If null, DATE-TIME values will be written in UTC format,
   *                         otherwise they will be written in the local timezone format.
   * @param array<string, string>|null $addresses An associative array of attendee addresses indexed by 'to' and 'cc'.
   * @param bool $series Indicates whether the event is part of a recurring series (true) or a standalone event (false).
   *
   * @return Event[]
   * @throws CalendarException
   */
  public static function createFromData(string $method, array $data, ?string $tzid=null, ?array $addresses=null, bool $series=false) : array
  {
    global $ignore_gaps_between_periods;

    // Get the period data for the room so that we know how to handle the start and end times
    list('enable_periods' => $room_enable_periods, 'periods' => $room_periods) = get_period_data($data['room_id']);

    // If it's in "times" mode then it's easy.
    if (!$room_enable_periods)
    {
      return [self::createSingleEventFromData($method, $data, $tzid, $addresses, $series)];
    }

    // Otherwise we need to create a series of sub-events, treating each period as a separate sub-event unless
    // they are consecutive, or we have been told to ignore gaps between periods.

    // But we can't do this if we don't have a timezone identifier.
    if (!isset($tzid))
    {
      throw new CalendarException("Cannot create events in periods mode without a timezone identifier");
    }

    // And we can't do it if the period times haven't been defined.
    if (!$room_periods->hasTimes())
    {
      throw new CalendarException("Cannot create events in periods mode because the period times have not been defined");
    }

    $sub_events = [];
    $start_date = (new DateTime('now', new DateTimeZone($tzid)))->setTimestamp($data['start_time'])->setTime(0, 0);
    $date = clone $start_date;
    $end_date = (new DateTime('now', new DateTimeZone($tzid)))->setTimestamp($data['end_time'])->setTime(0, 0);
    $days_diff = $start_date->diff($end_date)->days;

    // Cycle through the days in the interval
    for ($d = 0; $d <= $days_diff; $d++)
    {
      // Cycle through the periods in the day
      for ($i = 0; $i < $room_periods->count(); $i++)
      {
        $start_timestamp = $room_periods->getStartTimestamp($i, $date);
        // If this period starts before the start of the booking, then skip it.
        if ($start_timestamp < $data['start_time'])
        {
          continue;
        }
        // Get the real start and end times for this period
        if (false === ($this_start = $room_periods->timestampToRealStart($start_timestamp)))
        {
          throw new CalendarException("Cannot convert start time for period '" . $room_periods->name . "' to a real start time");
        }
        if (false === ($this_end = $room_periods->timestampToRealEnd($start_timestamp)))
        {
          throw new CalendarException("Cannot convert end time for period '" . $room_periods->name . "' to a real start time");
        }
        // If we haven't started a sub-event yet, then start one.
        if (!isset($sub_event))
        {
          $sub_event = [$this_start, $this_end];
        }
        // Otherwise check if we have reached the end of the booking, in which case store the sub-event and finish.
        elseif ($start_timestamp >= $data['end_time'])
        {
          $sub_events[] = $sub_event;
          break 2; // Exit both the period and day loops.
        }
        // Otherwise check if there's a gap between this period and the previous one, and we're not ignoring gaps.
        // If so, then store the sub-event and start a new one.
        elseif (($this_start > $sub_event[1]) && !$ignore_gaps_between_periods)
        {
          $sub_events[] = $sub_event;
          $sub_event = [$this_start, $this_end];
        }
        // Otherwise extend the end time of the sub-event.
        else
        {
          $sub_event[1] = $this_end;
        }
      }

      // We've reached the end of the day, so store a new sub-event for the periods so far, if any.
      if (isset($sub_event))
      {
        $sub_events[] = $sub_event;
        unset($sub_event);
      }
      // Move to the next day
      $date->modify('+1 day');
    }

    // Now we've got an array of sub-events, each of which has a start and end time, turn each one into an Event component.
    $result = [];
    foreach ($sub_events as $i => $sub_event)
    {
      list($data['start_time'], $data['end_time']) = $sub_event;
      // We need to give each sub-event that we create a different UID so that calendar programs will treat them as
      // separate events. But only do this if there is more than one sub-event, as this has the advantage of keeping,
      // if possible, the UID in the iCalendar the same as the UID in the database.  Most of the time people will
      // probably just be booking for one period.
      $uid_part = (count($sub_events) > 1) ? $i : null;
      $result[] = self::createSingleEventFromData($method, $data, $tzid, $addresses, $series, $uid_part);
    }

    return $result;
  }


  /**
   * Create a single instance of an Event component given the booking data.
   *
   * @param string $method Specifies the calendar method, such as 'CANCEL', which determines the event status.
   * @param array $data The event data.
   * @param string|null $tzid The timezone identifier.  If null, DATE-TIME values will be written in UTC format,
   *                         otherwise they will be written in the local timezone format.
   * @param array<string, string>|null $addresses An associative array of attendee addresses indexed by 'to' and 'cc'.
   * @param bool $series Indicates whether the event is part of a recurring series (true) or a standalone event (false).
   * @param int|null $uid_part If set, append this value to the UID to create a unique UID for the event.
   */
  private static function createSingleEventFromData(string $method, array $data, ?string $tzid=null, ?array $addresses=null, bool $series=false, ?int $uid_part=null) : self
  {
    global $mail_settings, $default_area_room_delimiter, $standard_fields;
    global $partstat_accepted;

    $event = new Event();
    // REQUIRED properties, but MUST NOT occur more than once
    // UID. Create a unique UID for the event by appending the uid_part to the original UID, if required.
    $uid = $data['ical_uid'];
    if (isset($uid_part))
    {
      $parts = explode('@', $uid, 2);
      $uid = $parts[0] . '-' . $uid_part;
      if (isset($parts[1]))
      {
        $uid .= '@' . $parts[1];
      }
    }
    $event->addProperty(new Property('UID', $uid));
    // DTSTAMP.
    $event->addProperty(Property::createFromTimestamps('DTSTAMP', time()));

    // Optional properties
    $last_modified = empty($data['last_updated']) ? time() : $data['last_updated'];
    $event->addProperty(Property::createFromTimestamps('LAST-MODIFIED', $last_modified));

    // Note: we try and write the event times in the format of a local time with
    // a timezone reference (ie RFC 5545 Form #3).   Only if we can't do that do we
    // fall back to a UTC time (ie RFC 5545 Form #2).
    //
    // The reason for this is that although this is not required by RFC 5545 (see
    // Appendix A.2), its predecessor, RFC 2445, did require it for recurring
    // events and is the standard against which older applications, notably Exchange
    // 2007, are written.   Note also that when using a local timezone format the
    // VTIMEZONE component must be provided in the calendar.  Some
    // applications will work without the VTIMEZONE component, but many follow the
    // standard and do require it.  Here is an extract from RFC 2445:

    // 'When used with a recurrence rule, the "DTSTART" and "DTEND" properties MUST be
    // specified in local time and the appropriate set of "VTIMEZONE" calendar components
    // MUST be included.'

    $event->addProperty(Property::createFromTimestamps('DTSTART', $data['start_time'], $tzid));
    $event->addProperty(Property::createFromTimestamps('DTEND', $data['end_time'], $tzid));

    if ($series)
    {
      $event->addProperty(new Property('RRULE', $data['repeat_rule']->toRFC5545Rule()));
      if (!empty($data['skip_list']))
      {
        $event->addProperty(Property::createFromTimestamps('EXDATE', $data['skip_list'], $tzid));
      }
    }

    $event->addProperty(new Property('SUMMARY', $data['name']));
    if (isset($data['description']))
    {
      $event->addProperty(new Property('DESCRIPTION', $data['description']));
    }
    $event->addProperty(new Property('LOCATION', $data['area_name'] . $default_area_room_delimiter . $data['room_name']));
    $event->addProperty(new Property('SEQUENCE', $data['ical_sequence']));
    // If this is an individual member of a series, then set the recurrence id.
    if (!$series && ($data['entry_type'] != ENTRY_SINGLE))
    {
      $event->addProperty(new Property('RECURRENCE-ID', $data['ical_recur_id']));
    }
    // STATUS: As we can have confirmed and tentative bookings, we will send that information
    // in the Status property, as some calendar apps will use it.  For example, Outlook 2007 will
    // distinguish between tentative and confirmed bookings.  However, having sent it, we need to
    // send a STATUS:CANCELLED on cancellation.  It's not clear from the spec whether this is
    // strictly necessary, but it can do no harm, and there are some apps that seem to need it -
    // for example, Outlook 2003 (but not 2007).
    if ($method === 'CANCEL')
    {
      $status = 'CANCELLED';
    }
    else
    {
      $status = (empty($data['tentative'])) ? 'CONFIRMED' : 'TENTATIVE';
    }
    $event->addProperty(new Property('STATUS', $status));

    /*
    Class is commented out for the moment.  To be useful it probably needs to go
    hand in hand with an ORGANIZER, otherwise people won't be able to see their own
    bookings
    $event->addProperty(new Property('CLASS', ($data['private']) ? 'PRIVATE' : 'PUBLIC'));
    */

    // ORGANIZER
    // The organizer is MRBS.  We don't make the create_by user the organizer because there
    // are some mail systems such as IBM Domino that silently discard the email notification
    // if the organizer's email address is the same as the recipient's - presumably because
    // they assume that the recipient already knows about the event.

    $organizer_addresses = parse_addresses($mail_settings['organizer']);
    if (empty($organizer_addresses))
    {
      // TODO: Review whether the ORGANIZER property is required.
      // RFC 5545 states:
      // "This property MUST be specified in an iCalendar object
      // that specifies a group-scheduled calendar entity.  This property
      // MUST be specified in an iCalendar object that specifies the
      // publication of a calendar user's busy time.  This property MUST
      // NOT be specified in an iCalendar object that specifies only a time
      // zone definition or that defines calendar components that are not
      // group-scheduled components, but are components only on a single
      // user's calendar."
      // Does MRBS count as a user? If so, does this mean that as long as
      // there is at least one ATTENDEE the property MUST be specified?
      $message = "The value '" . $mail_settings['organizer'] . "' supplied for " . '$mail_settings["organizer"]' .
        " is not a valid RFC822-style email address.  Please check your MRBS config file.";
      throw new Exception($message);
    }

    $organizer = $organizer_addresses[0];
    if (isset($organizer['address']) && ($organizer['address'] !== ''))
    {
      if (!isset($organizer['name']) || ($organizer['name'] === ''))
      {
        $organizer['name'] = get_mail_vocab('mrbs');
      }
      $property = new Property('ORGANIZER', 'mailto:' . $organizer['address']);
      $property->addParameter('CN', $organizer['name']);
      $event->addProperty($property);
    }

    // Put the people on the "to" list as required participants and those on the cc
    // list as non-participants.  In theory the email client can then decide whether
    // to enter the booking automatically on the user's calendar - although at the
    // time of writing (Dec 2010) there don't seem to be any that do so!
    if (!empty($addresses))
    {
      $attendees = $addresses;  // take a copy of $addresses as we're going to alter it
      $keys = array('to', 'cc');  // We won't do 'bcc' as they need to stay blind
      foreach ($keys as $key)
      {
        $attendees[$key] = parse_addresses($attendees[$key]);  // convert the list into an array
      }
      foreach ($keys as $key)
      {
        foreach ($attendees[$key] as $attendee)
        {
          if (!empty($attendee))
          {
            switch ($key)
            {
              case 'to':
                $role = "REQ-PARTICIPANT";
                break;
              default:
                if (in_array($attendee, $attendees['to']))
                {
                  // It's possible that an address could appear on more than one
                  // line, in which case we only want to have one ATTENDEE property
                  // for that address and we'll choose the REQ-PARTICIPANT.   (Apart
                  // from two conflicting ATTENDEES not making sense, it also breaks
                  // some applications, eg Apple Mail/iCal)
                  continue 2;  // Move on to the next attendeee
                }
                $role = "NON-PARTICIPANT";
                break;
            }
            $property = new Property('ATTENDEE', 'mailto:' . $attendee['address']);
            // Use the common name if there is one
            if (isset($attendee['name']) && ($attendee['name'] !== ''))
            {
              $property->addParameter('CN', $attendee['name']);
            }
            $property->addParameter('ROLE', $role);
            $property->addParameter('PARTSTAT', ($partstat_accepted) ? 'ACCEPTED' : 'NEEDS-ACTION');
            $event->addProperty($property);
          }
        }
      }
    }

    // MRBS specific properties
    // Type
    $event->addProperty(new Property('X-MRBS-TYPE', get_type_vocab($data['type'])));

    // Registration properties
    if (isset($data['allow_registration']))
    {
      $properties = [
        'allow_registration',
        'registrant_limit',
        'registrant_limit_enabled',
        'registration_opens',
        'registration_opens_enabled',
        'registration_closes',
        'registration_closes_enabled'
      ];
      foreach ($properties as $property)
      {
        $event->addProperty(new Property('X-MRBS-' . strtoupper(str_replace('_', '-', $property)), strval($data[$property])));
      }
      // Registrants (but only for individual entries)
      if (!$series)
      {
        // Get the registrants if they're not already in the data.
        $registrants = $data['registrants'] ?? get_registrants($data['id'], false);
        foreach ($registrants as $registrant)
        {
          // We can't use the ATTENDEE property because its value has to be a URI.
          $property = new Property('X-MRBS-REGISTRANT', $registrant['username']);
          $property->addParameter('X-MRBS-REGISTERED', strval($registrant['registered']));
          $property->addParameter('X-MRBS-CREATE-BY', $registrant['create_by']);
          $event->addProperty($property);
        }
      }
    }

    // Custom fields
    // These fields have already been handled above.
    $already_handled = [
      'last_updated',
      'tentative',
      'area_name',
      'room_name',
      'registrants'
    ];

    // These fields are in the area table and can be ignored.
    $area_table_fields = [
      'approval_enabled',
      'confirmation_enabled'
    ];

    // These are derived fields and can be ignored for the moment.  However we need to do something
    // in the future about 'awaiting_approval' and 'private'.
    // TODO
    $special_fields = [
      'awaiting_approval',
      'duration',
      'dur_units',
      'private',
      'repeat_rule',
      'skip_list'
    ];

    $ignore_fields = array_merge($standard_fields['entry'], $already_handled, $area_table_fields, $special_fields);

    foreach ($data as $key => $value)
    {
      if (!in_array($key, $ignore_fields) && isset($value))
      {
        // Column names are case-insensitive in MySQL, so we can safely convert them to upper-case to comply with
        // the RFC 5545 standard that they are case-insensitive but by convention written in upper-case.  In PostgreSQL
        // column names are also case-insensitive, unless they are quoted, in which case they are case-sensitive.  For
        // PostgreSQL it is therefore recommended to use unquoted column names.
        // Property names can only consist of ALPHA, DIGIT and "-" characters, so we convert "_" to "-".
        $property = new Property('X-MRBS-' . mb_strtoupper(str_replace('_', '-', $key)), $value);
        $event->addProperty($property);
      }
    }

    return $event;
  }


  protected function validateProperty(Property $property) : void
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
  }

}
