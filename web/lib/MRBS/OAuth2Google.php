<?php
declare(strict_types=1);
namespace MRBS;

use GuzzleHttp\Client;
use MRBS\Form\ElementA;
use MRBS\Form\ElementDiv;

/**
 * "Sign in with Google" (OAuth 2.0 / OpenID Connect, authorisation code flow with PKCE) for Google Workspace.
 *
 * This works alongside the normal login form: it does not replace it. It requires
 *
 *     $auth['type']    = 'oAuth2_google';
 *     $auth['session'] = 'oAuth2_google';
 *
 * Users are identified by their (lower case) email address, which is used as the username in the users table.
 *
 * See the "Google Workspace sign-in" section of the AUTHENTICATION file for the configuration.
 */
class OAuth2Google
{
  private const AUTH_ENDPOINT  = 'https://accounts.google.com/o/oauth2/v2/auth';
  private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
  private const ISSUERS        = ['https://accounts.google.com', 'accounts.google.com'];

  // How long, in seconds, the user has to complete the login at Google.
  private const STATE_LIFETIME = 600;

  // The name of the session variable used to hold the state between the redirect and the callback.
  private const SESSION_KEY = 'google_oauth';

  /** @var string|null Fallback target after the login form is shown following an error. */
  private static $error_target = null;

  // The Google "G" logo. It's inline so that there's no image file to install and nothing to load. It must not
  // be altered: see https://developers.google.com/identity/branding-guidelines
  private const LOGO_SVG =
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="18" height="18" aria-hidden="true" focusable="false">' .
    '<path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>' .
    '<path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>' .
    '<path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>' .
    '<path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>' .
    '</svg>';


  /**
   * Whether Google sign-in has been configured.
   */
  public static function isEnabled() : bool
  {
    global $auth;

    // Users are looked up in, and created in, the MRBS users table by the 'oAuth2_google' authentication type, and it
    // is the 'oAuth2_google' session that shows the button and can log in a user without a password.
    //
    // NOTE: this must only look at the configuration and not call session(), because it is called while the
    // session is being constructed.
    return !empty($auth['oAuth2_google']['enable']) &&
           (($auth['type'] ?? null) === 'oAuth2_google') &&
           (($auth['session'] ?? null) === 'oAuth2_google') &&
           !empty($auth['oAuth2_google']['client_id']) &&
           !empty($auth['oAuth2_google']['client_secret']) &&
           !empty(self::getAllowedDomains());
  }

  /**
   * Whether this user can only log in with Google, and not with a password. That is the case when Google
   * sign-in is enabled and the username is an email address at one of the allowed domains, even if the user
   * has a password set. Usernames that are not such an address (eg 'administrator') still use their password,
   * so there is always a way in if Google sign-in is misconfigured.
   */
  public static function isGoogleOnlyUser(?string $username) : bool
  {
    if (!isset($username) || !self::isEnabled())
    {
      return false;
    }

    $normalised = mb_strtolower(trim($username));

    return self::hasValidEmailDomain($normalised, self::getAllowedDomains());
  }

  /**
   * Returns the list of allowed domains, normalised to lower case.
   *
   * The config variable $auth['oAuth2_google']['allowed_domains'] is a string of comma separated domains, eg
   * 'example.com, example.org'. An array is also accepted.
   *
   * @return string[]
   */
  public static function getAllowedDomains() : array
  {
    global $auth;

    $setting = $auth['oAuth2_google']['allowed_domains'] ?? '';

    $domains = (is_array($setting)) ? $setting : explode(',', (string) $setting);
    $domains = array_map(function ($domain) {
        return ltrim(mb_strtolower(trim((string) $domain)), '@');
      }, $domains);

    return array_values(array_filter($domains, function ($domain) {
        return $domain !== '';
      }));
  }

