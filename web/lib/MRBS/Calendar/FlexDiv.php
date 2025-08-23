<?php
declare(strict_types=1);
namespace MRBS\Calendar;

use function MRBS\escape_html;

class FlexDiv
{
  public $id;

  private $classes = [];
  private $length = 1; // slots
  private $name;


  // Create a new FlexDiv, which either represents a booking with id $id,
  // or free slots
  public function __construct(?int $id)
  {
    if (isset($id))
    {
      $this->id = $id;
    }
    else
    {
      $this->classes = ['free'];
    }
  }


  public function addLength(int $increment) : void
  {
    $this->length += $increment;
  }


  public function getLength() : int
  {
    return $this->length;
  }


  public function setClasses(array $classes) : void
  {
    $this->classes = $classes;
  }


  public function setLength(int $length) : void
  {
    $this->length = $length;
  }


  public function setName(string $name) : void
  {
    $this->name = $name;
  }


  public function html(): string
  {
    // Fix the size of the div at one pixel per slot.  Allow it to grow,
    // but not shrink.
    $html = '<div style="flex: 1 0 ' . $this->getLength() . 'px"';

    if (!empty($this->classes))
    {
      $html .= ' class="' . escape_html(implode(' ', $this->classes)) . '"';
    }

    if (isset($this->name) && ($this->name !== ''))
    {
      $html .= ' title="' . escape_html($this->name) . '"';
    }

    $html .= '>';

    if (isset($this->name) && ($this->name !== ''))
    {
      $html .= escape_html($this->name);
    }

    $html .= '</div>';

    return $html;
  }

}
