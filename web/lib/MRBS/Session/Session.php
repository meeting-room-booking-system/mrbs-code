<?php
namespace MRBS\Session;

use MRBS\Form\Form;
use MRBS\Form\ElementFieldset;
use MRBS\Form\ElementInputSubmit;
use MRBS\Form\ElementP;
use MRBS\Form\FieldInputPassword;
use MRBS\Form\FieldInputSubmit;
use MRBS\Form\FieldInputText;


abstract class Session implements SessionInterface
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
