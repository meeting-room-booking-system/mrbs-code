<?php
namespace MRBS;

use MRBS\Form\Form;

// Start up sessions 
new SessionHandler();

// If sessions didn't work, then set a cookie containing the CSRF token.
if (session_id() === '')
{
  // Note that the technique of creating a dummy form to store the token
  // does not work when using sessions.  It only works for cookies.   That's
  // because when using sessions, the new token is stored immediately.  So by
  // the time we come to read $_SESSION to check the token we will be looking
  // at the *new* token.   However, when using cookies, the browser will have
  // already sent the cookie by the time we get to this point, so when reading
  // $_COOKIE we are looking at the *old* token, which is what we want.
  $dummy_form = new Form();
}
