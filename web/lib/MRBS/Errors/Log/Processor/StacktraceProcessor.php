<?php
declare(strict_types=1);
namespace MRBS\Errors\Log\Processor;

use Monolog\Processor\ProcessorInterface;
use function MRBS\str_ends_with_array;

class StacktraceProcessor implements ProcessorInterface
{
  private $ignore_args;
  private $skip_files;


  // $skip_classes is an array of classes (usually those concerned with error logging) to
  // leave out from the stack trace presented to the user.
  public function __construct(bool $ignore_args = false, array $skip_classes = [])
  {
    $this->ignore_args = $ignore_args;
    // Add this class to the list of classes to skip.
    $skip_classes[] = __CLASS__;
    // Turn these into filenames.
    $this->skip_files = array_map(function($value) {return "$value.php";}, $skip_classes);
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

    $record['extra']['calls'] = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);
    $record['extra']['skip'] = $this->skip_files;
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

    // Get rid of the calls on the stack which are just concerned with error handling and logging.  Note that
    // the error handler doesn't have a file.
    while (!empty($calls) && isset($calls[0]['file']) && str_ends_with_array($calls[0]['file'], $this->skip_files))
    {
      array_shift($calls);
    }


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
        // Add in the arguments, of required
        if (isset($call['args']) && ($i > -1))
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
    $result = [];

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


