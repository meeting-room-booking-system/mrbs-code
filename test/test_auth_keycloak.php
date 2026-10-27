<?php
declare(strict_types=1);
namespace MRBS;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MRBS\Auth\AuthKeycloak;
use MRBS\Auth\AuthSaml;
use MRBS\Session\SessionKeycloak;
use RuntimeException;

// Standalone, offline regression test. Uses the bundled HTTP mock handler and
// the real SAML user lookup and notification address-building functions.
require_once __DIR__ . '/../web/lib/autoload.inc';
require_once __DIR__ . '/../web/functions_global.inc';
require_once __DIR__ . '/../web/functions.inc';
require_once __DIR__ . '/../web/functions_mail.inc';


class KeycloakTestSaml
{
  public $attributes = [
    'username' => ['administrator'],
    'email' => ['admin@example.test'],
    'firstName' => ['Room'],
    'lastName' => ['Administrator'],
    'groups' => ['mrbs-admin']
  ];

  public function isAuthenticated() : bool
  {
    return true;
  }

  public function getAttributes() : array
  {
    return $this->attributes;
  }
}


class KeycloakTestSession extends SessionKeycloak
{
  public function __construct()
  {
    // Replace only the external SimpleSamlPhp instance. Do not start a PHP/DB
    // session in this CLI test; retain the real SessionSaml lookup methods.
    $this->ssp = new KeycloakTestSaml();
  }
}


function session() : KeycloakTestSession
{
  global $test_session;
  return $test_session;
}


function auth() : AuthKeycloak
{
  global $test_auth;
  return $test_auth;
}


function is_book_admin($room_id) : bool
{
  return true;
}


function check($actual, $expected, string $description) : void
{
  global $checks;
  if ($actual !== $expected)
  {
    throw new RuntimeException($description . ': expected ' . var_export($expected, true) .
      ', got ' . var_export($actual, true));
  }
  $checks++;
}


function json_response($data) : Response
{
  return new Response(200, ['Content-Type' => 'application/json'], json_encode($data));
}


function token_response(int $lifetime=300) : Response
{
  return json_response(['access_token' => 'test-token', 'expires_in' => $lifetime]);
}


function directory_response(string $username='booker') : Response
{
  return json_response([[
    'username' => $username,
    'email' => $username . '@example.test',
    'firstName' => 'Booking',
    'lastName' => 'Owner'
  ]]);
}


function make_auth(array $responses, array &$history) : AuthKeycloak
{
  $history = [];
  $stack = HandlerStack::create(new MockHandler($responses));
  $stack->push(Middleware::history($history));
  return new AuthKeycloak(new Client(['handler' => $stack]));
}


$checks = 0;
$max_level = 2;
$auth = [
  'type' => 'keycloak',
  'session' => 'keycloak',
  'saml' => [
    'attr' => ['username' => 'username', 'mail' => 'email', 'givenName' => 'firstName', 'surname' => 'lastName'],
    'admin' => ['groups' => ['mrbs-admin']],
    'user' => ['groups' => ['mrbs-user']]
  ],
  'keycloak' => [
    'server_url' => 'https://sso.example.test/auth/',
    'realm' => 'meeting rooms',
    'client_id' => 'mrbs-directory',
    'client_secret' => 'test-only-secret'
  ]
];
$mail_settings = [
  'admin_on_bookings' => true,
  'area_admin_on_bookings' => false,
  'room_admin_on_bookings' => false,
  'booker' => true,
  'recipients' => 'office@example.test',
  'from' => 'mrbs@example.test',
  'use_reply_to' => true,
  'use_from_for_all_mail' => true
];
$approval_enabled = true;
$test_session = new KeycloakTestSession();
$history = [];
$test_auth = make_auth([token_response(), directory_response()], $history);

