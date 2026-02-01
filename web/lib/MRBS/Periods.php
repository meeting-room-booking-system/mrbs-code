<?php
declare(strict_types=1);
namespace MRBS;

class Periods implements \Countable, \Iterator
{
  private $area_id;
  private $index = 0;
  private static $data;


  private function __construct(int $area_id)
  {
    $this->area_id = $area_id;
    if (!isset(self::$data[$area_id]))
    {
      self::$data[$area_id] = [];
    }
  }


  /**
   * Get the periods for a given area.
   *
   * @return false|self
   */
  public static function getForArea(int $area_id)
  {
    if (!isset(self::$data))
    {
      self::$data = [];
      $sql = "SELECT id, periods
                FROM " . _tbl('area');
      $res = db()->query($sql);

      while (false !== ($row = $res->next_row_keyed()))
      {
        $this_area_periods = [];
        $periods = json_decode($row['periods'], true);

        // The periods are stored in the database as either:
        //   (a) a simple array of period names (the old way of storing periods); or
        //   (b) an associative array of period names and start/end times.
        if (is_assoc($periods))
        {
          foreach ($periods as $period_name => $times)
          {
            $this_area_periods[] = new Period(
              $period_name,
              $times['start'],
              $times['end']
            );
          }
        }
        else
        {
          foreach ($periods as $period_name)
          {
            $this_area_periods[] = new Period(
              $period_name
            );
          }
        }

        self::$data[$row['id']] = $this_area_periods;
      }
    }

    if (!isset(self::$data[$area_id]))
    {
      return false;
    }

    return new self($area_id);
  }


  public function current() : Period
  {
    return self::$data[$this->area_id][$this->index];
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
    return isset(self::$data[$this->area_id][$this->index]);
  }

  public function rewind() : void
  {
    $this->index = 0;
  }

  public function count() : int
  {
    return count(self::$data[$this->area_id]);
  }

}
