<?php
declare(strict_types=1);
namespace MRBS\Auth;

use CAS_OutOfSequenceException;
use CAS_Request_AbstractRequest;
use CAS_Request_RequestInterface;
use GuzzleHttp\Client;

class AuthCasGuzzleRequest extends CAS_Request_AbstractRequest implements CAS_Request_RequestInterface
{

  private $client;
  private $method = 'GET';
  private $options = [];
  private $response_status_code;
  private $sent = false;  // $_sent is private in CAS_Request_AbstractRequest


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


  /**
   * @see CAS_Request_RequestInterface::setSslCaCert()
   */
  public function setSslCaCert($caCertPath, $validate_cn = true) : void
  {
    if ($this->sent)
    {
      throw new CAS_OutOfSequenceException('Request has already been sent cannot '.__METHOD__);
    }

    $this->options['verify'] = ($validate_cn) ? $caCertPath : false;
  }


  /**
   * @see CAS_Request_RequestInterface::getResponseStatusCode()
   */
  public function getResponseStatusCode() : int
  {
    if (!$this->sent)
    {
      throw new CAS_OutOfSequenceException('Request has not been sent yet. Cannot '.__METHOD__);
    }

    return $this->response_status_code;
  }


  /**
   * @see CAS_Request_AbstractRequest::sendRequest()
   */
  protected function sendRequest() : bool
  {
    try
    {
      $this->sent = true;
      $response = $this->client->request($this->method, $this->url, $this->options);
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
