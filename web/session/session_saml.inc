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
if (is_null($sspPath))
{
  header('Content-Type: text/plain', true, 500);
  die("\$auth['saml']['ssp_path'] must be set\r\n");
}

require "$sspPath/lib/_autoload.php";
use \SimpleSAML_Auth_Simple;

// Get the SimpleSamlPhp instance for the configured auth source
$authSource = $auth['saml']['authsource'];
if (is_null($authSource))
{
  $authSource = 'default-sp';
}
$ssp = new SimpleSAML_Auth_Simple($authSource);
