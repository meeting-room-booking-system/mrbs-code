<?php
declare(strict_types=1);
namespace MRBS\Errors\Handler;

use Monolog\Handler\MailHandler;
use Monolog\Logger;
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
    // TODO: Implement send() method.
  }

}
