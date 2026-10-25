<?php
declare(strict_types=1);
namespace MRBS\Session;

use MRBS\Cms\Joomla\Joomla;
use MRBS\User;
use function MRBS\auth;


class SessionJoomla extends SessionWithLogin
{

  private const NAMESPACE = 'MRBS';

  private $joomla;

  public function __construct()
  {
    $this->checkTypeMatchesSession();

    $this->joomla = Joomla::getInstance();

    parent::__construct();
  }


  public function init(int $lifetime) : void
  {
  }


  public function get(string $name)
  {
    return $this->joomla->session()->get($name, null, self::NAMESPACE);
  }


  public function isset(string $name) : bool
  {
    return ($this->get($name) !== null);
  }

  public function set(string $name, $value) : void
  {
    $this->joomla->session()->set($name, $value, self::NAMESPACE);
  }


  public function unset(string $name) : void
  {
    $this->joomla->session()->clear($name, self::NAMESPACE);
  }


  public function getCurrentUser() : ?User
  {
    return auth()->getCurrentUser() ?? parent::getCurrentUser();
  }


  protected function logonUser(string $username) : void
  {
    // Don't need to do anything: the user will have been logged on when the
    // username and password were validated.
  }


  public function logoffUser(?string $redirect_url = null) : void
  {
    // Joomla destroys the session on logout.  We need to preserve the kiosk session variables, so
    // get them before the logout and re-set them afterwards.
    $kiosk_vars = ['kiosk_password_hash', 'kiosk_url'];
    foreach ($kiosk_vars as $var)
    {
      $$var = $this->get($var);
    }

    // Log out the Joomla user
    $this->joomla->app()->logout();

    // Restore the kiosk variables
    foreach ($kiosk_vars as $var)
    {
      if (isset($$var))
      {
        $this->set($var, $$var);
      }
    }

    parent::logoffUser($redirect_url);
  }
}
