<?php
declare(strict_types=1);
namespace MRBS;

use Email\Parse;
use PHPMailer\PHPMailer\Exception;
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


  // Sets a series of To addresses given an RFC822 address string
  public function addAddressesRFC822(string $address_string) : bool
  {
    $parser = new Parse(new Logger());
    $parsed_addresses = $parser->parse($address_string);

    foreach ($parsed_addresses['email_addresses'] as $parsed_address)
    {
      if ($parsed_address['invalid'])
      {
        $error_message = "Invalid email address '" . $parsed_address['original_address'] . "': " . $parsed_address['invalid_reason'];
        $this->setError($error_message);
        $this->edebug($error_message);
        if ($this->exceptions) {
          throw new Exception($error_message);
        }
        return false;
      }
      $address = $parsed_address['local_part_parsed'] . '@' . $parsed_address['domain_part'];
      $name = $parsed_address['name_parsed'];
      if (false === $this->addAddress($address, $name))
      {
        return false;
      }
    }

    return true;
  }


  // Sets a From address taking an RFC822 address
  public function setFromRFC822(string $address, bool $auto=true) : bool
  {
    try
    {
      $parser = new Parse(new Logger());
      $parsed_address = $parser->parse($address, false);
    }
    catch (\Exception $e)
    {
      // You can get errors of the sort "Email\Parse->parse - corruption during parsing - leftovers:" for a simple
      // address such as 'kjh'.
      $parsed_address = [
        'original_address' => $address,
        'invalid' => true,
        'invalid_reason' => $e->getMessage()
      ];
    }

    if ($parsed_address['invalid'])
    {
      $error_message = "Invalid email address '" . $parsed_address['original_address'] . "': " . $parsed_address['invalid_reason'];
      $this->setError($error_message);
      $this->edebug($error_message);
      if ($this->exceptions) {
        throw new Exception($error_message);
      }
      return false;
    }

    $address = $parsed_address['local_part_parsed'] . '@' . $parsed_address['domain_part'];
    $name = $parsed_address['name_parsed'];

    return $this->setFrom($address, $name, $auto);
  }

}
