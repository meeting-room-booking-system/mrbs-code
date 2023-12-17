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
  private $from = '';
  private $until = '';


  private function __construct(string $text = '', string $from_date = '', string $until_date = '')
  {
    $this->setText($text);
    $this->setFromDate($from_date);
    $this->setUntilDate($until_date);
  }

  private function __clone()
  {
  }

  public function __wakeup()
  {
    // __wakeup() must have public visibility
    throw new \Exception("Cannot un-serialize a singleton.");
  }

  public static function getInstance(string $text = '', string $from_date = '', string $until_date = ''): Message
  {
    if (!isset(self::$instance))
    {
      self::$instance = new self($text, $from_date, $until_date);
    }

    return self::$instance;
  }


  // Loads a message from the database
  public function load() : void
  {
    $sql = "SELECT variable_content
              FROM " . _tbl('variable') . "
             WHERE variable_name='message'
             LIMIT 1";
    $res = db()->query_array($sql);

    $message = (count($res) === 0) ? [] : json_decode($res[0], true);

    $this->setText($message['text'] ?? '');
    $this->setFrom($message['from'] ?? '');
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
        'from' => $this->from,
        'until' => $this->until
      ])
    );
    $sql = db()->syntax_upsert($data, _tbl('variable'), $sql_params, 'id', ['id'], true);

    return (0 < db()->command($sql, $sql_params));
  }


  // Gets the message text
  public function getText() : string
  {
    return $this->text;
  }


  // Gets the message end date
  public function getFromDate() : string
  {
    if ($this->from === '')
    {
      return '';
    }

    if (false === ($date = DateTime::createFromFormat(self::FORMAT_DATETIME, $this->from)))
    {
      return '';
    }

    return $date->format(self::FORMAT_DATE);
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


  // Gets the message start timestamp
  public function getFromTimestamp() : ?int
  {
    if ($this->from === '')
    {
      return null;
    }

    if (false === ($date = DateTime::createFromFormat(self::FORMAT_DATETIME, $this->from)))
    {
      return null;
    }

    return $date->getTimestamp();
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


  // Get the message start time as a string in the user's locale
  public function getFromLocalString() : ?string
  {
    global $datetime_formats;

    $timestamp = $this->getFromTimestamp();

    if (!isset($timestamp))
    {
      return null;
    }

    return datetime_format($datetime_formats['date_and_time'], $timestamp);
  }


  // Get the message end time as a string in the user's locale
  public function getUntilLocalString() : ?string
  {
    global $datetime_formats;

    $timestamp = $this->getUntilTimestamp();

    if (!isset($timestamp))
    {
      return null;
    }

    return datetime_format($datetime_formats['date_and_time'], $timestamp);
  }


  // Sets the message text
  public function setText(string $text) : void
  {
    $this->text = $text;
  }


  // Sets the message end date
  public function setFromDate(string $from_date) : void
  {
    if ($from_date !== '')
    {
      // Set the date to the beginning of the day and save it with a time.
      // This allows a time to be added to the form in the future.
      // Note that the time is without timezone, so will be the time in the
      // timezone of the area that the message will be displayed in.
      $date = DateTime::createFromFormat(self::FORMAT_DATE, $from_date);
      if ($date === false)
      {
        trigger_error("Could not create date from '$from_date'; expecting format '" . self::FORMAT_DATE . "'.", E_USER_WARNING);
        $this->setFrom('');
      }
      else
      {
        $date->setTime(0, 0);
        $this->setFrom($date->format(self::FORMAT_DATETIME));
      }
    }
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


  // Determines whether there is a message that should be displayed, ie
  // the message exists and the expiry date hasn't passed.
  public function hasSomethingToDisplay() : bool
  {
    $text = $this->getText();

    if ($text === '')
    {
      return false;
    }

    $from_timestamp = $this->getFromTimestamp();
    $until_timestamp = $this->getUntilTimestamp();

    // Check to see whether we're in the time interval in which to display the message
    return ((!isset($from_timestamp)  || (time() >= $from_timestamp)) &&
            (!isset($until_timestamp)  || (time() < $until_timestamp)));
  }


  private function setFrom(string $date_time_string) : void
  {
    $this->from = $date_time_string;
  }


  private function setUntil(string $date_time_string) : void
  {
    $this->until = $date_time_string;
  }

}
