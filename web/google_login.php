<?php
declare(strict_types=1);
namespace MRBS;

// Starts the "Sign in with Google" login by redirecting the browser to Google.
// See the "Google Workspace sign-in" section of the AUTHENTICATION file.

require "defaultincludes.inc";

// The (local) page to go to after a successful login.  It is validated before the redirect.
$target_url = get_form_var('target_url', 'string');

GoogleLogin::start($target_url);
