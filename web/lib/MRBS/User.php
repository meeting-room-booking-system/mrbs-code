<?php
namespace MRBS;


// This is a class for a general MRBS user, regardless of the authentication type.  Once authenticated each
// user is converted into a standard MRBS user object with defined properties.  (Do not confuse this user
// with a user in the users table: the 'db' authentication method is just one of many that MRBS supports.)
use PHPMailer\PHPMailer\PHPMailer;

class User
{
  public $username;
  public $display_name;
  public $email;
  public $level;


  public function __construct($username=null)
  {
    $this->username = $username;

    // Set some default properties
    $this->display_name = $username;
    $this->setDefaultEmail();
    $this->level = 0; // Play it safe
  }


  public function load(array $data)
  {
    foreach ($data as $key => $value)
    {
      $this->$key = $value;
    }
  }


  // Returns an RFC 5322 mailbox address, ie an address in the format
  // "Display name <email address>"
  public function mailbox()
  {
    if (!isset($this->email))
    {
      return null;
    }

    if (!isset($this->display_name) || ($this->display_name === ''))
    {
      return $this->email;
    }

    $mailer = new PHPMailer();
    $mailer->CharSet = get_mail_charset();
    return $mailer->addrFormat(array($this->email, $this->display_name));
  }

  // Sets the default email address for the user (null if one can't be found)
  private function setDefaultEmail()
  {
    global $mail_settings;

    if (!isset($this->username) || $this->username === '')
    {
      $this->email = null;
    }
    else
    {
      $this->email = $this->username;

      // Remove the suffix, if there is one
      if (isset($mail_settings['username_suffix']) && ($mail_settings['username_suffix'] !== ''))
      {
        $suffix = $mail_settings['username_suffix'];
        if (substr($this->email, -strlen($suffix)) === $suffix)
        {
          $this->email = substr($this->email, 0, -strlen($suffix));
        }
      }

      // Add on the domain, if there is one
      if (isset($mail_settings['domain']) && ($mail_settings['domain'] !== ''))
      {
        // Trim any leading '@' character. Older versions of MRBS required the '@' character
        // to be included in $mail_settings['domain'], and we still allow this for backwards
        // compatibility.
        $domain = ltrim($mail_settings['domain'], '@');
        $this->email .= '@' . $domain;
      }
    }
  }

}
