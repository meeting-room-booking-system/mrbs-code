<?php
declare(strict_types=1);
namespace MRBS;


// A class for managing the optional message that is displayed at the top of the calendar
class Message
{
  private const FORMAT_DATE = 'Y-m-d';
  private const FORMAT_DATETIME = 'Y-m-d\TH:i:s';
  private static $instance = null;
  private $text = '';
  private $until = '';


  private function __construct(string $text = '', string $until_date = '')
  {
    $this->setText($text);
    $this->setUntilDate($until_date);
  }

  private function __clone()
  {
  }

  public function __wakeup()
  {
    // __wakeup() must have public visibility
    throw new \Exception("Cannot unserialize a singleton.");
  }

  public static function getInstance(string $text = '', string $until_date = ''): Message
  {
    if (!isset(self::$instance))
    {
      self::$instance = new self($text, $until_date);
    }

    return self::$instance;
  }


  // Loads a message from the database
  public function load() : void
  {
    $sql = "SELECT variable_content
              FROM " . _tbl('variables') . "
             WHERE variable_name='message'
             LIMIT 1";
    $res = db()->query_array($sql);

    $message = (count($res) === 0) ? [] : json_decode($res[0], true);

    $this->setText($message['text'] ?? '');
    $this->setUntil($message['until'] ?? '');
  }


  // Saves a message to the database
  public function save() : bool
  {
    $sql_params = array();
    $data = array(
      'variable_name' => 'message',
      'variable_content' => json_encode([
        'text' => $this->text,
        'until' => $this->until
      ])
    );
    $sql = db()->syntax_upsert($data, _tbl('variables'), $sql_params, 'id', ['id'], true);

    return (0 < db()->command($sql, $sql_params));
  }


  // Gets the message text
  public function getText() : string
  {
    return $this->text;
  }


  // Gets the message end date
  public function getUntilDate() : string
  {
    if ($this->until === '')
    {
      return '';
    }

    if (false === ($date = DateTime::createFromFormat(self::FORMAT_DATETIME, $this->until)))
    {
      return '';
    }

    return $date->modify('-1 day')->format(self::FORMAT_DATE);
  }


  // Gets the message end timestamp
  public function getUntilTimestamp() : ?int
  {
    if ($this->until === '')
    {
      return null;
    }

    if (false === ($date = DateTime::createFromFormat(self::FORMAT_DATETIME, $this->until)))
    {
      return null;
    }

    return $date->getTimestamp();
  }


  // Sets the message text
  public function setText(string $text) : void
  {
    $this->text = $text;
  }


  // Sets the message end date
  public function setUntilDate(string $until_date) : void
  {
    if ($until_date !== '')
    {
      // Set the date to the beginning of the next day and save it with a time.
      // This allows a time to be added to the form in the future.
      // Note that the time is without timezone, so will be the time in the
      // timezone of the area that the message will be displayed in.
      $date = DateTime::createFromFormat(self::FORMAT_DATE, $until_date);
      if ($date === false)
      {
        trigger_error("Could not create date from '$until_date'; expecting format '" . self::FORMAT_DATE . "'.", E_USER_WARNING);
        $this->setUntil('');
      }
      else
      {
        $date->setTime(0, 0)->modify('+1 day');
        $this->setUntil($date->format(self::FORMAT_DATETIME));
      }
    }

  }

  private function setUntil(string $date_time_string) : void
  {
    $this->until = $date_time_string;
  }

}
