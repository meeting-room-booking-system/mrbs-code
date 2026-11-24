<?php
declare(strict_types=1);
namespace MRBS\Session;

use MRBS\Form\ElementDiv;
use MRBS\GoogleLogin;
use function MRBS\get_vocab;
use function MRBS\this_page;

/**
 * Gestione delle sessioni PHP con supporto al redirect OAuth di Google,
 * al pulsante "Sign in with Google" e al pannello informativo login_notice.
 */
class SessionGoogle extends SessionPhp
{
  public function __construct()
  {
    // Google reindirizza il browser verso MRBS con una richiesta cross-site:
    // con SameSite=Strict il cookie di sessione non verrebbe inviato al rientro.
    if (GoogleLogin::isEnabled())
    {
      $this->samesite = self::SAMESITE_LAX;
    }

    parent::__construct();
  }


  /**
   * Effettua il login di un utente già autenticato tramite Google,
   * riutilizzando il metodo protected SessionPhp::logonUser().
   */
  public function logonVerifiedUser(string $username) : void
  {
    $this->logonUser($username);
  }


  /**
   * Delega il rendering del form a SessionWithLogin::authGet() e inietta
   * il pannello login_notice (prima del form) e il pulsante Google (dentro il form).
   */
  public function authGet(?string $target_url=null, ?string $returl=null, ?string $error=null, bool $raw=false) : void
  {
    $google_enabled = GoogleLogin::isEnabled();
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
      GoogleLogin::getButtonElement($resolved_target_url)->render();
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
        $form_start = $matches[0][1];

        // Inserisce il pulsante Google prima della chiusura </form> di #logon
        if ($button_html !== '')
        {
          $form_end = strpos($html, '</form>', $form_start);
          if ($form_end !== false)
          {
            $html = substr_replace($html, $button_html . '</form>', $form_end, strlen('</form>'));
          }
        }

        // Inserisce il div login_notice immediatamente prima di <form id="logon">
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