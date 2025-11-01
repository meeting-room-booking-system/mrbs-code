<?php
declare(strict_types=1);
namespace MRBS\Errors\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\MailHandler;
use Monolog\Logger;
use MRBS\Errors\Formatter\BrowserFormatter;
use MRBS\Errors\Formatter\ErrorLogFormatter;
use MRBS\Errors\Formatter\MailFormatter;
use PHPMailer\PHPMailer\PHPMailer;

class PHPMailerHandler extends MailHandler
{
  private $mailer;


  public function __construct(PHPMailer $mailer, $level = Logger::ERROR, bool $bubble = true)
  {
    parent::__construct($level, $bubble);
    $this->mailer = $mailer;
  }


  protected function send(string $content, array $records): void
  {
    $mailer = $this->buildMessage($content, $records);
    $mailer->send();
  }


  private function buildMessage(string $content, array $records): PHPMailer
  {
    $mailer = clone $this->mailer;

    $record = $records[0];
    if (isset($record['context']['details']))
    {
      $mailer->Subject = $record['context']['details'];
    }
    else
    {
      $mailer->Subject = $record['channel'] . '.' . $record['level_name'];
      if (isset($record['extra']['file']))
      {
        $mailer->Subject .= ' in ' . $record['extra']['file'];
      }
      if (isset($record['extra']['line']))
      {
        $mailer->Subject .= ' at line ' . $record['extra']['line'];
      }
    }
    $mailer->Body = $content;

    return $mailer;
  }


  protected function getDefaultFormatter(): FormatterInterface
  {
    return new ErrorLogFormatter();
  }

}
