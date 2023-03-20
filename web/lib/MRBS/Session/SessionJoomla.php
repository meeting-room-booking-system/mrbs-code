<?php
namespace MRBS\Session;

use MRBS\JFactory;
use MRBS\User;
use function MRBS\auth;

require_once MRBS_ROOT . '/auth/cms/joomla.inc';


class SessionJoomla extends SessionWithLogin
{

  private const NAMESPACE = 'MRBS';

  private $app;
  private $session;

  public function __construct()
  {
    $this->checkTypeMatchesSession();

    if (!defined('JVERSION'))
    {
      throw new \Exception("Joomla! version not known");
    }

    if (version_compare(JVERSION, '4.0', '<'))
    {
      $this->app = JFactory::getApplication('site');
      $this->app->initialise();
    }
    else
    {
      // Thanks to Alex Chartier and Emmanuel Ingelaere.
      // See https://groups.google.com/g/joomla-dev-general/c/55J2s9hhMxA

      // Boot the DI container
      $container = \Joomla\CMS\Factory::getContainer();

      // Alias the session service keys to the web session service as that is the primary session backend for this application.
      // In addition to aliasing "common" service keys, we also create aliases for the PHP classes to ensure autowiring objects
      // is supported.  This includes aliases for aliased class names, and the keys for aliased class names should be considered
      // deprecated to be removed when the class name alias is removed as well.
      $container->alias('session.web', 'session.web.site')
                ->alias('session', 'session.web.site')
                ->alias('JSession', 'session.web.site')
                ->alias(\Joomla\CMS\Session\Session::class, 'session.web.site')
                ->alias(\Joomla\Session\Session::class, 'session.web.site')
                ->alias(\Joomla\Session\SessionInterface::class, 'session.web.site');

      // Instantiate the application.
      $this->app = $container->get(\Joomla\CMS\Application\SiteApplication::class);

      // Set the application as global app
      \Joomla\CMS\Factory::$application = $this->app;
    }

    $this->session = JFactory::getSession();

    parent::__construct();
  }


  public function init(int $lifetime) : void
  {
  }


  public function get(string $name)
  {
    return $this->session->get($name, null, self::NAMESPACE);
  }


  public function isset(string $name) : bool
  {
    return ($this->get($name) !== null);
  }

  public function set(string $name, $value) : void
  {
    $this->session->set($name, $value, self::NAMESPACE);
  }


  public function unset(string $name) : void
  {
    $this->session->clear($name, self::NAMESPACE);
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


  public function logoffUser() : void
  {
    $this->app->logout();
  }
}
