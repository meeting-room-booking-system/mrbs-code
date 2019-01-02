<?php
namespace MRBS\Session;

use \phpCAS;


class SessionCas extends SessionWithLogin
{
  
  public function authGet()
  {
    // Useless Method
  }
  
  
  public function getUsername()
  {
    return (phpCAS::isAuthenticated()) ? phpCAS::getUser() : null;
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
  
}
