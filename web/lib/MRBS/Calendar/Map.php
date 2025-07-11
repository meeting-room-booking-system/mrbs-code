<?php
declare(strict_types=1);
namespace MRBS\Calendar;

// A class for building a map of bookings which can be used for constructing the calendar display
use MRBS\DateTime;
use MRBS\Exception;
use function MRBS\auth;
use function MRBS\get_start_first_slot;
use function MRBS\get_start_last_slot;
use function MRBS\get_vocab;
use function MRBS\getWritable;
use function MRBS\is_private_event;
use function MRBS\nominal_seconds;
use function MRBS\round_t_down;
use function MRBS\round_t_up;
use function MRBS\session;

class Map
{
  private $start_date;
  private $end_date;
  private $resolution;
  private $data_has_been_coalesced = false;

  // $data is a column of the map of the screen that will be displayed, and is an array indexed
  // by the room_id, then day, then number of nominal seconds (ie ignoring DST changes) since the
  // start of the calendar day which has the start of the booking day.  Each element of the array
  // consists of an array of entries that fall in that slot.
  private $data = [];

  // $entries is an array, indexed by entry id, storing the entries that we are using for this map.
  // Instead of storing the entry itself in the $data array, which will be many times for entries
  // spanning multiple slots, we just store the entry id to save memory. Normally this wouldn't
  // help as PHP uses copy-on-write, but we want to modify the entries to store extra information
  // relevant to the slot, thus triggering a copy-on-write.  Instead, we store the extra information
  // in an array together with the entry id.
  private $entries = [];

  // Keys for the entry data stored in $data.
  private const ENTRY_ID = 0;
  private const ENTRY_IS_MULTIDAY_START = 1;
  private const ENTRY_IS_MULTIDAY_END = 2;
  private const ENTRY_N_SLOTS = 3;


  public function __construct(DateTime $start_date, DateTime $end_date, int $resolution)
  {
    $this->start_date = $start_date;
    $this->end_date = $end_date;
    $this->resolution = $resolution;
  }


  public function addEntries(array $entries) : void
  {
    $date = clone $this->start_date;
    $d = 0;
    while ($date <= $this->end_date)
    {
      $this_day = $date->getDay();
      $this_month = $date->getMonth();
      $this_year = $date->getYear();

      $start_first_slot = get_start_first_slot($this_month, $this_day, $this_year);
      $start_last_slot = get_start_last_slot($this_month, $this_day, $this_year);

      foreach ($entries as $entry)
      {
        $this->addEntry($entry, $d, $start_first_slot, $start_last_slot);
      }

      $d++;
      $date->modify('+1 day');
    }
  }


  // Add an entry to the map of the bookings being prepared for display.
  //
  //    $entry             a booking from the database
  //    $day_index         the index day of the booking, starting at zero
  //    $start_first_slot  the start of the first slot of the booking day (Unix timestamp)
  //    $start_last_slot   the start of the last slot of the booking day (Unix timestamp)
  private function addEntry(array $entry, int $day_index, int $start_first_slot, int $start_last_slot) : void
  {
    // $entry is expected to have the following keys, when present:
    //       room_id
    //       start_time
    //       end_time
    //       name
    //       repeat_id
    //       id
    //       type
    //       description
    //       create_by
    //       awaiting_approval
    //       private
    //       tentative

    // Normally of course there will only be one entry per slot, but it is possible to have
    // multiple entries per slot if the resolution is increased, the day shifted since the
    // original bookings were made, or if the bookings were made using an older version of MRBS
    // that had faulty conflict detection.  For example, if you previously had a resolution of
    // 1800 seconds, you might have a booking (A) for 1000-1130 and another (B) for 1130-1230.
    // If you then increase the resolution to 3600 seconds, these two bookings
    // will both occupy the 1100-1200 time slot.
    //
    // We also store the following extra information:
    //       is_multiday_start  a boolean indicating if the booking stretches beyond the day start
    //       is_multiday_end    a boolean indicating if the booking stretches beyond the day end
    //       n_slots            the number of slots the booking lasts (tentatively set to 1)

    // s is the number of nominal seconds (ie ignoring DST changes) since the
    // start of the calendar day which has the start of the booking day
    if ($this->data_has_been_coalesced)
    {
      throw new Exception("Map: entries cannot be added after output has started");
    }

    // We're only interested in entries which occur on this day (it's possible
    // for $entry to contain entries for other days)
    if (($entry['start_time'] >= $start_last_slot + $this->resolution) ||
        ($entry['end_time'] <= $start_first_slot))
    {
      return;
    }

    // Fill in the map for this meeting. Start at the meeting start time,
    // or the day start time, whichever is later. End one slot before the
    // meeting end time (since the next slot is for meetings which start then),
    // or at the last slot in the day, whichever is earlier.
    // Time is of the format HHMM without leading zeros.

    // Adjust the starting and ending times so that bookings which don't
    // start or end at a recognised time still appear.
    $start_t = max(round_t_down($entry['start_time'], $this->resolution, $start_first_slot), $start_first_slot);
    $end_t = min(round_t_up($entry['end_time'], $this->resolution, $start_first_slot) - $this->resolution, $start_last_slot);

    // Calculate the times used for indexing - we index by nominal seconds since the start
    // of the calendar day which has the start of the booking day
    $start_s = nominal_seconds($start_t);
    $end_s = nominal_seconds($end_t);

    // Get some additional information about the entry related to the way it displays on the page
    $is_multiday_start = ($entry['start_time'] < $start_first_slot);
    $is_multiday_end = ($entry['end_time'] > ($start_last_slot + $this->resolution));

    // Tentatively assume that this booking occupies 1 slot.  Call coalesce() later to fix it.
    $n_slots = 1;

    for ($s = $start_s; $s <= $end_s; $s += $this->resolution)
    {
      // Add the entry to the array of entries if it's not already there
      if (!isset($this->entries[$entry['id']]))
      {
        $this->entries[$entry['id']] = self::prepareEntry($entry);
      }
      // Store a pointer to this entry, together with the additional data
      $this->data[$entry['room_id']][$day_index][$s][] = [
        self::ENTRY_ID => $entry['id'],
        self::ENTRY_IS_MULTIDAY_START => $is_multiday_start,
        self::ENTRY_IS_MULTIDAY_END => $is_multiday_end,
        self::ENTRY_N_SLOTS => $n_slots
      ];
    }
  }


