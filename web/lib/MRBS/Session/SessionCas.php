<?php
namespace MRBS\Session;

use \phpCAS;
use MRBS\Form\Form;


class SessionCas extends SessionWithLogin
{

  public function __construct()
  {
    $this->checkTypeMatchesSession();
    \MRBS\auth()->init();  // Initialise CAS
    parent::__construct();
  }


  public function authGet($target_url=null, $returl=null, $error=null, $raw=false)
  {
    // Useless Method - CAS does it all
  }


  public function getCurrentUser()
  {
    return (phpCAS::isAuthenticated()) ? \MRBS\auth()->getUser(phpCAS::getUser()) : null;
  }


  public function getLogonFormParams()
  {
    $target_url = \MRBS\this_page(true);

    return array(
        'action' => $target_url,
        'method' => 'post',
        'hidden_inputs' =>  array('target_url' => $target_url,
                                  'action'     => 'QueryName')
      );
  }


  public function processForm()
  {
    if (isset($this->form['action']))
    {
      // Target of the form with sets the URL argument "action=QueryName".
      if ($this->form['action'] == 'QueryName')
      {
        phpCAS::forceAuthentication();
      }

      // Target of the form with sets the URL argument "action=SetName".
      // Will eventually return to URL argument "target_url=whatever".
      if ($this->form['action'] == 'SetName')
      {
        // If we're going to do something then check the CSRF token first
        Form::checkToken();

        // You should only get here using CAS authentication after clicking the logoff
        // link, no matter what the value of the form parameters.
        $this->logoffUser();

        \MRBS\location_header($this->form['target_url']); // Redirect browser to initial page
      }
    }
  }


  public function logoffUser()
  {
    phpCAS::logout();
  }

}
