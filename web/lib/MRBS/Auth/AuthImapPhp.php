<?php
namespace MRBS\Auth;

/*
 * Authentication scheme that uses IMAP as the source for user
 * authentication. If the PHP version is less than 8.0 it requires
 * you to have the PHP 'imap' extension installed and enabled.
 *
 * To use this authentication scheme set the following
 * things in config.inc.php:
 *
 * $auth["realm"] = "MRBS";    // Or any other string
 * $auth["type"]  = "imap_php";
 *
 * You must also configure at least:
 *
 * $auth["imap_php"]["hostname"] = "mailserver.hostname";
 *
 * You can also specify any of the following options:
 *
 * // Specifies the port number to connect to
 * $auth["imap_php"]["port"] = 993;
 *
 * // Use SSL
 * $auth["imap_php"]["ssl"] = TRUE;
 *
 * // Use TLS
 * $auth["imap_php"]["tls"] = TRUE;
 *
 * // Turn off SSL/TLS certificate validation
 * $auth["imap_php"]["novalidate-cert"] = TRUE;
 *
 * Then, you may configure admin users:
 *
 * $auth["admin"][] = "imapuser1";
 * $auth["admin"][] = "imapuser2";
 */

use MRBS\Exception;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\Exceptions\ImapServerErrorException;

class AuthImapPhp extends Auth
{
  private $canUseWebklex;

  public function __construct()
  {
    assert(
      version_compare(MRBS_MIN_PHP_VERSION, '8.0') < 0,
      'The code below is no longer required.'
    );
    // The imap extension was removed in PHP 8.4 and so we use the Webklex/PHPIMAP library
    // instead. However this requires PHP 8.0 or greater.
    $this->canUseWebklex = (version_compare(PHP_VERSION, '8.0') >= 0);

    if (!$this->canUseWebklex && !function_exists('imap_open'))
    {
      throw new Exception("The imap extension is not installed on this server.");
    }
  }


  /**
   */
  public function validateUser(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
  {
    global $auth;

    // If required, check that the username is from the permitted domain
    if (isset($auth['imap_php']['user_domain']))
    {
      if (!filter_var($user, FILTER_VALIDATE_EMAIL))
      {
        return false;
      }

      list(, $domain) = explode('@', $user);

      if ($domain != $auth['imap_php']['user_domain'])
      {
        return false;
      }
    }

    if (!$this->canUseWebklex)
    {
      return $this->validateUserLegacy($user, $pass);
    }

    $cm = new ClientManager();

    $config = [
      'host'          => $auth['imap_php']['hostname'],
      'username'      => $user,
      'password'      => $pass,
      'protocol'      => 'imap'
    ];

    // The defaults are chosen to be compatible with the legacy behaviour.
    $config['port'] = $auth['imap_php']['port'] ?? 143;
    $config['validate_cert'] = empty($auth['imap_php']['novalidate-cert']);
    if (!empty($auth['imap_php']['ssl']))
    {
      $config['encryption'] = 'ssl';
    }
    elseif (!empty($auth['imap_php']['tls']))
    {
      $config['encryption'] = 'tls';
    }
    else
    {
      $config['encryption'] = '';
    }

    try {
      $client = $cm->make($config);
      $client->connect();  //Connect to the IMAP Server
      return $user;
    }
    catch (ImapServerErrorException | AuthFailedException $e) {
      // Don't do anything with these exceptions: they are normal when
      // authentication fails.
    }
    catch (\Exception $e) {
      // We weren't expecting any other exceptions so trigger an error.
      $message = "Caught exception '" . get_class($e) . "'\n";
      $message .= $e->getMessage() . "\n" . $e->getTraceAsString();
      trigger_error($message, E_USER_WARNING);
    }

    return false;
  }


  private function validateUserLegacy(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
  {
    global $auth;

    $location = '{' . $auth['imap_php']['hostname'];

    if (isset($auth['imap_php']['port']))
    {
      $location .= ':' . $auth['imap_php']['port'];
    }

    $location .= '/imap';

    if (!empty($auth['imap_php']['ssl']))
    {
      $location .= '/ssl';
    }

    if (!empty($auth['imap_php']['tls']))
    {
      $location .= '/tls';
    }

    if (!empty($auth['imap_php']['novalidate-cert']))
    {
      $location .= '/novalidate-cert';
    }

    $location .= '}INBOX';

    $mbox = imap_open($location, $user, $pass);

    if ($mbox !== false)
    {
      imap_close($mbox);
      return $user;
    }

    return false;
  }


  /**
   */
  public function canValidateByEmail() : bool
  {
    return true;
  }


  /**
   */
  public function canValidateByUsername() : bool
  {
    return false;
  }

}
