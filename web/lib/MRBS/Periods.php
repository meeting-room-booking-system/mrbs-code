<?php
declare(strict_types=1);
namespace MRBS;

class Periods
{
  private $area_id;
  private static $data;

  public static function getForArea(int $area_id) : self
  {
    if (!isset($data))
    {
      self::$data = [];
      $sql = "SELECT id, periods, timezone
                FROM " . _tbl('area');
      $res = db()->query($sql);
    }
  }
}
