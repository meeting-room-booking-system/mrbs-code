<?php
declare(strict_types=1);
namespace MRBS;

use Countable;
use SeekableIterator;

class Periods implements Countable, SeekableIterator
{
  private $area_id;
  private $index = 0;
  private $data = [];


  public function __construct(int $area_id)
  {
    $this->area_id = $area_id;
  }


  /**
   * Get the periods for a given area from the database.
   */
  public static function getForArea(int $area_id) : self
  {
    static $result = [];  // Cache for performance

    if (!isset($result[$area_id]))
    {
      $sql = "SELECT periods
                FROM " . _tbl('area') . "
               WHERE id=:id
               LIMIT 1";
      $res = db()->query($sql, [':id' => $area_id]);

      if (false !== ($row = $res->next_row_keyed()))
      {
        $result[$area_id] = self::fromDbValue($area_id, $row['periods']);
      }
    }

    return clone $result[$area_id];
  }


  /**
   * Convert a periods value as stored in the database to an instance of this class.
   */
  public static function fromDbValue(int $area_id, string $value) : self
  {
    $result = new self($area_id);

    $array = json_decode($value, true);
    // The periods are stored in the database as either:
    //   (a) a simple array of period names (the old way of storing periods
    //       which we handle for backwards compatibility); or
    //   (b) an associative array of period names and start/end times.
    if (is_assoc($array))
    {
      foreach ($array as $period_name => $times)
      {
        $result->add(new Period($period_name, $times[0], $times[1]));
      }
    }
    else
    {
      foreach ($array as $period_name)
      {
        $result->add(new Period($period_name));
      }
    }

    return $result;
  }


  /**
   * Convert the object to a value suitable for storing in the database.
   */
  public function toDbValue() : string
  {
    $result = [];
    foreach ($this->data as $period)
    {
      $result[$period->name] = [$period->start, $period->end];
    }
    return json_encode($result);
  }


  /**
   * Validate the periods, checking that the start and end times are valid and in ascending order.
   *
   * @return true|string
   */
  public function validate()
  {
    foreach ($this->data as $i => $period)
    {
      // If we're not using times, everything is OK.
      if (($i === 0) && (!isset($period->start)))
      {
        return true;
      }

      // Otherwise check that the start and end times are valid and are in ascending order.
      try
      {
        if (false === ($start = DateTime::createFromFormat('H:i', $period->start)))
        {
          return get_vocab('invalid_period_start_time', $period->start, $period->name);
        }
        if (false === ($end = DateTime::createFromFormat('H:i', $period->end)))
        {
          return get_vocab('invalid_period_end_time', $period->end, $period->name);
        }
        if (isset($last_end_time) && ($start < $last_end_time))
        {
          return get_vocab('period_start_before_last_end', $period->name);
        }
        if ($start >= $end)
        {
          return get_vocab('period_must_have_positive_duration', $period->name);
        }
        $last_end_time = $end;
      }
      catch (\Exception $e)
      {
        return get_vocab('invalid_period_time', $period->name);
      }
    }

    return true;
  }


  public function add(Period $period) : void
  {
    $this->data[] = $period;
  }

  public function current() : Period
  {
    return $this->data[$this->index];
  }

  public function next() : void
  {
    $this->index++;
  }

  public function key(): int
  {
    return $this->index;
  }

  public function valid(): bool
  {
    return isset($this->data[$this->index]);
  }

  public function rewind() : void
  {
    $this->index = 0;
  }

  public function count() : int
  {
    return count($this->data);
  }

  public function seek($offset) : void
  {
    if ($offset < 0 || $offset >= $this->count())
    {
      throw new \OutOfBoundsException("Invalid offset $offset");
    }
    $this->index = $offset;
  }

  /**
   * Get a period by its index. Note that the iterator key is unchanged.
   */
  public function offsetGet($offset) : Period
  {
    if ($offset < 0 || $offset >= $this->count())
    {
      throw new \OutOfBoundsException("Invalid offset $offset");
    }
    return $this->data[$offset];
  }

  /**
   * Get a period by its nominal start time in seconds. Note that the
   * iterator key is unchanged.
   */
  public function offsetGetByNominalSeconds(int $seconds) : Period
  {
    return $this->offsetGet(self::nominalSecondsToIndex($seconds));
  }

  /**
   * Converts a period nominal start time in seconds to an index into the $period array.
   */
  private static function nominalSecondsToIndex(int $seconds) : int
  {
    // Periods are counted as minutes from noon, ie 1200 is $period[0],
    // 1201 $period[1], etc.
    return intval($seconds/60) - (12*60);
  }
}
