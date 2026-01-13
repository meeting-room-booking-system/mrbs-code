<?php
declare(strict_types=1);
namespace MRBS\Auth;

use CAS_Request_AbstractRequest;
use CAS_Request_RequestInterface;
use GuzzleHttp\Client;

class AuthCasGuzzleRequest extends CAS_Request_AbstractRequest implements CAS_Request_RequestInterface
{

  private $client;
  private $method = 'GET';


  public function __construct()
  {
    $this->client = new Client();
  }


  /**
   * @see CAS_Request_RequestInterface::addCookie()
   */
  public function addCookie($name, $value) : void
  {
    // TODO: Implement addCookie() method.
  }

  public function addCookies(array $cookies)
  {
    // TODO: Implement addCookies() method.
  }

  public function addHeader($header)
  {
    // TODO: Implement addHeader() method.
  }

  public function addHeaders(array $headers)
  {
    // TODO: Implement addHeaders() method.
  }

  public function makePost()
  {
    // TODO: Implement makePost() method.
  }

  public function setPostBody($body)
  {
    // TODO: Implement setPostBody() method.
  }

  public function setSslCaCert($caCertPath, $validate_cn = true)
  {
    // TODO: Implement setSslCaCert() method.
  }


  public function getResponseStatusCode()
  {
    // TODO: Implement getResponseStatusCode() method.
  }


  /**
   * @see CAS_Request_AbstractRequest::sendRequest()
   */
  protected function sendRequest() : bool
  {
    try
    {
      $response = $this->client->request($this->method, $this->url);
      $this->storeResponseBody($response->getBody()->getContents());
      foreach ($response->getHeaders() as $name => $values)
      {
        $this->storeResponseHeader(mb_strtolower($name) . ': ' . implode(', ', $values));
      }
      return true;
    }
    catch (\Exception $e)
    {
      $this->storeErrorMessage( $e->getMessage());
      return false;
    }
  }

}