// Reproduce the original limitation, then check the same lookup with Keycloak.
$saml = new AuthSaml();
check($saml->getUserFresh('booker')->email, '', 'SAML cannot look up another user');
$user = $test_auth->getUserFresh('booker');
check($user->email, 'booker@example.test', 'Directory supplies the booker email');
check($user->display_name, 'Booking Owner', 'Directory supplies the display name');
check($user->level, 0, 'Directory does not grant access');
check(count($history), 2, 'One token and one directory request');
check($history[0]['request']->getMethod(), 'POST', 'Token uses POST');
check($history[0]['request']->getUri()->getPath(), '/auth/realms/meeting%20rooms/protocol/openid-connect/token',
  'Deployment prefix and realm are preserved');
parse_str((string) $history[0]['request']->getBody(), $form);
check($form, ['grant_type' => 'client_credentials', 'client_id' => 'mrbs-directory', 'client_secret' => 'test-only-secret'],
  'Token uses service-account credentials');
parse_str($history[1]['request']->getUri()->getQuery(), $query);
check($query, ['username' => 'booker', 'exact' => 'true', 'max' => '2'], 'Directory requests an exact bounded match');
check($history[1]['request']->getHeaderLine('Authorization'), 'Bearer test-token', 'Directory receives the token');
check($history[0]['options']['allow_redirects'], false, 'Token redirects are disabled');
check($history[1]['options']['allow_redirects'], false, 'Directory redirects are disabled');
check($history[1]['options']['verify'], true, 'TLS certificate verification remains enabled');
check($history[1]['options']['timeout'], 10, 'Network request has a time limit');

// Current SAML user, administrator and access-filter behavior must be unchanged,
// even with no responses available from the directory service.
$test_auth = make_auth([], $history);
$current = $test_auth->getUserFresh('administrator');
check($current->email, 'admin@example.test', 'Current user email comes from SAML');
check($current->display_name, 'Room Administrator', 'Current display name comes from SAML');
check($current->level, 2, 'SAML administrator retains access');
check($test_auth->validateUser('administrator', null), 'administrator', 'SAML login remains valid');
check($test_auth->validateUser('booker', null), false, 'Directory user cannot authenticate as the current user');
$test_session->ssp->attributes['groups'] = ['mrbs-user'];
check($test_auth->getUserFresh('administrator')->level, 1, 'SAML user filter grants ordinary user access');
$test_session->ssp->attributes['groups'] = ['unrelated'];
check($test_auth->getUserFresh('administrator')->level, 0, 'SAML user filter still denies unrelated users');
check(count($history), 0, 'Current session never depends on directory requests');
$test_session->ssp->attributes['groups'] = ['mrbs-admin'];

// Exercise actual notification recipient construction while an administrator
// owns the current session and the booking belongs to another user.
$test_auth = make_auth([token_response(), directory_response('notification-booker')], $history);
$data = ['create_by' => 'notification-booker', 'room_id' => 1];
foreach (['approve', 'book', 'delete', 'reject', 'more_info'] as $action)
{
  $addresses = create_addresses($data, [], $action);
  check(strpos($addresses['to'], 'notification-booker@example.test') !== false, true, "$action reaches booker");
  check($addresses['reply_to'], 'Room Administrator <admin@example.test>', "$action preserves reply-to");
  check($addresses['from'], 'mrbs@example.test', "$action preserves sender");
  $admin_field = in_array($action, ['approve', 'reject', 'more_info']) ? 'cc' : 'to';
  check(strpos($addresses[$admin_field], 'office@example.test') !== false, true, "$action preserves admin recipient");
}
check(count($history), 2, 'Notification user lookup is cached by Auth');

