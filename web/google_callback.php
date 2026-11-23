<?php
declare(strict_types=1);
namespace MRBS;

// The page that Google redirects the browser back to after the user has signed in.  This URL has to be
// registered in the Google Cloud console as an "Authorised redirect URI".
// See the "Google Workspace sign-in" section of the AUTHENTICATION file.

require "defaultincludes.inc";

GoogleLogin::handleCallback();
