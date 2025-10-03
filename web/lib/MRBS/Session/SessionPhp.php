<?php
declare(strict_types=1);
namespace MRBS\Session;

use MRBS\User;
use function MRBS\auth;
use function MRBS\get_form_var;
use function MRBS\is_ajax;
use function MRBS\str_ends_with_array;

// Uses PHP's built-in session handling

class SessionPhp extends SessionWithLogin
{

  public function __construct()
  {
    global $auth, $server;

    parent::__construct();

    // Check to see if we've been inactive for longer than allowed and if so log out the user.
    // Don't log out the user if we're in kiosk mode because the kiosk will normally be inactive.
    // Note that we cannot use is_kiosk_mode() here as that will create an infinite loop calling session().
    if (!empty($auth['session_php']['inactivity_expire_time']) && !isset($_SESSION['kiosk_password_hash']))
    {
      if (isset($_SESSION['LastActivity']) &&
          ((time() - $_SESSION['LastActivity']) > $auth['session_php']['inactivity_expire_time']))
      {
        $this->logoffUser();
      }
      // Ajax requests don't count as activity, unless it's the special Ajax request used
      // to record client side activity.
      $activity = get_form_var('activity', 'int');
      if ($activity || !is_ajax() || !isset($_SESSION['LastActivity'])) {
        $_SESSION['LastActivity'] = time();
      }
    }

    // Move the current page to the last page, so it can be used as a referrer, and store the new current page -
    // but only if (a) we are at the top level of the MRBS web directory (eg index.php) so as to eliminate all
    // the ./js, ./ajax and ./css pages and (b) this is not otherwise an Ajax request, eg one of the prefetch
    // calls to index.php.
    if (isset($server['SCRIPT_FILENAME']) && (MRBS_ROOT === dirname($server['SCRIPT_FILENAME'])) &&
        !(isset($server['HTTP_X_REQUESTED_WITH']) && ($server['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')))
    {
      $this->updatePage($server['REQUEST_URI'] ?? $server['PHP_SELF'] ?? null);
    }
  }


  // If the server has a Referrer-Policy of strict-origin then HTTP_REFERER will be unreliable
  // and it is better to use the last page that we have stored in the $_SESSION variable.
  public function getReferrer(): ?string
  {
    $result = parent::getReferrer();

    if (isset($result) && isset($_SESSION['last_page']))
    {
      $result = $_SESSION['last_page'];
    }

    return $result;
  }


  public function updatePage(?string $url): void
  {
    // Don't update the page if the URL as the same as the one we've already got
    // stored for this page.  This will be the case if the user has refreshed the
    // browser, and if we update the page then we'll lose the last page, which is
    // sometimes needed for MRBS to know where to go back to.
    if (isset($_SESSION['this_page']) && ($url === $_SESSION['this_page']))
    {
      return;
    }

    $_SESSION['last_page'] = $_SESSION['this_page'] ?? null;
    $_SESSION['this_page'] = $url;
  }


  public function getCurrentUser() : ?User
  {
    $result = $_SESSION['user'] ?? null;

    // For some unknown reason the integer value 0 is sometimes stored in the session
    // variable.  It's not clear how this can happen.
    if (isset($result) && !(is_object($result) && is_a($result, 'MRBS\User')))
    {
      trigger_error('$_SESSION["user"] is expected to be a User object, not ' . json_encode($result), E_USER_WARNING);
      $result = null;
    }

    return $_SESSION['user'] ?? parent::getCurrentUser();
  }


  protected function logonUser(string $username) : void
  {
    $user = auth()->getUser($username);

    // As a defence against session fixation, regenerate
    // the session id and delete the old session.
    $this->regenerate();
    $_SESSION['user'] = $user;

    // Problems have been reported on Windows IIS with session data not being
    // written out without a call to session_write_close()
    session_write_close();
  }


  public function logoffUser() : void
  {
    global $cookie_path_override;

    if (ini_get("session.use_cookies"))
    {
      // Delete the session cookie
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));

      // Delete any cookies which may have previously been set, incorrectly, before the fixes to get_cookie_path (see
      // https://github.com/meeting-room-booking-system/mrbs-code/commit/90ceeb8a0bc5f4850065695a3e085114c5ecae8e and
      // https://github.com/meeting-room-booking-system/mrbs-code/commit/cb74320048149c4199281b88d50fb69988a41312).
      // Note that the problem didn't occur if $cookie_path_override was set.
      // In time, once all the incorrect cookies have expired naturally, this block can be deleted.
      if (!isset($cookie_path_override))
      {
        $suffixes = array('ajax/', 'js/');
        // If the path ends with one of the suffixes we'll already have deleted it above
        if (!str_ends_with_array($params['path'], $suffixes))
        {
          foreach ($suffixes as $suffix)
          {
            setcookie(session_name(), '', time() - 42000, $params['path'] . $suffix, $params['domain'], $params['secure'], isset($params['httponly']));
          }
        }
      }
    }

    $this->destroy();
  }
}
