<?php
declare(strict_types=1);
namespace MRBS\Calendar;

abstract class Calendar
{
  protected $day;
  protected $month;
  protected $year;
  protected $area_id;
  protected $room_id;
  protected $view;
  protected $view_all;


  abstract public function innerHTML() : string;
}
