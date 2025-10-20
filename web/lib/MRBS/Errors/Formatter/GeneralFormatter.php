<?php
declare(strict_types=1);
namespace MRBS\Errors\Formatter;

use Monolog\Formatter\NormalizerFormatter;
use function MRBS\escape_html;

abstract class GeneralFormatter extends NormalizerFormatter
{
  private $useHTML;

  public function __construct(?string $dateFormat = null, $useHTML=false)
  {
    $this->useHTML = $useHTML;
    parent::__construct($dateFormat);
  }


  public function format(array $record): string
  {
    $lines = [];

    if (!isset($record['context']['details']))
    {
      // This will be when the logger is called directly from the MRBS code, rather than the Errors class.
      $record['context']['details'] = $record['channel'] . '.' .$record['level_name'] . ' in ' . $record['extra']['file'] . ' at line ' . $record['extra']['line'];
    }

    $lines[] = $this->escape($record['context']['details']);
    $lines[] = $this->escape($record['message']);

    // Add in any stacktrace
    if (!empty($record['context']['backtrace']))
    {
      foreach ($record['context']['backtrace'] as $call)
      {
        $lines[] = $this->escape($call);
      }
    }

    // Add in the GET and POST variables
    foreach(['$_GET' => 'get', '$_POST' => 'post'] as $name => $var)
    {
      if (isset($record['context'][$var]))
      {
        $line = $this->escape(print_r($record['context'][$var], true));
        if ($this->useHTML)
        {
          // Replace spaces with non-breaking spaces
          $line = str_replace(' ', '&nbsp;', $line);
        }
        // Remove the final new line
        $line = rtrim($line);
        if ($this->useHTML)
        {
          $line = str_replace("\n", "<br>\n", $line);
        }
        $lines[] = "$name: $line";
      }
    }

    if ($this->useHTML)
    {
      // Make the first line bold.
      $lines[0] = '<b>' . $lines[0] . '</b>';
    }

    $result = implode(($this->useHTML) ? "<br>\n" : "\n", $lines);

    if ($this->useHTML)
    {
      // Wrap it in a paragraph
      $result = "<p>\n" . $result . "\n</p>\n";
    }

    return $result;
  }


  private function escape(string $value): string
  {
    return ($this->useHTML) ? escape_html($value) : $value;
  }
}
