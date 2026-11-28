<?php
declare(strict_types=1);
namespace MRBS\Session;

use MRBS\Form\ElementDiv;
use MRBS\OAuth2Google;
use function MRBS\get_vocab;
use function MRBS\get_form_var;
use function MRBS\this_page;

/**
 * PHP session handler with Google OAuth redirect support, "Sign in with Google" button,
 * and optional login_notice panel.
 */
class SessionOAuth2Google extends SessionPhp
{
  public function __construct()
  {
    // Die with a clear message if $auth['type'] is not 'google'
    $this->checkTypeMatchesSession();

    // Google redirects back across sites: with SameSite=Strict the session cookie
    // would not be sent on return.
    if (OAuth2Google::isEnabled())
    {
      $this->samesite = self::SAMESITE_LAX;
    }

    parent::__construct();
  }

  /**
   * Log in a user already authenticated via Google by delegating to
   * SessionPhp::logonUser().
   */
  public function logonVerifiedUser(string $username) : void
  {
    $this->logonUser($username);
  }

  /**
   * Deals with the two requests that belong to "Log in with Google".  Called by session() once the session
   * object has been fully constructed and registered, so it is safe to call session() from here and from
   * GoogleLogin (see isCallback()).  On all other requests it does nothing, and costs only a look at the
   * query string.
   *
   *   - The user has clicked on the button ('oAuth2_google' parameter): redirect the browser to Google.
   *   - The browser has come back from Google ('state' and 'code' or 'error'): log the user on, or show
   *     the login form with an error.
   *
   * Neither of them returns.
   */
  public function handleRequest() : void
  {
    if (OAuth2Google::isCallback())
    {
      OAuth2Google::handleCallback();
    }
    elseif (OAuth2Google::isStart())
    {
      // The page to go to afterwards is validated by location_header() when the login is complete
      OAuth2Google::start(get_form_var('target_url', 'string'));
    }
  }

  /**
   * Render the login form via SessionWithLogin::authGet() and inject the
   * login_notice panel (before the form) and Google button (inside the form).
   */
  public function authGet(?string $target_url=null, ?string $returl=null, ?string $error=null, bool $raw=false) : void
  {
    $google_enabled = OAuth2Google::isEnabled();
    $notice = get_vocab('login_notice');
    $has_notice = (($notice !== 'login_notice') && ($notice !== ''));

    if (!$google_enabled && !$has_notice)
    {
      parent::authGet($target_url, $returl, $error, $raw);
      return;
    }

    $button_html = '';
    if ($google_enabled)
    {
      $resolved_target_url = $target_url ?? $this->form['target_url'] ?? this_page(true);
      ob_start();
      OAuth2Google::getButtonElement($resolved_target_url)->render();
      $button_html = ob_get_clean();
    }

    $notice_html = '';
    if ($has_notice)
    {
      $div = new ElementDiv();
      $div->addClass('login_notice')
          ->setText($notice, false, true);
      ob_start();
      $div->render();
      $notice_html = ob_get_clean();
    }

    ob_start(function (string $html) use ($button_html, $notice_html) : string {
      if (preg_match('/<form\b[^>]*\bid=["\']logon["\'][^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE))
      {
        $form_start = (int) $matches[0][1];

        // Insert the Google button right before the closing </form> of #logon
        if ($button_html !== '')
        {
          $form_end = strpos($html, '</form>', $form_start);
          if ($form_end !== false)
          {
            $html = substr_replace($html, $button_html . '</form>', $form_end, strlen('</form>'));
          }
        }

        // Insert the login_notice div immediately before <form id="logon">
        if ($notice_html !== '')
        {
          $html = substr_replace($html, $notice_html, $form_start, 0);
        }
      }

      return $html;
    });

    parent::authGet($target_url, $returl, $error, $raw);

    if (ob_get_level() > 0)
    {
      ob_end_flush();
    }
  }
}
