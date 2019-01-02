<?php
namespace MRBS\Session;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementP;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;


// An abstract class for those session schemes that implement a login form
abstract class SessionWithLogin implements SessionInterface
{
  protected static $form = array();
  
  
  public function __construct()
  {
    // Get non-standard form variables
    foreach (array('action', 'username', 'password', 'target_url', 'returl') as $var)
    {
      self::$form[$var] = \MRBS\get_form_var($var, 'string', null, INPUT_POST);
    }
  }
  
  
  public static function authGet()
  {
    \MRBS\print_header();
    $target_url = \MRBS\this_page(true);
    self::printLoginForm(\MRBS\this_page(), $target_url, self::$form['returl']);
    exit;
  }
  
  
  public static function getUsername()
  {
  }
  
  
  public static function getLogonFormParams()
  {
    return array(
        'action' => 'admin.php',
        'method' => 'post',
        'hidden_inputs' =>  array('target_url' => \MRBS\this_page(true),
                                  'action'     => 'QueryName')
      );
  }
  
  
  public static function getLogoffFormParams()
  {
    return array(
        'action' => 'admin.php',
        'method' => 'post',
        'hidden_inputs' =>  array('target_url' => \MRBS\this_page(true),
                                  'action'     => 'SetName',
                                  'username'   => '',
                                  'password'   => '')
      );
  }
  
  
  public function logoffUser()
  {
  }
  
  
  public function processForm()
  {
    if (isset(self::$form['action']))
    {
      // Target of the form with sets the URL argument "action=QueryName".
      // Will eventually return to URL argument "target_url=whatever".
      if (self::$form['action'] == 'QueryName')
      {
        \MRBS\print_header();
        self::printLoginForm(\MRBS\this_page(), self::$form['target_url'], self::$form['returl']);
        exit();
      }
      
      // Target of the form with sets the URL argument "action=SetName".
      // Will eventually return to URL argument "target_url=whatever".
      if (self::$form['action'] == 'SetName')
      {
        // If we're going to do something then check the CSRF token first
        Form::checkToken();
        
        // First make sure the password is valid
        if (self::$form['username'] == '')
        {
          $this->logoffUser();
        }
        else
        {
          if (($valid_username = \MRBS\authValidateUser(self::$form['username'], self::$form['password'])) === false)
          {
            \MRBS\print_header();
            self::printLoginForm(\MRBS\this_page(), self::$form['target_url'], self::$form['returl'], \MRBS\get_vocab('unknown_user'));
            exit();
          }
          
          // Successful login.  As a defence against session fixation, regenerate
          // the session id and delete the old session.
          session_regenerate_id(true);
          $_SESSION['UserName'] = $valid_username;
          
          if (!empty(self::$form['returl']))
          {
            // check to see whether there's a query string already
            self::$form['target_url'] .= (strpos(self::$form['target_url'], '?') === false) ? '?' : '&';
            self::$form['target_url'] .= urlencode(self::$form['returl']);
          }
        }

        // Problems have been reported on Windows IIS with session data not being
        // written out without a call to session_write_close()
        session_write_close();
        header ('Location: ' . self::$form['target_url']); /* Redirect browser to initial page */
        exit;
      }
    }
  }
  
  
  // Displays the login form. 
  // Will eventually return to $target_url with query string returl=$returl
  // If $error is set then an $error is printed.
  // If $raw is true then the message is not HTML escaped
  protected static function printLoginForm($action, $target_url, $returl, $error=null, $raw=false)
  {
    $form = new Form();
    $form->setAttributes(array('class'  => 'standard',
                               'id'     => 'logon',
                               'method' => 'post',
                               'action' => $action));
    
    // Hidden inputs
    $hidden_inputs = array('returl'     => $returl,
                           'target_url' => $target_url,
                           'action'     => 'SetName');
    $form->addHiddenInputs($hidden_inputs);
    
    // Now for the visible fields
    if (isset($error))
    {
      $p = new ElementP();
      $p->setText($error, false, $raw);
      $form->addElement($p);
    }
    
    $fieldset = new ElementFieldset();
    $fieldset->addLegend(\MRBS\get_vocab('please_login'));
    
    // The username field
    if (function_exists(__NAMESPACE__ . "\\canValidateByEmail")
        && canValidateByEmail())
    {
      $placeholder = \MRBS\get_vocab('username_or_email');
    }
    else
    {
      $placeholder = \MRBS\get_vocab('users.name');
    }
    
    $field = new FieldInputText();
    $field->setLabel(\MRBS\get_vocab('user'))
          ->setLabelAttributes(array('title' => $placeholder))
          ->setControlAttributes(array('id'          => 'username',
                                       'name'        => 'username',
                                       'placeholder' => $placeholder,
                                       'required'    => true,
                                       'autofocus'   => true));               
    $fieldset->addElement($field);
    
    // The password field
    $field = new FieldInputPassword();
    $field->setLabel(\MRBS\get_vocab('users.password'))
          ->setControlAttributes(array('id'          => 'password',
                                       'name'        => 'password'));               
    $fieldset->addElement($field);
    
    // The submit button
    $field = new FieldInputSubmit();
    $field->setControlAttributes(array('value' => \MRBS\get_vocab('login')));
    $fieldset->addElement($field);
    
    $form->addElement($fieldset);
    
    $form->render();
    
    // Print footer and exit
    \MRBS\print_footer(true);
  }
}
