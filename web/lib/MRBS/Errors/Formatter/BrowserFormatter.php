<?php
declare(strict_types=1);
namespace MRBS\Errors\Formatter;

use Monolog\Formatter\NormalizerFormatter;
use MRBS\Exception;
use function MRBS\escape_html;

class BrowserFormatter extends NormalizerFormatter
{
  public function format(array $record): string
  {
    $lines = [];
    $lines[] = '<b>' . escape_html($record['context']['details']) . '</b>';
    $lines[] = escape_html($record['message']);

    // Add in any stacktrace
    if (!empty($record['context']['backtrace']))
    {
      foreach ($record['context']['backtrace'] as $call)
      {
        $lines[] = escape_html($call);
      }
    }

    // Add in the GET and POST variables
    foreach(['$_GET' => 'get', '$_POST' => 'post'] as $name => $var)
    {
      if (isset($record['context'][$var]))
      {
        $line = print_r($record['context'][$var], true);
        // Escape the text and then replace spaces with non-breaking spaces
        $line = str_replace(' ', '&nbsp;', (escape_html($line)));
        // Remove the final new line
        $line = rtrim($line);
        $lines[] = "$name: " . str_replace("\n", "<br>\n", $line);
      }
    }

    return "<p>\n" . implode("<br>\n", $lines) . "<p>\n";
  }

}
