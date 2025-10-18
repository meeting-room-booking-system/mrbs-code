<?php
declare(strict_types=1);
namespace MRBS\Errors\Log\Processor;

use Monolog\Processor\ProcessorInterface;

class StacktraceProcessor implements ProcessorInterface
{
  private $ignore_args;


  public function __construct(bool $ignore_args = false)
  {
    $this->ignore_args = $ignore_args;
  }


  public function __invoke(array $record): array
  {
    $trace = $this->generateBacktrace();

    $record['extra'] = array_merge(
      $record['extra'],
      [
        'trace' => $trace
      ]
    );

    return $record;
  }


  // Generate a backtrace.  This function allows us to format the output slightly better
  // than debug_print_backtrace().
  private function generateBacktrace() : array
  {
    $result = [];

    $options = DEBUG_BACKTRACE_PROVIDE_OBJECT;
    if ($this->ignore_args)
    {
      $options = $options | DEBUG_BACKTRACE_IGNORE_ARGS;
    }
    $calls = debug_backtrace($options);

    foreach ($calls as $i => $call)
    {
      $trace = "#$i ";

      if (isset($call['class']) && isset($call['type']))
      {
        $trace .= $call['class'] . $call['type'];
      }

      if (isset($call['function']))
      {
        $trace .= $call['function'];
        $trace .= '(';
        // We're not interested in the args for the first two calls because they
        // are just going to repeat the error message
        if (isset($call['args']) && ($i > 1))
        {
          $trace .= $this->getArgString($call['args']);
        }
        $trace .= ')';
      }

      if (isset($call['file']) && isset($call['line']))
      {
        $trace .= ' called at [' . $call['file'] . ':' . $call['line'] . ']';
      }

      $result[] = $trace;
    }

    return $result;
  }


  private function getArgString(array $args) : string
  {
    $result = array();

    foreach ($args as $arg)
    {
      $type = gettype($arg);

      switch ($type)
      {
        case 'boolean':
          $result[] = ($arg) ? 'true' : 'false';
          break;

        case 'integer':
        case 'double':
        case 'string':
          $result[] = $arg;
          break;

        case 'object':
          $class = get_class($arg);
          $result[] = ($class == 'SensitiveParameterValue') ? "[$class]" : $type;
          break;

        default:
          $result[] = $type;
          break;
      }
    }

    return implode(', ', $result);
  }

}