  /**
   * Returns the URL that starts the login, ie the target of the "Log in with Google" button.
   * It is index.php with a 'oAuth2_google' parameter: the request is recognised and handled by
   * SessionGoogle::handleRequest(), so no page of its own is needed.
   *
   * @param string|null $target_url  A local URL to go to after a successful login
   */
  public static function getLoginUrl(?string $target_url=null) : string
  {
    $params = ['oAuth2_google' => 1];

    if (isset($target_url) && ($target_url !== ''))
    {
      $params['target_url'] = $target_url;
    }

    return multisite('index.php?' . http_build_query($params, '', '&'));
  }

  /**
   * Returns the "Log in with Google" button for the login page, to be added to the login form. It has to go
   * inside the form, rather than after it, because the form is floated (or, on some pages, is an item of a flex
   * container), so anything placed after it would end up beside it instead of underneath it.
   * The styling is in css/mrbs.css.php.
   */
  public static function getButtonElement(?string $target_url=null) : ElementDiv
  {
    // The text is raw because it contains the logo, so the label has to be escaped here.
    $a = new ElementA();
    $a->setAttributes(array('class' => 'google_login_button',
                            'href'  => self::getLoginUrl($target_url)))
      ->setText(self::LOGO_SVG . '<span>' . escape_html(get_vocab('login_with_google')) . '</span>', false, true);

    $div = new ElementDiv();
    $div->setAttribute('class', 'google_login')
        ->addElement($a);

    return $div;
  }

