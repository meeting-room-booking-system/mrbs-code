<?php
declare(strict_types=1);
namespace MRBS;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

// A very basic logger
class Logger implements LoggerInterface
{

  public function emergency($message, array $context = array())
  {
    throw new Exception($message);
  }

  public function alert($message, array $context = array())
  {
    throw new Exception($message);
  }

  public function critical($message, array $context = array())
  {
    throw new Exception($message);
  }

  public function error($message, array $context = array())
  {
    throw new Exception($message);
  }

  public function warning($message, array $context = array())
  {
    trigger_error($message, E_USER_WARNING);
  }

  public function notice($message, array $context = array())
  {
    trigger_error($message, E_USER_NOTICE);
  }

  public function info($message, array $context = array())
  {
    trigger_error($message, E_USER_NOTICE);
  }

  public function debug($message, array $context = array())
  {
    trigger_error($message, E_USER_NOTICE);
  }

  public function log($level, $message, array $context = array())
  {
    switch ($level)
    {
      case LogLevel::EMERGENCY:
        $this->emergency($message, $context);
        break;
      case LogLevel::ALERT:
        $this->alert($message, $context);
        break;
      case LogLevel::CRITICAL:
        $this->critical($message, $context);
        break;
      case LogLevel::ERROR:
        $this->error($message, $context);
        break;
      case LogLevel::WARNING:
        $this->warning($message, $context);
        break;
      case LogLevel::NOTICE:
        $this->notice($message, $context);
        break;
      case LogLevel::INFO:
        $this->info($message, $context);
        break;
      case LogLevel::DEBUG:
        $this->debug($message, $context);
        break;
      default:
        throw new \InvalidArgumentException('Unknown log level: ' . $level);
        break;
    }
  }
}
