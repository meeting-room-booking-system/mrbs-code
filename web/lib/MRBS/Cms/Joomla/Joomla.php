<?php
declare(strict_types=1);
namespace MRBS\Cms\Joomla;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;

/**
 * A Helper class for Joomla! that allows the initialisation of Joomla! to be postponed
 * until the last moment.
 */
class Joomla
{
  private static $instance;
  private $is_started;
  private $app;
  private $mainframe;
  private $session;


  private function __construct()
  {
    // We load the Joomla files now because of the problem with the incompatible LoggerInterface
    // declarations between MRBS and Joomla, but we delay starting Joomla until later.
    $this->load();
  }


  private function __clone()
  {
  }


  public function __unserialize(array $data) : void
  {
    // __unserialize() must have public visibility
    throw new \Exception("Cannot unserialize a singleton.");
  }


  // __wakeup() is deprecated from PHP 8.5.
  // "The __wakeup() serialization magic method has been deprecated. Implement __unserialize()
  // instead (or in addition, if support for old PHP versions is necessary)".
  // __unserialize() is only available from PHP 7.4.0
  public function __wakeup()
  {
    // __wakeup() must have public visibility
    throw new \Exception("Cannot unserialize a singleton.");
  }


  public static function getInstance() : self
  {
    if (!isset(self::$instance))
    {
      self::$instance = new self();
    }

    return self::$instance;
  }


  /**
   * Get the site application object
   */
  public function app() : SiteApplication
  {
    if (!$this->is_started)
    {
      $this->start();
    }

    return $this->app;
  }


  public function session()
  {
    if (!$this->is_started)
    {
      $this->start();
    }

    return $this->session;
  }


  private function load()
  {
    global $auth;

    define('_JEXEC', 1);

    $joomla_path = realpath(MRBS_ROOT . '/' . $auth['joomla']['rel_path']);

    if ($joomla_path === false)
    {
      $message = MRBS_ROOT . '/' . $auth['joomla']['rel_path'] . ' does not exist.  Check the setting ' .
        'of $auth["joomla"]["rel_path"] in your config file.';
      die($message);  // Too early for Errors::fatalError()
    }

    define('JPATH_BASE', $joomla_path);

    require_once JPATH_BASE . '/includes/defines.php';
    require_once JPATH_BASE . '/includes/framework.php';
  }


  private function start()
  {
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
      $container = Factory::getContainer();

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
      $this->app = $container->get(SiteApplication::class);
      // Build the namespace map and load the language (necessary from Joomla 4.3.0 onwards - see
      // https://groups.google.com/g/joomla-dev-general/c/55J2s9hhMxA/m/IpBrs3HZAgAJ?utm_medium=email&utm_source=footer&pli=1
      // and https://joomla.stackexchange.com/questions/32145/joomla-4-error-when-i-use-getarticleroute/32146#32146)
      if (version_compare(JVERSION, '4.3.0', '>='))
      {
        $this->app->createExtensionNamespaceMap();
        $lang = Language::getInstance('en');  // doesn't matter which language as we never use it
        $this->app->loadLanguage($lang);
      }

      // Set the application as global app
      Factory::$application = $this->app;
    }

    if (version_compare(JVERSION, '5.0', '<'))
    {
      $this->session = JFactory::getSession();
    }
    else
    {
      $this->session = Factory::getSession();
    }

    $this->is_started = true;
  }

}
