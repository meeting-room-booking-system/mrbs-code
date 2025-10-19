<?php
declare(strict_types=1);
namespace MRBS\Errors\Formatter;

use Monolog\Formatter\NormalizerFormatter;

class ErrorLogFormatter extends NormalizerFormatter
{
  public function format(array $record): string
  {
    $lines = [];
    if (!isset($record['context']['details']))
    {
      // This will be when the logger is called directly from the MRBS code, rather than the Errors class.
      $record['context']['details'] = $record['channel'] . '.' .$record['level_name'] . ' in ' . $record['extra']['file'] . ' at line ' . $record['extra']['line'];
    }
    $lines[] = $record['context']['details'];
    $lines[] = $record['message'];

    // Add in any stacktrace
    if (!empty($record['context']['backtrace']))
    {
      foreach ($record['context']['backtrace'] as $call)
      {
        $lines[] = $call;
      }
    }

    // Add in the GET and POST variables
    foreach(['$_GET' => 'get', '$_POST' => 'post'] as $name => $var)
    {
      if (isset($record['context'][$var]))
      {
        $line = print_r($record['context'][$var], true);
        // Remove the final new line
        $line = rtrim($line);
        $lines[] = $line;
      }
    }

    return implode("\n", $lines);
  }
}
