<?php
declare(strict_types=1);
namespace MRBS\Errors;

use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Registry;
use MRBS\Errors\Formatter\BrowserFormatter;
use MRBS\Errors\Formatter\ErrorLogFormatter;
use MRBS\Errors\Handler\PHPMailerHandler;
use MRBS\Exception;
use MRBS\Mailer;
use Psr\Log\LogLevel;
use Throwable;
use function MRBS\escape_html;
use function MRBS\get_charset;
use function MRBS\get_vocab;
use function MRBS\mrbs_default_timezone_set;
use function MRBS\print_footer;
use function MRBS\print_simple_header;

// A class for dealing with errors.
// (Don't call it Error, to avoid confusion with the PHP class \Error.)
class Errors
{

  private const LOG_LEVELS = [
    LogLevel::EMERGENCY,
    LogLevel::ALERT,
    LogLevel::CRITICAL,
    LogLevel::ERROR,
    LogLevel::WARNING,
    LogLevel::NOTICE,
    LogLevel::INFO,
    LogLevel::DEBUG
  ];

  private const MAJOR_LEVELS = [
    LogLevel::EMERGENCY,
    LogLevel::ALERT,
    LogLevel::CRITICAL,
    LogLevel::ERROR,
    LogLevel::WARNING
  ];

  private static $errno_levels = [
    E_ERROR => LogLevel::CRITICAL,
    E_WARNING => LogLevel::WARNING,
    E_NOTICE => LogLevel::NOTICE,
    E_CORE_ERROR => LogLevel::CRITICAL,
    E_CORE_WARNING => LogLevel::WARNING,
    E_COMPILE_ERROR => LogLevel::CRITICAL,
    E_COMPILE_WARNING => LogLevel::WARNING,
    E_DEPRECATED => LogLevel::WARNING,
    E_USER_ERROR => LogLevel::CRITICAL,
    E_USER_WARNING => LogLevel::WARNING,
    E_USER_NOTICE => LogLevel::NOTICE,
    E_USER_DEPRECATED => LogLevel::WARNING,
    E_RECOVERABLE_ERROR => LogLevel::CRITICAL
  ];


  public static function init(): void
  {
    global $debug;

    if ($debug && function_exists('opcache_reset'))
    {
      // Useful for making compile-time errors more obvious
      opcache_reset();
    }

    // Add in the E_STRICT level for old versions of PHP
    assert(version_compare(MRBS_MIN_PHP_VERSION, '8.0.0', '<'), "The if block below can be removed.");
    if (version_compare(phpversion(), '8.0.0', '<'))
    {
      self::$errno_levels[E_STRICT] = LogLevel::WARNING;
    }

    self::setDisplayErrors();
    self::setErrorLog();
    $error_level = self::getErrorLevel();
    error_reporting($error_level);
    self::initLogger();

    set_error_handler([__CLASS__, 'errorHandler'], $error_level);
    set_exception_handler([__CLASS__, 'exceptionHandler']);
    register_shutdown_function([__CLASS__, 'shutdownFunction']);
  }


  // "If the function returns false then the normal error handler continues."
  // (https://www.php.net/manual/en/function.set-error-handler.php)
  public static function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
  {
    // Check to see whether error reporting has been disabled by
    // the error suppression operator (@), because the custom error
    // handler is still called even if errors are suppressed.
    if (!(error_reporting() & $errno))
    {
      return true;
    }

    $details = self::get_error_name($errno) . " in $errfile at line $errline";

    if (!array_key_exists($errno, self::$errno_levels))
    {
      throw new Exception("Cannot find mapping for ERRNO level $errno");
    }

    self::output_error(self::$errno_levels[$errno], $errstr, $details);

    return true;
  }


  // Custom exception handler.  Logs the error and then outputs
  // a fatal error message
  public static function exceptionHandler(Throwable $exception): void
  {
    // Log the exception
    $class = get_class($exception);
    $details = "Uncaught exception '$class' in " . $exception->getFile() . " at line " . $exception->getLine();
    $message = $exception->getMessage();
    self::output_error(LogLevel::CRITICAL, $message, $details, $exception);

    // Then output a fatal error
    $namespace_root = strtok(__NAMESPACE__, '\\');
    switch (get_class($exception))
    {
      case $namespace_root . '\DB\DBExternalException':
        $fatal_message = get_vocab("fatal_db_ext_error");
        break;
      case $namespace_root . '\DB\DBException':
      case 'PDOException':
        $fatal_message = get_vocab("fatal_db_error");
        break;
      default:
        $fatal_message = get_vocab("fatal_error");
        break;
    }

    self::fatalError($fatal_message);
  }


