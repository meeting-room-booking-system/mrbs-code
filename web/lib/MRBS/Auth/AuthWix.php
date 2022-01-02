<?php
namespace MRBS\Auth;

use MRBS\User;

// A class for authenticating against a Wix system.  It requires code to be installed in
// http-functions.js in the Wix backend.  See wix/README for full details.
//
// It would be nice to be able to use Wix's OAuth2 server, but it seems to be limited to Wix
// apps.
//
// Another approach might be to use the Wix backend function getMember(), but this doesn't
// work inside http_functions as the URL endpoint for http_functions is not associated with
// a user session.
class AuthWix extends Auth
{

  /* validateUser($user, $pass)
   *
   * Checks if the specified username/password pair are valid
   *
   * $user  - The username
   * $pass  - The password
   *
   * Returns:
   *   false    - The pair are invalid or do not exist
   *   string   - The validated username
   */
  public function validateUser(?string $user, ?string $pass)
  {
    if (!isset($user) || !isset($pass))
    {
      return false;
    }

    $params = array(
        'email' => $user,
        'password' => $pass
      );

    $result = $this->http_functions('validateMember', $params);

    if ($result === false)
    {
      // curl_exec failure: we'll return false anyway
      return $result;
    }

    return (json_decode($result)) ? $user : false;
  }


  protected function getUserFresh(string $username) : ?User
  {
    global $auth;

    $params = array('email' => $username);
    $result = $this->http_functions('getMemberByEmail', $params);

    // The username doesn't exist - return NULL
    if ($result === false)
    {
      return null;
    }

    $result = json_decode($result);

    $user = new User($username);

    // Set the email address
    $user->email = $result->member->loginEmail;

    // Set the display name
    if (isset($result->member->name) && ($result->member->name !== ''))
    {
      $user->display_name = $result->member->name;
    }
    else
    {
      $user->display_name = $result->member->loginEmail;
    }

    // Set the level
    // First get the default level.  Any admins defined in the config
    // file override settings in the external database.
    $user->level = $this->getDefaultLevel($username);

    // Then if they are not an admin get their admin status from Wix
    if (($user->level < 2) && !empty($result->contact->info->extendedFields->{$auth['wix']['admin_property']}))
    {
      $user->level = 2;
    }

    return $user;
  }


  // Return an array of users, indexed by 'username' and 'display_name'
  public function getUsernames() : array
  {
    $params = array();
    $result = $this->http_functions('getMemberNames', $params);

    if ($result === false)
    {
      return array();
    }

    $users =  json_decode($result, true);
    self::sortUsers($users);

    return $users;
  }


  private function http_functions(string $function, array $params)
  {
    global $auth, $server;

    // Add a trailing '/' if necessary
    if (!str_ends_with($auth['wix']['site_url'], '/'))
    {
      $auth['wix']['site_url'] .= '/';
    }

    // Get a user agent to keep the other end happy
    if (isset($server['HTTP_USER_AGENT']) && ($server['HTTP_USER_AGENT'] !== ''))
    {
      $user_agent = $server['HTTP_USER_AGENT'];
    }
    else
    {
      $user_agent = 'PHP';
    }

    // Add in the API key
    $params['key'] = $auth['wix']['mrbs_api_key'];
    // And the API key secret name in Wix
    $params['secret_name'] = $auth['wix']['mrbs_api_key_secret_name'];

    // And the limit, for internal use by the Wix backend code
    if (isset($auth['wix']['limit']))
    {
      $params['limit'] = $auth['wix']['limit'];
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $auth['wix']['site_url'] . "_functions/$function");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Necessary to prevent "HTTP/2 stream 0 was not closed cleanly: INTERNAL_ERROR (err 2)" error;
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    // Necessary to prevent "OpenSSL SSL_read: Connection reset by peer, errno 104" error;
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($result === false)
    {
      trigger_error(curl_error($ch), E_USER_WARNING);
    }

    if ($http_code != 200)
    {
      trigger_error("Curl received HTTP response code $http_code: $result", E_USER_WARNING);
      $result = false;
    }

    curl_close($ch);
    return $result;
  }

}