$addresses = create_addresses(['create_by' => 'administrator', 'room_id' => 1], [], 'book');
check(strpos($addresses['to'], 'admin@example.test') !== false, true, 'New booking reaches the current SAML user');
check(count($history), 2, 'New booking uses the current session without a directory query');
$mail_settings['area_admin_on_bookings'] = true;
$mail_settings['room_admin_on_bookings'] = true;
$data += ['area_admin_email' => 'area@example.test', 'room_admin_email' => 'room@example.test'];
$previous = ['area_admin_email' => 'old-area@example.test', 'room_admin_email' => 'old-room@example.test'];
$addresses = create_addresses($data, $previous, 'approve');
foreach (['area', 'room', 'old-area', 'old-room'] as $admin)
{
  check(strpos($addresses['cc'], $admin . '@example.test') !== false, true, 'Room/area administrator stays on Cc');
}
$mail_settings['area_admin_on_bookings'] = false;
$mail_settings['room_admin_on_bookings'] = false;

// Reuse an unexpired token across different users; reacquire expired tokens.
$test_auth = make_auth([token_response(), directory_response('one'), directory_response('two')], $history);
check($test_auth->getUserFresh('one')->email, 'one@example.test', 'First user lookup');
check($test_auth->getUserFresh('two')->email, 'two@example.test', 'Second user lookup');
check(count($history), 3, 'Access token reused within the request');
$test_auth = make_auth([token_response(0), directory_response('one'), token_response(), directory_response('two')], $history);
$test_auth->getUserFresh('one');
$test_auth->getUserFresh('two');
check(count($history), 4, 'Expired access token is reacquired');

foreach ([[], [['username' => 'booker-other']], [['username' => 'booker'], ['username' => 'booker']],
          ['error' => 'unexpected object']] as $records)
{
  $test_auth = make_auth([token_response(), json_response($records)], $history);
  check($test_auth->getUserFresh('booker'), null, 'Missing, partial, ambiguous or malformed matches are rejected');
}
$test_auth = make_auth([token_response(), json_response([['username' => 'booker']])], $history);
$user = $test_auth->getUserFresh('booker');
check($user->email, null, 'Absent email does not fabricate an address');
check($user->display_name, 'booker', 'Absent names fall back to the username');
$test_auth = make_auth([token_response(), json_response([['username' => 'booker', 'email' => 'invalid']])], $history);
check($test_auth->getUserFresh('booker')->email, null, 'Invalid email is not a notification recipient');
$test_auth = make_auth([token_response(), directory_response('booker')], $history);
check($test_auth->getUserFresh('BOOKER')->email, 'booker@example.test', 'Exact Keycloak names can differ in case');

// An HTTP or JSON failure must not abort the booking action or use a guessed
// recipient. Only sanitized diagnostics may enter the log.
$error_log = tempnam(__DIR__, 'mrbs-keycloak-');
$previous_log = ini_set('error_log', $error_log);
try
{
  foreach ([new Response(403, [], 'test-only-secret'), new Response(302, ['Location' => 'https://other.example.test']),
            new Response(200, [], '{invalid'), json_response(null),
            new ConnectException('test-only-secret', new Request('GET', 'https://sso.example.test'))] as $failure)
  {
    $test_auth = make_auth([token_response(), $failure], $history);
    check($test_auth->getUserFresh('booker'), null, 'Directory failure is handled');
  }
  foreach ([new Response(401, [], 'test-only-secret'), json_response([]), json_response(['access_token' => 42])] as $failure)
  {
    $test_auth = make_auth([$failure], $history);
    check($test_auth->getUserFresh('booker'), null, 'Token failure is handled');
    check(count($history), 1, 'Token failure prevents a directory query');
  }
  $log = file_get_contents($error_log);
  check(strpos($log, 'test-only-secret'), false, 'Diagnostics omit secrets and response bodies');
  check(strpos($log, 'test-token'), false, 'Diagnostics omit tokens');
}
finally
{
  ini_set('error_log', $previous_log);
  unlink($error_log);
}

$test_auth = make_auth([], $history);
check($test_auth->getUserFresh(''), null, 'Empty username needs no directory request');
check(count($history), 0, 'Empty username does not query the directory');
echo "Passed $checks Keycloak/SAML notification checks.\n";
