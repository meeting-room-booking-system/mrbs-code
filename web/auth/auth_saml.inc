<?php
namespace MRBS;

/*
 * Authentication management scheme that delegates everything to a ready
 * configured SimpleSamlPhp instance.  You should use this scheme, along with
 * the session scheme with the same name, if you want your users to
 * authenticate using SAML Single Sign-on.
 *
 * See the session management scheme with the same name for information on
 * how to configure SAML authentication.  This authentication module on its
 * own doesn't work.
 */

if ($auth['session'] !== 'saml') {
	header('Content-Type: text/plain', true, 500);
	die("auth_saml doesn't work alone, set \$config['session'] = 'saml'; too.\r\n");
}

/* authValidateUser($user, $pass)
 * 
 * Doesn't check password, but check if username matches the SAML session
 * 
 * $user  - The user name
 * $pass  - The password (ignored)
 * 
 * Returns:
 *   false    - The user is not our SAML user
 *   string   - The user is our SAML user, the string is the username
 */
function authValidateUser($user, $pass)
{
	$username = getUserName();
	if (isset($username) && $username === $user) {
		return $user;
	}
	return false;
}

/* authGetUserLevel($user)
 * 
 * Determines the users access level
 *
 * It does this by comparing SAML attributes with $auth['saml']['admin']
 * If any attribute matches, the user is considered admin and 2 is returned.
 *
 * If the user is not logged in, or the provided username doesn't match our
 * SAML session, 0 is returned.
 *
 * Otherwise, 1 is returned.
 * 
 * $user - The user name
 *
 * Returns:
 *   The users access level
 */
function authGetUserLevel($user)
{
	global $ssp;
	global $auth;
	$userData = $ssp->getAttributes();
	$username = getUserName();
	if (isset($username) && $username === $user) {
		foreach($auth['saml']['admin'] as $attr => $values) {
			if (array_key_exists($attr, $userData)) foreach($values as $value) {
				if (in_array($value, $userData[$attr])) return 2;
			}
		}
		return 1;
	}
	return 0;
}

// Gets the users e-mail from the SAML attributes.
// Returns an empty string if no e-mail address was found
function authGetUserEmail($user)
{
	global $ssp;
	global $auth;
	$mailAttr = $auth['saml']['attr']['mail'];
	$userData = $ssp->getAttributes();
	$username = getUserName();
	if (isset($username) && $username === $user) {
		return array_key_exists($mailAttr, $userData) ? $userData[$mailAttr][0] : '';
	}
	return '';
}