  /**
   * Starts the login by redirecting the browser to Google.
   *
   * @param string|null $target_url A local URL to go to after a successful login
   */
  public static function start(?string $target_url=null) : void
  {
    global $auth;

    if (!self::isEnabled())
    {
      self::fail('google_login_not_configured', $target_url);
    }

    // The state protects against CSRF, the nonce against replay of an ID token and the PKCE verifier against
    // interception of the authorisation code.
    $state = self::randomString();
    $nonce = self::randomString();
    $verifier = self::randomString(48);

    session()->set(self::SESSION_KEY, [
        'state'    => $state,
        'nonce'    => $nonce,
        'verifier' => $verifier,
        'target'   => $target_url,
        'created'  => time()
      ]);

    $domains = self::getAllowedDomains();

    $params = [
        'client_id'             => $auth['oAuth2_google']['client_id'],
        'redirect_uri'          => self::getRedirectUri(),
        'response_type'         => 'code',
        'scope'                 => 'openid email profile',
        'state'                 => $state,
        'nonce'                 => $nonce,
        'code_challenge'        => self::base64UrlEncode(hash('sha256', $verifier, true)),
        'code_challenge_method' => 'S256',
        'prompt'                => 'select_account',
        // This is only a hint to Google to show the right accounts. It is NOT a security measure: the
        // domain is checked again, on the server, in handleCallback().
        'hd'                    => (count($domains) === 1) ? $domains[0] : '*'
      ];

    // Make sure the session data is written before we leave
    session_write_close();

    header('Location: ' . self::AUTH_ENDPOINT . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
    exit;
  }

  /**
   * Whether the current request is the one that starts the login, ie the user has clicked on the
   * "Log in with Google" button.  Called on every page, so it only looks at the query string.
   */
  public static function isStart() : bool
  {
    return self::isEnabled() && (get_form_var('oAuth2_google', 'int') === 1);
  }

  /**
   * Handles the redirect back from Google. Either logs the user in and redirects to the target page, or
   * shows the login form with an error message.
   */
  public static function handleCallback() : void
  {
    global $auth;

    if (!self::isEnabled())
    {
      self::fail('google_login_not_configured');
    }

    // The data stored when we started. It can only be used once.
    $saved = session()->get(self::SESSION_KEY);
    session()->unset(self::SESSION_KEY);

    $error_target = null;
    if (is_array($saved) && isset($saved['target']) && is_string($saved['target']) && ($saved['target'] !== ''))
    {
      $error_target = $saved['target'];
      self::$error_target = $error_target;
    }

    // Google reports problems, eg the user cancelling, in the 'error' parameter.
    if (null !== get_form_var('error', 'string'))
    {
      self::fail('google_login_failed', $error_target);
    }

    $code = get_form_var('code', 'string');
    $state = get_form_var('state', 'string');

    if (!is_array($saved) ||
        !isset($saved['state'], $saved['nonce'], $saved['verifier'], $saved['created']) ||
        !isset($code, $state) ||
        !hash_equals((string) $saved['state'], $state) ||
        ((time() - (int) $saved['created']) > self::STATE_LIFETIME))
    {
      self::fail('google_login_failed', $error_target);
    }

    $claims = self::fetchClaims($code, (string) $saved['verifier']);

    if (($claims === null) || !self::validateClaims($claims, (string) $saved['nonce'], (string) $auth['oAuth2_google']['client_id']))
    {
      self::fail('google_login_failed', $error_target);
    }

    $email = mb_strtolower(trim($claims['email']));

    // The domain check. All of the following have to be true:
    //   - Google says the email address is verified. Without this anybody could claim somebody else's address.
    //   - The 'hd' (hosted domain) claim is present and allowed. Only Google Workspace accounts have an 'hd'
    //     claim. Checking just the domain of the email address is not enough, because an ordinary Google
    //     account can be created with a verified email address at any domain.
    //   - The domain of the email address itself is allowed.
    if (!self::isAllowedAccount($claims, $email))
    {
      self::fail('google_login_domain_not_allowed', $error_target);
    }

    $username = self::findUser($email);

    if (!isset($username))
    {
      $username = self::createUser($email, $claims['name'] ?? '', $error_target);
    }

    session()->logonVerifiedUser($username);
    self::recordLogin($username);

    $target = (isset($saved['target']) && ($saved['target'] !== '')) ? $saved['target'] : 'index.php';
    location_header($target);
  }


  /**
   * Whether the current request is Google's redirect back to MRBS after the user has signed in (or has cancelled).
   *
   * This is called on every page, so it has to be cheap and it must not act on a request that merely happens to
   * have a 'code' or 'state' parameter.  All of the following have to be true:
   *   - Google sign-in is enabled;
   *   - the request has a 'state' and either a 'code' (success) or an 'error' (eg the user cancelled);
   *   - the session holds the data saved by start() before the browser was sent to Google, and the 'state'
   *     matches.  Without this anybody could send a forged request to the redirect URI.
   *
   * The state is compared here only to recognise the request.  handleCallback() checks it again, together with
   * its age, before using the code.
   */
  public static function isCallback() : bool
  {
    if (!self::isEnabled())
    {
      return false;
    }

    // The cheap checks first: most requests have no 'state' and stop here.
    $state = get_form_var('state', 'string');
    if (!isset($state) ||
        ((null === get_form_var('code', 'string')) && (null === get_form_var('error', 'string'))))
    {
      return false;
    }

    // hash_equals() for a constant time comparison.  The cast is needed because of strict_types.
    $saved = session()->get(self::SESSION_KEY);

    return is_array($saved) &&
           isset($saved['state']) &&
           hash_equals((string) $saved['state'], $state);
  }


  /**
   * Shows the login form with an error message. Never returns.
   */
  private static function fail(string $vocab_key, ?string $error_target=null) : void
  {
    $target = $error_target ?? self::$error_target ?? 'index.php';

    // Show the ordinary login form with the error. The target URL has to be given explicitly: if we let it
    // default, it would be this page, with Google's one-time code in the query string.
    if (method_exists(session(), 'authGet'))
    {
      session()->authGet($target, null, get_vocab($vocab_key));
    }

    // Fallback, which shouldn't happen
    http_response_code(403);
    echo escape_html(get_vocab($vocab_key));
    exit;
  }

  /**
   * Exchanges the authorisation code for the tokens and returns the claims in the ID token, or null on failure.
   *
   * We don't verify the signature of the ID token because we get it directly from Google's token endpoint over
   * TLS (OpenID Connect Core 1.0, section 3.1.3.7), but we do validate its claims in validateClaims().
   */
  private static function fetchClaims(string $code, string $verifier) : ?array
  {
    global $auth;

    try
    {
      $client = new Client(['timeout' => 10, 'connect_timeout' => 5, 'http_errors' => false]);
      $response = $client->post(self::TOKEN_ENDPOINT, [
          'form_params' => [
              'code'          => $code,
              'client_id'     => $auth['oAuth2_google']['client_id'],
              'client_secret' => $auth['oAuth2_google']['client_secret'],
              'redirect_uri'  => self::getRedirectUri(),
              'grant_type'    => 'authorization_code',
              'code_verifier' => $verifier
            ]
        ]);
    }
    catch (\Throwable $e)
    {
      error_log('MRBS Google login: token request failed: ' . $e->getMessage());
      return null;
    }

    if ($response->getStatusCode() !== 200)
    {
      error_log('MRBS Google login: token endpoint returned HTTP ' . $response->getStatusCode());
      return null;
    }

    $tokens = json_decode((string) $response->getBody(), true);
    if (!is_array($tokens) || !isset($tokens['id_token']) || !is_string($tokens['id_token']))
    {
      return null;
    }

    $parts = explode('.', $tokens['id_token']);
    if (count($parts) !== 3)
    {
      return null;
    }

    $claims = json_decode(self::base64UrlDecode($parts[1]), true);

    return (is_array($claims)) ? $claims : null;
  }

  /**
   * Validates the standard claims of the ID token.
   */
  private static function validateClaims(array $claims, string $nonce, string $client_id) : bool
  {
    $aud = $claims['aud'] ?? null;
    $aud_ok = (is_array($aud)) ? in_array($client_id, $aud, true) : ($aud === $client_id);

    return $aud_ok &&
           isset($claims['iss']) && in_array($claims['iss'], self::ISSUERS, true) &&
           isset($claims['exp']) && ((int) $claims['exp'] > time()) &&
           isset($claims['nonce']) && is_string($claims['nonce']) && hash_equals($nonce, $claims['nonce']) &&
           isset($claims['email']) && is_string($claims['email']) && ($claims['email'] !== '');
  }

  /**
   * Checks that the account belongs to one of the allowed domains. $email must already be normalised.
   */
  private static function isAllowedAccount(array $claims, string $email) : bool
  {
    // 'email_verified' is a boolean, but has been seen as the string "true"
    $verified = $claims['email_verified'] ?? false;
    if (($verified !== true) && ($verified !== 'true'))
    {
      return false;
    }

    $allowed = self::getAllowedDomains();

    $hd = (isset($claims['hd']) && is_string($claims['hd'])) ? mb_strtolower($claims['hd']) : null;
    if (!isset($hd) || !in_array($hd, $allowed, true))
    {
      return false;
    }

    return self::hasValidEmailDomain($email, $allowed);
  }

  /**
   * Checks that $address has a single '@' with a non-empty local part and a domain in $allowed_domains.
   *
   * @param string[] $allowed_domains
   */
  private static function hasValidEmailDomain(string $address, array $allowed_domains) : bool
  {
    $at = strpos($address, '@');
    if (($at === false) || ($at === 0) || (substr_count($address, '@') !== 1))
    {
      return false;
    }

    return in_array(substr($address, $at + 1), $allowed_domains, true);
  }

  /**
   * Returns the username of an existing user, or null if there isn't one.
   *
   * The email address is used as the username, and usernames are stored in lower case.
   */
  private static function findUser(string $email) : ?string
  {
    $sql = "SELECT name
              FROM " . _tbl('users') . "
             WHERE name=?
             LIMIT 1";

    $res = db()->query($sql, [$email]);

    if ($res->count() === 0)
    {
      return null;
    }

    $row = $res->next_row_keyed();

    return $row['name'];
  }

  /**
   * Creates a new user, provided automatic creation is enabled and the email address matches the configured
   * filter. Returns the username. If the user cannot be created then it shows an error and doesn't return.
   */
  private static function createUser(string $email, string $display_name, ?string $error_target=null) : string
  {
    global $auth;

    $filter = (string) ($auth['oAuth2_google']['auto_create_regex_filter'] ?? '');

    if (empty($auth['oAuth2_google']['auto_create_users']) || !self::matchesCreateFilter($email, $filter))
    {
      self::fail('google_login_no_account', $error_target);
    }

    $max_length = maxlength('users.name');
    if (isset($max_length) && (mb_strlen($email) > $max_length))
    {
      error_log("MRBS Google login: cannot create user '$email' because the users.name column is limited to " .
                "$max_length characters.");
      self::fail('google_login_name_too_long', $error_target);
    }

    $display_name = trim($display_name);
    $display_name = ($display_name === '') ? $email : mb_substr($display_name, 0, maxlength('users.display_name') ?? 191);

    // A NULL password hash: the user has no password and logs in with Google.
    $sql = "INSERT INTO " . _tbl('users') . "
                        (level, name, display_name, password_hash, email)
                 VALUES (?, ?, ?, NULL, ?)";
    $level = (int) ($auth['oAuth2_google']['default_level'] ?? 1);

    try
    {
      db()->command($sql, [$level, $email, $display_name, $email]);
    }
    catch (\Throwable $e)
    {
      // Probably two simultaneous first logins: if the user now exists then that's fine
      if (null === ($username = self::findUser($email)))
      {
        error_log('MRBS Google login: could not create user: ' . $e->getMessage());
        self::fail('google_login_failed', $error_target);
      }
      return $username;
    }

    return $email;
  }

  /**
   * Checks the email address against the optional regular expression filter.
   * If there is no filter then every address in the allowed domains matches.
   */
  private static function matchesCreateFilter(string $email, string $filter='') : bool
  {
    if ($filter === '')
    {
      return true;
    }

    $result = @preg_match($filter, $email);

    if ($result === false)
    {
      // An invalid regular expression: fail closed
      error_log("MRBS Google login: invalid \$auth['oAuth2_google']['auto_create_regex_filter']");
      return false;
    }

    return ($result === 1);
  }


  private static function recordLogin(string $username) : void
  {
    // Same as for the 'db' authentication type. Don't change the timestamp.
    $sql = "UPDATE " . _tbl('users') . "
               SET last_login=?, timestamp=timestamp
             WHERE name=?";
    db()->command($sql, [time(), $username]);
  }

  /**
   * Returns the redirect URI, ie the address that Google sends the browser back to after the sign-in.
   *
   * It is index.php: the redirect is recognised and completed by SessionGoogle::handleRequest(), so no page of
   * its own is needed.  The URI must be registered, exactly as it is returned here, as an "Authorised redirect
   * URI" in the Google Cloud console, and the same value must be used both when sending the user to Google
   * (start()) and when exchanging the code for the tokens (fetchClaims()), otherwise Google refuses the
   * request. That is why both call this method.
   *
   * $auth['oAuth2_google']['redirect_uri'] overrides the default, eg if MRBS is behind a reverse proxy and cannot
   * work out its own public URL.
   */
  private static function getRedirectUri() : string
  {
    global $auth;

    if (!empty($auth['oAuth2_google']['redirect_uri']))
    {
      return $auth['oAuth2_google']['redirect_uri'];
    }

    return url_base() . 'index.php';
  }

  private static function randomString(int $bytes=32) : string
  {
    return self::base64UrlEncode(random_bytes($bytes));
  }

  private static function base64UrlEncode(string $data) : string
  {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  private static function base64UrlDecode(string $data) : string
  {
    $decoded = base64_decode(strtr($data, '-_', '+/'), true);

    return ($decoded === false) ? '' : $decoded;
  }
}
