<?php
declare(strict_types=1);
namespace MRBS\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use MRBS\User;
use function MRBS\session;
use function MRBS\validate_email;


/**
 * SAML authentication with a Keycloak directory lookup for other users.
 *
 * The directory supplies contact details only. Access levels continue to come
 * from the authenticated SAML session, including its configured user filter.
 */
class AuthKeycloak extends AuthSaml
{
  private $client;
  private $config;
  private $access_token;
  private $token_expires = 0;


  public function __construct(?ClientInterface $client=null)
  {
    global $auth;

    parent::__construct();
    $this->config = $auth['keycloak'] ?? [];

    foreach (['server_url', 'realm', 'client_id', 'client_secret'] as $setting)
    {
      if (!isset($this->config[$setting]) || !is_string($this->config[$setting]) ||
          ($this->config[$setting] === ''))
      {
        throw new \InvalidArgumentException("\$auth['keycloak']['$setting'] must be set in the config file.");
      }
    }

    $this->config['server_url'] = rtrim($this->config['server_url'], '/');
    $this->client = $client ?? new Client();
  }


  public function getUserFresh(string $username) : ?User
  {
    // Keep login, the current user's contact details and SAML access control
    // independent of the directory API's availability.
    if ($username === session()->getUsername())
    {
      return parent::getUserFresh($username);
    }

    if ($username === '')
    {
      return null;
    }

    $token = $this->getAccessToken();
    if (!isset($token))
    {
      return null;
    }

    $realm = rawurlencode($this->config['realm']);
    $records = $this->requestJson('GET', "/admin/realms/$realm/users", [
      'headers' => ['Authorization' => 'Bearer ' . $token],
      'query' => ['username' => $username, 'exact' => 'true', 'max' => 2]
    ]);

    // Never use a partial or ambiguous match as a notification recipient.
    if (!isset($records))
    {
      $this->access_token = null;
      return null;
    }
    if ((count($records) !== 1) || !isset($records[0]['username']) ||
        !is_string($records[0]['username']) ||
        (strcasecmp($records[0]['username'], $username) !== 0))
    {
      return null;
    }

    $record = $records[0];
    $user = new User($username);
    $email = $record['email'] ?? null;
    $user->email = (is_string($email) && validate_email($email)) ? $email : null;
    $first_name = (isset($record['firstName']) && is_string($record['firstName'])) ? $record['firstName'] : '';
    $last_name = (isset($record['lastName']) && is_string($record['lastName'])) ? $record['lastName'] : '';
    $display_name = trim($first_name . ' ' . $last_name);
    $user->display_name = ($display_name !== '') ? $display_name : $username;
    // User defaults to level 0. Directory lookup must not grant access.

    return $user;
  }


  private function getAccessToken() : ?string
  {
    if (isset($this->access_token) && (time() < $this->token_expires))
    {
      return $this->access_token;
    }

    $realm = rawurlencode($this->config['realm']);
    $result = $this->requestJson('POST', "/realms/$realm/protocol/openid-connect/token", [
      'form_params' => [
        'grant_type' => 'client_credentials',
        'client_id' => $this->config['client_id'],
        'client_secret' => $this->config['client_secret']
      ]
    ]);

    if (!isset($result['access_token']) || !is_string($result['access_token']) ||
        ($result['access_token'] === ''))
    {
      return null;
    }

    $this->access_token = $result['access_token'];
    $lifetime = $result['expires_in'] ?? 0;
    $this->token_expires = time() + (is_numeric($lifetime) ? max(0, (int) $lifetime - 5) : 0);

    return $this->access_token;
  }


  private function requestJson(string $method, string $path, array $options) : ?array
  {
    // Bound failures, retain TLS verification, and don't forward credentials
    // to a redirect target. Avoid logging response bodies or exception messages:
    // these can contain credentials, access tokens or personal details.
    $options += [
      'timeout' => 10,
      'connect_timeout' => 5,
      'allow_redirects' => false,
      'http_errors' => false
    ];
    $options['headers']['Accept'] = 'application/json';

    try
    {
      $response = $this->client->request($method, $this->config['server_url'] . $path, $options);
    }
    catch (GuzzleException $e)
    {
      error_log('MRBS Keycloak: directory request failed.');
      return null;
    }

    if ($response->getStatusCode() !== 200)
    {
      error_log('MRBS Keycloak: directory request returned HTTP ' . $response->getStatusCode() . '.');
      return null;
    }

    $result = json_decode((string) $response->getBody(), true);
    if ((json_last_error() !== JSON_ERROR_NONE) || !is_array($result))
    {
      error_log('MRBS Keycloak: directory returned an invalid JSON response.');
      return null;
    }

    return $result;
  }
}
