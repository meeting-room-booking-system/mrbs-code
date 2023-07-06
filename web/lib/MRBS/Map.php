<?php
declare(strict_types=1);
namespace MRBS;

class Map
{
  private $resolution;
  private $data_has_been_coalesced = false;
  private $output_started = false;

  // $data is a column of the map of the screen that will be displayed, and is an array indexed
  // by the room_id, then day, then number of nominal seconds (ie ignoring DST changes) since the
  // start of the calendar day which has the start of the booking day.  Each element of the array
  // consists of an array of entries that fall in that slot.
  private $data = [];

  public function __construct(int $resolution)
  {
    $this->resolution = $resolution;
  }


  // Add an entry to the map of the bookings being prepared for display.
  //
  //    $entry             a booking from the database
  //    $day               the day of the booking
  //    $start_first_slot  the start of the first slot of the booking day (Unix timestamp)
  //    $start_last_slot   the start of the last slot of the booking day (Unix timestamp)
  public function add(array $entry, int $day, int $start_first_slot, int $start_last_slot) : void
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
    // that had faulty conflict detection.  For example if you previously had a resolution of 1800
    // seconds you might have a booking (A) for 1000-1130 and another (B) for 1130-1230.
    // If you then increase the resolution to 3600 seconds, these two bookings
    // will both occupy the 1100-1200 time slot.
    //
    // Each entry also has the following keys added:
    //       is_multiday_start  a boolean indicating if the booking stretches beyond the day start
    //       is_multiday_end    a boolean indicating if the booking stretches beyond the day end
    //       n_slots            the number of slots the booking lasts (tentatively set to 1)

    // s is the number of nominal seconds (ie ignoring DST changes) since the
    // start of the calendar day which has the start of the booking day

    if ($this->output_started)
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
    $entry['is_multiday_start'] = ($entry['start_time'] < $start_first_slot);
    $entry['is_multiday_end'] = ($entry['end_time'] > ($start_last_slot + $this->resolution));

    // Tentatively assume that this booking occupies 1 slot.  Call map_coalesce_bookings()
    // later to fix it.
    $entry['n_slots'] = 1;

    for ($s = $start_s; $s <= $end_s; $s += $this->resolution)
    {
      $this->data[$entry['room_id']][$day][$s][] = $entry;
    }
  }


  // Returns the entry that should be displayed at slot $s on day $day for room $room_id.
  // Returns an empty array if there is no entry.
  // Should not be called until after all the data has been added
  public function slot(int $room_id, int $day, int $slot) : array
  {
    if (!$this->data_has_been_coalesced)
    {
      $this->coalesce();;
    }

    $this->output_started = true;

    return $this->data[$room_id][$day][$slot] ?? [];
  }


  // Coalesces map entries that span consecutive time slots.
  private function coalesce() : void
  {
    // The map_add_booking() method set n_slots=1 for all map entries.  For each
    // booking in the room that spans multiple consecutive time slots, and that
    // does not have conflicting bookings, the first entry will have its slot
    // count adjusted, and the continuation entries will have their n_slots
    // attribute set to NULL.
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
              // Single booking for time slot $s.  If this event is a continuation
              // of a sole event from time slot $p (the previous slot), then
              // increment the slot count of the same booking in slot $p, and clear
              // out the redundant attributes in slot $s.
              if (count($day_data[$p]) == 1 && $day_data[$p][0]['id'] == $day_data[$s][0]['id'])
              {
                $this_booking = &$day_data[$s][0];
                $prev_booking = &$day_data[$p][0];
                $prev_booking['n_slots'] = 1 + $this_booking['n_slots'];
                $this_booking['n_slots'] = null;
              }
            }

            else
            {
              // Multiple bookings for time slot $s.  Mark all of them as 1 slot.
              foreach ($day_data[$s] as &$booking)
              {
                $booking['n_slots'] = 1;
              }
            }
          }
        }
      }
    }

    $this->data_has_been_coalesced = true;
  }

}