  // Prepares an entry for display by (a) adding in registration level information
  // and (b) replacing the text in private fields if necessary.
  public static function prepareEntry(array $entry) : array
  {
    global $is_private_field, $show_registration_level, $auth, $kiosk;

    // Add in the registration level details
    if ($show_registration_level && $entry['allow_registration'])
    {
      // Check whether we should be showing the registrants' names
      $show_names = ($auth['show_registrant_names_in_calendar'] && ($entry['n_registered'] > 0));
      if ($show_names && !$auth['show_registrant_names_in_public_calendar'])
      {
        // If we're not allowed to show names in the public calendar, check that the user is logged in
        // and has an access level of at least 1
        $mrbs_user = session()->getCurrentUser();
        $show_names = isset($mrbs_user) && ($mrbs_user->level > 0);
      }
      $names = ($show_names) ? implode(', ', auth()->getRegistrantsDisplayNames($entry)) : '';
      if ($entry['registrant_limit_enabled'])
      {
        $tag = ($show_names) ? 'registration_level_limited_with_names' : 'registration_level_limited';
        $entry['name'] .= get_vocab($tag, $entry['n_registered'], $entry['registrant_limit'], $names);
      }
      else
      {
        $tag = ($show_names) ? 'registration_level_unlimited_with_names' : 'registration_level_unlimited';
        $entry['name'] .= get_vocab($tag, $entry['n_registered'], $names);
      }
    }

    // Check whether the event is private
    if (is_private_event($entry['private']) &&
      ($kiosk || !getWritable($entry['create_by'], $entry['room_id'])))
    {
      $entry['private'] = true;

      foreach (array('name', 'description') as $key)
      {
        if ($is_private_field["entry.$key"])
        {
          $entry[$key] = get_vocab('unavailable');
        }
      }

      if (!empty($is_private_field['entry.type']))
      {
        $entry['type'] = 'private_type';
      }
    }
    else
    {
      $entry['private'] = false;
    }

    return $entry;
  }


  // Returns the entry or entries that should be displayed at slot $s on day $day for room $room_id.
  // Returns an empty array if there is no entry.
  // Should not be called until all the data has been added.
  public function slot(int $room_id, int $day, int $slot) : array
  {
    $result = [];

    if (!$this->data_has_been_coalesced)
    {
      $this->coalesce();
    }

    foreach ($this->data[$room_id][$day][$slot] ?? [] as $entry_data)
    {
      $entry = $this->entries[$entry_data[self::ENTRY_ID]];
      $entry['is_multiday_start'] = $entry_data[self::ENTRY_IS_MULTIDAY_START];
      $entry['is_multiday_end'] = $entry_data[self::ENTRY_IS_MULTIDAY_END];
      $entry['n_slots'] = $entry_data[self::ENTRY_N_SLOTS];
      $result[] = $entry;
    }

    return $result;
  }


  // Coalesces map entries that span consecutive time slots.
  private function coalesce() : void
  {
    // The add() method set n_slots=1 for all map entries.  For each booking in the
    // room that spans multiple consecutive time slots, and that does not have
    // conflicting bookings, the first entry will have its slot count adjusted, and
    // the continuation entries will have their n_slots attribute set to NULL.
    foreach ($this->data as &$room_data)
    {
      foreach ($room_data as &$day_data)
      {
        // Iterate through pairs of consecutive time slots in reverse chronological order
        for (end($day_data); ($s = key($day_data)) !== null; prev($day_data))
        {
          $p = $s - $this->resolution;      // The preceding time slot
          if (isset($day_data[$p]))
          {
            if (count($day_data[$s]) == 1)
            {
              // Single booking for time slot $s. If this event is a continuation
              // of a sole event from time slot $p (the previous slot), then
              // increment the slot count of the same booking in slot $p, and clear
              // out the redundant attributes in slot $s.
              if (count($day_data[$p]) == 1 && $day_data[$p][0][self::ENTRY_ID] == $day_data[$s][0][self::ENTRY_ID])
              {
                $this_booking = &$day_data[$s][0];
                $prev_booking = &$day_data[$p][0];
                $prev_booking[self::ENTRY_N_SLOTS] = 1 + $this_booking[self::ENTRY_N_SLOTS];
                $this_booking[self::ENTRY_N_SLOTS] = null;
              }
            }

            else
            {
              // Multiple bookings for time slot $s. Mark all of them as 1 slot.
              foreach ($day_data[$s] as &$booking)
              {
                $booking[self::ENTRY_N_SLOTS] = 1;
              }
            }
          }
        }
      }
    }

    $this->data_has_been_coalesced = true;
  }

}
