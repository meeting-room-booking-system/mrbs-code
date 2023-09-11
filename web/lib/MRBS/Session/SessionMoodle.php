<?php
namespace MRBS\Session;

use MRBS\User;
use function MRBS\auth;
use \context_coursecat;
require_once MRBS_ROOT . '/auth/cms/moodle.inc'; //sets moodle_dir or dies

require_once($moodle_dir . '/config.php');
global $USER, $moodle_dir, $PAGE;
require_login();
class SessionMoodle extends SessionWithLogin
{

  // User is expected to already be authenticated by the web server, so do nothing
  public function authGet(?string $target_url=null, ?string $returl=null, ?string $error=null, bool $raw=false) : void
  {
  }


  public function getCurrentUser() : ?User
  {
    global $USER;
    if ($USER->username === ''){
      return null;
  }
    $moodle_user = $USER->username;


    $user = new User($USER->username);
    $user->display_name = $USER->firstname . ' ' . $USER->lastname;
    $user->email = $USER->email;
    $user->level = $this->getUserLevel($USER->username);
    return $user;
    //return auth()->getUser($user->username);
  }


  public function getLogonFormParams() : ?array
  {
    global $auth;

    if (isset($auth['remote_user']['login_link']))
    {
      return array(
          'action' => $auth['remote_user']['login_link'],
          'method' => 'get'
        );
    }
    else
    {
      return null;
    }
  }


  public function getLogoffFormParams() : ?array
  {
    global $auth;
    global $USER;

    if (isset($auth['moodle_user']['logout_link']))
    {
      return array(
          'action' => $auth['moodle_user']['logout_link'] . '?sesskey='. $USER->sesskey,
          'method' => 'get'
        );
    }
    else
    {
      return null;
    }
  }

  private static function getUserLevel($username) : int
  {
    global $auth, $moodle_dir;
    global $USER, $PAGE;

    // User not logged in, user level '0'
    /*if ($USER->guest)
    {
      return 0;
    }*/

    // Otherwise get the user's access levels

    // Check if they have admin access
    if (isset($auth['moodle']['admin_access_levels']))
    {
      $admin = $auth['moodle']['admin_access_levels'];
      if ($USER->username === $admin)
      {
        return 2;
      }
    }

    // Check if they have user access
      //require_once $moodle_dir . '/lib/accesslib.php';
        $context = $PAGE->context;
      $instance = new \stdClass();
      #$block_prenotazioni = context_block::instance( $auth['moodle']['blockid_teachers'] );
      #if (has_capability('moodle/block:edit',  $block_prenotazioni))
    $contextcoursecatID = $auth['moodle']['contextcoursecatID'];
    $context = \context_coursecat::instance($contextcoursecatID);
    if (has_capability('moodle/course:create', $context))
      {
        return 1;
      }
    // Everybody else is access level '0'
    return 0;
  }
}
