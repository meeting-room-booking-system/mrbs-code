<?php
declare(strict_types=1);
namespace MRBS;

use Countable;
use Iterator;

class Periods implements Countable, Iterator
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
    $result = new self($area_id);

    $sql = "SELECT id, periods
              FROM " . _tbl('area') . "
             WHERE id=:id";
    $res = db()->query($sql, [':id' => $area_id]);

    while (false !== ($row = $res->next_row_keyed()))
    {
      $periods = json_decode($row['periods'], true);

      // The periods are stored in the database as either:
      //   (a) a simple array of period names (the old way of storing periods which we handle for backwards compatibility); or
      //   (b) an associative array of period names and start/end times.
      if (is_assoc($periods))
      {
        foreach ($periods as $period_name => $times)
        {
          $result->add(new Period(
            $period_name,
            $times['start'],
            $times['end']
          ));
        }
      }
      else
      {
        foreach ($periods as $period_name)
        {
          $result->add(new Period(
            $period_name
          ));
        }
      }
    }

    return $result;
  }


  /**
   * Convert the object to an array suitable for storing in the database.
   */
  public function toDbArray() : array
  {
    $result = [];
    foreach ($this->data as $period)
    {
      $result[$period->name] = ['start' => $period->start, 'end' => $period->end];
    }
    return $result;
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

}