  // Converts an error into an exception
  public static function exceptionThrower(int $errno, string $errstr)
  {
    throw new \Exception($errstr, $errno);
  }


  // Error handler - this is used to display serious errors such as database
  // errors without sending incomplete HTML pages. This is only used for
  // errors which "should never happen", not those caused by bad inputs.
  // Always outputs the bottom of the page and exits.
  public static function fatalError(string $message): void
  {
    print_simple_header();
    echo "<p>\n". escape_html($message) . "</p>\n";
    print_footer();
    exit;
  }


  public static function shutdownFunction() : void
  {
    $error = error_get_last();

    if (isset($error) &&
        (mb_strpos($error['message'], 'iconv()') !== false) &&
        !function_exists('iconv'))
    {
      // Help new admins understand what to do in case the iconv error occurs...
      $message = "MRBS - iconv module not installed. ";
      $details = "The iconv module, which provides PHP support for Unicode, is not " .
                 "installed on your system." .
                 "Unicode gives MRBS the ability to easily support languages other " .
                 "than English. Without Unicode, support for non-English-speaking " .
                 "users will be crippled." .
                 "To fix this error, you need to install and enable the iconv module." .
                 "On a Windows server, enable php_iconv.dll in %windir%\\php.ini, and " .
                 "make sure both %phpdir%\\dlls\\iconv.dll and %phpdir%\\extensions\\php_iconv.dll " .
                 "are in the path. One way to do this is to copy these two files to %windir%." .
                 "On a Unix server, recompile your PHP module with the appropriate option for " .
                 "enabling the iconv extension. Consult your PHP server documentation for " .
                 "more information about enabling iconv support.\n";
      self::output_error(LogLevel::NOTICE, $message, $details);
    }
  }


  private static function setDisplayErrors(): void
  {
    global $debug;

    if ($debug)
    {
      ini_set('display_errors', '1');
      ini_set('display_startup_errors', '1');  // ini_set() only accepts non-string values from PHP 8.1.0
    }
  }


  private static function setErrorLog() : void
  {
    // If the error log file is a relative path then turn it into an absolute one in
    // order to avoid problems in shutdown when the working directory can change.
    // (See the notes in https://www.php.net/manual/en/function.register-shutdown-function.php).
    // Check for both Windows and Unix style separators because Unix separators can be used
    // on Windows.
    $error_log = ini_get('error_log');
    if (($error_log !== '') &&
        (mb_strpos($error_log, '/') === false) &&
        (mb_strpos($error_log, '\\') === false))
    {
      ini_set('error_log', getcwd() . '/' . $error_log);
    }
  }


  private static function getErrorLevel() : int
  {
    global $debug;

    if ($debug)
    {
      return -1;
    }

    // Make sure notice errors are not reported; they can break mrbs code.
    $error_level = E_ALL & ~E_NOTICE & ~E_USER_NOTICE;

    if (defined("E_DEPRECATED"))
    {
      $error_level = $error_level & ~E_DEPRECATED;
    }

    // The Mail and Net libraries generate E_STRICT errors, so disable E_STRICT (which became
    // part of E_ALL in PHP 5.4).  E_STRICT is deprecated from PHP 8.4 (and not used since PHP 7).
    assert(version_compare(MRBS_MIN_PHP_VERSION, '8.0.0', '<'), "The if block below can be removed.");
    if (defined("E_STRICT") && (version_compare(PHP_VERSION, '8.4') < 0))
    {
      $error_level = $error_level & ~E_STRICT;
    }

    return $error_level;
  }


  private static function initLogger() : void
  {
    global $mail_settings, $sendmail_settings, $smtp_settings, $logger_settings;

    $logger = new Logger($logger_settings['channel_name']);
    $logger->pushProcessor(new IntrospectionProcessor());

    if (ini_get('display_errors'))
    {
      $handler = new StreamHandler('php://output');
      $handler->setFormatter(new BrowserFormatter());
      $logger->pushHandler($handler);
      $logger->pushHandler(new BrowserConsoleHandler());
    }

    if (ini_get('log_errors'))
    {
      $handler = new ErrorLogHandler();
      $handler->setFormatter(new ErrorLogFormatter());
      $logger->pushHandler($handler);
    }

    if ($logger_settings['mail']['enabled'])
    {
      $mailer = new Mailer($mail_settings, $sendmail_settings, $smtp_settings, true);
      $mailer->CharSet = get_charset();
      $mailer->setFromRFC822($logger_settings['mail']['from']);
      $mailer->addAddressesRFC822($logger_settings['mail']['to']);
      $handler = new PHPMailerHandler($mailer, $logger_settings['mail']['level']);
      $logger->pushHandler($handler);
    }

    Registry::addLogger($logger);
  }


