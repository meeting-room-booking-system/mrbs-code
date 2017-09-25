<?php
namespace MRBS;

/*
 * Session management scheme that delegates everything to a ready configured
 * SimpleSamlPhp instance.  You should use this scheme, along with the
 * authentication scheme with the same name, if you want your users to
 * authenticate using SAML Single Sign-on.
 *
 * in config.inc.php (assuming Active Directory attributes):
 * $auth['type'] = 'saml';
 * $auth['session'] = 'saml';
 * $auth['saml']['ssp_path'] = '/opt/simplesamlphp';
 * $auth['saml']['authsource'] = 'default-sp';
 * $auth['saml']['attr']['username'] = 'sAMAccountName';
 * $auth['saml']['attr']['mail'] = 'mail';
 * $auth['saml']['admin']['memberOf'] = ['CN=Domain Admins,CN=Users,DC=example,DC=com'];
 *
 * This scheme assumes that you've already configured SimpleSamlPhp,
 * and that you have set up aliases in your webserver so that SimpleSamlPhp
 * can handle incoming assertions.  Refer to the SimpleSamlPhp documentation
 * for more information on how to do that.
 *
 * https://simplesamlphp.org/docs/stable/simplesamlphp-install
 * https://simplesamlphp.org/docs/stable/simplesamlphp-sp
 */

require_once MRBS_ROOT . '/session/functions_session.inc';

// Include the SimpleSamlPhp autoloader
$sspPath = $auth['saml']['ssp_path'];
if (is_null($sspPath)) {
	header('Content-Type: text/plain', true, 500);
	die("\$auth['saml']['ssp_path'] must be set\r\n");
}
require "$sspPath/lib/_autoload.php";
use \SimpleSAML_Auth_Simple;

// Get the SimpleSamlPhp instance for the configured auth source
$authSource = $auth['saml']['authsource'];
if (is_null($authSource)) {
	$authSource = 'default-sp';
}
$ssp = new SimpleSAML_Auth_Simple($authSource);

/* getAuth()
 * 
 *  No need to prompt for a name - this is done by SimpleSamlPhp
 */
function authGet()
{
	global $ssp;
	$ssp->requireAuth();
}

/* getUserName()
 *
 *  If the user is authenticated using SAML, get the username
 */
function getUserName()
{
	global $ssp;
	global $auth;
	if (!$ssp->isAuthenticated()) return NULL;
	$userData = $ssp->getAttributes();
	$userNameAttr = $auth['saml']['attr']['username'];
	if (is_null($userNameAttr)) {
		header('Content-Type: text/plain', true, 500);
		die("\$auth['saml']['attr']['username'] must be set\r\n");
	}
	return array_key_exists($userNameAttr, $userData) ? $userData[$userNameAttr][0] : NULL;
}

// Print the logon entry on the top banner.
function print_logon_box()
{
	global $ssp;
	global $QUERY_STRING;

	$target_url = this_page();

	if (isset($QUERY_STRING))
	{
		$target_url = $target_url . "?" . $QUERY_STRING;
	}

	$user=getUserName();

	if ($ssp->isAuthenticated())
	{
		print_report_link(isset($user) ? $user : '-invalid-');
		print_saml_logoff_button('/'.$target_url);
	}
	else
	{
		print_unknown_user();
		print_saml_logon_button('/'.$target_url);
	}

	print_userlist_link();
}

// Print a login button that will initiate a SAML session
// Code mostly modified copy/paste from functions_session
function print_saml_logon_button($target_url)
{
	global $ssp;
	$url = $ssp->getLoginURL($target_url);
	$baseURL = strstr($url, '?', true);
	parse_str(substr(strstr($url, '?'), 1), $params);
	$html = '';

	$html .= "<form method=\"get\" action=\"" . htmlspecialchars($baseURL) . "\">\n";
	foreach($params as $key => $value) {
		$html .= "<input type=\"hidden\" name=\"" . htmlspecialchars($key) . "\" value=\"" . htmlspecialchars($value) . "\">\n";
	}
	$html .= "<div>\n";
	$html .= "<input type=\"submit\" value=\"" . get_vocab('login') . "\">\n";
	$html .= "</div>\n";
	$html .= "</form>\n";

	echo $html;
}


// Print a logout button that will terminate a SAML session
// Code mostly modified copy/paste from functions_session
function print_saml_logoff_button($target_url)
{
	global $ssp;
	$url = $ssp->getLogoutURL($target_url);
	$baseURL = strstr($url, '?', true);
	parse_str(substr(strstr($url, '?'), 1), $params);
	$html = '';

	$html .= "<form method=\"get\" action=\"" . htmlspecialchars($baseURL) . "\">\n";
	foreach($params as $key => $value) {
		$html .= "<input type=\"hidden\" name=\"" . htmlspecialchars($key) . "\" value=\"" . htmlspecialchars($value) . "\">\n";
	}
	$html .= "<div>\n";
	$html .= "<input type=\"submit\" value=\"" . get_vocab('logoff') . "\">\n";
	$html .= "</div>\n";
	$html .= "</form>\n";

	echo $html;
}
