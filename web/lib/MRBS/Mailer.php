<?php
declare(strict_types=1);
namespace MRBS;

use PHPMailer\PHPMailer\PHPMailer;

class Mailer extends PHPMailer
{

  public function __construct(array $mail_settings, array $sendmail_settings, array $smtp_settings, ?bool $exceptions = null)
  {
    parent::__construct($exceptions);

    switch ($mail_settings['admin_backend'])
    {
      case 'mail':
        $this->isMail();
        break;

      case 'qmail':
        $this->isQmail();
        if (isset($mail_settings['qmail']['qmail-inject-path']))
        {
          $this->Sendmail = $mail_settings['qmail']['qmail-inject-path'];
        }
        break;

      case 'sendmail':
        $this->isSendmail();
        $this->Sendmail = $sendmail_settings['path'];
        if (isset($sendmail_settings['args']) && ($sendmail_settings['args'] !== ''))
        {
          $this->Sendmail .= ' ' . $sendmail_settings['args'];
        }
        break;

      case 'smtp':
        $this->isSMTP();
        $this->Host = $smtp_settings['host'];
        $this->Port = $smtp_settings['port'];
        $this->SMTPAuth = $smtp_settings['auth'];
        $this->SMTPSecure = $smtp_settings['secure'];
        $this->Username = $smtp_settings['username'];
        $this->Password = $smtp_settings['password'];
        $this->Hostname = $smtp_settings['hostname'];
        $this->Helo = $smtp_settings['helo'];
        if ($smtp_settings['disable_opportunistic_tls'])
        {
          $this->SMTPAutoTLS = false;
        }
        $this->SMTPOptions = array
        (
          'ssl' => array
          (
            'verify_peer' => $smtp_settings['ssl_verify_peer'],
            'verify_peer_name' => $smtp_settings['ssl_verify_peer_name'],
            'allow_self_signed' => $smtp_settings['ssl_allow_self_signed']
          )
        );
        break;

      default:
        $this->isMail();
        trigger_error("Unknown mail backend '" . $mail_settings['admin_backend'] . "'." .
          " Defaulting to 'mail'.",
          E_USER_WARNING);
        break;
    }
  }

}