  // Logs an exception
  private static function output_exception_error(Throwable $exception) : void
  {
    $class = get_class($exception);
    $details = "Uncaught exception '$class' in " . $exception->getFile() . " at line " . $exception->getLine();
    $message = $exception->getMessage();
    self::output_error(LogLevel::CRITICAL, $message, $details, $exception);
  }


  private static function output_error(string $level, string $message, string $details, ?Throwable $e = null) : void
  {
    global $debug, $auth, $get, $post;

    static $default_timezone_set = false;

    // We can't start outputting any error messages unless the default timezone has been set,
    // so if we are not sure that it has been set, then set it.
    if (!$default_timezone_set)
    {
      mrbs_default_timezone_set();
      $default_timezone_set = true;
    }

    if (!in_array($level, self::LOG_LEVELS))
    {
      throw new \InvalidArgumentException("Invalid log level '$level'.");
    }

    $context = [];

    if (in_array($level, self::MAJOR_LEVELS))
    {
      if (isset($get))
      {
        $context['get'] = $get;
      }
      if (isset($post))
      {
        $context['post'] = $post;
        if (!$auth['log_credentials'])
        {
          // Overwrite the username and password to stop them appearing
          // in error logs.
          foreach (array('username', 'password') as $var)
          {
            if (isset($context['post'][$var]) && ($context['post'][$var] !== ''))
            {
              $context['post'][$var] = '****';
            }
          }
        }
      }
    }

    if ($debug || in_array($level, self::MAJOR_LEVELS))
    {
      $backtrace = self::generateBacktrace($e);
      $context['backtrace'] = $backtrace;
    }

    $context['details'] = $details;
    Registry::MRBS()->log($level, $message, $context);
  }


  // Generate a backtrace.  This function allows us to format the output slightly better
  // than debug_print_backtrace().
  private static function generateBacktrace(?Throwable $e = null) : array
  {
    global $debug;

    $result = [];

    // Get the backtrace. If we've been given a throwable then use that to get the
    // trace as it goes further back in the stack.
    if (isset($e))
    {
      $calls = $e->getTrace();
    }
    else
    {
      $options = DEBUG_BACKTRACE_PROVIDE_OBJECT;
      // Unless we are debugging ignore arguments as these can give away
      // database credentials
      if (!$debug)
      {
        $options = $options | DEBUG_BACKTRACE_IGNORE_ARGS;
      }
      $calls = debug_backtrace($options);
    }

    // Get rid of calls on the stack which are just concerned with error handling and logging.
    while (!empty($calls) && isset($calls[0]['class']) && ($calls[0]['class'] === __CLASS__))
    {
      array_shift($calls);
    }

    // Turn each call into a string
    foreach ($calls as $i => $call)
    {
      $trace = "#$i " . self::callToString($call);
      $result[] = $trace;
    }

    return $result;
  }


  private static function callToString(array $call) : string
  {
    $result = '';

    if (isset($call['class']) && isset($call['type']))
    {
      $result .= $call['class'] . $call['type'];
    }

    if (isset($call['function']))
    {
      $result .= $call['function'];
      $result .= '(';
      // Add in the args if required, unless it was trigger_error() that was called
      // because that will just repeat the error message.
      if (isset($call['args']) && ($call['function'] !== 'trigger_error'))
      {
        $result .= self::getArgString($call['args']);
      }
      $result .= ')';
    }

    if (isset($call['file']) && isset($call['line']))
    {
      $result .= ' called at [' . $call['file'] . ':' . $call['line'] . ']';
    }

    return $result;
  }


  private static function getArgString(array $args) : string
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


  // Translate an error constant value into the name of the constant
  private static function get_error_name(int $errno) : string
  {
    $constants = get_defined_constants(true);
    $keys = array_keys($constants['Core'], $errno);
    $keys = array_filter($keys, function($value) {
      return (mb_strpos($value, 'E_') === 0);
    });
    return implode('|', $keys); // There should only be one member of the array, all being well.
  }

}
